<?php declare(strict_types = 1);

namespace EventoImport\import\action;

use EventoImport\import\action\event\EventActionFactory;
use EventoImport\import\service\IliasEventObjectService;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\event\EventAction;
use EventoImport\import\db\IliasEventObjectRepository;
use EventoImport\import\db\EventLocationsRepository;

class EventImportActionDecider
{
    private IliasEventObjectService $repository_facade;
    private EventActionFactory $event_action_factory;
    private IliasEventObjectRepository $ilias_event_obj_repo;
    private EventLocationsRepository $location_repository;

    public function __construct(IliasEventObjectService $repository_facade, IliasEventObjectRepository $ilias_event_obj_repo, EventActionFactory $event_action_factory, EventLocationsRepository $location_repo)
    {
        $this->repository_facade = $repository_facade;
        $this->ilias_event_obj_repo = $ilias_event_obj_repo;
        $this->event_action_factory = $event_action_factory;
        $this->location_repository = $location_repo;
    }

    public function determineAction(EventoEvent $evento_event) : EventAction
    {
        $ilias_event = $this->ilias_event_obj_repo->getEventByEventoId($evento_event->getEventoId());
        if (!is_null($ilias_event)) {
            // Already is registered as ilias-event
            return $this->determineActionForExistingIliasEventoEvent($evento_event, $ilias_event);
        }

        if ($evento_event->hasCreateCourseFlag()) {
            // Has create flag
            return $this->determineActionForNewEventsWithCreateFlag($evento_event);
        }

        // Has no create flag
        return $this->determineActionForNonRegisteredEventsWithoutCreateFlag($evento_event);
    }

    protected function determineActionForExistingIliasEventoEvent(EventoEvent $evento_event, IliasEventoEvent $ilias_event) : EventAction
    {
        // In this case, there were suddenly added similar events (at least 1) in Evento which made this event to a multi group event
        if ($evento_event->hasGroupMemberFlag() && is_null($ilias_event->getParentEventKey())) {
            return $this->event_action_factory->convertSingleEventToMultiGroupEvent($evento_event, $ilias_event);
        }
        
        return $this->event_action_factory->updateExistingEvent($evento_event, $ilias_event);
    }

    protected function determineActionForNewEventsWithCreateFlag(EventoEvent $evento_event) : EventAction
    {
        $destination_ref_id = $this->location_repository->getRefIdForEventoObject($evento_event);

        if ($destination_ref_id === null) {
            return $this->event_action_factory->reportUnknownLocationForEvent($evento_event);
        }

        if (!$evento_event->hasGroupMemberFlag()) {
            // Is single Group
            return $this->event_action_factory->createSingleEvent($evento_event, $destination_ref_id);
        }

        // Is MultiGroup
        $parent_event = $this->ilias_event_obj_repo->getParentEventForName($evento_event->getGroupUniqueKey());
        if (!is_null($parent_event)) {
            // Parent event in multi group exists
            return $this->event_action_factory->createEventInParentEvent($evento_event, $parent_event);
        }

        // Parent event in multi group has also to be created
        return $this->event_action_factory->createEventWithParent($evento_event, $destination_ref_id);
    }

    protected function determineActionForNonRegisteredEventsWithoutCreateFlag(EventoEvent $evento_event) : EventAction
    {
        $matched_course = $this->repository_facade->searchEventableIliasObjectByTitle($evento_event->getName());

        if (!is_null($matched_course)) {
            return $this->event_action_factory->markExistingIliasObjAsEvent($evento_event, $matched_course);
        }

        return $this->event_action_factory->reportNonIliasEvent($evento_event);
    }
}

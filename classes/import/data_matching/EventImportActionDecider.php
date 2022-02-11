<?php declare(strict_types = 1);

namespace EventoImport\import\data_matching;

use EventoImport\import\action\event\EventActionFactory;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\event\EventAction;
use EventoImport\import\action\ReportDatasetWithoutAction;

class EventImportActionDecider
{
    private RepositoryFacade $repository_facade;
    private EventActionFactory $event_action_factory;

    public function __construct(RepositoryFacade $repository_facade, EventActionFactory $event_action_factory)
    {
        $this->repository_facade = $repository_facade;
        $this->event_action_factory = $event_action_factory;
    }

    public function determineAction(\EventoImport\communication\api_models\EventoEvent $evento_event) : EventAction
    {
        $ilias_event = $this->repository_facade->iliasEventoEventRepository()->getEventByEventoId($evento_event->getEventoId());
        if (!is_null($ilias_event)) {
            // Already is registered as ilias-event
            return $this->determineActionForExistingIliasEventoEvent($evento_event, $ilias_event);
        }

        if ($evento_event->hasCreateCourseFlag()) {
            // Has create flag
            return $this->determineActionForNewEventsWithCreateFlag($evento_event);
        } else {
            // Has no create flag
            return $this->determineActionForNonRegisteredEventsWithoutCreateFlag($evento_event);
        }
    }

    protected function determineActionForExistingIliasEventoEvent(EventoEvent $evento_event, IliasEventoEvent $ilias_event) : EventAction
    {
        if ($evento_event->hasGroupMemberFlag() && is_null($ilias_event->getParentEventKey())) {
            return $this->event_action_factory->convertSingleEventToMultiGroupEvent($evento_event, $ilias_event);
        }
        
        return $this->event_action_factory->updateExistingEvent($evento_event, $ilias_event);
    }

    protected function determineActionForNewEventsWithCreateFlag($evento_event) : EventAction
    {
        $destination_ref_id = $this->repository_facade->departmentLocationRepository()->fetchRefIdForEventoObject($evento_event);

        if ($destination_ref_id === null) {
            return $this->event_action_factory->reportUnknownLocationForEvent($evento_event);
        }

        if (!$evento_event->hasGroupMemberFlag()) {
            // Is single Group
            return $this->event_action_factory->createSingleEvent($evento_event, $destination_ref_id);
        }

        // Is MultiGroup
        $parent_event = $this->repository_facade->searchPossibleParentEventForEvent($evento_event);
        if (!is_null($parent_event)) {
            // Parent event in multi group exists
            return $this->event_action_factory->createEventInParentEvent($evento_event, $parent_event);
        }

        // Parent event in multi group has also to be created
        return $this->event_action_factory->createEventWithParent($evento_event, $destination_ref_id);
    }

    protected function determineActionForNonRegisteredEventsWithoutCreateFlag(EventoEvent $evento_event) : EventAction
    {
        $matched_course = $this->repository_facade->searchExactlyOneMatchingCourseByTitle($evento_event);

        if (!is_null($matched_course)) {
            return $this->event_action_factory->markExistingIliasObjAsEvent($evento_event, $matched_course);
        } else {
            return $this->event_action_factory->reportNonIliasEvent($evento_event);
        }
    }
}

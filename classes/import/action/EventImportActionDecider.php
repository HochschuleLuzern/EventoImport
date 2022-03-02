<?php declare(strict_types = 1);

namespace EventoImport\import\action;

use EventoImport\import\action\event\EventActionFactory;
use EventoImport\import\service\IliasEventObjectService;
use EventoImport\import\manager\db\model\IliasEventoEvent;
use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\event\EventImportAction;
use EventoImport\import\manager\db\IliasEventoEventObjectRepository;
use EventoImport\import\manager\db\EventLocationsRepository;

class EventImportActionDecider
{
    private IliasEventObjectService $ilias_event_obj_service;
    private EventActionFactory $event_action_factory;
    private IliasEventoEventObjectRepository $evento_event_object_repo;
    private EventLocationsRepository $location_repository;

    public function __construct(IliasEventObjectService $ilias_event_obj_service, IliasEventoEventObjectRepository $evento_event_object_repo, EventActionFactory $event_action_factory, EventLocationsRepository $location_repo)
    {
        $this->ilias_event_obj_service = $ilias_event_obj_service;
        $this->evento_event_object_repo = $evento_event_object_repo;
        $this->event_action_factory = $event_action_factory;
        $this->location_repository = $location_repo;
    }

    public function determineImportAction(EventoEvent $evento_event) : EventImportAction
    {
        $ilias_event = $this->evento_event_object_repo->getEventByEventoId($evento_event->getEventoId());
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

    protected function determineActionForExistingIliasEventoEvent(EventoEvent $evento_event, IliasEventoEvent $ilias_event) : EventImportAction
    {
        // In this case, there were suddenly added similar events (at least 1) in Evento which made this event to a multi group event
        if ($evento_event->hasGroupMemberFlag() && is_null($ilias_event->getParentEventKey())) {
            return $this->event_action_factory->convertSingleEventToMultiGroupEvent($evento_event, $ilias_event);
        }
        
        return $this->event_action_factory->updateExistingEvent($evento_event, $ilias_event);
    }

    protected function determineActionForNewEventsWithCreateFlag(EventoEvent $evento_event) : EventImportAction
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
        $parent_event = $this->evento_event_object_repo->getParentEventForName($evento_event->getGroupUniqueKey());
        if (!is_null($parent_event)) {
            // Parent event in multi group exists
            return $this->event_action_factory->createEventInParentEvent($evento_event, $parent_event);
        }

        // Parent event in multi group has also to be created
        return $this->event_action_factory->createEventWithParent($evento_event, $destination_ref_id);
    }

    protected function determineActionForNonRegisteredEventsWithoutCreateFlag(EventoEvent $evento_event) : EventImportAction
    {
        $matched_course = $this->ilias_event_obj_service->searchEventableIliasObjectByTitle($evento_event->getName());

        if (!is_null($matched_course)) {
            return $this->event_action_factory->markExistingIliasObjAsEvent($evento_event, $matched_course);
        }

        return $this->event_action_factory->reportNonIliasEvent($evento_event);
    }

    public function determineDeleteAction(IliasEventoEvent $ilias_evento_event)
    {
        // Events which were created manually are just removed from the Evento-ILIAS-binding
        if (!$ilias_evento_event->wasAutomaticallyCreated()) {
            return $this->event_action_factory->unmarkExistingIliasObjFromEventoEvents($ilias_evento_event);
        }

        if ($ilias_evento_event->isSubGroupEvent()) {
            $parent_event = $this->evento_event_object_repo->getParentEventbyGroupUniqueKey($ilias_evento_event->getParentEventKey());

            if (!is_null($parent_event) || $this->evento_event_object_repo->getNumberOfChildEventsForParentEventKey($parent_event) <= 1) {
                return $this->event_action_factory->deleteEventGroupWithParentEventCourse($ilias_evento_event, $parent_event);
            }

            return $this->event_action_factory->deleteGroupEventInCourse($ilias_evento_event);
        }

        return $this->event_action_factory->deleteSingleCourseEvent($ilias_evento_event);

        throw new \Exception('Error while removing in Evento deleted event from ILIAS: Given event was created
         automatically. It is not a course object but also is not part of an automatically created parent event. 
         This scenario should not be possible!');
    }
}

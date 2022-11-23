<?php declare(strict_types = 1);

namespace EventoImport\import\action;

use EventoImport\import\action\event\EventActionFactory;
use EventoImport\import\data_management\ilias_core_service\IliasEventObjectService;
use EventoImport\import\data_management\repository\model\IliasEventoEvent;
use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\event\EventImportAction;
use EventoImport\import\data_management\repository\IliasEventoEventObjectRepository;
use EventoImport\config\locations\EventLocationsRepository;
use EventoImport\config\EventLocations;
use EventoImport\import\data_management\EventManager;

class EventImportActionDecider
{
    private EventManager $event_manager;
    private EventActionFactory $event_action_factory;
    private EventLocations $event_locations;

    public function __construct(EventManager $event_manager, EventActionFactory $event_action_factory, EventLocations $event_locations)
    {
        $this->event_manager = $event_manager;
        $this->event_action_factory = $event_action_factory;
        $this->event_locations = $event_locations;
    }

    public function determineImportAction(EventoEvent $evento_event) : EventImportAction
    {
        $ilias_event = $this->event_manager->searchIliasEventoEventByEventoEvent($evento_event);
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
        if ($ilias_event->wasAutomaticallyCreated() && $evento_event->hasGroupMemberFlag() && !$ilias_event->isSubGroupEvent()) {
            return $this->event_action_factory->convertSingleEventToMultiGroupEvent($evento_event, $ilias_event);
        }

        if (!$this->event_manager->isIliasObjectToIliasEventoEventStillExisting($ilias_event)) {
            return $this->event_action_factory->unmarkDeletedIliasObject($evento_event, $ilias_event);
        }

        return $this->event_action_factory->updateExistingEvent($evento_event, $ilias_event);
    }

    protected function determineActionForNewEventsWithCreateFlag(EventoEvent $evento_event) : EventImportAction
    {
        $destination_ref_id = $this->event_locations->getLocationRefIdForEventoEvent($evento_event, true);

        if (is_null($destination_ref_id)) {
            return $this->event_action_factory->reportUnknownLocationForEvent($evento_event);
        }

        if (!$evento_event->hasGroupMemberFlag()) {
            // Is single Group
            return $this->event_action_factory->createSingleEvent($evento_event, $destination_ref_id);
        }

        // Is MultiGroup
        $parent_event = $this->event_manager->searchParentEventForEventoEvent($evento_event);
        if (!is_null($parent_event)) {
            // Parent event in multi group exists
            return $this->event_action_factory->createEventInParentEvent($evento_event, $parent_event);
        }

        // Parent event in multi group has also to be created
        return $this->event_action_factory->createEventWithParent($evento_event, $destination_ref_id);
    }

    protected function determineActionForNonRegisteredEventsWithoutCreateFlag(EventoEvent $evento_event) : EventImportAction
    {
        $matched_course = $this->event_manager->searchEventableObjectForEventoEvent($evento_event);

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
            $parent_event = $this->event_manager->getParentEventForIliasEventoEvent($ilias_evento_event);

            if (!is_null($parent_event) && $this->event_manager->getNumberOfChildEventsForParentEvent($parent_event) <= 1) {
                return $this->event_action_factory->deleteEventGroupWithParentEventCourse($ilias_evento_event, $parent_event);
            }

            return $this->event_action_factory->deleteGroupEventInCourse($ilias_evento_event);
        }

        return $this->event_action_factory->deleteSingleCourseEvent($ilias_evento_event);
    }
}

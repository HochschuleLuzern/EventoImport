<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\service\IliasEventObjectService;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\manager\db\model\IliasEventoParentEvent;
use EventoImport\import\service\MembershipManager;
use EventoImport\import\manager\db\model\IliasEventoEvent;
use EventoImport\import\manager\db\IliasEventoEventObjectRepository;
use EventoImport\import\Logger;
use EventoImport\import\EventManager;

class EventActionFactory
{
    private EventManager $event_manager;
    private MembershipManager $membership_manager;
    private Logger $logger;

    public function __construct(
        EventManager $event_manager,
        MembershipManager $membership_manager,
        Logger $logger
    ) {
        $this->event_manager = $event_manager;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
    }

    public function createSingleEvent(EventoEvent $evento_event, int $destination_ref_id) : CreateSingleEvent
    {
        return new CreateSingleEvent(
            $evento_event,
            $this->event_manager,
            $this->membership_manager,
            $this->logger,
        );
    }

    public function createEventWithParent(EventoEvent $evento_event, int $destination_ref_id) : CreateEventWithParent
    {
        return new CreateEventWithParent(
            $evento_event,
            $this->event_manager,
            $this->membership_manager,
            $this->logger,
        );
    }

    public function createEventInParentEvent(EventoEvent $evento_event, IliasEventoParentEvent $parent_event) : CreateEventInParentEvent
    {
        return new CreateEventInParentEvent(
            $evento_event,
            $parent_event,
            $this->event_manager,
            $this->membership_manager,
            $this->logger,
        );
    }

    public function updateExistingEvent(EventoEvent $evento_event, IliasEventoEvent $ilias_event) : UpdateExistingEvent
    {
        return new UpdateExistingEvent(
            $evento_event,
            $ilias_event,
            $this->event_manager,
            $this->membership_manager,
            $this->logger,
        );
    }

    public function convertSingleEventToMultiGroupEvent(EventoEvent $evento_event, IliasEventoEvent $ilias_event)
    {
        return new ConvertSingleEventToMultiGroupEvent(
            $evento_event,
            $ilias_event,
            $this->event_manager,
            $this->membership_manager,
            $this->logger
        );
    }

    public function markExistingIliasObjAsEvent(
        EventoEvent $evento_event,
        \ilContainer $ilias_obj
    ) : MarkExistingIliasObjAsEvent {
        return new MarkExistingIliasObjAsEvent(
            $evento_event,
            $ilias_obj,
            $this->event_manager,
            $this->membership_manager,
            $this->logger,
        );
    }

    public function reportNonIliasEvent(EventoEvent $evento_event) : ReportEventImportDatasetWithoutAction
    {
        return new ReportEventImportDatasetWithoutAction(
            Logger::CREVENTO_MA_NON_ILIAS_EVENT,
            $evento_event->getEventoId(),
            null,
            $evento_event->getDecodedApiData(),
            $this->logger
        );
    }

    public function reportUnknownLocationForEvent(EventoEvent $evento_event) : ReportEventImportDatasetWithoutAction
    {
        return new ReportEventImportDatasetWithoutAction(
            Logger::CREVENTO_MA_EVENT_LOCATION_UNKNOWN,
            $evento_event->getEventoId(),
            null,
            $evento_event->getDecodedApiData(),
            $this->logger
        );
    }

    public function deleteSingleCourseEvent(IliasEventoEvent $ilias_evento_event) : DeleteSingleCourseEvent
    {
        return new DeleteSingleCourseEvent(
            $ilias_evento_event,
            $this->event_manager,
            $this->logger
        );
    }

    public function deleteGroupEventInCourse(IliasEventoEvent $ilias_evento_event) : DeleteGroupEventInCourse
    {
        return new DeleteGroupEventInCourse(
            $ilias_evento_event,
            $this->event_manager,
            $this->logger
        );
    }

    public function deleteEventGroupWithParentEventCourse(IliasEventoEvent $ilias_evento_event, IliasEventoParentEvent $ilias_evento_parent_event) : DeleteEventGroupWithParentEventCourse
    {
        return new DeleteEventGroupWithParentEventCourse(
            $ilias_evento_event,
            $ilias_evento_parent_event,
            $this->event_manager,
            $this->logger
        );
    }

    public function unmarkExistingIliasObjFromEventoEvents(IliasEventoEvent $ilias_evento_event) : UnmarkExistingIliasObjFromEventoEvents
    {
        return new UnmarkExistingIliasObjFromEventoEvents(
            $ilias_evento_event,
            $this->event_manager,
            $this->logger
        );
    }
}

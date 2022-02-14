<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\IliasEventObjectService;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\db\MembershipManager;
use EventoImport\import\db\model\IliasEventoEvent;

class EventActionFactory
{
    private IliasEventObjectService $repository_facade;
    private IliasEventObjectFactory $event_object_factory;
    private MembershipManager $membership_manager;
    private \EventoImport\import\Logger $logger;

    public function __construct(
        IliasEventObjectFactory $event_object_factory,
        IliasEventObjectService $repository_facade,
        MembershipManager $membership_manager,
        \EventoImport\import\Logger $logger
    ) {
        $this->event_object_factory = $event_object_factory;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
    }

    public function createSingleEvent(EventoEvent $evento_event, int $destination_ref_id) : CreateSingleEvent
    {
        return new CreateSingleEvent(
            $evento_event,
            $destination_ref_id,
            $this->event_object_factory,
            $this->repository_facade,
            $this->membership_manager,
            $this->logger,
        );
    }

    public function createEventWithParent(EventoEvent $evento_event, int $destination_ref_id) : CreateEventWithParent
    {
        return new CreateEventWithParent(
            $evento_event,
            $destination_ref_id,
            $this->event_object_factory,
            $this->repository_facade,
            $this->membership_manager,
            $this->logger,
        );
    }

    public function createEventInParentEvent(EventoEvent $evento_event, IliasEventoParentEvent $parent_event) : CreateEventInParentEvent
    {
        return new CreateEventInParentEvent(
            $evento_event,
            $parent_event,
            $this->event_object_factory,
            $this->repository_facade,
            $this->membership_manager,
            $this->logger,
        );
    }

    public function updateExistingEvent(EventoEvent $evento_event, IliasEventoEvent $ilias_event) : UpdateExistingEvent
    {
        return new UpdateExistingEvent(
            $evento_event,
            $ilias_event,
            $this->repository_facade,
            $this->membership_manager,
            $this->logger,
        );
    }

    public function convertSingleEventToMultiGroupEvent(EventoEvent $evento_event, IliasEventoEvent $ilias_event)
    {
        return new ConvertSingleEventToMultiGroupEvent(
            $evento_event,
            $ilias_event,
            $this->event_object_factory,
            $this->repository_facade,
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
            $this->repository_facade,
            $this->membership_manager,
            $this->logger,
        );
    }

    public function reportNonIliasEvent(EventoEvent $evento_event) : ReportEventImportDatasetWithoutAction
    {
        return new ReportEventImportDatasetWithoutAction(
            \EventoImport\import\Logger::CREVENTO_MA_NON_ILIAS_EVENT,
            $evento_event->getEventoId(),
            null,
            $evento_event->getDecodedApiData(),
            $this->logger
        );
    }

    public function reportUnknownLocationForEvent(EventoEvent $evento_event) : ReportEventImportDatasetWithoutAction
    {
        return new ReportEventImportDatasetWithoutAction(
            \EventoImport\import\Logger::CREVENTO_MA_EVENT_LOCATION_UNKNOWN,
            $evento_event->getEventoId(),
            null,
            $evento_event->getDecodedApiData(),
            $this->logger
        );
    }
}

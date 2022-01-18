<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\ReportDatasetWithoutAction;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\settings\DefaultEventSettings;
use EventoImport\import\IliasEventWrapper;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\db\MembershipManager;
use EventoImport\import\db\model\IliasEventoEvent;

/**
 * Class EventActionFactory
 * @package EventoImport\import\action\event
 */
class EventActionFactory
{
    /** @var RepositoryFacade */
    private RepositoryFacade $repository_facade;

    /** @var IliasEventObjectFactory */
    private IliasEventObjectFactory $event_object_factory;

    /** @var MembershipManager */
    private MembershipManager $membership_manager;

    /** @var \ilEventoImportLogger */
    private \ilEventoImportLogger $logger;

    /**
     * EventActionFactory constructor.
     * @param IliasEventObjectFactory $event_object_factory
     * @param RepositoryFacade        $repository_facade
     * @param MembershipManager       $membership_manager
     * @param \ilEventoImportLogger   $logger
     */
    public function __construct(
        IliasEventObjectFactory $event_object_factory,
        RepositoryFacade $repository_facade,
        MembershipManager $membership_manager,
        \ilEventoImportLogger $logger
    ) {
        $this->event_object_factory = $event_object_factory;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
    }

    /**
     * @param EventoEvent $evento_event
     * @param int         $destination_ref_id
     * @return CreateSingleEvent
     */
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

    /**
     * @param EventoEvent $evento_event
     * @param int         $destination_ref_id
     * @return CreateEventWithParent
     */
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

    /**
     * @param EventoEvent            $evento_event
     * @param IliasEventoParentEvent $parent_event
     * @return CreateEventInParentEvent
     */
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

    /**
     * @param EventoEvent       $evento_event
     * @param IliasEventoEvent $ilias_event
     * @return UpdateExistingEvent
     */
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

    /**
     * @param EventoEvent  $evento_event
     * @param \ilContainer $ilias_obj
     * @return MarkExistingIliasObjAsEvent
     */
    public function convertExistingIliasObjToEvent(
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

    /**
     * @param EventoEvent $evento_event
     * @return ReportEventImportDatasetWithoutAction
     */
    public function reportNonIliasEvent(EventoEvent $evento_event) : ReportEventImportDatasetWithoutAction
    {
        return new ReportEventImportDatasetWithoutAction(
            \ilEventoImportLogger::CREVENTO_MA_NON_ILIAS_EVENT,
            $evento_event->getEventoId(),
            null,
            $evento_event->getDecodedApiData(),
            $this->logger
        );
    }

    /**
     * @param EventoEvent $evento_event
     * @return ReportDatasetWithoutAction
     */
    public function reportUnknownLocationForEvent(EventoEvent $evento_event) : ReportEventImportDatasetWithoutAction
    {
        return new ReportEventImportDatasetWithoutAction(
            \ilEventoImportLogger::CREVENTO_MA_EVENT_LOCATION_UNKNOWN,
            $evento_event->getEventoId(),
            null,
            $evento_event->getDecodedApiData(),
            $this->logger
        );
    }
}

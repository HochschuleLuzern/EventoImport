<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\ReportError;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\settings\DefaultEventSettings;
use EventoImport\import\IliasEventWrapper;
use EventoImport\import\db\model\IliasEventoParentEvent;

class EventActionFactory
{
    private $logger;
    private $default_event_settings;
    private $user_facade;
    private $repository_facade;
    private $event_object_factory;

    public function __construct(
        IliasEventObjectFactory $event_object_factory,
        RepositoryFacade $repository_facade,
        UserFacade $user_facade,
        DefaultEventSettings $default_event_settings,
        \ilEventoImportLogger $logger
    ) {
        $this->event_object_factory = $event_object_factory;
        $this->repository_facade = $repository_facade;
        $this->user_facade = $user_facade;
        $this->default_event_settings = $default_event_settings;
        $this->logger = $logger;
    }

    public function createSingleEvent(EventoEvent $evento_event, int $destination_ref_id) : CreateSingleEvent
    {
        return new CreateSingleEvent(
            $evento_event,
            $destination_ref_id,
            $this->event_object_factory,
            $this->default_event_settings,
            $this->repository_facade,
            $this->user_facade,
            $this->logger,
            $this->user_facade->rbacServices()
        );
    }

    public function createEventWithParent(EventoEvent $evento_event, int $destination_ref_id) : CreateEventWithParent
    {
        return new CreateEventWithParent(
            $evento_event,
            $destination_ref_id,
            $this->event_object_factory,
            $this->default_event_settings,
            $this->repository_facade,
            $this->user_facade,
            $this->logger,
            $this->user_facade->rbacServices()
        );
    }

    public function createEventInParentEvent(EventoEvent $evento_event, IliasEventoParentEvent $parent_event) : CreateEventInParentEvent
    {
        return new CreateEventInParentEvent(
            $evento_event,
            $parent_event,
            $this->event_object_factory,
            $this->default_event_settings,
            $this->repository_facade,
            $this->user_facade,
            $this->logger,
            $this->user_facade->rbacServices()
        );
    }

    public function updateExistingEvent(EventoEvent $evento_event, IliasEventWrapper $ilias_event) : UpdateExistingEvent
    {
        return new UpdateExistingEvent(
            $evento_event,
            $ilias_event,
            $this->event_object_factory,
            $this->default_event_settings,
            $this->repository_facade,
            $this->user_facade,
            $this->logger,
            $this->user_facade->rbacServices()
        );
    }

    public function convertExistingIliasObjToEvent(
        EventoEvent $evento_event,
        \ilContainer $ilias_obj
    ) : ConvertExistingIliasObjToEvent {
        return new ConvertExistingIliasObjToEvent(
            $evento_event,
            $ilias_obj,
            $this->event_object_factory,
            $this->default_event_settings,
            $this->repository_facade,
            $this->user_facade,
            $this->logger,
            $this->user_facade->rbacServices()
        );
    }

    public function reportNonIliasEvent(EventoEvent $evento_event) : ReportNonIliasEvent
    {
        return new ReportNonIliasEvent($evento_event, $this->logger);
    }

    public function reportUnknownLocationForEvent(EventoEvent $evento_event) : ReportError
    {
        return new ReportError(\ilEventoImportLogger::CREVENTO_MA_NOTICE_MISSING_IN_ILIAS, [], $this->logger);
    }
}

<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\settings\DefaultEventSettings;
use EventoImport\import\db\MembershipManager;

class CreateSingleEvent implements EventAction
{
    private EventoEvent $evento_event;
    private int $destination_ref_id;
    private IliasEventObjectFactory $event_object_factory;
    private RepositoryFacade $repository_facade;
    private MembershipManager $membership_manager;
    private \ilEventoImportLogger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, int $destination_ref_id, IliasEventObjectFactory $event_object_factory, RepositoryFacade $repository_facade, MembershipManager $membership_manager, \ilEventoImportLogger $logger)
    {
        $this->evento_event = $evento_event;
        $this->destination_ref_id = $destination_ref_id;
        $this->event_object_factory = $event_object_factory;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = \ilEventoImportLogger::CREVENTO_MA_SINGLE_EVENT_CREATED;
    }

    public function executeAction() : void
    {
        $course_object = $this->event_object_factory->buildNewCourseObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $this->destination_ref_id,
        );

        $ilias_event = $this->repository_facade->addNewIliasEvent($this->evento_event, $course_object);
        $this->membership_manager->syncMemberships($this->evento_event, $ilias_event);

        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $course_object->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}

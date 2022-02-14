<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\db\MembershipManager;

class CreateEventWithParent implements EventAction
{
    private EventoEvent $evento_event;
    private int $destination_ref_id;
    private IliasEventObjectFactory $event_object_factory;
    private RepositoryFacade $repository_facade;
    private MembershipManager $membership_manager;
    private \EventoImport\import\Logger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, int $destination_ref_id, IliasEventObjectFactory $event_object_factory, RepositoryFacade $repository_facade, MembershipManager $membership_manager, \EventoImport\import\Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->destination_ref_id = $destination_ref_id;
        $this->event_object_factory = $event_object_factory;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = \EventoImport\import\Logger::CREVENTO_MA_EVENT_WITH_PARENT_EVENT_CREATED;
    }

    public function executeAction() : void
    {
        $parent_event_crs_obj = $this->event_object_factory->buildNewCourseObject(
            $this->evento_event->getGroupName(),
            $this->evento_event->getDescription(),
            $this->destination_ref_id,
        );

        $event_sub_group = $this->event_object_factory->buildNewGroupObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $parent_event_crs_obj->getRefId(),
        );

        $this->repository_facade->addNewIliasEventoParentEvent(
            $this->evento_event,
            $parent_event_crs_obj
        );

        $ilias_event = $this->repository_facade->addNewIliasEvent($this->evento_event, $event_sub_group);

        $this->membership_manager->syncMemberships($this->evento_event, $ilias_event);

        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $event_sub_group->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}

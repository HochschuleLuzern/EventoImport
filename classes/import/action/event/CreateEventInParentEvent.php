<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\db\IliasEventObjectService;
use EventoImport\import\db\IliasUserServices;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\db\MembershipManager;

class CreateEventInParentEvent implements EventAction
{
    private EventoEvent $evento_event;
    private IliasEventoParentEvent $parent_event;
    private IliasEventObjectFactory $event_object_factory;
    private IliasEventObjectService $repository_facade;
    private MembershipManager $membership_manager;
    private \EventoImport\import\Logger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, IliasEventoParentEvent $parent_event, IliasEventObjectFactory $event_object_factory, IliasEventObjectService $repository_facade, MembershipManager $membership_manager, \EventoImport\import\Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->parent_event = $parent_event;
        $this->event_object_factory = $event_object_factory;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = \EventoImport\import\Logger::CREVENTO_MA_EVENT_IN_EXISTING_PARENT_EVENT_CREATED;
    }

    public function executeAction() : void
    {
        $event_sub_group = $this->event_object_factory->buildNewGroupObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $this->parent_event->getRefId(),
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

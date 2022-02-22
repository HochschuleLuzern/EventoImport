<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\service\IliasEventObjectService;
use EventoImport\import\service\IliasUserServices;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\service\MembershipManager;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\IliasEventObjectRepository;
use EventoImport\import\Logger;

class CreateEventInParentEvent implements EventAction
{
    private EventoEvent $evento_event;
    private IliasEventoParentEvent $parent_event;
    private IliasEventObjectRepository $ilias_event_object_repo;
    private IliasEventObjectService $repository_facade;
    private MembershipManager $membership_manager;
    private Logger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, IliasEventoParentEvent $parent_event, IliasEventObjectService $repository_facade, IliasEventObjectRepository $ilias_event_object_repo, MembershipManager $membership_manager, Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->parent_event = $parent_event;
        $this->ilias_event_object_repo = $ilias_event_object_repo;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = Logger::CREVENTO_MA_EVENT_IN_EXISTING_PARENT_EVENT_CREATED;
    }

    public function executeAction() : void
    {
        $event_sub_group = $this->repository_facade->buildNewGroupObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $this->parent_event->getRefId(),
        );

        $ilias_event = $this->createSubGroupEvent($this->evento_event, $this->parent_event);

        $this->membership_manager->syncMemberships($this->evento_event, $ilias_event);

        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $event_sub_group->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }

    private function createSubGroupEvent(EventoEvent $evento_event, IliasEventoParentEvent $evento_parent_event) : IliasEventoEvent
    {
        $event_sub_group = $this->repository_facade->buildNewGroupObject(
            $evento_event->getName(),
            $evento_event->getDescription(),
            $evento_parent_event->getRefId()
        );

        $ilias_event = new IliasEventoEvent(
            $evento_event->getEventoId(),
            $evento_event->getName(),
            $evento_event->getDescription(),
            $evento_event->getType(),
            $evento_event->hasCreateCourseFlag(),
            $evento_event->getStartDate(),
            $evento_event->getEndDate(),
            $event_sub_group->getType(),
            $event_sub_group->getRefId(),
            $event_sub_group->getId(),
            $event_sub_group->getDefaultAdminRole(),
            $event_sub_group->getDefaultMemberRole(),
            $evento_event->getGroupUniqueKey()
        );

        $this->ilias_event_object_repo->addNewEventoIliasEvent($ilias_event);

        return $ilias_event;
    }
}

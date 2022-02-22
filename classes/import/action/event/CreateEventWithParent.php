<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\service\IliasEventObjectService;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\service\MembershipManager;
use EventoImport\import\db\IliasEventObjectRepository;
use EventoImport\import\Logger;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;

class CreateEventWithParent implements EventAction
{
    private EventoEvent $evento_event;
    private int $destination_ref_id;
    private IliasEventObjectRepository $ilias_event_object_repo;
    private IliasEventObjectService $repository_facade;
    private MembershipManager $membership_manager;
    private Logger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, int $destination_ref_id, IliasEventObjectService $repository_facade, IliasEventObjectRepository $ilias_event_object_repo, MembershipManager $membership_manager, Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->destination_ref_id = $destination_ref_id;
        $this->ilias_event_object_repo = $ilias_event_object_repo;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = Logger::CREVENTO_MA_EVENT_WITH_PARENT_EVENT_CREATED;
    }

    private function createParentEvent(EventoEvent $evento_event) : IliasEventoParentEvent
    {
        $parent_event_crs_obj = $this->repository_facade->buildNewCourseObject(
            $evento_event->getGroupName(),
            $evento_event->getDescription(),
            $this->destination_ref_id,
        );

        $ilias_evento_parent_event = new IliasEventoParentEvent(
            $evento_event->getGroupUniqueKey(),
            $evento_event->getGroupId(),
            $evento_event->getGroupName(),
            $parent_event_crs_obj->getRefId(),
            $parent_event_crs_obj->getDefaultAdminRole(),
            $parent_event_crs_obj->getDefaultMemberRole()
        );

        $this->ilias_event_object_repo->addNewParentEvent($ilias_evento_parent_event);

        return $ilias_evento_parent_event;
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

    public function executeAction() : void
    {
        $parent_event = $this->createParentEvent($this->evento_event);
        $sub_group_event = $this->createSubGroupEvent($this->evento_event, $parent_event);

        $this->membership_manager->syncMemberships($this->evento_event, $sub_group_event);

        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $sub_group_event->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}

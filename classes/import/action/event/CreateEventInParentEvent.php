<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\service\IliasEventObjectService;
use EventoImport\import\service\IliasUserServices;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\service\MembershipManager;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\IliasEventoEventObjectRepository;
use EventoImport\import\Logger;

class CreateEventInParentEvent implements EventImportAction
{
    private EventoEvent $evento_event;
    private IliasEventoParentEvent $parent_event;
    private IliasEventoEventObjectRepository $evento_event_object_repo;
    private IliasEventObjectService $ilias_event_obj_service;
    private MembershipManager $membership_manager;
    private Logger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, IliasEventoParentEvent $parent_event, IliasEventObjectService $ilias_event_obj_service, IliasEventoEventObjectRepository $evento_event_object_repo, MembershipManager $membership_manager, Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->parent_event = $parent_event;
        $this->evento_event_object_repo = $evento_event_object_repo;
        $this->ilias_event_obj_service = $ilias_event_obj_service;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = Logger::CREVENTO_MA_EVENT_IN_EXISTING_PARENT_EVENT_CREATED;
    }

    public function executeAction() : void
    {
        $event_sub_group = $this->ilias_event_obj_service->buildNewGroupObject(
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
        $event_sub_group = $this->ilias_event_obj_service->buildNewGroupObject(
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
            (int) $event_sub_group->getType(),
            (int) $event_sub_group->getRefId(),
            (int) $event_sub_group->getId(),
            (int) $event_sub_group->getDefaultAdminRole(),
            (int) $event_sub_group->getDefaultMemberRole(),
            $evento_event->getGroupUniqueKey()
        );

        $this->evento_event_object_repo->addNewEventoIliasEvent($ilias_event);

        return $ilias_event;
    }
}

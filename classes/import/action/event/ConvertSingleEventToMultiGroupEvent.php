<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\IliasEventObjectService;
use EventoImport\import\db\MembershipManager;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\IliasEventObjectRepository;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\Logger;

class ConvertSingleEventToMultiGroupEvent implements EventAction
{
    private EventoEvent $evento_event;
    private IliasEventoEvent $ilias_event;
    private IliasEventObjectService $repository_facade;
    private IliasEventObjectRepository $ilias_event_object_repo;
    private MembershipManager $membership_manager;
    private Logger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, IliasEventoEvent $ilias_event, IliasEventObjectService $repository_facade, IliasEventObjectRepository $ilias_event_object_repo, MembershipManager $membership_manager, Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->ilias_event = $ilias_event;
        // TODO: Refactor with refactoring of repository facade
        $this->repository_facade = $repository_facade;
        $this->ilias_event_object_repo = $ilias_event_object_repo;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = Logger::CREVENTO_MA_SINGLE_EVENT_TO_MULTI_GROUP_CONVERTED;
    }

    public function executeAction() : void
    {
        $current_event_object = $this->repository_facade->getCourseObjectForRefId($this->ilias_event->getRefId());

        // Only change title of crs-obj if it has not been changed by an admin
        if ($this->evento_event->getName() == $current_event_object->getTitle()) {
            $current_event_object->setTitle($this->evento_event->getGroupName());
            $current_event_object->update();
        }

        // Create first subgroup which now is the new event object
        $this->createParentEvent($this->evento_event, $current_event_object);

        // Update DB-Entries
        $parent_event = $this->createParentEvent($this->evento_event, $current_event_object);
        $updated_ilias_event = $this->createCourseAndUpdateIliasEventoEntry($this->evento_event, $parent_event);

        $this->membership_manager->syncMemberships($this->evento_event, $updated_ilias_event);

        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $updated_ilias_event->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }

    private function createParentEvent(EventoEvent $evento_event, \ilObjCourse $parent_event_crs_obj) : IliasEventoParentEvent
    {
        $ilias_evento_parent_event = new IliasEventoParentEvent(
            $evento_event->getGroupUniqueKey(),
            $evento_event->getGroupId(),
            $evento_event->getGroupName(),
            $parent_event_crs_obj->getRefId(),
            $parent_event_crs_obj->getDefaultAdminRole(),
            $parent_event_crs_obj->getDefaultMemberRole()
        );

        $this->ilias_event_object_repo->addNewParentEvent($ilias_evento_parent_event);
    }

    private function createCourseAndUpdateIliasEventoEntry(EventoEvent $evento_event, IliasEventoParentEvent $evento_parent_event) : IliasEventoEvent
    {
        $event_sub_group = $this->repository_facade->buildNewGroupObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $evento_parent_event->getRefId()
        );

        $ilias_evento_event = new IliasEventoEvent(
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

        $this->ilias_event_object_repo->updateIliasEventoEvent($ilias_evento_event);

        return $ilias_evento_event;
    }
}

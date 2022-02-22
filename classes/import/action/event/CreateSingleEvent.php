<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\IliasEventObjectService;
use EventoImport\import\db\MembershipManager;
use EventoImport\import\db\IliasEventObjectRepository;
use EventoImport\import\Logger;
use EventoImport\import\db\model\IliasEventoEvent;

class CreateSingleEvent implements EventAction
{
    private EventoEvent $evento_event;
    private int $destination_ref_id;
    private IliasEventObjectService $repository_facade;
    private IliasEventObjectRepository $event_object_repo;
    private MembershipManager $membership_manager;
    private Logger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, int $destination_ref_id, IliasEventObjectService $repository_facade, IliasEventObjectRepository $event_object_repo, MembershipManager $membership_manager, Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->destination_ref_id = $destination_ref_id;
        $this->repository_facade = $repository_facade;
        $this->event_object_repo = $event_object_repo;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = Logger::CREVENTO_MA_SINGLE_EVENT_CREATED;
    }

    public function executeAction() : void
    {
        $course_object = $this->repository_facade->buildNewCourseObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $this->destination_ref_id,
        );

        $ilias_evento_event = new IliasEventoEvent(
            $this->evento_event->getEventoId(),
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $this->evento_event->getType(),
            $this->evento_event->hasCreateCourseFlag(),
            $this->evento_event->getStartDate(),
            $this->evento_event->getEndDate(),
            $course_object->getType(),
            $course_object->getRefId(),
            $course_object->getId(),
            $course_object->getDefaultAdminRole(),
            $course_object->getDefaultMemberRole(),
            $this->evento_event->getGroupUniqueKey()
        );

        $this->event_object_repo->addNewEventoIliasEvent($ilias_evento_event);

        $this->membership_manager->syncMemberships($this->evento_event, $ilias_evento_event);

        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $ilias_evento_event->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}

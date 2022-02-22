<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\service\MembershipManager;
use EventoImport\import\db\IliasEventObjectRepository;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\Logger;

class MarkExistingIliasObjAsEvent implements EventAction
{
    private EventoEvent $evento_event;
    private \ilContainer $ilias_object;
    private IliasEventObjectRepository $ilias_event_obj_repo;
    private MembershipManager $membership_manager;
    private Logger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, \ilContainer $ilias_object, IliasEventObjectRepository $ilias_event_obj_repo, MembershipManager $membership_manager, Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->ilias_object = $ilias_object;
        $this->ilias_event_obj_repo = $ilias_event_obj_repo;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = Logger::CREVENTO_MA_EXISTING_ILIAS_COURSE_AS_EVENT_MARKED;
    }

    public function executeAction() : void
    {
        $ilias_evento_event = new IliasEventoEvent(
            $this->evento_event->getEventoId(),
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $this->evento_event->getType(),
            $this->evento_event->hasCreateCourseFlag(),
            $this->evento_event->getStartDate(),
            $this->evento_event->getEndDate(),
            $this->ilias_object->getType(),
            $this->ilias_object->getRefId(),
            $this->ilias_object->getId(),
            $this->ilias_object->getDefaultAdminRole(),
            $this->ilias_object->getDefaultMemberRole(),
            $this->evento_event->getGroupUniqueKey()
        );
        $this->ilias_event_obj_repo->addNewEventoIliasEvent($ilias_evento_event);

        $this->membership_manager->syncMemberships($this->evento_event, $ilias_evento_event);
        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $ilias_evento_event->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}

<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\service\MembershipManager;
use EventoImport\import\db\IliasEventoEventObjectRepository;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\Logger;

class MarkExistingIliasObjAsEvent implements EventAction
{
    private EventoEvent $evento_event;
    private \ilContainer $ilias_object;
    private IliasEventoEventObjectRepository $evento_event_object_repo;
    private MembershipManager $membership_manager;
    private Logger $logger;
    private int $log_code;

    public function __construct(EventoEvent $evento_event, \ilContainer $ilias_object, IliasEventoEventObjectRepository $evento_event_object_repo, MembershipManager $membership_manager, Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->ilias_object = $ilias_object;
        $this->evento_event_object_repo = $evento_event_object_repo;
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
            (int) $this->ilias_object->getRefId(),
            (int) $this->ilias_object->getId(),
            (int) $this->ilias_object->getDefaultAdminRole(),
            (int) $this->ilias_object->getDefaultMemberRole(),
            $this->evento_event->getGroupUniqueKey()
        );
        $this->evento_event_object_repo->addNewEventoIliasEvent($ilias_evento_event);

        $this->membership_manager->syncMemberships($this->evento_event, $ilias_evento_event);
        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $ilias_evento_event->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}

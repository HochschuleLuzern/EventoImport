<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\data_management\MembershipManager;
use EventoImport\import\Logger;
use EventoImport\import\data_management\EventManager;

class CreateEventWithParent implements EventImportAction
{
    private EventoEvent $evento_event;
    private EventManager $event_manager;
    private MembershipManager $membership_manager;
    private Logger $logger;
    private int $log_info_code;

    public function __construct(EventoEvent $evento_event, EventManager $event_manager, MembershipManager $membership_manager, Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->event_manager = $event_manager;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;

        $this->log_info_code = Logger::CREVENTO_MA_EVENT_WITH_PARENT_EVENT_CREATED;
    }

    public function executeAction() : void
    {
        $parent_event = $this->event_manager->createParentEventCourse($this->evento_event);
        $sub_group_event = $this->event_manager->createSubGroupEvent($this->evento_event, $parent_event);

        $this->membership_manager->syncMemberships($this->evento_event, $sub_group_event);

        $this->logger->logEventImport(
            $this->log_info_code,
            $this->evento_event->getEventoId(),
            $sub_group_event->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}

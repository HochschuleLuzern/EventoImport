<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\service\IliasEventObjectService;
use EventoImport\import\service\MembershipManager;
use EventoImport\import\manager\db\IliasEventoEventObjectRepository;
use EventoImport\import\Logger;
use EventoImport\import\manager\db\model\IliasEventoEvent;
use EventoImport\import\EventManager;

class CreateSingleEvent implements EventImportAction
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

        $this->log_info_code = Logger::CREVENTO_MA_SINGLE_EVENT_CREATED;
    }

    public function executeAction() : void
    {
        $ilias_evento_event = $this->event_manager->createNewSingleCourseEvent($this->evento_event);
        $this->membership_manager->syncMemberships($this->evento_event, $ilias_evento_event);

        $this->logger->logEventImport(
            $this->log_info_code,
            $this->evento_event->getEventoId(),
            $ilias_evento_event->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}

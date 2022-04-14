<?php declare(strict_type=1);

namespace EventoImport\import\action\event;

use EventoImport\import\data_management\repository\model\IliasEventoEvent;
use EventoImport\import\data_management\EventManager;
use EventoImport\import\data_management\MembershipManager;
use EventoImport\import\Logger;
use EventoImport\communication\api_models\EventoEvent;

class UnmarkDeletedIliasObject implements EventImportAction
{
    private EventoEvent $evento_event;
    private IliasEventoEvent $ilias_event;
    private EventManager $event_manager;
    private MembershipManager $membership_manager;
    private Logger $logger;

    private $log_info_code;

    public function __construct(
        EventoEvent $evento_event,
        IliasEventoEvent $ilias_event,
        EventManager $event_manager,
        MembershipManager $membership_manager,
        Logger $logger
    )
    {
        $this->evento_event = $evento_event;
        $this->ilias_event = $ilias_event;
        $this->event_manager = $event_manager;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;

        $this->log_info_code = Logger::CREVENTO_MA_ILIAS_OBJECT_FOR_EVENT_REMOVED;
    }

    public function executeAction() : void
    {
        $this->event_manager->removeIliasEventoEventConnection($this->ilias_event);
        $this->membership_manager->removeEventoIliasMembershipConnectionsForEvent($this->ilias_event);

        $this->logger->logEventImport(
            $this->log_info_code,
            $this->ilias_event->getEventoEventId(),
            null,
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}
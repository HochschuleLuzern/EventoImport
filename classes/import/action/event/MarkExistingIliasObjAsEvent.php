<?php declare(strict_types = 1);

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\data_management\MembershipManager;
use EventoImport\import\data_management\repository\IliasEventoEventObjectRepository;
use EventoImport\import\data_management\repository\model\IliasEventoEvent;
use EventoImport\import\Logger;
use EventoImport\import\data_management\EventManager;

class MarkExistingIliasObjAsEvent implements EventImportAction
{
    private EventoEvent $evento_event;
    private \ilContainer $ilias_object;
    private EventManager $event_manager;
    private MembershipManager $membership_manager;
    private Logger $logger;
    private int $log_info_code;

    public function __construct(EventoEvent $evento_event, \ilContainer $ilias_object, EventManager $event_manager, MembershipManager $membership_manager, Logger $logger)
    {
        $this->evento_event = $evento_event;
        $this->ilias_object = $ilias_object;
        $this->event_manager = $event_manager;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;

        $this->log_info_code = Logger::CREVENTO_MA_EXISTING_ILIAS_COURSE_AS_EVENT_MARKED;
    }

    public function executeAction() : void
    {
        $ilias_evento_event = $this->event_manager->createIliasObjectAndEventoEventConnection($this->evento_event, $this->ilias_object);

        $this->membership_manager->syncMemberships($this->evento_event, $ilias_evento_event);
        $this->logger->logEventImport(
            $this->log_info_code,
            $this->evento_event->getEventoId(),
            $ilias_evento_event->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}

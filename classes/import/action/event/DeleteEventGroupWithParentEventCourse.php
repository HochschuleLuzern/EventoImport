<?php declare(strict_type=1);

namespace EventoImport\import\action\event;

use EventoImport\import\manager\db\model\IliasEventoEvent;
use EventoImport\import\manager\db\model\IliasEventoParentEvent;
use EventoImport\import\service\IliasEventObjectService;
use EventoImport\import\manager\db\IliasEventoEventObjectRepository;
use EventoImport\import\Logger;
use EventoImport\import\EventManager;

class DeleteEventGroupWithParentEventCourse implements EventDeleteAction
{
    private IliasEventoEvent $ilias_evento_event;
    private IliasEventoParentEvent $ilias_evento_parent_event;
    private EventManager $event_manager;
    private Logger $logger;

    private int $log_info_code;

    public function __construct(
        IliasEventoEvent $ilias_evento_event,
        IliasEventoParentEvent $ilias_evento_parent_event,
        EventManager $event_manager,
        Logger $logger
    ) {
        $this->ilias_evento_event = $ilias_evento_event;
        $this->ilias_evento_parent_event = $ilias_evento_parent_event;
        $this->event_manager = $event_manager;
        $this->logger = $logger;

        $this->log_info_code = Logger::CREVENTO_MA_DELETE_EVENT_WITH_PARENT;
    }

    public function executeAction() : void
    {
        throw new \Exception("This action is not implemented yet");

        $this->logger->logEventImport($this->log_info_code, $this->ilias_evento_event->getEventoEventId());
        // TODO: Implement executeAction() method.
    }
}

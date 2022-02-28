<?php declare(strict_type=1);

namespace EventoImport\import\action\event;

use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\service\IliasEventObjectService;
use EventoImport\import\db\IliasEventoEventObjectRepository;
use EventoImport\import\Logger;

class DeleteSingleCourseEvent implements EventDeleteAction
{
    private IliasEventoEvent $ilias_evento_event;
    private IliasEventObjectService $ilias_event_obj_service;
    private IliasEventoEventObjectRepository $evento_event_object_repo;
    private Logger $logger;

    private int $log_info_code;

    public function __construct(
        IliasEventoEvent $ilias_evento_event,
        IliasEventObjectService $ilias_event_obj_service,
        IliasEventoEventObjectRepository $evento_event_object_repo,
        Logger $logger
    ) {
        $this->ilias_evento_event = $ilias_evento_event;
        $this->ilias_event_obj_service = $ilias_event_obj_service;
        $this->evento_event_object_repo = $evento_event_object_repo;
        $this->logger = $logger;

        $this->log_info_code = Logger::CREVENTO_MA_DELETE_SINGLE_EVENT;
    }

    public function executeAction() : void
    {
        throw new \Exception("This action is not implemented yet");

        $this->logger->logEventImport(
            $this->log_info_code,
            $this->ilias_evento_event->getEventoEventId(),
            $this->ilias_evento_event->getRefId(),
            []
        );
    }
}
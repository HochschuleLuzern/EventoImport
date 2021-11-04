<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\EventoImportAction;

class ReportNonIliasEvent implements EventoImportAction
{
    private $evento_event;
    private $logger;

    public function __construct(EventoEvent $evento_event, \ilEventoImportLogger $logger)
    {
        $this->evento_event;
        $this->logger;
    }

    public function executeAction()
    {
        // TODO: Implement executeAction() method.
    }
}
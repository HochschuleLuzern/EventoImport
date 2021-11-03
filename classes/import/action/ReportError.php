<?php

namespace EventoImport\import\action;

class ReportError implements EventoImportAction
{
    private $error_code;
    private $error_data;
    private $logger;

    public function __construct(int $error_code, array $error_data, \ilEventoImportLogger $logger)
    {
        $this->error_code  = $error_code;
        $this->error_data = $error_data;
        $this->logger = $logger;
    }

    public function executeAction()
    {
        $this->logger->log($this->error_code, $this->error_data);
    }
}
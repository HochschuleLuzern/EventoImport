<?php

namespace EventoImport\import\action;

abstract class ReportError implements EventoImportAction
{
    protected $error_code;
    protected $error_data;
    protected $logger;

    public function __construct(int $error_code, array $error_data, \ilEventoImportLogger $logger)
    {
        $this->error_code = $error_code;
        $this->error_data = $error_data;
        $this->logger = $logger;
    }
}

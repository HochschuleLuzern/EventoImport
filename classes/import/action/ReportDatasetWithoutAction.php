<?php

namespace EventoImport\import\action;

abstract class ReportDatasetWithoutAction implements EventoImportAction
{
    protected $log_info_code;
    protected $log_data;
    protected $logger;

    public function __construct(int $log_info_code, array $log_data, \ilEventoImportLogger $logger)
    {
        $this->log_info_code = $log_info_code;
        $this->log_data = $log_data;
        $this->logger = $logger;
    }
}

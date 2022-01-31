<?php declare(strict_types = 1);

namespace EventoImport\import\action;

abstract class ReportDatasetWithoutAction implements EventoImportAction
{
    protected int $log_info_code;
    protected array $log_data;
    protected \ilEventoImportLogger $logger;

    public function __construct(int $log_info_code, array $log_data, \ilEventoImportLogger $logger)
    {
        $this->log_info_code = $log_info_code;
        $this->log_data = $log_data;
        $this->logger = $logger;
    }
}

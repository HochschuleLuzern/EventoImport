<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\ReportDatasetWithoutAction;

class ReportNonIliasEvent extends ReportDatasetWithoutAction
{
    public function __construct(int $log_info_code, array $log_data, \ilEventoImportLogger $logger)
    {
        parent::__construct($log_info_code, $log_data, $logger);
    }

    public function executeAction()
    {
        throw new \Error('Method not implemented yet');
    }
}

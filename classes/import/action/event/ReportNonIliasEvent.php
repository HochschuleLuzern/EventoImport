<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\ReportError;

class ReportNonIliasEvent extends ReportError
{
    public function __construct(int $error_code, array $error_data, \ilEventoImportLogger $logger)
    {
        parent::__construct($error_code, $error_data, $logger);
    }

    public function executeAction()
    {
        throw new \Error('Method not implemented yet');
    }
}

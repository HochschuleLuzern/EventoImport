<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\ReportDatasetWithoutAction;

/**
 * Class ReportNonIliasEvent
 * @package EventoImport\import\action\event
 */
class ReportNonIliasEvent extends ReportDatasetWithoutAction
{
    /**
     * ReportNonIliasEvent constructor.
     * @param int                   $log_info_code
     * @param array                 $log_data
     * @param \ilEventoImportLogger $logger
     */
    public function __construct(int $log_info_code, array $log_data, \ilEventoImportLogger $logger)
    {
        parent::__construct($log_info_code, $log_data, $logger);
    }

    public function executeAction() : void
    {
        throw new \Error('Method not implemented yet');
    }
}

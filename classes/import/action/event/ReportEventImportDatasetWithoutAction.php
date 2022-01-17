<?php

namespace EventoImport\import\action\event;

use EventoImport\import\action\ReportDatasetWithoutAction;

/**
 * Class ReportEventImportDatasetWithoutAction
 * @package EventoImport\import\action\event
 */
class ReportEventImportDatasetWithoutAction extends ReportDatasetWithoutAction implements EventAction
{
    /** @var int */
    private int $evento_id;

    /** @var ?int */
    private ?int $ref_id;

    public function __construct(int $log_info_code, int $evento_id, ?int $ref_id, array $error_data, \ilEventoImportLogger $logger)
    {
        $this->evento_id = $evento_id;
        $this->ref_id = $ref_id;
        parent::__construct($log_info_code, $error_data, $logger);
    }

    public function executeAction() : void
    {
        $this->logger->logEventImport(
            $this->log_info_code,
            $this->evento_id,
            $this->ref_id,
            $this->log_data
        );
    }
}

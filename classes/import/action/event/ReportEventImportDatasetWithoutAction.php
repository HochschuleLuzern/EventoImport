<?php

namespace EventoImport\import\action\event;

use EventoImport\import\action\ReportDatasetWithoutAction;

class ReportEventImportDatasetWithoutAction extends ReportDatasetWithoutAction
{
    private $evento_id;
    private $ref_id;

    public function __construct(int $log_info_code, int $evento_id, ?int $ref_id, array $error_data, \ilEventoImportLogger $logger)
    {
        $this->evento_id = $evento_id;
        $this->ref_id = $ref_id;
        parent::__construct($log_info_code, $error_data, $logger);
    }

    public function executeAction()
    {
        $this->logger->logEventImport(
            $this->log_info_code,
            $this->evento_id,
            $this->ref_id,
            $this->log_data
        );
    }
}

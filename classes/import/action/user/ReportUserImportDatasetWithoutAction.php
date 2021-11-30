<?php

namespace EventoImport\import\action\user;

use EventoImport\import\action\ReportDatasetWithoutAction;

class ReportUserImportDatasetWithoutAction extends ReportDatasetWithoutAction
{
    /** @var int */
    private $evento_id;

    /** @var string */
    private $user_name;

    public function __construct(int $log_info_code, int $evento_id, string $user_name, array $error_data, \ilEventoImportLogger $logger)
    {
        $this->evento_id = $evento_id;
        $this->user_name = $user_name;
        parent::__construct($log_info_code, $error_data, $logger);
    }

    public function executeAction()
    {
        $this->logger->logUserImport(
            $this->log_info_code,
            $this->evento_id,
            $this->user_name,
            $this->log_data
        );
    }
}
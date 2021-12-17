<?php

namespace EventoImport\import\action\user;

use EventoImport\import\action\ReportDatasetWithoutAction;

/**
 * Class ReportUserImportDatasetWithoutAction
 * @package EventoImport\import\action\user
 */
class ReportUserImportDatasetWithoutAction extends ReportDatasetWithoutAction
{
    /** @var int */
    private int $evento_id;

    /** @var string */
    private string $user_name;

    /**
     * ReportUserImportDatasetWithoutAction constructor.
     * @param int                   $log_info_code
     * @param int                   $evento_id
     * @param string                $user_name
     * @param array                 $error_data
     * @param \ilEventoImportLogger $logger
     */
    public function __construct(int $log_info_code, int $evento_id, string $user_name, array $error_data, \ilEventoImportLogger $logger)
    {
        $this->evento_id = $evento_id;
        $this->user_name = $user_name;
        parent::__construct($log_info_code, $error_data, $logger);
    }

    public function executeAction() : void
    {
        $this->logger->logUserImport(
            $this->log_info_code,
            $this->evento_id,
            $this->user_name,
            $this->log_data
        );
    }
}

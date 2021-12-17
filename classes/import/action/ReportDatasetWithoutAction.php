<?php

namespace EventoImport\import\action;

/**
 * Class ReportDatasetWithoutAction
 * @package EventoImport\import\action
 */
abstract class ReportDatasetWithoutAction implements EventoImportAction
{
    /** @var int */
    protected int $log_info_code;

    /** @var array */
    protected array $log_data;

    /** @var \ilEventoImportLogger */
    protected \ilEventoImportLogger $logger;

    /**
     * ReportDatasetWithoutAction constructor.
     * @param int                   $log_info_code
     * @param array                 $log_data
     * @param \ilEventoImportLogger $logger
     */
    public function __construct(int $log_info_code, array $log_data, \ilEventoImportLogger $logger)
    {
        $this->log_info_code = $log_info_code;
        $this->log_data = $log_data;
        $this->logger = $logger;
    }
}

<?php

namespace EventoImport\import\action\user;

use EventoImport\import\action\ReportError;

class ReportUserImportError extends ReportError
{
    /** @var int */
    private $evento_id;

    /** @var string */
    private $user_name;

    public function __construct(int $error_code, int $evento_id, string $user_name, array $error_data, \ilEventoImportLogger $logger)
    {
        $this->evento_id = $evento_id;
        $this->user_name = $user_name;
        parent::__construct($error_code, $error_data, $logger);
    }

    public function executeAction()
    {
        $this->logger->logUserImport(
            $this->error_code,
            $this->evento_id,
            $this->user_name,
            $this->error_data
        );
    }
}

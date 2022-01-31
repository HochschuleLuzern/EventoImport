<?php declare(strict_types = 1);

class ilEventoImportApiDataException extends ilException
{
    public function __construct(string $operation, string $a_message, $api_data, $a_code = 0)
    {
        parent::__construct($a_message, $a_code);
    }
}

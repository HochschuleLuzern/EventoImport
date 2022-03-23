<?php declare(strict_types = 1);

class ilEventoImportApiDataException extends ilException
{
    private string $operation;
    private $api_data;

    public function __construct(string $operation, string $a_message, $api_data, $a_code = 0)
    {
        parent::__construct($a_message, $a_code);

        $this->operation = $operation;
        $this->api_data = $api_data;
    }

    public function getApiData()
    {
        return $this->api_data;
    }
}

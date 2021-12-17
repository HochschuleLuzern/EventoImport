<?php

namespace EventoImport\communication\api_models;

/**
 * Class EventoUserShort
 * @package EventoImport\communication\api_models
 */
class EventoUserShort extends ApiDataModelBase
{
    const JSON_ID = 'idAccount';
    const JSON_EMAIL = 'email';

    /** @var int */
    private int $evento_id;

    /** @var string */
    private string $email_address;

    public function __construct(array $data_set)
    {
        $this->evento_id = $this->validateAndReturnNumber($data_set, self::JSON_ID);
        $this->email_address = $this->validateAndReturnString($data_set, self::JSON_EMAIL);

        $this->checkErrorsAndMaybeThrowException();
        $this->decoded_api_data = $data_set;
    }

    /**
     * @return int
     */
    public function getEventoId() : int
    {
        return $this->evento_id;
    }

    /**
     * @return string
     */
    public function getEmailAddress() : string
    {
        return $this->email_address;
    }

    public function getDecodedApiData() : array
    {
        return $this->decoded_api_data;
    }
}

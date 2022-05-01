<?php declare(strict_types = 1);

namespace EventoImport\communication\api_models;

class EventoUserShort extends ApiDataModelBase
{
    const JSON_ID = 'idAccount';
    const JSON_EMAIL = 'email';

    private ?int $evento_id;
    private ?string $email_address;

    public function __construct(array $data_set)
    {
        $this->evento_id = $this->validateAndReturnNumber($data_set, self::JSON_ID);
        $this->email_address = $this->validateAndReturnString($data_set, self::JSON_EMAIL);

        $this->decoded_api_data = $data_set;
        $this->checkErrorsAndMaybeThrowException();
    }

    public function getEventoId() : int
    {
        return $this->evento_id;
    }

    public function getEmailAddress() : string
    {
        return $this->email_address;
    }

    public function getDecodedApiData() : array
    {
        return $this->decoded_api_data;
    }
}

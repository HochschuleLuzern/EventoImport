<?php declare(strict_types = 1);

namespace EventoImport\communication\api_models;

class EventoUserShort extends ApiDataModelBase
{
    const JSON_ID = 'idAccount';
    const JSON_EDU_ID = 'eduId';

    private ?int $evento_id = null;
    private ?string $edu_id = null;

    public function __construct(array $data_set)
    {
        $this->evento_id = $this->validateAndReturnNumber($data_set, self::JSON_ID);
        $this->edu_id = $this->validateAndReturnString($data_set, self::EDU_ID);

        $this->decoded_api_data = $data_set;
        $this->checkErrorsAndMaybeThrowException();
    }

    public function getEventoId() : int
    {
        return $this->evento_id;
    }

    public function getEduId() : string
    {
        return $this->edu_id ?? '';
    }

    public function getDecodedApiData() : array
    {
        return $this->decoded_api_data;
    }
}

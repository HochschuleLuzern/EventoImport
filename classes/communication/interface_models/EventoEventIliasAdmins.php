<?php

namespace EventoImport\communication\api_models;

class EventoEventIliasAdmins extends ApiDataModelBase
{
    const JSON_ID = 'idEvent';
    const JSON_ACCOUNTS = 'accounts';

    private $evento_id;
    private $account_list;

    public function __construct(array $data_set)
    {
        $this->evento_id = $this->validateAndReturnNumber($data_set, self::JSON_ID);
        $account_list = $this->validateAndReturnArray($data_set, self::JSON_ACCOUNTS);

        $this->account_list = [];
        foreach ($account_list as $account_data) {
            $this->account_list[] = new EventoUserShort($account_data);
        }

        $this->checkErrorsAndMaybeThrowException();
    }

    /**
     * @return int
     */
    public function getEventoId() : int
    {
        return $this->evento_id;
    }

    /**
     * @return array
     */
    public function getAccountList() : array
    {
        return $this->account_list;
    }
}

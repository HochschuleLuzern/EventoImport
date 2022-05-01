<?php declare(strict_types = 1);

namespace EventoImport\import\data_management\repository\model;

class IliasEventoUser
{
    private int $ilias_user_id;
    private int $evento_user_id;
    private string $account_type;

    public function __construct(int $evento_user_id, int $ilias_user_id, string $account_type)
    {
        $this->evento_user_id = $evento_user_id;
        $this->ilias_user_id = $ilias_user_id;
        $this->account_type = $account_type;
    }

    public function getIliasUserId() : int
    {
        return $this->ilias_user_id;
    }

    public function getEventoUserId() : int
    {
        return $this->evento_user_id;
    }

    public function getAccountType() : string
    {
        return $this->account_type;
    }
}

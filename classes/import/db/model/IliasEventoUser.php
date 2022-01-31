<?php declare(strict_types = 1);

namespace EventoImport\import\db\model;

class IliasEventoUser
{
    private int $ilias_user_id;
    private int $evento_user_id;

    public function __construct(int $evento_user_id, int $ilias_user_id)
    {
        $this->evento_user_id = $evento_user_id;
        $this->ilias_user_id = $ilias_user_id;
    }

    public function getIliasUserId() : int
    {
        return $this->ilias_user_id;
    }

    public function getEventoUserId() : int
    {
        return $this->evento_user_id;
    }
}

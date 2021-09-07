<?php

namespace EventoImport\import\data_models;

class EventoUser
{
    private $evento_id;
    private $ilias_user_id;

    public function __construct(int $evento_id, int $ilias_user_id)
    {
        $this->evento_id = $evento_id;
        $this->ilias_user_id = $ilias_user_id;
    }

    public function getEventoId() : int
    {
        return $this->evento_id;
    }

    public function getIliasUserId() : int
    {
        return $this->ilias_user_id;
    }
}
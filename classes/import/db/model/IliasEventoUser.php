<?php

namespace EventoImport\import\db\model;

/**
 * Class IliasEventoUser
 * @package EventoImport\import\db\model
 */
class IliasEventoUser
{
    /** @var int */
    private int $ilias_user_id;

    /** @var int */
    private int $evento_user_id;

    public function __construct(int $evento_user_id, int $ilias_user_id)
    {
        $this->evento_user_id = $evento_user_id;
        $this->ilias_user_id = $ilias_user_id;
    }

    /**
     * @return int
     */
    public function getIliasUserId() : int
    {
        return $this->ilias_user_id;
    }

    /**
     * @return int
     */
    public function getEventoUserId() : int
    {
        return $this->evento_user_id;
    }
}

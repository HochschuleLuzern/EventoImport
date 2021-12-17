<?php

namespace EventoImport\import\db\model;

/**
 * Class EventMemberships
 * @package EventoImport\import\db\model
 */
class EventMemberships
{
    /** @var int */
    private int $event_id;

    /** @var int */
    private int $user_evento_id;

    /** @var int */
    private int $role_id;

    public function __construct(int $event_ref_id, int $user_evento_id, int $role_id)
    {
        $this->event_id = $event_ref_id;
        $this->user_evento_id = $user_evento_id;
        $this->role_id = $role_id;
    }

    /**
     * @return int
     */
    public function getEventId() : int
    {
        return $this->event_id;
    }

    /**
     * @return int
     */
    public function getUserEventoId() : int
    {
        return $this->user_evento_id;
    }

    /**
     * @return int
     */
    public function getRoleId() : int
    {
        return $this->role_id;
    }
}

<?php

namespace EventoImport\import\db\repository;

class EventMembershipRepository
{
    public const TABLE_NAME = 'crevento_memberships';
    public const COL_EVENTO_EVENT_ID = 'evento_event_id';
    public const COL_EVENTO_USER_ID = 'evento_user_id';
    public const COL_ROLE_TYPE = 'role_type';

    /** @var \ilDBInterface */
    private $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }
}
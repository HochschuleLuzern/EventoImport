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

    public function fetchUserIdFromMembership($evento_event_id, $user_id)
    {
        $query = "SELECT usr.".EventoUserRepository::COL_ILIAS_USER_ID." AS user_id FROM " . self::TABLE_NAME . " AS memb"
            . " JOIN " . EventoUserRepository::TABLE_NAME . " AS usr ON memb.".self::COL_EVENTO_USER_ID . " = usr" .EventoUserRepository::COL_ILIAS_USER_ID
            . " WHERE memb.".self::COL_EVENTO_USER_ID . " = " . $this->db->quote($user_id, \ilDBConstants::T_INTEGER)
                . " AND memb.".self::COL_EVENTO_EVENT_ID . " = " . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);
        if($row = $this->db->fetchAssoc($result)) {
            return (int)$row['user_id'];
        }
    }
}
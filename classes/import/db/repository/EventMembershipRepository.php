<?php

namespace EventoImport\import\db\repository;

use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\model\IliasEventoUser;

class EventMembershipRepository
{
    public const TABLE_NAME = 'crevento_memberships';

    public const COL_EVENTO_EVENT_ID = 'evento_event_id';
    public const COL_EVENTO_USER_ID = 'evento_user_id';
    public const COL_ROLE_TYPE = 'role_type';

    public const ROLE_ADMIN = 1;
    public const ROLE_MEMBER = 2;

    /** @var \ilDBInterface */
    private $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function fetchUserIdFromMembership($evento_event_id, $user_id)
    {
        $query = "SELECT usr." . EventoUserRepository::COL_ILIAS_USER_ID . " AS user_id FROM " . self::TABLE_NAME . " AS memb"
            . " JOIN " . EventoUserRepository::TABLE_NAME . " AS usr ON memb." . self::COL_EVENTO_USER_ID . " = usr" . EventoUserRepository::COL_ILIAS_USER_ID
            . " WHERE memb." . self::COL_EVENTO_USER_ID . " = " . $this->db->quote($user_id, \ilDBConstants::T_INTEGER)
                . " AND memb." . self::COL_EVENTO_EVENT_ID . " = " . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);
        if ($row = $this->db->fetchAssoc($result)) {
            return (int) $row['user_id'];
        }
    }

    public function fetchIliasEventoUsersForEventAndRole(int $evento_event_id, int $role_of_event) : array
    {
        $query = 'SELECT usr.' . EventoUserRepository::COL_EVENTO_ID . ' AS evento_user_id, usr.' . EventoUserRepository::COL_ILIAS_USER_ID . ' AS ilias_user_id'
            . ' FROM ' . self::TABLE_NAME . ' AS mem'
            . ' JOIN ' . EventoUserRepository::TABLE_NAME . ' AS usr ON usr.' . EventoUserRepository::COL_EVENTO_ID . ' = mem.' . self::COL_EVENTO_USER_ID
            . ' WHERE mem.' . self::COL_EVENTO_EVENT_ID . ' = ' . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER)
                . ' AND mem.' . self::COL_ROLE_TYPE . ' = ' . $this->db->quote($role_of_event, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);

        $users = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $users[] = new IliasEventoUser($row['evento_user_id'], $row['ilias_user_id']);
        }

        return $users;
    }

    public function addMembershipIfNotExist(int $evento_event_id, int $user_id, int $role_type)
    {
        $query = "SELECT 1 FROM " . self::TABLE_NAME
            . " WHERE " . self::COL_EVENTO_EVENT_ID . " = " . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER)
            . " AND " . self::COL_EVENTO_USER_ID . " = " . $this->db->quote($user_id, \ilDBConstants::T_INTEGER)
            . " LIMIT 1";
        $result = $this->db->query($query);

        $row = $this->db->fetchAssoc($result);
        if (is_null($row)) {
            $this->db->insert(
                self::TABLE_NAME,
                [
                    self::COL_EVENTO_EVENT_ID => [\ilDBConstants::T_INTEGER, $evento_event_id],
                    self::COL_EVENTO_USER_ID => [\ilDBConstants::T_INTEGER, $user_id],
                    self::COL_ROLE_TYPE => [\ilDBConstants::T_INTEGER, $role_type]
                ]
            );
        }
    }

    public function checkIfUserHasMembershipInOtherSubEvent(int $parent_event_id, int $user_evento_id, int $excluding_evento_event_id) : bool
    {
        $q = "SELECT * "
            . " FROM " . IliasEventoEventsRepository::TABLE_NAME . " AS event"
            . " JOIN " . self::TABLE_NAME . " AS mem ON mem." . self::COL_EVENTO_EVENT_ID . " = event." . IliasEventoEventsRepository::COL_EVENTO_ID
            . " WHERE event." . IliasEventoEventsRepository::COL_PARENT_EVENT_KEY . " = " . $this->db->quote($parent_event_id, \ilDBConstants::T_INTEGER)
            . " AND event." . IliasEventoEventsRepository::COL_EVENTO_ID . " != " . $this->db->quote($excluding_evento_event_id, \ilDBConstants::T_INTEGER)
            . " AND mem." . self::COL_EVENTO_USER_ID . " = " . $this->db->quote($user_evento_id, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($q);


        $this->db->in(self::COL_EVENTO_EVENT_ID, [], false, \ilDBConstants::T_INTEGER);
    }
}

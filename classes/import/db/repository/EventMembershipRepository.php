<?php declare(strict_types = 1);

namespace EventoImport\import\db\repository;

use EventoImport\import\db\model\IliasEventoUser;
use EventoImport\import\db\table_definition\IliasEventoEvents;
use EventoImport\import\db\table_definition\IliasEventoEventMemberships;

class EventMembershipRepository
{
    public const TABLE_NAME = 'crevento_memberships';

    public const COL_EVENTO_EVENT_ID = 'evento_event_id';
    public const COL_EVENTO_USER_ID = 'evento_user_id';
    public const COL_ROLE_TYPE = 'role_type';

    public const ROLE_ADMIN = 1;
    public const ROLE_MEMBER = 2;

    private $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function fetchUserIdFromMembership($evento_event_id, $user_id) : ?int
    {
        $query = "SELECT usr." . IliasEventoUserRepository::COL_ILIAS_USER_ID . " AS user_id FROM " . IliasEventoEventMemberships::TABLE_NAME . " AS memb"
            . " JOIN " . IliasEventoUserRepository::TABLE_NAME . " AS usr ON memb." . IliasEventoEventMemberships::COL_EVENTO_USER_ID . " = usr" . IliasEventoUserRepository::COL_ILIAS_USER_ID
            . " WHERE memb." . IliasEventoEventMemberships::COL_EVENTO_USER_ID . " = " . $this->db->quote($user_id, \ilDBConstants::T_INTEGER)
                . " AND memb." . IliasEventoEventMemberships::COL_EVENTO_EVENT_ID . " = " . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);
        if ($row = $this->db->fetchAssoc($result)) {
            return (int) $row['user_id'];
        }

        return null;
    }

    public function fetchIliasEventoUsersForEventAndRole(int $evento_event_id, int $role_of_event) : array
    {
        $query = 'SELECT usr.' . IliasEventoUserRepository::COL_EVENTO_ID . ' AS evento_user_id, usr.' . IliasEventoUserRepository::COL_ILIAS_USER_ID . ' AS ilias_user_id'
            . ' FROM ' . IliasEventoEventMemberships::TABLE_NAME . ' AS mem'
            . ' JOIN ' . IliasEventoUserRepository::TABLE_NAME . ' AS usr ON usr.' . IliasEventoUserRepository::COL_EVENTO_ID . ' = mem.' . IliasEventoEventMemberships::COL_EVENTO_USER_ID
            . ' WHERE mem.' . IliasEventoEventMemberships::COL_EVENTO_EVENT_ID . ' = ' . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER)
                . ' AND mem.' . IliasEventoEventMemberships::COL_ROLE_TYPE . ' = ' . $this->db->quote($role_of_event, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);

        $users = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $users[] = new IliasEventoUser($row['evento_user_id'], $row['ilias_user_id']);
        }

        return $users;
    }

    public function fetchIliasEventoUserForEvent(int $evento_event_id) : array
    {
        $query = 'SELECT usr.' . IliasEventoUserRepository::COL_EVENTO_ID . ' AS evento_user_id, usr.' . IliasEventoUserRepository::COL_ILIAS_USER_ID . ' AS ilias_user_id'
            . ' FROM ' . IliasEventoEventMemberships::TABLE_NAME . ' AS mem'
            . ' JOIN ' . IliasEventoUserRepository::TABLE_NAME . ' AS usr ON usr.' . IliasEventoUserRepository::COL_EVENTO_ID . ' = mem.' . IliasEventoEventMemberships::COL_EVENTO_USER_ID
            . ' WHERE mem.' . IliasEventoEventMemberships::COL_EVENTO_EVENT_ID . ' = ' . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);

        $users = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $users[] = new IliasEventoUser($row['evento_user_id'], $row['ilias_user_id']);
        }

        return $users;
    }

    public function addMembershipIfNotExist(int $evento_event_id, int $user_id, int $role_type) : void
    {
        $query = "SELECT 1 FROM " . IliasEventoEventMemberships::TABLE_NAME
            . " WHERE " . IliasEventoEventMemberships::COL_EVENTO_EVENT_ID . " = " . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER)
            . " AND " . IliasEventoEventMemberships::COL_EVENTO_USER_ID . " = " . $this->db->quote($user_id, \ilDBConstants::T_INTEGER)
            . " LIMIT 1";
        $result = $this->db->query($query);

        $row = $this->db->fetchAssoc($result);
        if (is_null($row)) {
            $this->db->insert(
                IliasEventoEventMemberships::TABLE_NAME,
                [
                    IliasEventoEventMemberships::COL_EVENTO_EVENT_ID => [\ilDBConstants::T_INTEGER, $evento_event_id],
                    IliasEventoEventMemberships::COL_EVENTO_USER_ID => [\ilDBConstants::T_INTEGER, $user_id],
                    IliasEventoEventMemberships::COL_ROLE_TYPE => [\ilDBConstants::T_INTEGER, $role_type]
                ]
            );
        }
    }

    public function checkIfUserHasMembershipInOtherSubEvent(int $parent_event_id, int $user_evento_id, int $excluding_evento_event_id) : bool
    {
        $q = "SELECT * "
            . " FROM " . IliasEventoEvents::TABLE_NAME . " AS event"
            . " JOIN " . IliasEventoEventMemberships::TABLE_NAME . " AS mem ON mem." . IliasEventoEventMemberships::COL_EVENTO_EVENT_ID . " = event." . IliasEventoEvents::COL_EVENTO_ID
            . " WHERE event." . IliasEventoEvents::COL_PARENT_EVENT_KEY . " = " . $this->db->quote($parent_event_id, \ilDBConstants::T_INTEGER)
            . " AND event." . IliasEventoEvents::COL_EVENTO_ID . " != " . $this->db->quote($excluding_evento_event_id, \ilDBConstants::T_INTEGER)
            . " AND mem." . IliasEventoEventMemberships::COL_EVENTO_USER_ID . " = " . $this->db->quote($user_evento_id, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($q);

        return $this->db->numRows($result) > 0;
    }
}

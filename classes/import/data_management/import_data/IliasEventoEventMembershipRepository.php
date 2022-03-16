<?php declare(strict_types = 1);

namespace EventoImport\import\data_management\repository;

use EventoImport\import\data_management\repository\model\IliasEventoUser;
use EventoImport\db\IliasEventoEventsTblDef;
use EventoImport\db\IliasEventoEventMembershipsTblDef;
use EventoImport\db\IliasEventoUserTblDef;

class IliasEventoEventMembershipRepository
{
    public const ROLE_ADMIN = 1;
    public const ROLE_MEMBER = 2;

    private $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function fetchUserIdFromMembership($evento_event_id, $user_id) : ?int
    {
        $query = "SELECT usr." . IliasEventoUserTblDef::COL_ILIAS_USER_ID . " AS user_id FROM " . IliasEventoEventMembershipsTblDef::TABLE_NAME . " AS memb"
            . " JOIN " . IliasEventoUserTblDef::TABLE_NAME . " AS usr ON memb." . IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID . " = usr" . IliasEventoUserTblDef::COL_ILIAS_USER_ID
            . " WHERE memb." . IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID . " = " . $this->db->quote($user_id, \ilDBConstants::T_INTEGER)
                . " AND memb." . IliasEventoEventMembershipsTblDef::COL_EVENTO_EVENT_ID . " = " . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);
        if ($row = $this->db->fetchAssoc($result)) {
            return (int) $row['user_id'];
        }

        return null;
    }

    public function fetchIliasEventoUsersForEventAndRole(int $evento_event_id, int $role_of_event) : array
    {
        $query = 'SELECT usr.' . IliasEventoUserTblDef::COL_EVENTO_ID . ' AS evento_user_id, usr.' . IliasEventoUserTblDef::COL_ILIAS_USER_ID . ' AS ilias_user_id'
            . ' FROM ' . IliasEventoEventMembershipsTblDef::TABLE_NAME . ' AS mem'
            . ' JOIN ' . IliasEventoUserTblDef::TABLE_NAME . ' AS usr ON usr.' . IliasEventoUserTblDef::COL_EVENTO_ID . ' = mem.' . IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID
            . ' WHERE mem.' . IliasEventoEventMembershipsTblDef::COL_EVENTO_EVENT_ID . ' = ' . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER)
                . ' AND mem.' . IliasEventoEventMembershipsTblDef::COL_ROLE_TYPE . ' = ' . $this->db->quote($role_of_event, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);

        $users = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $users[] = new IliasEventoUser($row['evento_user_id'], $row['ilias_user_id']);
        }

        return $users;
    }

    public function fetchIliasEventoUsersForEvent(int $evento_event_id) : array
    {
        $query = 'SELECT usr.' . IliasEventoUserTblDef::COL_EVENTO_ID . ' AS evento_user_id, usr.' . IliasEventoUserTblDef::COL_ILIAS_USER_ID . ' AS ilias_user_id'
            . ' FROM ' . IliasEventoEventMembershipsTblDef::TABLE_NAME . ' AS mem'
            . ' JOIN ' . IliasEventoUserTblDef::TABLE_NAME . ' AS usr ON usr.' . IliasEventoUserTblDef::COL_EVENTO_ID . ' = mem.' . IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID
            . ' WHERE mem.' . IliasEventoEventMembershipsTblDef::COL_EVENTO_EVENT_ID . ' = ' . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);

        $users = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $users[] = $row['evento_user_id'];// new IliasEventoUser($row['evento_user_id'], $row['ilias_user_id']);
        }

        return $users;
    }

    public function addMembershipIfNotExist(int $evento_event_id, int $user_id, int $role_type) : void
    {
        $query = "SELECT 1 FROM " . IliasEventoEventMembershipsTblDef::TABLE_NAME
            . " WHERE " . IliasEventoEventMembershipsTblDef::COL_EVENTO_EVENT_ID . " = " . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER)
            . " AND " . IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID . " = " . $this->db->quote($user_id, \ilDBConstants::T_INTEGER)
            . " LIMIT 1";
        $result = $this->db->query($query);

        $row = $this->db->fetchAssoc($result);
        if (is_null($row)) {
            $this->db->insert(
                IliasEventoEventMembershipsTblDef::TABLE_NAME,
                [
                    IliasEventoEventMembershipsTblDef::COL_EVENTO_EVENT_ID => [\ilDBConstants::T_INTEGER, $evento_event_id],
                    IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID => [\ilDBConstants::T_INTEGER, $user_id],
                    IliasEventoEventMembershipsTblDef::COL_ROLE_TYPE => [\ilDBConstants::T_INTEGER, $role_type]
                ]
            );
        }
    }

    public function checkIfUserHasMembershipInOtherSubEvent(int $parent_event_id, int $user_evento_id, int $excluding_evento_event_id) : bool
    {
        $q = "SELECT * "
            . " FROM " . IliasEventoEventsTblDef::TABLE_NAME . " AS event"
            . " JOIN " . IliasEventoEventMembershipsTblDef::TABLE_NAME . " AS mem ON mem." . IliasEventoEventMembershipsTblDef::COL_EVENTO_EVENT_ID . " = event." . IliasEventoEventsTblDef::COL_EVENTO_ID
            . " WHERE event." . IliasEventoEventsTblDef::COL_PARENT_EVENT_KEY . " = " . $this->db->quote($parent_event_id, \ilDBConstants::T_INTEGER)
            . " AND event." . IliasEventoEventsTblDef::COL_EVENTO_ID . " != " . $this->db->quote($excluding_evento_event_id, \ilDBConstants::T_INTEGER)
            . " AND mem." . IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID . " = " . $this->db->quote($user_evento_id, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($q);

        return $this->db->numRows($result) > 0;
    }

    public function removeMembershipIfItExists(int $evento_event_id, int $evento_user_id)
    {
        $query = "DELETE FROM " . IliasEventoEventMembershipsTblDef::TABLE_NAME
            . " WHERE " . IliasEventoEventMembershipsTblDef::COL_EVENTO_EVENT_ID . " = " . $this->db->quote($evento_event_id, \ilDBConstants::T_INTEGER)
            . " AND " . IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID . " = " . $this->db->quote($evento_user_id, \ilDBConstants::T_INTEGER);
        $this->db->manipulate($query);
    }
}

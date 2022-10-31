<?php declare(strict_types=1);

namespace EventoImport\config\local_roles;

use EventoImport\db\LocalVisitorRolesTblDef;

class LocalVisitorRoleRepository
{
    private \ilDBInterface $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function getAllVisitorRoles() : array
    {
        $query = "SELECT " . LocalVisitorRolesTblDef::COL_LOCAL_ROLE_ID . ', ' . LocalVisitorRolesTblDef::COL_LOCATION_REF_ID . ', ' . LocalVisitorRolesTblDef::COL_DEPARTMENT_LOCATION_NAME . ', ' . LocalVisitorRolesTblDef::COL_KIND_LOCATION_NAME .', ' . LocalVisitorRolesTblDef::COL_DEPARTMENT_API_NAME
            . " FROM " . LocalVisitorRolesTblDef::TABLE_NAME;

        $result = $this->db->query($query);

        $roles = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $roles[] = $this->buildObjectFromTableRow($row);
        }

        return $roles;
    }

    public function getVisitorRoleByDepartmentAndKind(string $department_name, string $kind_name) : ?LocalVisitorRole
    {
        $query = "SELECT " . LocalVisitorRolesTblDef::COL_LOCAL_ROLE_ID . ', ' . LocalVisitorRolesTblDef::COL_LOCATION_REF_ID . ', ' . LocalVisitorRolesTblDef::COL_DEPARTMENT_LOCATION_NAME . ', ' . LocalVisitorRolesTblDef::COL_KIND_LOCATION_NAME .', ' . LocalVisitorRolesTblDef::COL_DEPARTMENT_API_NAME
            . " FROM " . LocalVisitorRolesTblDef::TABLE_NAME
            . " WHERE " . LocalVisitorRolesTblDef::COL_DEPARTMENT_LOCATION_NAME . "=" . $this->db->quote($department_name, \ilDBConstants::T_TEXT)
            . " AND " . LocalVisitorRolesTblDef::COL_KIND_LOCATION_NAME . "=" . $this->db->quote($kind_name, \ilDBConstants::T_TEXT);

        $result = $this->db->query($query);

        $role = null;
        if ($row = $this->db->fetchAssoc($result)) {
            $role = $this->buildObjectFromTableRow($row);
        }

        return $role;
    }

    public function updateDepartmentApiName(LocalVisitorRole $role, string $dep_api_name)
    {
        $this->db->update(
            LocalVisitorRolesTblDef::TABLE_NAME,
            [
                LocalVisitorRolesTblDef::COL_DEPARTMENT_API_NAME => [\ilDBConstants::T_TEXT, $dep_api_name]
            ],
            [
                LocalVisitorRolesTblDef::COL_LOCAL_ROLE_ID => [\ilDBConstants::T_INTEGER, $role->getRoleId()]
            ]
        );
    }

    private function buildObjectFromTableRow($row) : LocalVisitorRole
    {
        global $DIC;
        return new LocalVisitorRole(
            $this->buildRoleObjectForRoleId((int) $row[LocalVisitorRolesTblDef::COL_LOCAL_ROLE_ID]),
            (int) $row[LocalVisitorRolesTblDef::COL_LOCATION_REF_ID],
            $row[LocalVisitorRolesTblDef::COL_DEPARTMENT_LOCATION_NAME],
            $row[LocalVisitorRolesTblDef::COL_KIND_LOCATION_NAME],
            $row[LocalVisitorRolesTblDef::COL_DEPARTMENT_API_NAME],
            $DIC->rbac()
        );
    }

    private function buildRoleObjectForRoleId(int $role_obj_id) : \ilObjRole
    {
        return new \ilObjRole($role_obj_id);
    }

    public function addNewVisitorRole(LocalVisitorRole $visitor_role)
    {
        $this->db->insert(
            LocalVisitorRolesTblDef::TABLE_NAME,
            [
                LocalVisitorRolesTblDef::COL_LOCAL_ROLE_ID => [\ilDBConstants::T_INTEGER, $visitor_role->getRoleId()],
                LocalVisitorRolesTblDef::COL_DEPARTMENT_LOCATION_NAME => [\ilDBConstants::T_TEXT, $visitor_role->getDepartmentLocationName()],
                LocalVisitorRolesTblDef::COL_KIND_LOCATION_NAME => [\ilDBConstants::T_TEXT, $visitor_role->getKindLocationName()],
                LocalVisitorRolesTblDef::COL_LOCATION_REF_ID => [\ilDBConstants::T_INTEGER, $visitor_role->getLocationRefId()],
                LocalVisitorRolesTblDef::COL_DEPARTMENT_API_NAME => [\ilDBConstants::T_TEXT, $visitor_role->getDepartmentApiName()]
            ]
        );
    }

    public function removeVisitorRole(LocalVisitorRole $visitor_role)
    {
        $sql = "DELETE FROM " . LocalVisitorRolesTblDef::TABLE_NAME . " WHERE " . LocalVisitorRolesTblDef::COL_LOCAL_ROLE_ID . "=" . $this->db->quote($visitor_role->getRoleId(), 'integer');
        $this->db->manipulate($sql);
    }
}
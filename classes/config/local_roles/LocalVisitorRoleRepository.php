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
        $query = "SELECT " . LocalVisitorRolesTblDef::COL_LOCAL_ROLE_ID . ', ' . LocalVisitorRolesTblDef::COL_LOCATION_REF_ID . ', ' . LocalVisitorRolesTblDef::COL_DEPARTMENT_API_NAME . ', ' . LocalVisitorRolesTblDef::COL_KIND
            . " FROM " . LocalVisitorRolesTblDef::TABLE_NAME;

        $result = $this->db->query($query);

        $roles = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $roles[] = $this->buildObjectFromTableRow($row);
        }

        return $roles;
    }

    private function buildObjectFromTableRow($row) : LocalVisitorRole
    {
        global $DIC;
        return new LocalVisitorRole(
            $this->buildRoleObjectForRoleId((int) $row[LocalVisitorRolesTblDef::COL_LOCAL_ROLE_ID]),
            (int) $row[LocalVisitorRolesTblDef::COL_LOCATION_REF_ID],
            $row[LocalVisitorRolesTblDef::COL_DEPARTMENT_API_NAME],
            $row[LocalVisitorRolesTblDef::COL_KIND],
            $DIC->rbac()
        );
    }

    private function buildRoleObjectForRoleId(int $role_obj_id) : \ilObjRole
    {
        return new \ilObjRole($role_obj_id);
    }
}
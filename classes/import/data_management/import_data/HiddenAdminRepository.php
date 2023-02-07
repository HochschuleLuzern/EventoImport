<?php declare(strict_types=1);

namespace EventoImport\import\data_management\repository;

use EventoImport\import\data_management\repository\model\IliasEventoEvent;
use EventoImport\db\HiddenAdminsTableDef;

class HiddenAdminRepository
{
    private \ilDBInterface $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function addNewIliasObjectWithHiddenAdmin(int $membership_obj_ref_id, int $role_obj_id)
    {
        $this->db->insert(
            HiddenAdminsTableDef::TABLE_NAME,
            [
                HiddenAdminsTableDef::COL_OBJECT_REF_ID => array(\ilDBConstants::T_INTEGER, $membership_obj_ref_id),
                HiddenAdminsTableDef::COL_HIDDEN_ADMIN_ROLE_ID => array(\ilDBConstants::T_INTEGER, $role_obj_id)
            ]
        );
    }

    public function getRoleIdForContainerRefId(int $membershipable_obj_ref_id) : ?int
    {
        $query = 'SELECT * FROM ' . HiddenAdminsTableDef::TABLE_NAME
            . ' WHERE ' . HiddenAdminsTableDef::COL_OBJECT_REF_ID . ' = ' . $this->db->quote(
                $membershipable_obj_ref_id,
                \ilDBConstants::T_INTEGER
            );
        $result = $this->db->query($query);

        if ($row = $this->db->fetchAssoc($result)) {
            return (int) $row[HiddenAdminsTableDef::COL_HIDDEN_ADMIN_ROLE_ID];
        }

        return null;
    }
}
<?php declare(strict_types=1);

namespace EventoImport\config\local_roles;

use ILIAS\DI\RBACServices;

class LocalVisitorRoleFactory
{
    public function __construct(RBACServices $rbac)
    {
        $this->rbac_review = $rbac->review();
        $this->rbac_admin = $rbac->admin();
    }

    public function buildLocalRole(string $role_name, string $role_description, int $ref_id) : \ilObjRole
    {
        $role_template_id = $this->getRoleTemplateId();

        $role = new \ilObjRole();
        $role->setTitle($role_name);
        $role->setDescription($role_description);
        $role->create();

        $this->rbac_admin->assignRoleToFolder($role->getId(), $ref_id);

        // protect
        $this->rbac_admin->setProtected(
            $ref_id,
            $role->getId(),
            'y'
        );

        // copy rights
        $parentRoles = $this->rbac_review->getParentRoleIds($ref_id, true);
        $this->rbac_admin->copyRoleTemplatePermissions(
            $role_template_id,
            $parentRoles[$role_template_id]["parent"],
            $ref_id,
            $role->getId(),
            false
        );

        $role->changeExistingObjects(
            $ref_id,
            \ilObjRole::MODE_PROTECTED_KEEP_LOCAL_POLICIES,
            ['all']
        );

        return $role;
    }

    private function getRoleTemplateId() : int
    {
        $rolt_id = null;
        $ids = \ilObject::_getIdsForTitle('il_crs_member', 'rolt');
        foreach ($ids as $id) {
            $rolt_id = (int) $id;
        }

        if (!is_null($rolt_id)) {
            return $rolt_id;
        }

        throw new \ilException("Error in finding the course member role template to base the visitor role on");
    }
}
<?php declare(strict_types=1);

namespace EventoImport\config\local_roles;

use ILIAS\DI\RBACServices;

class LocalVisitorRole
{
    private \ilObjRole $role;
    private int $location_ref_id;
    private string $department;
    private string $kind;
    private \ilRbacReview $rbac_review;
    private \ilRbacAdmin $rbac_admin;

    public function __construct(\ilObjRole $role, int $location_ref_id, string $department, string $kind, RBACServices $rbac)
    {
        $this->role = $role;
        $this->location_ref_id = $location_ref_id;
        $this->department = $department;
        $this->kind = $kind;
        $this->rbac_review = $rbac->review();
        $this->rbac_admin = $rbac->admin();
    }

    public function getLocationRefId() : int
    {
        return $this->location_ref_id;
    }

    public function getDepartment() : string
    {
        return $this->department;
    }

    public function getKind() : string
    {
        return $this->kind;
    }

    public function synchronizeWithIliasEventoUserList(array $ilias_evento_user_list)
    {
        $this->addNewUsersToRole($ilias_evento_user_list);
        $this->removeUsersNotInListFromRole($ilias_evento_user_list);
    }

    private function addNewUsersToRole(array $ilias_evento_user_list)
    {
        foreach ($ilias_evento_user_list as $ilias_evento_user) {
            $this->rbac_admin->assignUser($this->role->getId(), $ilias_evento_user->getIliasUserId());
        }
    }

    private function removeUsersNotInListFromRole(array $ilias_evento_user_list)
    {
        foreach ($this->rbac_review->assignedUsers($this->role->getId()) as $assigned_user) {
            if (!$this->isUserInEventoIliasUserList($assigned_user, $ilias_evento_user_list)) {
                $this->rbac_admin->deassignUser($this->role->getId(), $assigned_user);
            }
        }
    }

    private function isUserInEventoIliasUserList(int $user_id, array $ilias_evento_user_list) : bool
    {
        foreach ($ilias_evento_user_list as $ilias_evento_user) {
            if ($ilias_evento_user->getIliasUserId() == $user_id) {
                return true;
            }
        }

        return false;
    }

}
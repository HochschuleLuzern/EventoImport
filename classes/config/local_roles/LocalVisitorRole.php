<?php declare(strict_types=1);

namespace EventoImport\config\local_roles;

use ILIAS\DI\RBACServices;

class LocalVisitorRole
{
    private \ilObjRole $role;
    private int $location_ref_id;
    private string $department_location_name;
    private string $kind_location_name;
    private string $department_api_name;
    private \ilRbacReview $rbac_review;
    private \ilRbacAdmin $rbac_admin;

    public function __construct(\ilObjRole $role, int $location_ref_id, string $department_location_name, string $kind_location_name, string $department_api_name, RBACServices $rbac)
    {
        $this->role = $role;
        $this->location_ref_id = $location_ref_id;
        $this->department_location_name = $department_location_name;
        $this->kind_location_name = $kind_location_name;
        $this->department_api_name = $department_api_name;
        $this->rbac_review = $rbac->review();
        $this->rbac_admin = $rbac->admin();
    }

    public function getRoleId() : int
    {
        return $this->role->getId();
    }

    public function getLocationRefId() : int
    {
        return $this->location_ref_id;
    }

    public function getDepartmentLocationName() : string
    {
        return $this->department_location_name;
    }

    public function getKindLocationName() : string
    {
        return $this->kind_location_name;
    }

    public function getDepartmentApiName() : string
    {
        return $this->department_api_name;
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
            if (!$this->isUserInEventoIliasUserList((int) $assigned_user, $ilias_evento_user_list)) {
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
<?php declare(strict_types=1);

namespace EventoImport\config\local_roles;

use ILIAS\DI\RBACServices;

class LocalVisitorRoleManager
{
    private LocalVisitorRoleRepository $local_role_repo;
    private LocalVisitorRoleFactory $factory;
    private \ilRbacAdmin $rbac_admin;
    private \ilRbacReview $rbac_review;
    private RBACServices $rbac;

    public function __construct(LocalVisitorRoleRepository $local_role_repo, LocalVisitorRoleFactory $factory, RBACServices $rbac)
    {
        $this->local_role_repo = $local_role_repo;
        $this->factory = $factory;
        $this->rbac = $rbac;
    }

    public function getLocalVisitorRoleByDepartmentAndKind(string $department_name, string $kind_name) : ?LocalVisitorRole
    {
        return $this->local_role_repo->getVisitorRoleByDepartmentAndKind($department_name, $kind_name);
    }

    public function configLocalRoleByDepartmentAndKind(string $department_name, string $kind_name, int $location_ref_id, string $dep_api_name)
    {
        $role = $this->local_role_repo->getVisitorRoleByDepartmentAndKind($department_name, $kind_name);
        if (!is_null($role)) {
            $this->local_role_repo->updateDepartmentApiName($role, $dep_api_name);
        } else {
            $this->createNewLocalVisitorRole($department_name, $kind_name, $location_ref_id, $dep_api_name);
        }
    }

    public function unconfigLocalRoleByDepartmentAndKind(string $department_name, string $kind_name, int $location_ref_id)
    {
        $role = $this->local_role_repo->getVisitorRoleByDepartmentAndKind($department_name, $kind_name);
        if (!is_null($role)) {
            $this->removeLocalVisitorRole($role);
            $this->local_role_repo->removeVisitorRole($role);
        }
    }

    private function createNewLocalVisitorRole(string $department_name, string $kind_name, int $location_ref_id, string $dep_api_name)
    {
        $title = "$department_name $kind_name Visitor";
        $description = "Visitor Role for $department_name / $kind_name";
        $role = $this->factory->buildLocalRole($title, $description, $location_ref_id);

        $this->local_role_repo->addNewVisitorRole(
            new LocalVisitorRole(
                $role,
                $location_ref_id,
                $department_name,
                $kind_name,
                $dep_api_name,
                $this->rbac
            )
        );
    }

    private function removeLocalVisitorRole(LocalVisitorRole $visitor_role)
    {
        $ilias_role_obj = new \ilObjRole($visitor_role->getRoleId());
        $ilias_role_obj->setParent($visitor_role->getLocationRefId());
        $ilias_role_obj->delete();
    }
}
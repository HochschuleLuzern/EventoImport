<?php

namespace EventoImport\import;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;
use ILIAS\DI\RBACServices;

abstract class IliasEventWrapper
{
    protected $rbac_admin;
    protected $rbac_review;

    protected function __construct(RBACServices $rbac_services = null)
    {
        global $DIC;

        $rbac_services = $rbac_services ?? $DIC->rbac();
        $this->rbac_admin = $rbac_services->admin();
        $this->rbac_review = $rbac_services->review();
    }

    abstract public function getIliasEventoEventObj() : IliasEventoEvent;

    protected function addUserToGivenRole(int $user_id, int $role_id)
    {
        if (!$this->rbac_review->isAssigned($user_id, $role_id)) {
            $this->rbac_admin->assignUser($role_id, $user_id);
        }
    }

    abstract public function addUserAsAdminToEvent(int $user_id);
    abstract public function addUserAsStudentToEvent(int $user_id);

    abstract public function getAllAdminRoles() : array;
    abstract public function getAllMemberRoles() : array;
}

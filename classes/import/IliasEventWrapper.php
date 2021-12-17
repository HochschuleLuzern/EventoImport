<?php

namespace EventoImport\import;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;
use ILIAS\DI\RBACServices;

/**
 * Class IliasEventWrapper
 * @package EventoImport\import
 */
abstract class IliasEventWrapper
{
    /** @var \ilRbacAdmin */
    protected \ilRbacAdmin $rbac_admin;

    /** @var \ilRbacReview */
    protected \ilRbacReview $rbac_review;

    /**
     * IliasEventWrapper constructor.
     * @param RBACServices|null $rbac_services
     */
    protected function __construct(RBACServices $rbac_services = null)
    {
        global $DIC;

        $rbac_services = $rbac_services ?? $DIC->rbac();
        $this->rbac_admin = $rbac_services->admin();
        $this->rbac_review = $rbac_services->review();
    }

    /**
     * @return IliasEventoEvent
     */
    abstract public function getIliasEventoEventObj() : IliasEventoEvent;

    /**
     * @param int $user_id
     * @param int $role_id
     */
    protected function addUserToGivenRole(int $user_id, int $role_id)
    {
        if (!$this->rbac_review->isAssigned($user_id, $role_id)) {
            $this->rbac_admin->assignUser($role_id, $user_id);
        }
    }

    /**
     * @param int $user_id
     */
    abstract public function addUserAsAdminToEvent(int $user_id) : void;

    /**
     * @param int $user_id
     */
    abstract public function addUserAsStudentToEvent(int $user_id) : void;

    /**
     * @return array
     */
    abstract public function getAllAdminRoles() : array;

    /**
     * @return array
     */
    abstract public function getAllMemberRoles() : array;
}

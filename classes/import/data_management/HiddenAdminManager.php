<?php declare(strict_types=1);

namespace EventoImport\import\data_management;

use EventoImport\import\data_management\repository\HiddenAdminRepository;
use ILIAS\DI\RBACServices;
use EventoImport\import\data_management\ilias_core\MembershipablesEventInTreeSeeker;

class HiddenAdminManager
{
    private const ROLE_DESCRIPTION = "Hidden admin of ref_id = ";
    private HiddenAdminRepository $hidden_admin_repo;

    private const ROLE_TITLE_CRS = "Versteckte Kursadministration";
    private const ROLE_TITLE_GRP = "Versteckte Gruppenadministration";
    private UserManager $user_manager;
    private \ilRbacReview $rbac_review;

    public function __construct(HiddenAdminRepository $hidden_admin_repo, MembershipablesEventInTreeSeeker $membershipables_seeker, UserManager $user_manager, RBACServices $rbac_services)
    {
        $this->hidden_admin_repo = $hidden_admin_repo;
        $this->membershipables_seeker = $membershipables_seeker;
        $this->user_manager = $user_manager;
        $this->rbac_review = $rbac_services->review();
        $this->rbac_admin = $rbac_services->admin();
    }

    private function getHiddenRoleAdminIdOrCreateIfNotExists(int $ref_id)
    {
        $hidden_role_id = $this->hidden_admin_repo->getRoleIdForContainerRefId($ref_id);
        if (is_null($hidden_role_id)) {
            $hidden_role_id = $this->createHiddenRoleForContainerRefId($ref_id);
        }

        return $hidden_role_id;
    }

    public function synchronizeEventAdmins(
        \EventoImport\communication\api_models\EventoEventIliasAdmins $event_admin_list,
        repository\model\IliasEventoEvent $ilias_evento_event
    ) {
        $ilias_user_list = $this->getIliasUserListForEventAdminsList($event_admin_list);
        $hidden_role_id = $this->getHiddenRoleAdminIdOrCreateIfNotExists($ilias_evento_event->getRefId());

        $users_to_delete = $this->checkForUsersToRemoveFromRole($hidden_role_id, $ilias_user_list);
        $parent_membershipables = $this->membershipables_seeker->getRefIdsOfParentMembershipables($ilias_evento_event->getRefId());
        $this->assignUserListToRole($hidden_role_id, $ilias_user_list);
        if (count($users_to_delete) > 0) {
            $this->removeUserListFromRole($hidden_role_id, $users_to_delete);
            $this->addAndRemoveUsersFromParentMembershipables($ilias_user_list, $users_to_delete, $parent_membershipables);
        } else {
            $this->addUsersToParentMembershipables($ilias_user_list, $parent_membershipables);
        }
    }

    private function createHiddenRoleForContainerRefId(int $ref_id) : int
    {
        $type = \ilObject::_lookupType($ref_id, true);

        if($type == 'crs') {
            $title = self::ROLE_TITLE_CRS;
            $role_template = 'il_crs_admin';
        } else if($type == 'grp') {
            $title = self::ROLE_TITLE_GRP;
            $role_template = 'il_grp_admin';
        } else {
            throw new \InvalidArgumentException("Given ref_id to create hidden role is neither a course nor a group obj. ID = " . $ref_id);
        }

        $role_obj = \ilObjRole::createDefaultRole(
            $title,
            self::ROLE_DESCRIPTION . $ref_id,
            $role_template,
            $ref_id
        );

        $this->hidden_admin_repo->addNewIliasObjectWithHiddenAdmin($ref_id, $role_obj->getId());
        return $role_obj->getId();
    }

    private function checkForUsersToRemoveFromRole(
        ?int $hidden_role_id,
        array $ilias_user_list
    ) : array
    {
        $not_delivered_admins = [];

        foreach ($this->rbac_review->assignedUsers($hidden_role_id) as $role_member) {
            if (!in_array($role_member, $ilias_user_list)) {
                $not_delivered_admins[] = $role_member;
            }
        }

        return $not_delivered_admins;
    }

    private function getIliasUserListForEventAdminsList(
        \EventoImport\communication\api_models\EventoEventIliasAdmins $event_admin_list
    ) {
        $user_list = [];
        foreach ($event_admin_list->getAccountList() as $evento_ilias_user) {
            $user_id = $this->user_manager->getIliasUserIdByEventoUserShort($evento_ilias_user);
            if (!is_null($user_id)) {
                $user_list[] = $user_id;
            }
        }

        return $user_list;
    }

    private function assignUserListToRole(int $hidden_role_id, array $ilias_user_list)
    {
        foreach ($ilias_user_list as $ilias_user_id) {
            if (!$this->rbac_review->isAssigned($ilias_user_id, $hidden_role_id)) {
                $this->rbac_admin->assignUser($hidden_role_id, $ilias_user_id);
            }
        }
    }

    private function removeUserListFromRole(int $hidden_role_id, array $users_to_delete)
    {
        foreach ($users_to_delete as $ilias_user_id) {
            if ($this->rbac_review->isAssigned($ilias_user_id, $hidden_role_id)) {
                $this->rbac_admin->deassignUser($hidden_role_id, $ilias_user_id);
            }
        }
    }

    private function addUsersToParentMembershipables(array $ilias_user_list, array $parent_membershipable)
    {
        foreach ($parent_membershipable as $parent_ref_id) {
            $hidden_role_id = $this->getHiddenRoleAdminIdOrCreateIfNotExists($parent_ref_id);
            $this->assignUserListToRole($hidden_role_id, $ilias_user_list);
        }
    }

    private function addAndRemoveUsersFromParentMembershipables(
        array $ilias_user_list,
        array $users_to_delete,
        $parent_membershipable
    ) {

        foreach ($parent_membershipable as $parent_ref_id) {
            $co_groups = $this->membershipables_seeker->getMembershipableCoGroups($parent_ref_id);
            $hidden_role_id = $this->getHiddenRoleAdminIdOrCreateIfNotExists($parent_ref_id);

            // Add users
            $this->assignUserListToRole($hidden_role_id, $ilias_user_list);

            // Remove users which are note in any co-membershipable hidden role
            $users_to_delete = $this->filterUsersToDeleteListFromCoMembershipableMembers($users_to_delete, $co_groups);
            $this->removeUserListFromRole($hidden_role_id, $users_to_delete);
        }
    }

    private function filterUsersToDeleteListFromCoMembershipableMembers(array $users_to_delete, array $co_membershipables) : array
    {
        $assigned_to_co_membershipable = [];
        foreach ($users_to_delete as $array_key => $user_to_delete_id) {
            $is_assigned_to_co_group = false;
            foreach ($co_membershipables as $co_group_ref_id) {
                $co_group_hidden_role_id = $this->hidden_admin_repo->getRoleIdForContainerRefId($co_group_ref_id);
                if (!is_null($co_group_hidden_role_id) && $this->rbac_review->isAssigned($user_to_delete_id, $co_group_hidden_role_id)) {
                    $is_assigned_to_co_group = true;
                }
            }

            if ($is_assigned_to_co_group) {
                $assigned_to_co_membershipable[$array_key] = $users_to_delete;
            }
        }

        foreach ($assigned_to_co_membershipable as $array_key => $assigned_user) {
            unset($users_to_delete[$array_key]);
        }

        return $users_to_delete;
    }
}
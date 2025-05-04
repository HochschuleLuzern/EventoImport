<?php declare(strict_types = 1);

namespace EventoImport\import\data_management;

use EventoImport\import\data_management\repository\IliasEventoEventMembershipRepository;
use ILIAS\DI\RBACServices;
use EventoImport\communication\api_models\EventoEvent;
use EventoImport\communication\api_models\EventoUserShort;
use EventoImport\import\data_management\repository\model\IliasEventoEvent;
use EventoImport\import\data_management\repository\model\IliasEventoUser;
use EventoImport\import\data_management\ilias_core\MembershipablesEventInTreeSeeker;
use EventoImport\communication\api_models\EventoEventIliasAdmins;
use EventoImport\import\Logger;

class MembershipManager
{
    private UserManager $user_manager;
    private IliasEventoEventMembershipRepository $membership_repo;
    private \ilFavouritesManager $favourites_manager;
    private Logger $logger;
    private \ilRbacReview $rbac_review;
    private \ilRbacAdmin $rbac_admin;
    private \DateTimeImmutable $now;

    private MembershipablesEventInTreeSeeker $tree_seeker;
    /** @var \ilParticipants[]  */
    private array $participant_object_cache;

    public function __construct(
        MembershipablesEventInTreeSeeker $tree_seeker,
        IliasEventoEventMembershipRepository $membership_repo,
        UserManager $user_manager,
        \ilFavouritesManager $favourites_manager,
        Logger $logger,
        RBACServices $rbac_services,
        \DateTimeImmutable $now = null
    ) {
        $this->membership_repo = $membership_repo;
        $this->tree_seeker = $tree_seeker;
        $this->favourites_manager = $favourites_manager;
        $this->logger = $logger;
        $this->rbac_review = $rbac_services->review();
        $this->rbac_admin = $rbac_services->admin();
        $this->participant_object_cache = [];

        $this->user_manager = $user_manager;

        $this->now = $now ?? new \DateTimeImmutable();
    }

    public function syncMemberships(EventoEvent $imported_event, IliasEventoEvent $ilias_event) : void
    {
        $delete_not_delivered_members = true;
        if (is_null($imported_event->getEndDate()) || $imported_event->getEndDate() <= $this->now) {
            $delete_not_delivered_members = false;
        }

        // If Ilias Event is already a course -> no need to find parent membershipables
        if ($ilias_event->getIliasType() == 'crs') {
            $this->syncMembershipsWithoutParentObjects($imported_event, $ilias_event, $delete_not_delivered_members);
            return;
        }

        // Else -> search for parent membershipable objects
        $parent_events = $this->tree_seeker->getRefIdsOfParentMembershipables($ilias_event->getRefId());

        // Check if any parent membershipables were found
        if (count($parent_events) > 0) {
            $this->syncMembershipsWithParentObjects($imported_event, $ilias_event, $parent_events, $delete_not_delivered_members);
            return;
        }

        $this->syncMembershipsWithoutParentObjects($imported_event, $ilias_event, $delete_not_delivered_members);
    }

    private function addUsersToMembershipableObject(
        \ilParticipants $participants_object,
        EventoEvent $evento_event,
        int $admin_role_code,
        int $student_role_code,
        int $membershipable_ref_id
    ) : void
    {
        /** @var EventoUserShort $employee */
        foreach ($evento_event->getEmployees() as $employee) {
            $employee_user_id = $this->user_manager->getIliasUserIdByEventoUserShort($employee);
            if (is_null($employee_user_id)) {
                continue;
            }

            $log_info_code = Logger::CREVENTO_SUB_ALREADY_ASSIGNED;
            if (!$participants_object->isAssigned($employee_user_id)) {
                $participants_object->add($employee_user_id, $admin_role_code);
                $log_info_code = Logger::CREVENTO_SUB_NEWLY_ADDED;
                \ilForumNotification::checkForumsExistsInsert($membershipable_ref_id, $employee_user_id);
            }

            $this->logger->logEventMembership($log_info_code, $evento_event->getEventoId(), $employee->getEventoId(), $admin_role_code);
            $this->membership_repo->addMembershipIfNotExist($evento_event->getEventoId(), $employee->getEventoId(), $admin_role_code);
        }

        /** @var EventoUserShort $student */
        foreach ($evento_event->getStudents() as $student) {
            $student_user_id = $this->user_manager->getIliasUserIdByEventoUserShort($student);
            if (is_null($student_user_id)) {
                continue;
            }

            $log_info_code = Logger::CREVENTO_SUB_ALREADY_ASSIGNED;
            if (!$participants_object->isAssigned($student_user_id)) {
                $participants_object->add($student_user_id, $student_role_code);
                $log_info_code = Logger::CREVENTO_SUB_NEWLY_ADDED;
                \ilForumNotification::checkForumsExistsInsert($membershipable_ref_id, $student_user_id);
            }

            $this->logger->logEventMembership($log_info_code, $evento_event->getEventoId(), $student->getEventoId(), $student_role_code);
            $this->membership_repo->addMembershipIfNotExist($evento_event->getEventoId(), $student->getEventoId(), $student_role_code);
        }
    }

    private function getUsersToRemove(EventoEvent $imported_event) : array
    {
        $from_import_subscribed_members = $this->membership_repo->fetchIliasEventoUserIdsForEvent($imported_event->getEventoId());

        $user_ids_to_remove = [];

        foreach ($from_import_subscribed_members as $member_id) {
            if ($this->isUserInCurrentImport((int) $member_id, $imported_event)) {
                continue;
            }

            $ilias_evento_user = $this->user_manager->getIliasEventoUserByEventoId((int) $member_id);
            if (!is_null($ilias_evento_user)) {
                $user_ids_to_remove[] = $ilias_evento_user;
            }
        }

        return $user_ids_to_remove;
    }

    private function syncMembershipsWithoutParentObjects(EventoEvent $imported_event, IliasEventoEvent $ilias_event, bool $delete_not_delivered_members) : void
    {
        $participants_obj = $this->getParticipantsObjectForRefId($ilias_event->getRefId());

        $admin_role_code = $ilias_event->getIliasType() == 'crs' ? \ilParticipants::IL_CRS_ADMIN : \ilParticipants::IL_GRP_ADMIN;
        $member_role_code = $ilias_event->getIliasType() == 'crs' ? \ilParticipants::IL_CRS_MEMBER : \ilParticipants::IL_GRP_MEMBER;

        $this->addUsersToMembershipableObject($participants_obj, $imported_event, $admin_role_code, $member_role_code, $ilias_event->getRefId());

        // TODO: Refactor. This was just a quick fix to stop removing members from events which reached their end date
        if (!$delete_not_delivered_members) {
            return;
        }

        // Remove from event and sub events
        $sub_membershipable_objs = $this->tree_seeker->getAllSubGroups($ilias_event->getRefId());
        /** @var IliasEventoUser $user_to_remove */
        foreach ($this->getUsersToRemove($imported_event) as $user_to_remove) {
            if ($participants_obj->isAssigned($user_to_remove->getIliasUserId())) {
                $participants_obj->delete($user_to_remove->getIliasUserId());
                $this->logger->logEventMembership(
                    Logger::CREVENTO_SUB_REMOVED,
                    $imported_event->getEventoId(),
                    $user_to_remove->getEventoUserId()
                );
            } else {
                $this->logger->logEventMembership(
                    Logger::CREVENTO_SUB_ALREADY_DEASSIGNED,
                    $imported_event->getEventoId(),
                    $user_to_remove->getEventoUserId()
                );
            }
            $this->membership_repo->removeMembershipIfItExists($imported_event->getEventoId(), $user_to_remove->getEventoUserId());

            $this->removeUserFromSubMembershipables($user_to_remove, $sub_membershipable_objs);
        }
    }

    private function syncMembershipsWithParentObjects(EventoEvent $imported_event, IliasEventoEvent $ilias_event, array $parent_events, bool $delete_not_delivered_members) : void
    {
        // Add users to main event
        $participants_obj_of_event = $this->getParticipantsObjectForRefId($ilias_event->getRefId());

        $this->addUsersToMembershipableObject(
        $participants_obj_of_event,
        $imported_event,
        \ilParticipants::IL_GRP_ADMIN,
        \ilParticipants::IL_GRP_MEMBER,
        $ilias_event->getRefId()
        );

        // Add users to all parent membershipable objects
        foreach ($parent_events as $parent_event) {
            $participants_obj_of_parent = $this->getParticipantsObjectForRefId($parent_event);

            if ($participants_obj_of_parent instanceof \ilCourseParticipants) {
                $this->addUsersToMembershipableObject(
                    $participants_obj_of_parent,
                    $imported_event,
                    \ilParticipants::IL_CRS_ADMIN,
                    \ilParticipants::IL_CRS_MEMBER,
                    $parent_event
                );
                continue;
            }

            if ($participants_obj_of_parent instanceof \ilGroupParticipants) {
                $this->addUsersToMembershipableObject(
                    $participants_obj_of_parent,
                    $imported_event,
                    \ilParticipants::IL_GRP_ADMIN,
                    \ilParticipants::IL_GRP_MEMBER,
                    $parent_event
                );
            }
        }

        // TODO: Refactor. This was just a quick fix to stop removing members from events which reached their end date
        if (!$delete_not_delivered_members) {
            return;
        }

        $users_to_remove = $this->getUsersToRemove($imported_event);

        $sub_membershipable_objs = $this->tree_seeker->getAllSubGroups($ilias_event->getRefId());

        /** @var IliasEventoUser $user_to_remove */
        foreach ($users_to_remove as $user_to_remove) {

            // Remove from main event
            if ($participants_obj_of_event->isAssigned($user_to_remove->getIliasUserId())) {
                $participants_obj_of_event->delete($user_to_remove->getIliasUserId());
                $this->logger->logEventMembership(Logger::CREVENTO_SUB_REMOVED, $imported_event->getEventoId(), $user_to_remove->getEventoUserId());
            } else {
                $this->logger->logEventMembership(
                    Logger::CREVENTO_SUB_ALREADY_DEASSIGNED,
                    $imported_event->getEventoId(),
                    $user_to_remove->getEventoUserId()
                );
            }
            $this->membership_repo->removeMembershipIfItExists($imported_event->getEventoId(), $user_to_remove->getEventoUserId());

            // Remove from sub events
            $this->removeUserFromSubMembershipables($user_to_remove, $sub_membershipable_objs);

            // For each parent event -> remove user if it is not in a co-membershipable
            foreach ($parent_events as $parent_event) {
                $co_membershipables = $this->tree_seeker->getMembershipableCoGroups($ilias_event->getRefId());
                $is_in_co_membershipable = false;
                foreach ($co_membershipables as $co_membershipable) {
                    if ($co_membershipable == $ilias_event->getRefId()) {
                        continue;
                    }

                    $co_particpants_list = $this->getParticipantsObjectForRefId($co_membershipable);
                    if ($co_particpants_list->isAssigned($user_to_remove->getIliasUserId())) {
                        $is_in_co_membershipable = true;
                    }
                }

                if (!$is_in_co_membershipable) {
                    $sub_event_participants_obj = $this->getParticipantsObjectForRefId($parent_event);
                    if ($sub_event_participants_obj->isAssigned($user_to_remove->getIliasUserId())) {
                        $sub_event_participants_obj->delete($user_to_remove->getIliasUserId());
                    }
                }
            }
        }
    }

    private function removeUserFromSubMembershipables(IliasEventoUser $user_to_remove, array $sub_membershipables) : void
    {
        foreach ($sub_membershipables as $sub_membershipable) {
            $sub_event_participants_obj = $this->getParticipantsObjectForRefId($sub_membershipable);
            if ($sub_event_participants_obj->isAssigned($user_to_remove->getIliasUserId())) {
                $sub_event_participants_obj->delete($user_to_remove->getIliasUserId());
            }
        }
    }

    private function isUserInCurrentImport(int $user_evento_id, EventoEvent $imported_event) : bool
    {
        foreach ($imported_event->getEmployees() as $evento_user) {
            if ($evento_user instanceof EventoUserShort && $user_evento_id == $evento_user->getEventoId()) {
                return true;
            }
        }

        foreach ($imported_event->getStudents() as $evento_user) {
            if ($evento_user instanceof EventoUserShort && $user_evento_id == $evento_user->getEventoId()) {
                return true;
            }
        }

        return false;
    }

    public function addEventAdmins(EventoEventIliasAdmins $event_admin_list, IliasEventoEvent $ilias_evento_event) : void
    {
        $event_participant_obj = $this->getParticipantsObjectForRefId($ilias_evento_event->getRefId());
        $this->addAdminListToObject(
            $event_participant_obj,
            $event_admin_list->getAccountList(),
            $ilias_evento_event->getIliasType() == 'crs' ? \ilParticipants::IL_CRS_ADMIN : \ilParticipants::IL_GRP_ADMIN
        );

        $parent_membershipables = $this->tree_seeker->getRefIdsOfParentMembershipables($ilias_evento_event->getRefId());
        foreach ($parent_membershipables as $parent_membershipable) {
            $participants_obj = $this->getParticipantsObjectForRefId($parent_membershipable);

            if ($participants_obj instanceof \ilCourseParticipants) {
                $this->addAdminListToObject(
                    $participants_obj,
                    $event_admin_list->getAccountList(),
                    \ilParticipants::IL_CRS_ADMIN,
                );
                continue;
            }

            if ($participants_obj instanceof \ilGroupParticipants) {
                $this->addAdminListToObject(
                    $participants_obj,
                    $event_admin_list->getAccountList(),
                    \ilParticipants::IL_GRP_ADMIN,
                );
            }
        }
    }

    private function addAdminListToObject(\ilParticipants $participants_object, array $admin_list, int $admin_role_code) : void
    {
        foreach ($admin_list as $admin) {
            $employee_user_id = $this->user_manager->getIliasUserIdByEventoId($admin->getEventoId());
            if (!is_null($employee_user_id) && !$participants_object->isAssigned($employee_user_id)) {
                $participants_object->add($employee_user_id, $admin_role_code);
            }
        }
    }

    private function getParticipantsObjectForRefId(int $ref_id) : \ilParticipants
    {
        if (!isset($this->participant_object_cache[$ref_id])) {
            $this->participant_object_cache[$ref_id] = \ilParticipants::getInstance($ref_id);
        }

        return $this->participant_object_cache[$ref_id];
    }

    public function removeEventoIliasMembershipConnectionsForEvent(IliasEventoEvent $ilias_event)
    {
        $this->membership_repo->removeAllMembershipsForEventoId($ilias_event->getEventoEventId());
    }
}

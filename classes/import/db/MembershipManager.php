<?php

namespace EventoImport\import\db;

use EventoImport\import\db\repository\EventMembershipRepository;
use ILIAS\DI\RBACServices;
use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\repository\EventoUserRepository;
use EventoImport\communication\api_models\EventoUserShort;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\repository\IliasEventoEventsRepository;
use EventoImport\import\db\model\IliasEventoUser;
use phpDocumentor\Reflection\Types\Self_;
use EventoImport\import\db\query\MembershipableObjectsQuery;

/**
 * Class MembershipManager
 * @package EventoImport\import\db
 */
class MembershipManager
{
    /**
     * @var EventMembershipRepository
     */
    private $membership_repo;
    private $favourites_manager;
    private $logger;

    private const ROLE_ADMIN = 1;
    private const ROLE_MEMBER = 2;

    /**
     * MembershipManager constructor.
     * @param EventMembershipRepository   $membership_repo
     * @param EventoUserRepository        $user_repo
     * @param IliasEventoEventsRepository $event_repo
     * @param \ilFavouritesManager        $favourites_manager
     * @param \ilEventoImportLogger       $logger
     * @param RBACServices                $rbac_services
     */
    public function __construct(
        EventMembershipRepository $membership_repo,
        EventoUserRepository $user_repo,
        IliasEventoEventsRepository $event_repo,
        \ilFavouritesManager $favourites_manager,
        \ilEventoImportLogger $logger,
        RBACServices $rbac_services
    ) {
        $this->membership_repo = $membership_repo;
        $this->membership_query = new MembershipableObjectsQuery();
        $this->user_repo = $user_repo;
        $this->event_repo = $event_repo;
        $this->favourites_manager = $favourites_manager;
        $this->logger = $logger;
        $this->rbac_review = $rbac_services->review();
        $this->rbac_admin = $rbac_services->admin();
    }

    /**
     * @param EventoEvent      $imported_event
     * @param IliasEventoEvent $ilias_event
     * @throws \ilException
     */
    public function syncMemberships(EventoEvent $imported_event, IliasEventoEvent $ilias_event)
    {
        // If Ilias Event is already a course -> no need to find parent membershipables
        if ($ilias_event->getIliasType() == 'crs') {
            $this->syncMembershipsWithoutParentObjects($imported_event, $ilias_event);
        } else {

            // Else -> search for parent membershipable objects
            $parent_events = $this->membership_query->getRefIdsOfParentMembershipables($ilias_event->getRefId());

            // Check if any parent membershipables were found
            if (count($parent_events) > 0) {
                $this->syncMembershipsWithParentObjects($imported_event, $ilias_event, $parent_events);
            } else {
                $this->syncMembershipsWithoutParentObjects($imported_event, $ilias_event);
            }
        }
    }

    /**
     * @param \ilParticipants $participants_object
     * @param array           $employees
     * @param int             $admin_role_code
     * @param array           $students
     * @param int             $student_role_code
     */
    private function addUsersToMembershipableObject(\ilParticipants $participants_object, array $employees, int $admin_role_code, array $students, int $student_role_code)
    {
        foreach ($employees as $employee) {
            $employee_user_id = $this->user_repo->getIliasUserIdByEventoId($employee->getEventoId());
            if (!$participants_object->isAssigned($employee_user_id)) {
                $participants_object->add($employee_user_id, $admin_role_code);
            }
        }

        foreach ($students as $student) {
            $student_user_id = $this->user_repo->getIliasUserIdByEventoId($student->getEventoId());
            if (!$participants_object->isAssigned($student_user_id)) {
                $participants_object->add($student_user_id, $student_role_code);
            }
        }
    }

    private function getUsersToRemove(EventoEvent $imported_event) : array
    {
        $from_import_subscribed_members = $this->membership_repo->fetchIliasEventoUserForEvent($imported_event->getEventoId());
        $users_to_remove = [];
        /** @var IliasEventoUser $member */
        foreach ($from_import_subscribed_members as $member) {
            if (!$this->isUserInCurrentImport($member, $imported_event)) {
                $users_to_remove[] = $member;
            }
        }
    }

    private function syncMembershipsWithoutParentObjects(EventoEvent $imported_event, IliasEventoEvent $ilias_event)
    {
        $participants_obj = $this->membership_query->getParticipantsObjectForRefId($ilias_event->getRefId());

        $admin_role_code = $ilias_event->getIliasType() == 'crs' ? IL_CRS_ADMIN : IL_GRP_ADMIN;
        $member_role_code = $ilias_event->getIliasType() == 'crs' ? IL_CRS_MEMBER : IL_GRP_MEMBER;

        $this->addUsersToMembershipableObject(
            $participants_obj,
            $imported_event->getEmployees(),
            $admin_role_code,
            $imported_event->getStudents(),
            $member_role_code
        );


        $users_to_remove = $this->getUsersToRemove($imported_event);

        // Remove from event and sub events
        $sub_events = $this->membership_query->getAllSubGroups($ilias_event->getRefId());
        /** @var IliasEventoUser $user_to_remove */
        foreach ($users_to_remove as $user_to_remove) {
            if ($participants_obj->isAssigned($user_to_remove->getIliasUserId())) {
                $participants_obj->delete($user_to_remove->getIliasUserId());
            }

            foreach ($sub_events as $sub_event) {
                $sub_event_participants_obj = $this->membership_query->getParticipantsObjectForRefId($sub_event);
                if ($sub_event_participants_obj->isAssigned($user_to_remove->getIliasUserId())) {
                    $sub_event_participants_obj->delete($user_to_remove->getIliasUserId());
                }
            }
        }
    }

    private function syncMembershipsWithParentObjects(EventoEvent $imported_event, IliasEventoEvent $ilias_event, array $parent_events)
    {
        // Add users to main event
        $participants_obj = $this->membership_query->getParticipantsObjectForRefId($ilias_event->getRefId());
        $this->addUsersToMembershipableObject(
            $participants_obj,
            $imported_event->getEmployees(),
            IL_GRP_ADMIN,
            $imported_event->getStudents(),
            IL_GRP_MEMBER
        );

        // Add users to all parent membershipable objects
        foreach ($parent_events as $parent_event) {
            $participants_obj = $this->membership_query->getParticipantsObjectForRefId($parent_event);

            if ($participants_obj instanceof \ilCourseParticipants) {
                $this->addUsersToMembershipableObject(
                    $participants_obj,
                    $imported_event->getEmployees(),
                    IL_CRS_ADMIN,
                    $imported_event->getStudents(),
                    IL_CRS_MEMBER
                );
            } elseif ($participants_obj instanceof \ilGroupParticipants) {
                $this->addUsersToMembershipableObject(
                    $participants_obj,
                    $imported_event->getEmployees(),
                    IL_GRP_ADMIN,
                    $imported_event->getStudents(),
                    IL_GRP_MEMBER
                );
            }
        }

        $users_to_remove = $this->getUsersToRemove($imported_event);

        $sub_events = $this->membership_query->getAllSubGroups($ilias_event->getRefId());

        /** @var IliasEventoUser $user_to_remove */
        foreach ($users_to_remove as $user_to_remove) {

            // Remove from main event
            if ($participants_obj->isAssigned($user_to_remove->getIliasUserId())) {
                $participants_obj->delete($user_to_remove->getIliasUserId());
            }

            // Remove from sub events
            foreach ($sub_events as $sub_event) {
                $sub_event_participants_obj = $this->membership_query->getParticipantsObjectForRefId($sub_event);
                if ($sub_event_participants_obj->isAssigned($user_to_remove->getIliasUserId())) {
                    $sub_event_participants_obj->delete($user_to_remove->getIliasUserId());
                }
            }

            // For each parent event -> remove user if it is not in a co-membershipable
            foreach ($parent_events as $parent_event) {
                $co_membershipables = $this->membership_query->getMembershipableCoGroups($ilias_event->getRefId());
                $is_in_co_membershipable = false;
                foreach ($co_membershipables as $co_membershipable) {
                    $co_particpants_list = $this->membership_query->getParticipantsObjectForRefId($co_membershipable);
                    if ($co_particpants_list->isAssigned($user_to_remove)) {
                        $is_in_co_membershipable = true;
                    }
                }

                if (!$is_in_co_membershipable) {
                    $sub_event_participants_obj = $this->membership_query->getParticipantsObjectForRefId($parent_event);
                    if ($sub_event_participants_obj->isAssigned($user_to_remove->getIliasUserId())) {
                        $sub_event_participants_obj->delete($user_to_remove->getIliasUserId());
                    }
                }
            }
        }
    }

    /**
     * @param \ilObject $parent_membership_object
     * @param int       $role_type
     * @return int|null
     */
    private function getRoleIdForObjectOrNull(\ilObject $parent_membership_object, int $role_type) : ?int
    {
        if ($role_type != self::ROLE_ADMIN && $role_type != self::ROLE_MEMBER) {
            return null;
        }

        if ($parent_membership_object instanceof \ilObjCourse) {
            return $role_type == self::ROLE_ADMIN
                ? $parent_membership_object->getDefaultAdminRole()
                : $parent_membership_object->getDefaultMemberRole();
        } elseif ($parent_membership_object instanceof \ilObjGroup) {
            return $role_type == self::ROLE_ADMIN
                ? $parent_membership_object->getDefaultAdminRole()
                : $parent_membership_object->getDefaultMemberRole();
        }

        return null;
    }

    /**
     * @param int $current_event_ref_id
     * @return array
     */
    private function searchMembershipParentObjectsForEvent(int $current_event_ref_id) : array
    {
        global $DIC;
        $parent_objects = [];

        $current_ref_id = $current_event_ref_id;
        $deadlock_prevention = 0;
        do {
            $current_ref_id = $DIC->repositoryTree()->getParentId($current_ref_id);
            $type = \ilObject::_lookupType($current_ref_id, true);

            if ($type == 'crs') {
                $parent_objects[] = new \ilObjCourse($current_ref_id, true);
            } elseif ($type == 'grp') {
                $parent_objects[] = $crs_obj = new \ilObjCourse($current_ref_id, true);
            }
            $deadlock_prevention++;
        } while (in_array($type, ['crs', 'grp', 'fold']) && $current_ref_id > 1 && $deadlock_prevention < 100);

        return $parent_objects;
    }

    /**
     * @param       $user
     * @param array $evento_user_list
     * @return bool
     */
    private function isUserInCurrentImport(IliasEventoUser $user, EventoEvent $imported_event) : bool
    {
        foreach ($imported_event->getEmployees() as $evento_user) {
            if ($evento_user instanceof EventoUserShort && $user->getEventoUserId() == $evento_user->getEventoId()) {
                return true;
            }
        }

        foreach ($imported_event->getStudents() as $evento_user) {
            if ($evento_user instanceof EventoUserShort && $user->getEventoUserId() == $evento_user->getEventoId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param IliasEventoEvent $ilias_evento_event
     * @param array            $evento_user_list
     * @param int              $role_id_of_main_event
     * @param array            $parent_membership_objects
     * @param int              $role_type
     */
    private function addUsersToEvent(
        IliasEventoEvent $ilias_evento_event,
        array $evento_user_list,
        int $role_id_of_main_event,
        array $parent_membership_objects,
        int $role_type
    ) {
        /** @var EventoUserShort $evento_user */
        foreach ($evento_user_list as $evento_user) {
            $user_id = $this->user_repo->getIliasUserIdByEventoId($evento_user->getEventoId());

            if (!$this->rbac_review->isAssigned($user_id, $role_id_of_main_event)) {
                $this->rbac_admin->assignUser($role_id_of_main_event, $user_id);
                $this->favourites_manager->add($user_id, $ilias_evento_event->getRefId());
                $this->logger->logEventMembership(\ilEventoImportLogger::CREVENTO_SUB_NEWLY_ADDED, $ilias_evento_event->getEventoEventId(), $evento_user->getEventoId(), $role_type);
            } else {
                $this->logger->logEventMembership(\ilEventoImportLogger::CREVENTO_SUB_ALREADY_ASSIGNED, $ilias_evento_event->getEventoEventId(), $evento_user->getEventoId(), $role_type);
            }

            /** @var \ilObject $parent_membership_object */
            foreach ($parent_membership_objects as $parent_membership_object) {
                $role_id = $this->getRoleIdForObjectOrNull($parent_membership_object, $role_type);

                if (!is_null($role_id) && !$this->rbac_review->isAssigned($user_id, $role_id)) {
                    $this->rbac_admin->assignUser($role_id, $user_id);
                    $this->favourites_manager->add($user_id, $parent_membership_object->getRefId());
                }
            }
            $this->membership_repo->addMembershipIfNotExist($ilias_evento_event->getEventoEventId(), $user_id, $role_type);
        }
    }

    /**
     * @param IliasEventoEvent $ilias_evento_event
     * @param array            $evento_user_list
     * @param int              $role_id_of_main_event
     * @param array            $from_import_subscribed_members
     * @param array            $parent_membership_objects
     * @param int              $role_type
     */
    private function removeUsersFromEvent(
        IliasEventoEvent $ilias_evento_event,
        array $evento_user_list,
        int $role_id_of_main_event,
        array $from_import_subscribed_members,
        array $parent_membership_objects,
        int $role_type
    ) {
        /** @var IliasEventoUser $ilias_evento_user */
        foreach ($from_import_subscribed_members as $ilias_evento_user) {

            // Check if user was in current import
            if (!$this->isUserInCurrentImport($ilias_evento_user, $evento_user_list)) {

                // Always remove from main event
                if (!$this->rbac_review->isAssigned($ilias_evento_user->getIliasUserId(), $role_id_of_main_event)) {
                    $this->rbac_admin->deassignUser($role_id_of_main_event, $ilias_evento_user->getIliasUserId());
                    $this->favourites_manager->remove($ilias_evento_user->getIliasUserId(), $ilias_evento_event->getRefId());
                    $this->logger->logEventMembership(\ilEventoImportLogger::CREVENTO_SUB_REMOVED, $ilias_evento_event->getEventoEventId(), $ilias_evento_user->getEventoId(), $role_type);
                } else {
                    $this->logger->logEventMembership(\ilEventoImportLogger::CREVENTO_SUB_ALREADY_DEASSIGNED, $ilias_evento_event->getEventoEventId(), $ilias_evento_user->getEventoId(), $role_type);
                }

                if (!is_null($ilias_evento_event->getParentEventKey())
                    && !$this->membership_repo->checkIfUserHasMembershipInOtherSubEvent(
                        $ilias_evento_event->getParentEventKey(),
                        $ilias_evento_user->getEventoUserId(),
                        $ilias_evento_event->getEventoEventId()
                    )
                ) {
                    foreach ($parent_membership_objects as $parent_membership_object) {
                        $role_id = $this->getRoleIdForObjectOrNull($parent_membership_object, $role_type);

                        if (!is_null($role_id) && !$this->rbac_review->isAssigned($ilias_evento_user->getIliasUserId(), $parent_membership_object)) {
                            $this->rbac_admin->deassignUser($role_id, $ilias_evento_user->getIliasUserId());
                            $this->favourites_manager->add($ilias_evento_user->getIliasUserId(), $parent_membership_object->getRefId());
                        }
                    }
                }
            }
        }
    }

    /**
     * @param IliasEventoEvent $ilias_evento_event
     * @param array            $evento_user_list
     * @param int              $role_id_of_main_event
     * @param array            $parent_membership_objects
     * @param int              $role_type
     */
    private function synchronizeRolesWithMembers(
        IliasEventoEvent $ilias_evento_event,
        array $evento_user_list,
        int $role_id_of_main_event,
        array $parent_membership_objects,
        int $role_type
    ) {

        // Add
        $this->addUsersToEvent(
            $ilias_evento_event,
            $evento_user_list,
            $role_id_of_main_event,
            $parent_membership_objects,
            $role_type
        );

        // Remove
        $from_import_subscribed_members = $this->membership_repo->fetchIliasEventoUsersForEventAndRole($ilias_evento_event->getEventoEventId(), $role_type);
        if (count($from_import_subscribed_members) != count($evento_user_list)) {
            $this->removeUsersFromEvent(
                $ilias_evento_event,
                $evento_user_list,
                $role_id_of_main_event,
                $from_import_subscribed_members,
                $parent_membership_objects,
                $role_type
            );
        }
    }

    /**
     * @param EventoEvent      $evento_event
     * @param IliasEventoEvent $ilias_evento_event
     */
    public function synchronizeMembershipsWithEvent(EventoEvent $evento_event, IliasEventoEvent $ilias_evento_event)
    {
        $this->synchronizeRolesWithMembers(
            $ilias_evento_event,
            $evento_event->getEmployees(),
            $ilias_evento_event->getAdminRoleId(),
            $this->searchMembershipParentObjectsForEvent($ilias_evento_event->getRefId()),
            EventMembershipRepository::ROLE_ADMIN
        );

        $this->synchronizeRolesWithMembers(
            $ilias_evento_event,
            $evento_event->getStudents(),
            $ilias_evento_event->getStudentRoleId(),
            $this->searchMembershipParentObjectsForEvent($ilias_evento_event->getRefId()),
            EventMembershipRepository::ROLE_MEMBER
        );
    }
}

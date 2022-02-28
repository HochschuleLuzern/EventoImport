<?php declare(strict_types = 1);

namespace EventoImport\import\service;

use EventoImport\import\db\IliasEventoEventMembershipRepository;
use ILIAS\DI\RBACServices;
use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\IliasEventoUserRepository;
use EventoImport\communication\api_models\EventoUserShort;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\model\IliasEventoUser;
use EventoImport\import\db\query\MembershipablesInTreeSeeker;
use EventoImport\communication\api_models\EventoEventIliasAdmins;
use EventoImport\import\Logger;

class MembershipManager
{
    private $membership_repo;
    private $favourites_manager;
    private $logger;
    private \ilRbacReview $rbac_review;
    private \ilRbacAdmin $rbac_admin;

    private IliasEventoUserRepository $user_repo;
    private MembershipablesInTreeSeeker $tree_seeker;

    /** @var \ilParticipants[]  */
    private array $participant_object_cache;

    public function __construct(
        MembershipablesInTreeSeeker $tree_seeker,
        IliasEventoEventMembershipRepository $membership_repo,
        IliasEventoUserRepository $user_repo,
        \ilFavouritesManager $favourites_manager,
        Logger $logger,
        RBACServices $rbac_services
    ) {
        $this->membership_repo = $membership_repo;
        $this->tree_seeker = $tree_seeker;
        $this->user_repo = $user_repo;
        $this->favourites_manager = $favourites_manager;
        $this->logger = $logger;
        $this->rbac_review = $rbac_services->review();
        $this->rbac_admin = $rbac_services->admin();
        $this->participant_object_cache = [];
    }

    public function syncMemberships(EventoEvent $imported_event, IliasEventoEvent $ilias_event) : void
    {
        // If Ilias Event is already a course -> no need to find parent membershipables
        if ($ilias_event->getIliasType() == 'crs') {
            $this->syncMembershipsWithoutParentObjects($imported_event, $ilias_event);
        } else {

            // Else -> search for parent membershipable objects
            $parent_events = $this->tree_seeker->getRefIdsOfParentMembershipables($ilias_event->getRefId());

            // Check if any parent membershipables were found
            if (count($parent_events) > 0) {
                $this->syncMembershipsWithParentObjects($imported_event, $ilias_event, $parent_events);
            } else {
                $this->syncMembershipsWithoutParentObjects($imported_event, $ilias_event);
            }
        }
    }

    private function addUsersToMembershipableObject(\ilParticipants $participants_object, array $employees, int $admin_role_code, array $students, int $student_role_code) : void
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

        return $users_to_remove;
    }

    private function syncMembershipsWithoutParentObjects(EventoEvent $imported_event, IliasEventoEvent $ilias_event) : void
    {
        $participants_obj = $this->getParticipantsObjectForRefId($ilias_event->getRefId());

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
        $sub_membershipable_objs = $this->tree_seeker->getAllSubGroups($ilias_event->getRefId());
        /** @var IliasEventoUser $user_to_remove */
        foreach ($users_to_remove as $user_to_remove) {
            if ($participants_obj->isAssigned($user_to_remove->getIliasUserId())) {
                $participants_obj->delete($user_to_remove->getIliasUserId());
                $this->logger->logEventMembership(Logger::CREVENTO_SUB_REMOVED, $imported_event->getEventoId(), $user_to_remove->getEventoUserId(), 0);
            } else {
                $this->logger->logEventMembership(
                    Logger::CREVENTO_SUB_ALREADY_DEASSIGNED,
                    $imported_event->getEventoId(),
                    $user_to_remove->getEventoUserId(),
                    0
                );
            }

            $this->removeUserFromSubMembershipables($user_to_remove, $sub_membershipable_objs);
        }
    }

    private function syncMembershipsWithParentObjects(EventoEvent $imported_event, IliasEventoEvent $ilias_event, array $parent_events) : void
    {
        // Add users to main event
        $participants_obj = $this->getParticipantsObjectForRefId($ilias_event->getRefId());
        $this->addUsersToMembershipableObject(
            $participants_obj,
            $imported_event->getEmployees(),
            IL_GRP_ADMIN,
            $imported_event->getStudents(),
            IL_GRP_MEMBER
        );

        // Add users to all parent membershipable objects
        foreach ($parent_events as $parent_event) {
            $participants_obj = $this->getParticipantsObjectForRefId($parent_event);

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

        $sub_membershipable_objs = $this->tree_seeker->getAllSubGroups($ilias_event->getRefId());

        /** @var IliasEventoUser $user_to_remove */
        foreach ($users_to_remove as $user_to_remove) {

            // Remove from main event
            if ($participants_obj->isAssigned($user_to_remove->getIliasUserId())) {
                $participants_obj->delete($user_to_remove->getIliasUserId());
                $this->logger->logEventMembership(Logger::CREVENTO_SUB_REMOVED, $imported_event->getEventoId(), $user_to_remove->getEventoUserId(), 0);
            } else {
                $this->logger->logEventMembership(
                    Logger::CREVENTO_SUB_ALREADY_DEASSIGNED,
                    $imported_event->getEventoId(),
                    $user_to_remove->getEventoUserId(),
                    0
                );
            }

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
                    if ($co_particpants_list->isAssigned($user_to_remove)) {
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

    public function addEventAdmins(EventoEventIliasAdmins $event_admin_list, IliasEventoEvent $ilias_evento_event) : void
    {
        $event_participant_obj = $this->getParticipantsObjectForRefId($ilias_evento_event->getRefId());
        $this->addAdminListToObject(
            $event_participant_obj,
            $event_admin_list->getAccountList(),
            $ilias_evento_event->getIliasType() == 'crs' ? IL_CRS_ADMIN : IL_GRP_ADMIN
        );

        $parent_membershipables = $this->tree_seeker->getRefIdsOfParentMembershipables($ilias_evento_event->getRefId());
        foreach ($parent_membershipables as $parent_membershipable) {
            $participants_obj = $this->getParticipantsObjectForRefId($parent_membershipable);

            if ($participants_obj instanceof \ilCourseParticipants) {
                $this->addAdminListToObject(
                    $participants_obj,
                    $event_admin_list->getAccountList(),
                    IL_CRS_ADMIN,
                );
            } elseif ($participants_obj instanceof \ilGroupParticipants) {
                $this->addAdminListToObject(
                    $participants_obj,
                    $event_admin_list->getAccountList(),
                    IL_GRP_ADMIN,
                );
            }
        }
    }

    private function addAdminListToObject(\ilParticipants $participants_object, array $admin_list, int $admin_role_code) : void
    {
        foreach ($admin_list as $admin) {
            $employee_user_id = $this->user_repo->getIliasUserIdByEventoId($admin->getEventoId());
            if (!$participants_object->isAssigned($employee_user_id)) {
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
}
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
        $this->user_repo = $user_repo;
        $this->event_repo = $event_repo;
        $this->favourites_manager = $favourites_manager;
        $this->logger = $logger;
        $this->rbac_review = $rbac_services->review();
        $this->rbac_admin = $rbac_services->admin();
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
     * @param       $member
     * @param array $evento_user_list
     * @return bool
     */
    private function isMemberInCurrentImport($member, array $evento_user_list) : bool
    {
        // TODO: Implement to test with real data set
        return true;
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
            if (!$this->isMemberInCurrentImport($ilias_evento_user, $evento_user_list)) {

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

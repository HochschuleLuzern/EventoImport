<?php declare(strict_types = 1);

namespace EventoImport\import\db;

use EventoImport\import\db\query\IliasUserQuerying;
use EventoImport\import\db\repository\EventMembershipRepository;
use EventoImport\import\db\repository\EventoUserRepository;
use ILIAS\DI\RBACServices;
use EventoImport\communication\api_models\EventoUserShort;
use EventoImport\communication\api_models\EventoUser;

/**
 * Class UserFacade
 *
 * This class is a take on encapsulation all the "User" specific functionality from the rest of the import. Things like
 * searching an ILIAS-Object by title or saving something event-specific to the DB should go through this class.
 *
 * It started as a take on the facade pattern (hence the name) but quickly became something more. Because of the lack
 * for a better / more matching name, the class was not renamed till now.
 *
 * TODO: Find a more matching name and unify use of method (e.g. replace the object-getters with actual logic)
 *
 * @package EventoImport\import\db
 */
class UserFacade
{
    private IliasUserQuerying $user_query;
    private EventoUserRepository $evento_user_repo;
    private EventMembershipRepository $event_membership_rep;
    private RBACServices $rbac_services;
    private $student_role_id;

    public function __construct(IliasUserQuerying $user_query, EventoUserRepository $evento_user_repo, EventMembershipRepository $event_membership_rep, RBACServices $rbac_services)
    {
        $this->user_query = $user_query;
        $this->evento_user_repo = $evento_user_repo;
        $this->event_membership_rep = $event_membership_rep;
        $this->rbac_services = $rbac_services;

        $this->student_role_id = null;
    }

    public function addEventoMembership($user_id, $event_id, $role_type)
    {
        $this->event_membership_rep->addMembershipIfNotExist($event_id, $user_id, $role_type);
    }

    public function fetchUserIdsByEmail($email)
    {
        return $this->user_query->fetchUserIdsByEmailAdresses($email);
    }

    public function fetchUserIdsByEventoId($evento_id)
    {
        return $this->user_query->fetchUserIdsByEventoId($evento_id);
    }

    public function fetchUserIdByLogin(string $login_name)
    {
        return \ilObjUser::getUserIdByLogin($login_name);
    }

    public function createNewIliasUserObject() : \ilObjUser
    {
        return new \ilObjUser();
    }

    public function getExistingIliasUserObject(int $user_id) : \ilObjUser
    {
        return new \ilObjUser($user_id);
    }

    public function eventoUserRepository() : EventoUserRepository
    {
        return $this->evento_user_repo;
    }

    public function rbacServices() : \ILIAS\DI\RBACServices
    {
        return $this->rbac_services;
    }

    public function fetchUserIdByMembership($evento_event_id, EventoUserShort $evento_user) : ?int
    {
        $user_id = $this->evento_user_repo->getIliasUserIdByEventoId($evento_user->getEventoId());

        if (!is_null($user_id) && $user_id > 0) {
            return $user_id;
        }

        $user_ids = $this->user_query->fetchUserIdsByEmailAdress($evento_user->getEmailAddress());
        if (count($user_ids) == 1) {
            return $user_ids[1];
        }

        return null;
    }

    public function assignUserToRole(int $role_id, int $user_id)
    {
        $this->rbac_services->admin()->assignUser($role_id, $user_id);
    }

    public function setMailPreferences(int $user_id, int $incoming_type)
    {
        $mail_options = new \ilMailOptions($user_id);
        $mail_options->setIncomingType($incoming_type);
        $mail_options->updateOptions();
    }

    public function deleteEventoIliasUserConnection(int $evento_id, \ilObjUser $ilias_user)
    {
        $this->evento_user_repo->deleteEventoUser($evento_id);
    }

    public function userWasStudent(\ilObjUser $ilias_user_object) : bool
    {
        // TODO: Implement config for this
        if (is_null($this->student_role_id)) {
            $this->student_role_id = \ilObjRole::_lookupTitle('Studierende');
            if (is_null($this->student_role_id)) {
                return false;
            }
        }

        return $this->rbac_services->review()->isAssigned($ilias_user_object->getId(), $this->student_role_id);
    }

    public function userHasPersonalPicture(\ilObjUser $ilias_user) : bool
    {
        $personal_picturpath = \ilObjUser::_getPersonalPicturePath($ilias_user->getId(), "small", false);

        return strpos(
            $personal_picturpath,
            'data:image/svg+xml'
        ) !== false;
    }

    public function sendLoginChangedMail(\ilObjUser $ilias_user, string $old_login, EventoUser $evento_user)
    {
        $mail = new \EventoImport\import\ImportMailNotification();
        $mail->setType(\EventoImport\import\ImportMailNotification::MAIL_TYPE_USER_NAME_CHANGED);
        $mail->setUserInformation(
            $ilias_user->getId(),
            $old_login,
            $evento_user->getLoginName(),
            $ilias_user->getEmail()
        );
        $mail->send();
    }

    public function setUserTimeLimits()
    {
        $until_max = 0;
        $this->user_query->setTimeLimitForUnlimitedUsersExceptSpecialUsers($until_max);
        $this->user_query->setUserTimeLimitsToAMaxValue($until_max);
        $this->user_query->setUserTimeLimitsBelowThresholdToGivenValue(90, 7889229);
    }
}

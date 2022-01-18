<?php

namespace EventoImport\import\db;

use EventoImport\import\db\query\IliasUserQuerying;
use EventoImport\import\db\repository\EventMembershipRepository;
use EventoImport\import\db\repository\EventoUserRepository;
use ILIAS\DI\RBACServices;
use EventoImport\communication\api_models\EventoUserShort;
use EventoImport\communication\api_models\EventoUser;

/**
 * Class UserFacade
 * @package EventoImport\import\db
 */
class UserFacade
{
    /** @var IliasUserQuerying */
    private IliasUserQuerying $user_query;

    /** @var EventoUserRepository */
    private EventoUserRepository $evento_user_repo;

    /** @var EventMembershipRepository */
    private EventMembershipRepository $event_membership_rep;

    /** @var RBACServices */
    private RBACServices $rbac_services;

    /** @var null */
    private $student_role_id;

    public function __construct(IliasUserQuerying $user_query = null, EventoUserRepository $evento_user_repo = null, EventMembershipRepository $event_membership_rep = null, RBACServices $rbac_services)
    {
        global $DIC;
        
        $this->user_query = $user_query ?? new IliasUserQuerying($DIC->database());
        $this->evento_user_repo = $evento_user_repo ?? new EventoUserRepository($DIC->database());
        $this->event_membership_rep = $event_membership_rep ?? new EventMembershipRepository($DIC->database());
        $this->rbac_services = $rbac_services ?? $DIC->rbac();

        $this->student_role_id = null;
    }

    /**
     * @depracated
     */
    public function addEventoMembership($user_id, $event_id, $role_type)
    {
        $this->event_membership_rep->addMembershipIfNotExist($event_id, $user_id, $role_type);
    }

    /**
     * @param $email
     * @return array
     */
    public function fetchUserIdsByEmail($email)
    {
        return $this->user_query->fetchUserIdsByEmailAdresses($email);
    }

    /**
     * @param $evento_id
     * @return array
     */
    public function fetchUserIdsByEventoId($evento_id)
    {
        return $this->user_query->fetchUserIdsByEventoId($evento_id);
    }

    /**
     * @param string $login_name
     * @return int
     */
    public function fetchUserIdByLogin(string $login_name)
    {
        return \ilObjUser::getUserIdByLogin($login_name);
    }

    /**
     * @return \ilObjUser
     */
    public function createNewIliasUserObject() : \ilObjUser
    {
        return new \ilObjUser();
    }

    /**
     * @param int $user_id
     * @return \ilObjUser
     */
    public function getExistingIliasUserObject(int $user_id) : \ilObjUser
    {
        return new \ilObjUser($user_id);
    }

    /**
     * @return EventoUserRepository
     */
    public function eventoUserRepository() : EventoUserRepository
    {
        return $this->evento_user_repo;
    }

    /**
     * @return RBACServices
     */
    public function rbacServices() : \ILIAS\DI\RBACServices
    {
        return $this->rbac_services;
    }

    /**
     * @param                 $evento_event_id
     * @param EventoUserShort $evento_user
     * @return int|mixed
     */
    public function fetchUserIdByMembership($evento_event_id, EventoUserShort $evento_user)
    {
        $user_id = $this->evento_user_repo->getIliasUserIdByEventoId($evento_user->getEventoId());

        if (!is_null($user_id) && $user_id > 0) {
            return $user_id;
        }

        $user_ids = $this->user_query->fetchUserIdsByEmailAdress($evento_user->getEmailAddress());
        if (count($user_ids) == 1) {
            return $user_ids[1];
        }
    }

    /**
     * @param int $role_id
     * @param int $user_id
     */
    public function assignUserToRole(int $role_id, int $user_id)
    {
        $this->rbac_services->admin()->assignUser($role_id, $user_id);
    }

    /**
     * @param int $user_id
     * @param int $incoming_type
     */
    public function setMailPreferences(int $user_id, int $incoming_type)
    {
        $mail_options = new \ilMailOptions($user_id);
        $mail_options->setIncomingType($incoming_type);
        $mail_options->updateOptions();
    }

    /**
     * @param int        $evento_id
     * @param \ilObjUser $ilias_user
     */
    public function deleteEventoIliasUserConnection(int $evento_id, \ilObjUser $ilias_user)
    {
        $this->evento_user_repo->deleteEventoUser($evento_id);
    }

    /**
     * @param \ilObjUser $ilias_user_object
     * @return bool
     */
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

    /**
     * @param \ilObjUser $ilias_user
     * @return bool
     * @throws \ilWACException
     */
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
        $mail = new \ilEventoimportMailNotification();
        $mail->setType(\ilEventoimportMailNotification::MAIL_TYPE_USER_NAME_CHANGED);
        $mail->setUserInformation(
            $ilias_user->getId(),
            $old_login,
            $evento_user->getLoginName(),
            $ilias_user->getEmail()
        );
        $mail->send();
    }
}

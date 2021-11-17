<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\UserFacade;
use EventoImport\import\settings\DefaultUserSettings;

class Update extends UserAction
{
    private $default_user_settings;
    private $ilias_user_id;

    public function __construct(
        EventoUser $evento_user,
        int $ilias_user_id,
        UserFacade $user_facade,
        DefaultUserSettings $default_user_settings,
        \ilEventoImportLogger $logger
    ) {
        parent::__construct($evento_user, $user_facade, $logger);

        $this->default_user_settings = $default_user_settings;
        $this->ilias_user_id = $ilias_user_id;
    }

    private function changeLoginName(int $getId, string $getLoginName)
    {
    }

    private function synchronizeUserWithGlobalRoles(int $user_id, array $imported_evento_roles)
    {
        $rbac = $this->user_facade->rbacServices();
        $review = $rbac->review();
        $admin = $rbac->admin();

        // Assign default user role if not assigned
        if (!$review->isAssigned($user_id, $this->default_user_settings->getDefaultUserRoleId())) {
            $admin->assignUser($this->default_user_settings->getDefaultUserRoleId(), $user_id);
        }

        // Set ilias roles according to given evento roles
        foreach ($this->default_user_settings->getEventoCodeToIliasRoleMapping() as $evento_role_code => $ilias_role_id) {

            // Assign if import delivers role but user is not assigned
            if (in_array($evento_role_code, $imported_evento_roles) && !$review->isAssigned($user_id, $ilias_role_id)) {
                $admin->assignUser($ilias_role_id, $user_id);
            } else {
                // Deassign if import does not deliver role but user is assigned
                if (!in_array($evento_role_code, $imported_evento_roles) && $review->isAssigned($user_id, $ilias_role_id)) {
                    $admin->deassignUser($ilias_role_id, $user_id);
                }
            }
        }
    }

    public function executeAction()
    {
        $user_updated = false;
        $userObj = $this->user_facade->getExistingIliasUserObject($this->ilias_user_id);
        $userObj->read();
        $userObj->readPrefs();

        if ($userObj->getFirstname() != $this->evento_user->getFirstName()
            || $userObj->getlastname() != $this->evento_user->getLastName()
            //|| $userObj->getGender() != strtolower($this->evento_user->getGender())
            //|| $userObj->getSecondEmail() != $evento_user->getEmailList()[0]
            || $userObj->getMatriculation() != ('Evento:' . $this->evento_user->getEventoId())
            || $userObj->getAuthMode() != $this->default_user_settings->getAuthMode()
            || !$userObj->getActive()
        ) {
            $user_updated = true;

            $old_user_data = array();
            $old_user_data['old_data']['FirstName'] = $userObj->getFirstname();
            $old_user_data['old_data']['LastName'] = $userObj->getLastname();
            $old_user_data['old_data']['Gender'] = $userObj->getGender();
            $old_user_data['old_data']['SecondEmail'] = $userObj->getSecondEmail();
            $old_user_data['old_data']['Matriculation'] = $userObj->getMatriculation();
            $old_user_data['old_data']['AuthMode'] = $userObj->getAuthMode();
            $old_user_data['old_data']['Active'] = (string) $userObj->getActive();
        }

        $userObj->setFirstname($this->evento_user->getFirstName());
        $userObj->setLastname($this->evento_user->getLastName());
        //$userObj->setSecondEmail($evento_user['Email']);

        $userObj->setTitle($userObj->getFullname());
        $userObj->setDescription($userObj->getEmail());
        $userObj->setMatriculation('Evento:' . $this->evento_user->getEventoId());
        $userObj->setExternalAccount($this->evento_user->getEventoId() . '@hslu.ch');
        $userObj->setAuthMode($this->default_user_settings->getAuthMode());

        if (\ilLDAPServer::isAuthModeLDAP($this->default_user_settings->getAuthMode())) {
            $userObj->setPasswd('');
        }

        $userObj->setActive(true);

        // Reset login attempts over night -> needed since login attempts are limited to 8
        $userObj->setLoginAttempts(0);

        if ($this->default_user_settings->getAccDurationAfterImport()->getTimestamp() == 0) {
            $userObj->setTimeLimitUnlimited(true);
        } else {
            $userObj->setTimeLimitUnlimited(false);

            if ($userObj->getTimeLimitFrom() == 0 ||
                $userObj->getTimeLimitFrom() > $this->default_user_settings->getNow()->getTimestamp()) {
                $userObj->setTimeLimitFrom($this->default_user_settings->getNow()->getTimestamp());
            }

            $userObj->setTimeLimitUntil($this->default_user_settings->getAccDurationAfterImport()->getTimestamp());
        }

        $userObj->setPref(
            'public_profile',
            $this->default_user_settings->isProfilePublic()
        ); //profil standard öffentlich
        $userObj->setPref(
            'public_upload',
            $this->default_user_settings->isProfilePicturePublic()
        ); //profilbild öffentlich
        $userObj->setPref('public_email', $this->default_user_settings->isMailPublic()); //e-mail öffentlich
        $userObj->setPasswd('', IL_PASSWD_PLAIN);
        $userObj->update();

        // Assign user to global user role
        if (!$this->user_facade->rbacServices()->review()->isAssigned(
            $userObj->getId(),
            $this->default_user_settings->getDefaultUserRoleId()
        )) {
            $this->user_facade->rbacServices()->admin()->assignUser(
                $this->default_user_settings->getDefaultUserRoleId(),
                $userObj->getId()
            );
        }

        $this->synchronizeUserWithGlobalRoles($userObj->getId(), $this->evento_user->getRoles());

        // Upload image
        if (strpos(
            \ilObjUser::_getPersonalPicturePath($userObj->getId(), "small", false),
            'data:image/svg+xml'
        ) !== false) {
            //$this->addPersonalPicture($this->evento_user->getEventoId(), $userObj->getId());
        }

        $oldLogin = $userObj->getLogin();

        if ($oldLogin != $this->evento_user->getLoginName()) {
            //$evento_user['oldLogin'] = $oldLogin;
            //$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_RENAMED, $evento_user);
/*
            $this->changeLoginName($userObj->getId(), $this->evento_user->getLoginName());
            $userObj->setLogin('');
/*
            $mail = new \ilEventoimportMailNotification();
            $mail->setType($mail::MAIL_TYPE_USER_NAME_CHANGED);
            $mail->setUserInformation($userObj->id, $oldLogin, $this->evento_user->getLoginName(),
                $userObj->getEmail());
            $mail->send();
*/
        } else {
            if ($user_updated) {
                //$this->logger->log(\ilEventoImportLogger::CREVENTO_USR_UPDATED, $this->evento_user);
            }
        }
    }
}

<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\UserFacade;
use EventoImport\import\settings\DefaultUserSettings;

class Update extends UserAction
{
    public function __construct(EventoUser $evento_user, UserFacade $user_facade, DefaultUserSettings $default_user_settings)
    {
        parent::__construct($evento_user, $user_facade);
    }

    public function executeAction()
    {
        $user_updated = false;
        $userObj = $this->user_facade->getExistingIliasUserObject($this->ilias_user);
        $userObj->read();
        $userObj->readPrefs();



        if ($userObj->getFirstname() != $evento_user->getFirstName()
            || $userObj->getlastname() != $evento_user->getLastName()
            || $userObj->getGender() != strtolower($evento_user->getGender())
            //|| $userObj->getSecondEmail() != $evento_user->getEmailList()[0]
            || $userObj->getMatriculation() != ('Evento:'. $evento_user->getEventoId())
            || $userObj->getAuthMode() != $this->auth_mode
            || !$userObj->getActive()
        ) {
            $user_updated = true;

            $old_user_data = array();
            $old_user_data['old_data']['FirstName']     = $userObj->getFirstname();
            $old_user_data['old_data']['LastName']      = $userObj->getLastname();
            $old_user_data['old_data']['Gender']        = $userObj->getGender();
            $old_user_data['old_data']['SecondEmail']   = $userObj->getSecondEmail();
            $old_user_data['old_data']['Matriculation'] = $userObj->getMatriculation();
            $old_user_data['old_data']['AuthMode']      = $userObj->getAuthMode();
            $old_user_data['old_data']['Active']        = (string) $userObj->getActive();
        }

        $userObj->setFirstname($evento_user->getFirstName());
        $userObj->setLastname($evento_user->getLastName());
        $userObj->setGender(($evento_user->getGender()=='F') ? 'f':'m');
        //$userObj->setSecondEmail($evento_user['Email']);

        $userObj->setTitle($userObj->getFullname());
        $userObj->setDescription($userObj->getEmail());
        $userObj->setMatriculation('Evento:'. $evento_user->getEventoId());
        $userObj->setExternalAccount($evento_user->getEventoId().'@hslu.ch');
        $userObj->setAuthMode($this->auth_mode);

        //if(ilLDAPServer::isAuthModeLDAP($this->auth_mode)) $userObj->setPasswd('');

        $userObj->setActive(true);

        // Reset login attempts over night -> needed since login attempts are limited to 8
        $userObj->setLoginAttempts(0);

        if ($this->until == 0) {
            $userObj->setTimeLimitUnlimited(true);
        } else {
            $userObj->setTimeLimitUnlimited(false);

            if ($userObj->getTimeLimitFrom() == 0 ||
                $userObj->getTimeLimitFrom() > $this->now) {
                $userObj->setTimeLimitFrom($this->now);
            }

            $userObj->setTimeLimitUntil($this->until);
        }

        $userObj->setPref('public_profile','y'); //profil standard öffentlich
        $userObj->setPref('public_upload','y'); //profilbild öffentlich
        $userObj->setPref('public_email','y'); //e-mail öffentlich
        $userObj->setPasswd('', IL_PASSWD_PLAIN);
        $userObj->update();

        // Assign user to global user role
        if (!$this->rbacreview->isAssigned($userObj->getId(), $this->usr_role_id)) {
            $this->rbacadmin->assignUser($this->usr_role_id, $userObj->getId());
        }

        $this->assignUserToAdditionalRoles($userObj->getId(), $evento_user->getRoles());

        // Upload image
        if (strpos(ilObjUser::_getPersonalPicturePath($userObj->getId(), "small", false),'data:image/svg+xml') !== false) {
            $this->addPersonalPicture($evento_user->getEventoId(), $userObj->getId());
        }

        $oldLogin = $userObj->getLogin();

        if ($oldLogin != $evento_user->getLoginName()) {
            //$evento_user['oldLogin'] = $oldLogin;
            //$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_RENAMED, $evento_user);

            $this->changeLoginName($userObj->getId(), $evento_user->getLoginName());

            include_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportMailNotification.php';
            $mail = new ilEventoimportMailNotification();
            $mail->setType($mail::MAIL_TYPE_USER_NAME_CHANGED);
            $mail->setUserInformation($userObj->id, $oldLogin, $evento_user->getLoginName(), $userObj->getEmail());
            $mail->send();
        } else if ($user_updated) {
            //$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_UPDATED, $evento_user);
        }
    }
}
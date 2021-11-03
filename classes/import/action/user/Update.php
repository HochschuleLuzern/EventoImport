<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\UserFacade;
use EventoImport\import\settings\DefaultUserSettings;

class Update extends UserAction
{
    private $default_user_settings;
    private $ilias_user_id;

    public function __construct(EventoUser $evento_user, int $ilias_user_id, UserFacade $user_facade, DefaultUserSettings $default_user_settings, \ilEventoImportLogger $logger)
    {
        parent::__construct($evento_user, $user_facade, $logger);

        $this->default_user_settings = $default_user_settings;
        $this->ilias_user_id = $ilias_user_id;
    }

    public function executeAction()
    {
        $user_updated = false;
        $userObj = $this->user_facade->getExistingIliasUserObject($this->ilias_user_id);
        $userObj->read();
        $userObj->readPrefs();



        if ($userObj->getFirstname() != $this->evento_user->getFirstName()
            || $userObj->getlastname() != $this->evento_user->getLastName()
            || $userObj->getGender() != strtolower($this->evento_user->getGender())
            //|| $userObj->getSecondEmail() != $evento_user->getEmailList()[0]
            || $userObj->getMatriculation() != ('Evento:'. $this->evento_user->getEventoId())
            || $userObj->getAuthMode() != $this->default_user_settings->getAuthMode()
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

        $userObj->setFirstname($this->evento_user->getFirstName());
        $userObj->setLastname($this->evento_user->getLastName());
        $userObj->setGender(($this->evento_user->getGender()=='F') ? 'f':'m');
        //$userObj->setSecondEmail($evento_user['Email']);

        $userObj->setTitle($userObj->getFullname());
        $userObj->setDescription($userObj->getEmail());
        $userObj->setMatriculation('Evento:'. $this->evento_user->getEventoId());
        $userObj->setExternalAccount($this->evento_user->getEventoId().'@hslu.ch');
        $userObj->setAuthMode($this->default_user_settings->getAuthMode());

        //if(ilLDAPServer::isAuthModeLDAP($this->auth_mode)) $userObj->setPasswd('');

        $userObj->setActive(true);

        // Reset login attempts over night -> needed since login attempts are limited to 8
        $userObj->setLoginAttempts(0);

        if ($this->default_user_settings->getValidUntilTimestamp() == 0) {
            $userObj->setTimeLimitUnlimited(true);
        } else {
            $userObj->setTimeLimitUnlimited(false);

            if ($userObj->getTimeLimitFrom() == 0 ||
                $userObj->getTimeLimitFrom() > $this->default_user_settings->getNowTimestamp()) {
                $userObj->setTimeLimitFrom($this->default_user_settings->getNowTimestamp());
            }

            $userObj->setTimeLimitUntil($this->default_user_settings->getValidUntilTimestamp());
        }

        $userObj->setPref('public_profile','y'); //profil standard öffentlich
        $userObj->setPref('public_upload','y'); //profilbild öffentlich
        $userObj->setPref('public_email','y'); //e-mail öffentlich
        $userObj->setPasswd('', IL_PASSWD_PLAIN);
        $userObj->update();

        // Assign user to global user role
        if (!$this->user_facade->rbacServices()->review()->isAssigned($userObj->getId(), $this->default_user_settings->getDefaultUserRoleId())) {
            $this->user_facade->rbacServices()->admin()->assignUser($this->default_user_settings->getDefaultUserRoleId(), $userObj->getId());
        }

        //$this->assignUserToAdditionalRoles($userObj->getId(), $this->evento_user->getRoles());

        // Upload image
        if (strpos(\ilObjUser::_getPersonalPicturePath($userObj->getId(), "small", false),'data:image/svg+xml') !== false) {
            //$this->addPersonalPicture($this->evento_user->getEventoId(), $userObj->getId());
        }

        $oldLogin = $userObj->getLogin();

        if ($oldLogin != $this->evento_user->getLoginName()) {
            //$evento_user['oldLogin'] = $oldLogin;
            //$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_RENAMED, $evento_user);

            //$this->changeLoginName($userObj->getId(), $evento_user->getLoginName());
/*
            include_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportMailNotification.php';
            $mail = new ilEventoimportMailNotification();
            $mail->setType($mail::MAIL_TYPE_USER_NAME_CHANGED);
            $mail->setUserInformation($userObj->id, $oldLogin, $evento_user->getLoginName(), $userObj->getEmail());
            $mail->send();
*/
        } else if ($user_updated) {
            //$this->logger->log(\ilEventoImportLogger::CREVENTO_USR_UPDATED, $this->evento_user);
        }
    }
}
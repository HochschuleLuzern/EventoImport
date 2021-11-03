<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\action\EventoImportAction;
use EventoImport\import\db\UserFacade;
use EventoImport\import\settings\DefaultUserSettings;

class Create extends UserAction
{
    protected $default_user_settings;

    public function __construct(EventoUser $evento_user, UserFacade $user_facade, DefaultUserSettings $default_user_settings, \ilEventoImportLogger $logger)
    {
        parent::__construct($evento_user, $user_facade, $logger);

        $this->default_user_settings = $default_user_settings;
    }

    private function assignUserToAdditionalRoles(int $getId, array $getRoles)
    {

    }

    private function setMailPreferences(int $usrId)
    {
        global $DIC;
        $DIC->database()->manipulateF("UPDATE mail_options SET incoming_type = '2' WHERE user_id = %s", array("integer"), array($usrId)); //mail nur intern nach export
    }

    public function executeAction()
    {
        $ilias_user_object = $this->user_facade->createNewIliasUserObject();

        $ilias_user_object->setLogin($this->evento_user->getLoginName());
        $ilias_user_object->setFirstname($this->evento_user->getFirstName());
        $ilias_user_object->setLastname($this->evento_user->getLastName());
        $ilias_user_object->setGender(($this->evento_user->getGender() =='F') ? 'f':'m');
        $ilias_user_object->setEmail($this->evento_user->getEmailList()[0]);
        if(isset($this->evento_user->getEmailList()[1])){
            $ilias_user_object->setSecondEmail($this->evento_user->getEmailList()[1]);
        };
        $ilias_user_object->setTitle($ilias_user_object->getFullname());
        $ilias_user_object->setDescription($ilias_user_object->getEmail());
        $ilias_user_object->setMatriculation('Evento:'. $this->evento_user->getEventoId());
        $ilias_user_object->setExternalAccount($this->evento_user->getEventoId().'@hslu.ch');
        $ilias_user_object->setAuthMode($this->default_user_settings->getAuthMode());

//        if(!(ilLDAPServer::isAuthModeLDAP($this->auth_mode))){ $userObj->setPasswd(strtolower($evento_user['Password'])) ; }

        $ilias_user_object->setActive(true);
        $ilias_user_object->setTimeLimitFrom($this->default_user_settings->getNowTimestamp());
        if ($this->default_user_settings->getValidUntilTimestamp() == 0) {
            $ilias_user_object->setTimeLimitUnlimited(true);
        } else {
            $ilias_user_object->setTimeLimitUnlimited(false);
            $ilias_user_object->setTimeLimitUntil($this->default_user_settings->getValidUntilTimestamp());
        }

        $ilias_user_object->create();

        //insert user data in table user_data
        $ilias_user_object->saveAsNew(false);

        // Set default prefs
        $ilias_user_object->setPref('hits_per_page','100'); //100 hits per page
        $ilias_user_object->setPref('show_users_online','associated'); //nur Leute aus meinen Kursen zeigen

        $ilias_user_object->setPref('public_profile','y'); //profil standard öffentlich
        $ilias_user_object->setPref('public_upload','y'); //profilbild öffentlich
        $ilias_user_object->setPref('public_email','y'); //profilbild öffentlich

        $ilias_user_object->writePrefs();

        // update mail preferences
        $this->setMailPreferences($ilias_user_object->getId());

        // Assign user to global user role
        $this->user_facade->assignUserToRole($this->default_user_settings->getDefaultUserRoleId(), $ilias_user_object->getId());

        $this->assignUserToAdditionalRoles($ilias_user_object->getId(), $this->evento_user->getRoles());

        //$this->addPersonalPicture($this->evento_user->getEventoId(), $ilias_user_object->getId());

        //$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_CREATED, $evento_user);
        $this->user_facade->eventoUserRepository()->addNewEventoIliasUser($this->evento_user, $ilias_user_object);
    }
}
<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\action\EventoImportAction;
use EventoImport\import\db\UserFacade;
use EventoImport\import\settings\DefaultUserSettings;
use EventoImport\communication\EventoUserPhotoImporter;

class CreateUser extends UserImportAction
{
    use ImportUserPhoto;

    protected $default_user_settings;
    private $photo_importer;

    public function __construct(
        EventoUser $evento_user,
        UserFacade $user_facade,
        DefaultUserSettings $default_user_settings,
        EventoUserPhotoImporter $photo_importer,
        \ilEventoImportLogger $logger
    ) {
        parent::__construct($evento_user, $user_facade, $logger);

        $this->default_user_settings = $default_user_settings;
        $this->photo_importer = $photo_importer;
    }

    private function assignUserToAdditionalRoles(int $user_id, array $role_list)
    {
        $rbac = $this->user_facade->rbacServices();
        foreach ($role_list as $role_id) {
            if ($rbac->review()->isAssigned($user_id, $role_list)) {
                $rbac->admin()->assignUser($role_id, $user_id);
            }
        }
    }

    private function assignUserToIliasRoles(int $user_id, array $imported_evento_roles)
    {
        $rbac_admin = $this->user_facade->rbacServices()->admin();

        // Add user to default ilias user role
        $rbac_admin->assignUser($this->default_user_settings->getDefaultUserRoleId(), $user_id);

        foreach ($this->default_user_settings->getEventoCodeToIliasRoleMapping() as $evento_code => $ilias_role_id) {
            if (in_array($evento_code, $imported_evento_roles)) {
                $rbac_admin->assignUser($ilias_role_id, $user_id);
            }
        }
    }

    public function executeAction()
    {
        $ilias_user_object = $this->user_facade->createNewIliasUserObject();

        $ilias_user_object->setLogin($this->evento_user->getLoginName());
        $ilias_user_object->setFirstname($this->evento_user->getFirstName());
        $ilias_user_object->setLastname($this->evento_user->getLastName());
        $ilias_user_object->setGender($this->convertEventoToIliasGenderChar($this->evento_user->getGender()));
        $ilias_user_object->setEmail($this->evento_user->getEmailList()[0]);
        $ilias_user_object->setSecondEmail($this->evento_user->getEmailList()[0]);
        $ilias_user_object->setTitle($ilias_user_object->getFullname());
        $ilias_user_object->setDescription($ilias_user_object->getEmail());
        $ilias_user_object->setMatriculation('Evento:' . $this->evento_user->getEventoId());
        $ilias_user_object->setExternalAccount($this->evento_user->getEventoId() . '@hslu.ch');
        $ilias_user_object->setAuthMode($this->default_user_settings->getAuthMode());

        $ilias_user_object->setActive(true);
        $ilias_user_object->setTimeLimitFrom($this->default_user_settings->getNow()->getTimestamp());
        if ($this->default_user_settings->getAccDurationAfterImport() == 0) {
            $ilias_user_object->setTimeLimitUnlimited(true);
        } else {
            $ilias_user_object->setTimeLimitUnlimited(false);
            $ilias_user_object->setTimeLimitUntil($this->default_user_settings->getAccDurationAfterImport()->getTimestamp());
        }

        $ilias_user_object->create();

        //insert user data in table user_data
        $ilias_user_object->saveAsNew(false);

        // Set default prefs
        $ilias_user_object->setPref(
            'hits_per_page',
            (string) $this->default_user_settings->getDefaultHitsPerPage()
        ); //100 hits per page
        $ilias_user_object->setPref(
            'show_users_online',
            $this->default_user_settings->getDefaultShowUsersOnline()
        ); //nur Leute aus meinen Kursen zeigen

        $ilias_user_object->setPref(
            'public_profile',
            $this->default_user_settings->isProfilePublic()
        ); //profil standard öffentlich
        $ilias_user_object->setPref(
            'public_upload',
            $this->default_user_settings->isProfilePicturePublic()
        ); //profilbild öffentlich
        $ilias_user_object->setPref(
            'public_email',
            $this->default_user_settings->isMailPublic()
        ); //profilbild öffentlich

        $ilias_user_object->writePrefs();

        // update mail preferences
        $this->user_facade->setMailPreferences(
            $ilias_user_object->getId(),
            $this->default_user_settings->getMailIncomingType()
        );

        // Assign user to global user role
        $this->assignUserToIliasRoles($ilias_user_object->getId(), $this->evento_user->getRoles());

        // Import and set User Photos
        $this->importAndSetUserPhoto($this->evento_user->getEventoId(), $ilias_user_object, $this->photo_importer, $this->user_facade);

        $this->logger->logUserImport(
            \ilEventoImportLogger::CREVENTO_USR_CREATED,
            $this->evento_user->getEventoId(),
            $this->evento_user->getLoginName(),
            ['api_data' => $this->evento_user->getDecodedApiData()]
        );
        $this->user_facade->eventoUserRepository()->addNewEventoIliasUser($this->evento_user, $ilias_user_object);
    }
}

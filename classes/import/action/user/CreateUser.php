<?php declare(strict_types = 1);

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\UserFacade;
use EventoImport\import\settings\DefaultUserSettings;
use EventoImport\communication\EventoUserPhotoImporter;

class CreateUser implements UserImportAction
{
    use ImportUserPhoto;
    use UserImportActionTrait;

    private EventoUser $evento_user;
    private UserFacade $user_facade;
    protected DefaultUserSettings $default_user_settings;
    private EventoUserPhotoImporter $photo_importer;
    private \EventoImport\import\Logger $logger;

    public function __construct(
        EventoUser $evento_user,
        UserFacade $user_facade,
        DefaultUserSettings $default_user_settings,
        EventoUserPhotoImporter $photo_importer,
        \EventoImport\import\Logger $logger
    ) {
        $this->evento_user = $evento_user;
        $this->user_facade = $user_facade;
        $this->default_user_settings = $default_user_settings;
        $this->photo_importer = $photo_importer;
        $this->logger = $logger;
    }

    public function executeAction() : void
    {
        $ilias_user_object = $this->user_facade->createNewIliasUserObject();

        $this->setUserValuesFromImport($ilias_user_object, $this->evento_user);
        $ilias_user_object->create();

        //insert user data in table user_data
        $ilias_user_object->saveAsNew(false);

        $this->setForcedUserSettings($ilias_user_object, $this->default_user_settings);
        $this->setUsersDefaultSettings($ilias_user_object, $this->default_user_settings, $this->user_facade);

        // Assign user to global user role
        $this->synchronizeUserWithGlobalRoles(
            $ilias_user_object->getId(),
            $this->evento_user->getRoles(),
            $this->default_user_settings,
            $this->user_facade->rbacServices()
        );

        // Import and set User Photos
        $this->importAndSetUserPhoto($this->evento_user->getEventoId(), $ilias_user_object, $this->photo_importer, $this->user_facade);

        // Create map from evento to ilias user and log this to log-table
        $this->user_facade->eventoUserRepository()->addNewEventoIliasUser($this->evento_user, $ilias_user_object);
        $this->logger->logUserImport(
            \EventoImport\import\Logger::CREVENTO_USR_CREATED,
            $this->evento_user->getEventoId(),
            $this->evento_user->getLoginName(),
            ['api_data' => $this->evento_user->getDecodedApiData()]
        );
    }

    private function setUserValuesFromImport(\ilObjUser $ilias_user, EventoUser $evento_user) : void
    {
        $ilias_user->setLogin($evento_user->getLoginName());
        $ilias_user->setFirstname($evento_user->getFirstName());
        $ilias_user->setLastname($evento_user->getLastName());
        $ilias_user->setGender($this->convertEventoToIliasGenderChar($evento_user->getGender()));
        $ilias_user->setEmail($evento_user->getEmailList()[0]);
        $ilias_user->setSecondEmail($evento_user->getEmailList()[0]);
        $ilias_user->setTitle($ilias_user->getFullname());
        $ilias_user->setDescription($ilias_user->getEmail());
        $ilias_user->setMatriculation('Evento:' . $evento_user->getEventoId());
        $ilias_user->setExternalAccount($evento_user->getEventoId() . '@hslu.ch');
    }

    private function setUsersDefaultSettings(\ilObjUser $ilias_user_object, DefaultUserSettings $user_settings, UserFacade $user_facade) : void
    {
        $ilias_user_object->setActive(true);
        $ilias_user_object->setTimeLimitFrom($this->default_user_settings->getNow()->getTimestamp());
        if ($this->default_user_settings->getAccDurationAfterImport() == 0) {
            $ilias_user_object->setTimeLimitUnlimited(true);
        } else {
            $ilias_user_object->setTimeLimitUnlimited(false);
            $ilias_user_object->setTimeLimitUntil($this->default_user_settings->getAccDurationAfterImport()->getTimestamp());
        }

        $ilias_user_object->setAuthMode($this->default_user_settings->getAuthMode());

        // Set default prefs
        $ilias_user_object->setPref(
            'hits_per_page',
            (string) $this->default_user_settings->getDefaultHitsPerPage()
        ); //100 hits per page
        $ilias_user_object->setPref(
            'show_users_online',
            $this->default_user_settings->getDefaultShowUsersOnline()
        ); //nur Leute aus meinen Kursen zeigen

        // update mail preferences
        $user_facade->setMailPreferences(
            $ilias_user_object->getId(),
            $this->default_user_settings->getMailIncomingType()
        );
    }
}

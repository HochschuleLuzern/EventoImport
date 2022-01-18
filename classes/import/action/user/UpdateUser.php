<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\UserFacade;
use EventoImport\import\settings\DefaultUserSettings;
use EventoImport\communication\EventoUserPhotoImporter;

/**
 * Class UpdateUser
 * @package EventoImport\import\action\user
 */
class UpdateUser implements UserImportAction
{
    use ImportUserPhoto;
    use UserImportActionTrait;

    /** @var EventoUser */
    private EventoUser $evento_user;

    /** @var \ilObjUser */
    private \ilObjUser $ilias_user;

    /** @var UserFacade */
    private UserFacade $user_facade;

    /** @var DefaultUserSettings */
    private DefaultUserSettings $default_user_settings;

    /** @var EventoUserPhotoImporter */
    private EventoUserPhotoImporter $photo_importer;

    /** @var \ilEventoImportLogger */
    private \ilEventoImportLogger $logger;

    /**
     * UpdateUser constructor.
     * @param EventoUser              $evento_user
     * @param int                     $ilias_user
     * @param UserFacade              $user_facade
     * @param DefaultUserSettings     $default_user_settings
     * @param EventoUserPhotoImporter $photo_importer
     * @param \ilEventoImportLogger   $logger
     */
    public function __construct(
        EventoUser $evento_user,
        \ilObjUser $ilias_user,
        UserFacade $user_facade,
        DefaultUserSettings $default_user_settings,
        EventoUserPhotoImporter $photo_importer,
        \ilEventoImportLogger $logger
    ) {
        $this->evento_user = $evento_user;
        $this->ilias_user = $ilias_user;
        $this->user_facade = $user_facade;
        $this->default_user_settings = $default_user_settings;
        $this->photo_importer = $photo_importer;
        $this->logger = $logger;
    }

    /**
     * @throws \ilUserException
     * @throws \ilWACException
     */
    public function executeAction() : void
    {
        $this->user_facade->eventoUserRepository()->registerUserAsDelivered(
            $this->evento_user->getEventoId()
        );

        $changed_user_data = $this->updateUserData($this->ilias_user, $this->evento_user);

        $this->synchronizeUserWithGlobalRoles(
            $this->ilias_user->getId(),
            $this->evento_user->getRoles(),
            $this->default_user_settings,
            $this->user_facade->rbacServices()
        );

        if (!$this->user_facade->userHasPersonalPicture($this->ilias_user)) {
            $this->importAndSetUserPhoto($this->evento_user->getEventoId(), $this->ilias_user, $this->photo_importer, $this->user_facade);
        }

        $old_login = $this->ilias_user->getLogin();
        if ($old_login != $this->evento_user->getLoginName()) {
            $login_change_successful = $this->ilias_user->updateLogin($this->evento_user->getLoginName());
            if ($login_change_successful) {
                $this->user_facade->sendLoginChangedMail($this->ilias_user, $old_login, $this->evento_user);

                $this->logger->logUserImport(
                    \ilEventoImportLogger::CREVENTO_USR_RENAMED,
                    $this->evento_user->getEventoId(),
                    $this->evento_user->getLoginName(),
                    [
                            'api_data' => $this->evento_user->getDecodedApiData(),
                            'old_login' => $old_login,
                            'changed_user_data' => $changed_user_data
                        ]
                );
            } else {
                $this->logger->logException('UserImport - UpdateUser', 'Failed to change login from user with evento ID ' . $this->evento_user->getEventoId());
                $this->logger->logUserImport(
                    \ilEventoImportLogger::CREVENTO_USR_UPDATED,
                    $this->evento_user->getEventoId(),
                    $this->evento_user->getLoginName(),
                    [
                            'api_data' => $this->evento_user->getDecodedApiData(),
                            'changed_user_data' => $changed_user_data
                        ]
                );
            }
        } else {
            if (count($changed_user_data) > 0) {
                $this->logger->logUserImport(
                    \ilEventoImportLogger::CREVENTO_USR_UPDATED,
                    $this->evento_user->getEventoId(),
                    $this->evento_user->getLoginName(),
                    [
                        'api_data' => $this->evento_user->getDecodedApiData(),
                        'changed_user_data' => $changed_user_data
                    ]
                );
            }
        }
    }

    private function updateUserData(\ilObjUser $ilias_user, EventoUser $evento_user) : array
    {
        $changed_user_data = [];
        if ($ilias_user->getFirstname() != $evento_user->getFirstName()) {
            $changed_user_data['first_name'] = [
                'old' => $ilias_user->getFirstname(),
                'new' => $evento_user->getFirstName()
            ];
            $ilias_user->setFirstname($evento_user->getFirstName());
        }

        if ($ilias_user->getlastname() != $evento_user->getLastName()) {
            $changed_user_data['last_name'] = [
                'old' => $ilias_user->getFirstname(),
                'new' => $evento_user->getFirstName()
            ];
            $ilias_user->setLastname($evento_user->getLastName());
        }

        $received_gender_char = $this->convertEventoToIliasGenderChar($evento_user->getGender());
        if ($ilias_user->getGender() != $received_gender_char) {
            $changed_user_data['last_name'] = [
                'old' => $ilias_user->getGender(),
                'new' => $received_gender_char
            ];
            $ilias_user->setGender($received_gender_char);
        }

        if ($ilias_user->getMatriculation() != ('Evento:' . $evento_user->getEventoId())) {
            $changed_user_data['matriculation'] = [
                'old' => $ilias_user->getMatriculation(),
                'new' => 'Evento:' . $evento_user->getEventoId()
            ];
            $ilias_user->setMatriculation('Evento:' . $evento_user->getEventoId());
        }

        if ($ilias_user->getAuthMode() != $this->default_user_settings->getAuthMode()) {
            $changed_user_data['auth_mode'] = [
                'old' => $ilias_user->getAuthMode(),
                'new' => $this->default_user_settings->getAuthMode()
            ];
            $ilias_user->setAuthMode($this->default_user_settings->getAuthMode());
        }

        if (!$ilias_user->getActive()) {
            $changed_user_data['active'] = [
                'old' => false,
                'new' => true
            ];
            $ilias_user->setActive(true);
        }

        return $changed_user_data;
    }
}

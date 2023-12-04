<?php declare(strict_types = 1);

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\import\Logger;
use EventoImport\import\data_management\UserManager;

class UpdateUser implements UserImportAction
{
    private EventoUser $evento_user;
    private \ilObjUser $ilias_user;
    private UserManager $user_manager;
    private EventoUserPhotoImporter $photo_importer;
    private Logger $logger;

    public function __construct(
        EventoUser $evento_user,
        \ilObjUser $ilias_user,
        UserManager $user_manager,
        EventoUserPhotoImporter $photo_importer,
        Logger $logger
    ) {
        $this->evento_user = $evento_user;
        $this->ilias_user = $ilias_user;
        $this->user_manager = $user_manager;
        $this->photo_importer = $photo_importer;
        $this->logger = $logger;
    }

    public function executeAction() : void
    {
        $this->user_manager->registerEventoUserAsDelivered($this->evento_user);

        $changed_user_data = $this->user_manager->updateIliasUserFromEventoUser($this->ilias_user, $this->evento_user);
        $this->user_manager->updateSettingsForExistingUser($this->ilias_user);
        $this->user_manager->synchronizeIliasUserWithEventoRoles($this->ilias_user, $this->evento_user->getRoles());
        $this->user_manager->importAndSetUserPhoto($this->ilias_user, $this->evento_user->getEventoId(), $this->photo_importer);

        if ($this->evento_user->getEduId() === '') {
            $this->user_manager->addUserToGroupForEduIdMessage($this->ilias_user);
        }

        $old_login = $this->ilias_user->getLogin();
        if ($old_login != $this->evento_user->getLoginName()) {
            $login_change_successful = $this->ilias_user->updateLogin($this->evento_user->getLoginName());
            if ($login_change_successful) {
                $this->logger->logUserImport(
                    Logger::CREVENTO_USR_RENAMED,
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
                    Logger::CREVENTO_USR_UPDATED,
                    $this->evento_user->getEventoId(),
                    $this->evento_user->getLoginName(),
                    [
                        'api_data' => $this->evento_user->getDecodedApiData(),
                        'changed_user_data' => $changed_user_data
                    ]
                );
            }
        } else {
            $this->logger->logUserImport(
                Logger::CREVENTO_USR_UPDATED,
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

<?php declare(strict_types = 1);

namespace EventoImport\import\action\user;

use EventoImport\import\Logger;
use EventoImport\import\data_management\UserManager;

class RemoveConnectionToInstitution implements UserConnectionRemoveAction
{
    private \ilObjUser $ilias_user;
    private int $evento_id;
    private UserManager $user_manager;
    private Logger $logger;
    private int $log_info_code;
    private string $auth_mode;

    public function __construct(
        \ilObjUser $ilias_user,
        int $evento_id,
        UserManager $user_manager,
        Logger $logger
    ) {
        $this->ilias_user = $ilias_user;
        $this->evento_id = $evento_id;
        $this->user_manager = $user_manager;
        $this->logger = $logger;
        $this->log_info_code = Logger::CREVENTO_USR_REMOVED;
        $this->auth_mode = 'local';
    }

    public function executeAction() : void
    {
        $this->user_manager->removeUserAccessesAfterLeavingInstitution(
            $this->ilias_user
        );

        $this->user_manager->deleteEventoUserToIliasUserConnection($this->evento_id);

        $this->logger->logUserImport(
            $this->log_info_code,
            $this->evento_id,
            $this->ilias_user->getLogin(),
            [
                'ilias_user_id' => $this->ilias_user->getId(),
            ]
        );
    }
}

<?php declare(strict_types = 1);

namespace EventoImport\import\action\user;

use EventoImport\import\data_management\ilias_core_service\IliasUserServices;
use EventoImport\import\Logger;
use EventoImport\import\data_management\repository\IliasEventoUserRepository;
use EventoImport\import\data_management\UserManager;

class ConvertAndDeactivateUser implements UserDeleteAction
{
    private \ilObjUser $ilias_user;
    private int $evento_id;
    private string $converted_auth_mode;
    private UserManager $user_manager;
    private Logger $logger;
    private int $log_info_code;
    private string $auth_mode;

    public function __construct(\ilObjUser $ilias_user, int $evento_id, string $converted_auth_mode, UserManager $user_manager, Logger $logger)
    {
        $this->ilias_user = $ilias_user;
        $this->evento_id = $evento_id;
        $this->converted_auth_mode = $converted_auth_mode;
        $this->user_manager = $user_manager;
        $this->logger = $logger;
        $this->log_info_code = Logger::CREVENTO_USR_CONVERTED;
        $this->auth_mode = 'local';
    }

    public function executeAction() : void
    {
        $this->ilias_user->setAuthMode('local');
        $this->ilias_user->setTimeLimitUntil(date("Y-m-d H:i:s"));
        $this->ilias_user->update();

        $this->user_manager->deleteEventoUserToIliasUserConnection($this->evento_id);

        $this->logger->logUserImport(
            $this->log_info_code,
            $this->evento_id,
            $this->ilias_user->getLogin(),
            [
                'ilias_user_id' => $this->ilias_user->getId(),
                'new_auth_mode' => $this->auth_mode,
                'deactivate_after_convert' => true,
            ]
        );
    }
}

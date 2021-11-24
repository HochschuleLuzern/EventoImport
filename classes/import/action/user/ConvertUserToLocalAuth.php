<?php

namespace EventoImport\import\action\user;

use EventoImport\import\action\EventoImportAction;
use EventoImport\import\db\UserFacade;

class ConvertUserToLocalAuth implements EventoImportAction
{
    /**
     * @var \ilObjUser
     */
    private $ilias_user;
    private $evento_id;
    private $converted_auth_mode;
    private $user_facade;
    private $logger;
    private $log_info_code;
    private $auth_mode;

    public function __construct(\ilObjUser $ilias_user, int $evento_id, string $converted_auth_mode, UserFacade $user_facade, \ilEventoImportLogger $logger)
    {
        $this->ilias_user = $ilias_user;
        $this->evento_id = $evento_id;
        $this->converted_auth_mode = $converted_auth_mode;
        $this->user_facade = $user_facade;
        $this->logger = $logger;
        $this->log_info_code = \ilEventoImportLogger::CREVENTO_USR_CONVERTED;
        $this->auth_mode = 'local';
    }

    public function executeAction()
    {
        $this->ilias_user->setAuthMode('local');
        $this->ilias_user->update();

        $this->user_facade->deleteEventoIliasUserConnection($this->evento_id, $this->ilias_user);

        $this->logger->logUserImport(
            $this->log_info_code,
            $this->evento_id,
            $this->ilias_user->getLogin(),
            [
                'ilias_user_id' => $this->ilias_user->getId(),
                'new_auth_mode' => $this->auth_mode,
                'deactivate_after_convert' => false,
            ]
        );
    }
}

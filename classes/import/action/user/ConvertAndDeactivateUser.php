<?php

namespace EventoImport\import\action\user;

use EventoImport\import\db\UserFacade;
use EventoImport\import\action\EventoImportAction;

class ConvertAndDeactivateUser implements EventoImportAction
{
    /** @var \ilObjUser */
    private \ilObjUser $ilias_user;

    /** @var int */
    private int $evento_id;

    /** @var string */
    private string $converted_auth_mode;

    /** @var UserFacade */
    private UserFacade $user_facade;

    /** @var \ilEventoImportLogger */
    private \ilEventoImportLogger $logger;

    /** @var int */
    private int $log_info_code;

    /** @var string */
    private string $auth_mode;

    /**
     * ConvertAndDeactivateUser constructor.
     * @param \ilObjUser            $ilias_user
     * @param int                   $evento_id
     * @param string                $converted_auth_mode
     * @param UserFacade            $user_facade
     * @param \ilEventoImportLogger $logger
     */
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

    public function executeAction() : void
    {
        $this->ilias_user->setAuthMode('local');
        $this->ilias_user->setTimeLimitUntil(date("Y-m-d H:i:s"));
        $this->ilias_user->update();

        $this->user_facade->deleteEventoIliasUserConnection($this->evento_id, $this->ilias_user);

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

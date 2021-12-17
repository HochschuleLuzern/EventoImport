<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\UserFacade;

/**
 * Class UserImportAction
 * @package EventoImport\import\action\user
 */
abstract class UserImportAction implements \EventoImport\import\action\EventoImportAction
{
    /** @var EventoUser */
    protected EventoUser $evento_user;

    /** @var UserFacade */
    protected UserFacade $user_facade;

    /** @var \ilEventoImportLogger */
    protected \ilEventoImportLogger $logger;

    /**
     * UserImportAction constructor.
     * @param EventoUser            $evento_user
     * @param UserFacade            $user_facade
     * @param \ilEventoImportLogger $logger
     */
    public function __construct(EventoUser $evento_user, UserFacade $user_facade, \ilEventoImportLogger $logger)
    {
        $this->evento_user = $evento_user;
        $this->user_facade = $user_facade;
        $this->logger = $logger;
    }

    /**
     * @param string $evento_gender_char
     * @return string
     */
    protected function convertEventoToIliasGenderChar(string $evento_gender_char) : string
    {
        switch (strtolower($evento_gender_char)) {
            case 'f':
                return 'f';
            case 'm':
                return 'm';
            case 'x':
            default:
                return 'n';
        }
    }
}

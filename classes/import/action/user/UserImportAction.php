<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\UserFacade;

abstract class UserImportAction implements \EventoImport\import\action\EventoImportAction
{
    protected $evento_user;
    protected $user_facade;
    protected $logger;

    public function __construct(EventoUser $evento_user, UserFacade $user_facade, \ilEventoImportLogger $logger)
    {
        $this->evento_user = $evento_user;
        $this->user_facade = $user_facade;
        $this->logger = $logger;
    }
}
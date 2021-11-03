<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\UserFacade;
use EventoImport\import\settings\DefaultUserSettings;
use ILIAS\UI\Component\Test\Renderer;

class UserActionFactory
{
    private $user_facade;
    private $default_user_settings;

    function __construct(UserFacade $user_facade, DefaultUserSettings $default_user_settings)
    {
        $this->user_facade = $user_facade;
        $this->default_user_settings = $default_user_settings;
    }

    public function buildCreateAction(EventoUser $evento_user) : Create
    {
        return new Create($evento_user, $this->user_facade, $this->default_user_settings);
    }

    public function buildUpdateAction(EventoUser $evento_user, $ilias_user) : Update
    {
        return new Update($evento_user, $this->user_facade, $this->default_user_settings);
    }

    public function buildRenameExistingAndCreateNewAction(EventoUser $evento_user, $old_ilias_user) : RenameExistingCreateNew
    {
        return new RenameExistingCreateNew(
            $this->buildCreateAction($evento_user),
            $old_ilias_user,
            $this->user_facade,
        );
    }
}
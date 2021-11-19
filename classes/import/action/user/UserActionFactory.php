<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\action\ReportError;
use EventoImport\import\db\UserFacade;
use EventoImport\import\settings\DefaultUserSettings;
use ILIAS\UI\Component\Test\Renderer;

class UserActionFactory
{
    private $user_facade;
    private $default_user_settings;

    public function __construct(UserFacade $user_facade, DefaultUserSettings $default_user_settings, \ilEventoImportLogger $logger)
    {
        $this->logger = $logger;
        $this->user_facade = $user_facade;
        $this->default_user_settings = $default_user_settings;
    }

    public function buildCreateAction(EventoUser $evento_user) : Create
    {
        return new Create($evento_user, $this->user_facade, $this->default_user_settings, $this->logger);
    }

    public function buildUpdateAction(EventoUser $evento_user, int $ilias_user_id) : Update
    {
        return new Update($evento_user, $ilias_user_id, $this->user_facade, $this->default_user_settings, $this->logger);
    }

    public function buildRenameExistingAndCreateNewAction(EventoUser $evento_user, \ilObjUser $old_ilias_user, string $found_by) : RenameExistingCreateNew
    {
        return new RenameExistingCreateNew(
            $this->buildCreateAction($evento_user),
            $evento_user,
            $old_ilias_user,
            $found_by,
            $this->user_facade,
            $this->logger
        );
    }

    private function convertEventoUserToBasicLogData(EventoUser $evento_user)
    {
        // How the data is logged (before refactoring)
        // '{$data['id']}', '{$data['loginName']}', ".$this->ilDB->quote(serialize($data), 'text').", '".date("Y-m-d H:i:s")."', '$result')
        return [
            'id' => $evento_user->getEventoId(),
            'loginName' => $evento_user->getLoginName(),
            'emailList' => $evento_user->getEmailList(),
            'firstName' => $evento_user->getFirstName(),
            'lastName' => $evento_user->getLastName(),
            'gender' => $evento_user->getGender(),
            'roles ' => $evento_user->getRoles()
        ];
    }

    public function buildReportConflict(EventoUser $evento_user)
    {
        $log_data = $this->convertEventoUserToBasicLogData($evento_user);
        return new ReportUserImportError(
            \ilEventoImportLogger::CREVENTO_USR_NOTICE_CONFLICT,
            $evento_user->getEventoId(),
            $evento_user->getLoginName(),
            $evento_user->getDecodedApiData(),
            $this->logger
        );
    }

    public function buildReportError(EventoUser $evento_user)
    {
        return new ReportUserImportError(
            \ilEventoImportLogger::CREVENTO_USR_ERROR_ERROR,
            $evento_user->getEventoId(),
            $evento_user->getLoginName(),
            $evento_user->getDecodedApiData(),
            $this->logger
        );
    }
}

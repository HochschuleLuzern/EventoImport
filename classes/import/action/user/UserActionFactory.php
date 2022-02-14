<?php declare(strict_types = 1);

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\action\ReportDatasetWithoutAction;
use EventoImport\import\db\UserFacade;
use EventoImport\import\settings\DefaultUserSettings;
use EventoImport\import\action\EventoImportAction;
use EventoImport\communication\EventoUserPhotoImporter;

class UserActionFactory
{
    private UserFacade $user_facade;
    private DefaultUserSettings $default_user_settings;
    private EventoUserPhotoImporter $photo_importer;
    private \EventoImport\import\Logger $logger;

    public function __construct(UserFacade $user_facade, DefaultUserSettings $default_user_settings, EventoUserPhotoImporter $photo_importer, \EventoImport\import\Logger $logger)
    {
        $this->user_facade = $user_facade;
        $this->default_user_settings = $default_user_settings;
        $this->photo_importer = $photo_importer;
        $this->logger = $logger;
    }

    public function buildCreateAction(EventoUser $evento_user) : CreateUser
    {
        return new CreateUser($evento_user, $this->user_facade, $this->default_user_settings, $this->photo_importer, $this->logger);
    }

    public function buildUpdateAction(EventoUser $evento_user, int $ilias_user_id) : UpdateUser
    {
        return new UpdateUser(
            $evento_user,
            $this->user_facade->getExistingIliasUserObject($ilias_user_id),
            $this->user_facade,
            $this->default_user_settings,
            $this->photo_importer,
            $this->logger
        );
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

    public function buildReportConflict(EventoUser $evento_user) : ReportDatasetWithoutAction
    {
        return new ReportUserImportDatasetWithoutAction(
            \EventoImport\import\Logger::CREVENTO_USR_NOTICE_CONFLICT,
            $evento_user->getEventoId(),
            $evento_user->getLoginName(),
            $evento_user->getDecodedApiData(),
            $this->logger
        );
    }

    public function buildReportError(EventoUser $evento_user)
    {
        return new ReportUserImportDatasetWithoutAction(
            \EventoImport\import\Logger::CREVENTO_USR_ERROR_ERROR,
            $evento_user->getEventoId(),
            $evento_user->getLoginName(),
            $evento_user->getDecodedApiData(),
            $this->logger
        );
    }

    public function buildConvertUserAuth(\ilObjUser $ilias_user_object, int $evento_id) : EventoImportAction
    {
        return new ConvertUserToLocalAuth(
            $ilias_user_object,
            $evento_id,
            'local',
            $this->user_facade,
            $this->logger
        );
    }

    public function buildConvertAuthAndDeactivateUser(\ilObjUser $ilias_user_object, int $evento_id) : EventoImportAction
    {
        return new ConvertAndDeactivateUser(
            $ilias_user_object,
            $evento_id,
            'local',
            $this->user_facade,
            $this->logger
        );
    }
}

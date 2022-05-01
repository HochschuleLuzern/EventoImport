<?php declare(strict_types = 1);

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\action\ReportDatasetWithoutAction;
use EventoImport\import\action\EventoImportAction;
use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\import\Logger;
use EventoImport\import\data_management\UserManager;

class UserActionFactory
{
    private UserManager $user_manager;
    private EventoUserPhotoImporter $photo_importer;
    private Logger $logger;

    public function __construct(UserManager $user_manager, EventoUserPhotoImporter $photo_importer, Logger $logger)
    {
        $this->user_manager = $user_manager;
        $this->photo_importer = $photo_importer;
        $this->logger = $logger;
    }

    public function buildCreateAction(EventoUser $evento_user) : CreateUser
    {
        return new CreateUser(
            $evento_user,
            $this->user_manager,
            $this->photo_importer,
            $this->logger
        );
    }

    public function buildUpdateAction(EventoUser $evento_user, int $ilias_user_id) : UpdateUser
    {
        return new UpdateUser(
            $evento_user,
            $this->user_manager->getExistingIliasUserObjectById($ilias_user_id),
            $this->user_manager,
            $this->photo_importer,
            $this->logger
        );
    }

    public function buildRenameExistingAndCreateNewAction(
        EventoUser $evento_user,
        \ilObjUser $old_ilias_user,
        string $found_by
    ) : RenameExistingUserExecuteNextImportAction {
        return new RenameExistingUserExecuteNextImportAction(
            $this->buildCreateAction($evento_user),
            $evento_user,
            $old_ilias_user,
            $found_by,
            $this->logger
        );
    }

    public function buildRenameExistingAndUpdateDeliveredAction(
        EventoUser $evento_user,
        int $user_id_of_found_delivered_user,
        \ilObjUser $old_ilias_user,
        string $found_by
    ) {
        return new RenameExistingUserExecuteNextImportAction(
            $this->buildUpdateAction($evento_user, $user_id_of_found_delivered_user),
            $evento_user,
            $old_ilias_user,
            $found_by,
            $this->logger
        );
    }

    public function buildReportConflict(EventoUser $evento_user) : ReportDatasetWithoutAction
    {
        return new ReportUserImportDatasetWithoutAction(
            Logger::CREVENTO_USR_NOTICE_CONFLICT,
            $evento_user->getEventoId(),
            $evento_user->getLoginName(),
            $evento_user->getDecodedApiData(),
            $this->logger
        );
    }

    public function buildReportError(EventoUser $evento_user)
    {
        return new ReportUserImportDatasetWithoutAction(
            Logger::CREVENTO_USR_ERROR_ERROR,
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
            $this->user_manager,
            $this->logger
        );
    }

    public function buildConvertAuthAndDeactivateUser(
        \ilObjUser $ilias_user_object,
        int $evento_id
    ) : EventoImportAction {
        return new ConvertAndDeactivateUser(
            $ilias_user_object,
            $evento_id,
            'local',
            $this->user_manager,
            $this->logger
        );
    }
}

<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\action\ReportDatasetWithoutAction;
use EventoImport\import\db\UserFacade;
use EventoImport\import\settings\DefaultUserSettings;
use EventoImport\import\action\EventoImportAction;
use EventoImport\communication\EventoUserPhotoImporter;

class UserActionFactory
{
    /** @var UserFacade */
    private UserFacade $user_facade;

    /** @var DefaultUserSettings */
    private DefaultUserSettings $default_user_settings;

    /** @var EventoUserPhotoImporter */
    private EventoUserPhotoImporter $photo_importer;

    /** @var \ilEventoImportLogger */
    private \ilEventoImportLogger $logger;

    /**
     * UserActionFactory constructor.
     * @param UserFacade              $user_facade
     * @param DefaultUserSettings     $default_user_settings
     * @param EventoUserPhotoImporter $photo_importer
     * @param \ilEventoImportLogger   $logger
     */
    public function __construct(UserFacade $user_facade, DefaultUserSettings $default_user_settings, EventoUserPhotoImporter $photo_importer, \ilEventoImportLogger $logger)
    {
        $this->user_facade = $user_facade;
        $this->default_user_settings = $default_user_settings;
        $this->photo_importer = $photo_importer;
        $this->logger = $logger;
    }

    /**
     * @param EventoUser $evento_user
     * @return CreateUser
     */
    public function buildCreateAction(EventoUser $evento_user) : CreateUser
    {
        return new CreateUser($evento_user, $this->user_facade, $this->default_user_settings, $this->photo_importer, $this->logger);
    }

    /**
     * @param EventoUser $evento_user
     * @param int        $ilias_user_id
     * @return UpdateUser
     */
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

    /**
     * @param EventoUser $evento_user
     * @param \ilObjUser $old_ilias_user
     * @param string     $found_by
     * @return RenameExistingCreateNew
     */
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

    /**
     * @param EventoUser $evento_user
     * @return array
     */
    private function convertEventoUserToBasicLogData(EventoUser $evento_user) : array
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

    /**
     * @param EventoUser $evento_user
     * @return ReportUserImportDatasetWithoutAction
     */
    public function buildReportConflict(EventoUser $evento_user) : ReportDatasetWithoutAction
    {
        $log_data = $this->convertEventoUserToBasicLogData($evento_user);
        return new ReportUserImportDatasetWithoutAction(
            \ilEventoImportLogger::CREVENTO_USR_NOTICE_CONFLICT,
            $evento_user->getEventoId(),
            $evento_user->getLoginName(),
            $evento_user->getDecodedApiData(),
            $this->logger
        );
    }

    public function buildReportError(EventoUser $evento_user)
    {
        return new ReportUserImportDatasetWithoutAction(
            \ilEventoImportLogger::CREVENTO_USR_ERROR_ERROR,
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

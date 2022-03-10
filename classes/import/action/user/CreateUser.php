<?php declare(strict_types = 1);

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\Logger;
use EventoImport\import\UserManager;
use EventoImport\communication\EventoUserPhotoImporter;

class CreateUser implements UserImportAction
{
    private EventoUser $evento_user;
    private UserManager $user_manager;
    private EventoUserPhotoImporter $photo_importer;
    private Logger $logger;

    public function __construct(
        EventoUser $evento_user,
        UserManager $user_manager,
        EventoUserPhotoImporter $photo_importer,
        Logger $logger
    ) {
        $this->evento_user = $evento_user;
        $this->user_manager = $user_manager;
        $this->photo_importer = $photo_importer;
        $this->logger = $logger;
    }

    public function executeAction() : void
    {
        $ilias_user_object = $this->user_manager->createAndSetupNewIliasUser($this->evento_user);

        $this->user_manager->synchronizeIliasUserWithEventoRoles($ilias_user_object, $this->evento_user->getRoles());
        $this->user_manager->importAndSetUserPhoto($ilias_user_object, $this->evento_user->getEventoId(), $this->photo_importer);

        $this->logger->logUserImport(
            Logger::CREVENTO_USR_CREATED,
            $this->evento_user->getEventoId(),
            $this->evento_user->getLoginName(),
            ['api_data' => $this->evento_user->getDecodedApiData()]
        );
    }
}

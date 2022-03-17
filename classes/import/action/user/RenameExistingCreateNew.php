<?php declare(strict_types = 1);

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\Logger;

class RenameExistingCreateNew implements UserImportAction
{
    private CreateUser $create_action;
    private EventoUser $new_evento_user;
    private \ilObjUser $old_user_to_rename;
    private string $found_by;
    private Logger $logger;

    public function __construct(CreateUser $create_action, EventoUser $new_evento_user, \ilObjUser $old_user_to_rename, string $found_by, Logger $logger)
    {
        $this->new_evento_user = $new_evento_user;
        $this->create_action = $create_action;
        $this->old_user_to_rename = $old_user_to_rename;
        $this->found_by = $found_by;
        $this->logger = $logger;
    }

    public function executeAction() : void
    {
        $this->renameExistingUser($this->old_user_to_rename);
        $this->create_action->executeAction();
    }

    private function renameExistingUser(\ilObjUser $old_user) : void
    {
        $old_user_evento_id = trim(substr($old_user->getMatriculation() ?? '', 8));
        $changed_user_data['user_id'] = $old_user->getId();
        $changed_user_data['EvtID'] = $old_user_evento_id;
        $changed_user_data['new_user_info'] = $this->new_evento_user->getEventoId();
        $changed_user_data['found_by'] = $this->found_by;

        $data['Login'] = date('Ymd') . '_' . $old_user->getLogin();
        $data['FirstName'] = $old_user->getFirstname();
        $data['LastName'] = $old_user->getLastname();
        $data['Gender'] = $old_user->getGender();
        $data['Matriculation'] = $old_user->getMatriculation();

        $old_user->setActive(false);
        $old_user->update();
        $old_user->setLogin($data['Login']);
        $old_user->updateLogin($old_user->getLogin());

        $this->logger->logUserImport(
            Logger::CREVENTO_USR_RENAMED,
            $old_user_evento_id,
            $old_user->getLogin(),
            ['changed_user_data' => $changed_user_data]
        );
    }
}

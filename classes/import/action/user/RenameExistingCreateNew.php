<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\UserFacade;

class RenameExistingCreateNew extends UserAction
{
    private $create_action;
    private $found_by;
    /** @var \ilObjUser */
    private $old_user_to_rename;

    public function __construct(Create $create_action, EventoUser $new_evento_user, \ilObjUser $old_user_to_rename, string $found_by, UserFacade $user_facade, \ilEventoImportLogger $logger)
    {
        parent::__construct($new_evento_user, $user_facade, $logger);

        $this->create_action = $create_action;
        $this->old_user_to_rename = $old_user_to_rename;
        $this->found_by = $found_by;
    }

    private function renameExistingUser(\ilObjUser $old_user)
    {
        /*
        $userObj = $this->user_facade->getExistingIliasUserObject($old_user_id);
        $userObj->read();
*/
        $old_user_evento_id = trim(substr($old_user->getMatriculation(), 8));
        $changed_user_data['user_id'] = $old_user->getId();
        $changed_user_data['EvtID'] = $old_user_evento_id;
        $changed_user_data['new_user_info'] = $this->evento_user->getEventoId();
        $changed_user_data['found_by'] = $this->found_by;

        $data['Login'] = date('Ymd') . '_' . $userObj->getLogin();
        $data['FirstName'] = $userObj->getFirstname();
        $data['LastName'] = $userObj->getLastname();
        $data['Gender'] = $userObj->getGender();
        $data['Matriculation'] = $userObj->getMatriculation();

        $userObj->setActive(false);
        $userObj->update();
        $userObj->setLogin($data['Login']);
        $userObj->updateLogin($userObj->getLogin());

        $this->logger->logUserImport(
            \ilEventoImportLogger::CREVENTO_USR_RENAMED,
            $old_user_evento_id,
            $old_user->getLogin(),
            ['changed_user_data' => $changed_user_data]
        );
    }

    public function executeAction()
    {
        $this->renameExistingUser($this->old_user_to_rename);
        $this->create_action->executeAction();
    }
}

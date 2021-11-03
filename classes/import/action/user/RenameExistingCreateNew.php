<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\UserFacade;

class RenameExistingCreateNew extends UserAction
{
    private $create_action;

    public function __construct(Create $create_action, EventoUser $existing_user, UserFacade $user_facade)
    {
        parent::__construct($existing_user, $user_facade);

        $this->create_action = $create_action;
    }

    private function renameExistingUser($old_user_id)
    {
        $userObj = $this->user_facade->getExistingIliasUserObject($old_user_id);
        $userObj->read();

        $changed_user_data['user_id'] = $data['ids_by_email'][0];
        $changed_user_data['EvtID'] = trim(substr($objByEmail->getMatriculation(), 8));
        $changed_user_data['new_user_info'] = 'EventoID: '.$data['EvtID'];
        $changed_user_data['found_by'] = 'E-Mail';

        $changed_user_data['user_id'] = $data['id_by_login'];
        $changed_user_data['EvtID'] = trim(substr($objByLogin->getMatriculation(), 8));
        $changed_user_data['new_user_info'] = 'EventoID: '.$data['EvtID'];
        $changed_user_data['found_by'] = 'Login';

        $data['Login'] = date('Ymd').'_'.$userObj->getLogin();
        $data['FirstName'] = $userObj->getFirstname();
        $data['LastName'] = $userObj->getLastname();
        $data['Gender'] = $userObj->getGender();
        $data['Matriculation'] = $userObj->getMatriculation();

        $userObj->setActive(false);
        $userObj->update();
        $userObj->setLogin($data['Login']);
        $userObj->updateLogin($userObj->getLogin());
        $this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_RENAMED, $data);
    }

    public function executeAction()
    {
        $this->renameExistingUser();
        $this->create_action->executeAction();
    }
}
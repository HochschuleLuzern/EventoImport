<?php

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\UserFacade;

/**
 * Class RenameExistingCreateNew
 * @package EventoImport\import\action\user
 */
class RenameExistingCreateNew extends UserImportAction
{
    /** @var CreateUser */
    private CreateUser $create_action;

    /** @var string */
    private string $found_by;

    /** @var \ilObjUser */
    private \ilObjUser $old_user_to_rename;

    /**
     * RenameExistingCreateNew constructor.
     * @param CreateUser            $create_action
     * @param EventoUser            $new_evento_user
     * @param \ilObjUser            $old_user_to_rename
     * @param string                $found_by
     * @param UserFacade            $user_facade
     * @param \ilEventoImportLogger $logger
     */
    public function __construct(CreateUser $create_action, EventoUser $new_evento_user, \ilObjUser $old_user_to_rename, string $found_by, UserFacade $user_facade, \ilEventoImportLogger $logger)
    {
        parent::__construct($new_evento_user, $user_facade, $logger);

        $this->create_action = $create_action;
        $this->old_user_to_rename = $old_user_to_rename;
        $this->found_by = $found_by;
    }

    /**
     * @param \ilObjUser $old_user
     * @throws \ilUserException
     */
    private function renameExistingUser(\ilObjUser $old_user)
    {
        $old_user_evento_id = trim(substr($old_user->getMatriculation(), 8));
        $changed_user_data['user_id'] = $old_user->getId();
        $changed_user_data['EvtID'] = $old_user_evento_id;
        $changed_user_data['new_user_info'] = $this->evento_user->getEventoId();
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
            \ilEventoImportLogger::CREVENTO_USR_RENAMED,
            $old_user_evento_id,
            $old_user->getLogin(),
            ['changed_user_data' => $changed_user_data]
        );
    }

    public function executeAction() : void
    {
        $this->renameExistingUser($this->old_user_to_rename);
        $this->create_action->executeAction();
    }
}

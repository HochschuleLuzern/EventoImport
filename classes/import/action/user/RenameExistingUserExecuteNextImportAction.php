<?php declare(strict_types = 1);

namespace EventoImport\import\action\user;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\Logger;

class RenameExistingUserExecuteNextImportAction implements UserImportAction
{
    private UserImportAction $next_action;
    private EventoUser $new_evento_user;

    /**
     * @param array<\ilObjUser> $old_users_to_rename
     */
    private array $old_users_to_rename;
    private string $found_by;
    private Logger $logger;

    /**
     * @param array<\ilObjUser> $old_users_to_rename
     */
    public function __construct(
        UserImportAction $next_action,
        EventoUser $new_evento_user,
        array $old_users_to_rename,
        string $found_by,
        Logger $logger
    ) {
        $this->new_evento_user = $new_evento_user;
        $this->next_action = $next_action;
        $this->old_users_to_rename = $old_users_to_rename;
        $this->found_by = $found_by;
        $this->logger = $logger;
    }

    public function executeAction() : void
    {
        foreach ($this->old_users_to_rename as $old_user_to_rename) {
            $this->renameExistingUser($old_user_to_rename);
        }
        $this->next_action->executeAction();
    }

    private function renameExistingUser(\ilObjUser $old_user) : void
    {
        $old_user_evento_id = trim(mb_substr($old_user->getMatriculation(), 7));
        $changed_user_data['user_id'] = $old_user->getId();
        $changed_user_data['EvtID'] = $old_user_evento_id;
        $changed_user_data['new_user_info'] = $this->new_evento_user->getEventoId();
        $changed_user_data['found_by'] = $this->found_by;

        $login = $old_user->getLogin();
        if ($old_user->getLogin() === $this->new_evento_user->getLoginName()) {
            $login = date('Ymd') . '_' . $login;
        }
        $changed_user_data['Login'] =  $login;
        $changed_user_data['FirstName'] = $old_user->getFirstname();
        $changed_user_data['LastName'] = $old_user->getLastname();
        $changed_user_data['Gender'] = $old_user->getGender();
        $changed_user_data['External Account'] = '';
        $changed_user_data['Matriculation'] = $old_user->getMatriculation();

        $old_user->setActive(false);
        $old_user->setExternalAccount('');
        $old_user->update();
        $old_user->setLogin($changed_user_data['Login']);
        $old_user->updateLogin($old_user->getLogin());

        $this->logger->logUserImport(
            Logger::CREVENTO_USR_RENAMED,
            $old_user_evento_id,
            $old_user->getLogin(),
            ['changed_user_data' => $changed_user_data]
        );
    }
}

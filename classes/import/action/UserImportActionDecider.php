<?php declare(strict_types = 1);

namespace EventoImport\import\action;

use EventoImport\import\data_management\ilias_core_service\IliasUserServices;
use EventoImport\import\action\user\UserActionFactory;
use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\data_management\repository\IliasEventoUserRepository;

class UserImportActionDecider
{
    private IliasUserServices $ilias_user_service;
    private UserActionFactory $action_factory;
    private IliasEventoUserRepository $evento_user_repo;

    public function __construct(IliasUserServices $ilias_user_service, IliasEventoUserRepository $evento_user_repo, UserActionFactory $action_factory)
    {
        $this->ilias_user_service = $ilias_user_service;
        $this->evento_user_repo = $evento_user_repo;
        $this->action_factory = $action_factory;
    }

    private function addUserToEventoIliasMappingTable(
        EventoUser $evento_user,
        int $ilias_user_id
    ) {
        $ilias_user = $this->ilias_user_service->getExistingIliasUserObjectById($ilias_user_id);
        $this->evento_user_repo->addNewEventoIliasUserByEventoUser($evento_user, $ilias_user, IliasEventoUserRepository::TYPE_HSLU_AD);
    }

    public function determineImportAction(EventoUser $evento_user) : EventoImportAction
    {
        $matched_user_id = $this->evento_user_repo->getIliasUserIdByEventoId($evento_user->getEventoId());

        if (is_null($matched_user_id)) {
            return $this->matchToIliasUsersAndDetermineAction($evento_user);
        }

        $current_login_of_matched_user = $this->ilias_user_service->getLoginByUserId($matched_user_id);
        // Check if login of delivered user has changed AND the changed login name is already taken
        if ($current_login_of_matched_user != $evento_user->getLoginName()
            && $this->ilias_user_service->getUserIdByLogin($evento_user->getLoginName()) > 0
        ) {
            $id_of_user_to_rename = $this->ilias_user_service->getUserIdByLogin($evento_user->getLoginName());
            $user_to_rename = $this->ilias_user_service->getExistingIliasUserObjectById($id_of_user_to_rename);
            return $this->action_factory->buildRenameExistingAndUpdateDeliveredAction(
                $evento_user,
                $matched_user_id,
                $user_to_rename,
                'login'
            );
        }

        return $this->action_factory->buildUpdateAction($evento_user, $matched_user_id);
    }

    private function matchToIliasUsersAndDetermineAction(EventoUser $evento_user) : EventoImportAction
    {
        $data['id_by_login'] = $this->ilias_user_service->getUserIdByLogin($evento_user->getLoginName());
        $data['ids_by_matriculation'] = $this->ilias_user_service->getUserIdsByEventoId($evento_user->getEventoId());
        $data['ids_by_email'] = $this->ilias_user_service->getUserIdsByEmailAddresses($evento_user->getEmailList());


        if (count($data['ids_by_matriculation']) == 0 &&
            $data['id_by_login'] == 0 &&
            count($data['ids_by_email']) == 0) {

            // We couldn't find a user account neither by
            // matriculation, login nor e-mail
            // --> Insert new user account.
            return $this->action_factory->buildCreateAction($evento_user);
        }

        if (count($data['ids_by_matriculation']) == 0 &&
            $data['id_by_login'] != 0) {

            // We couldn't find a user account by matriculation, but we found
            // one by login.

            $user_obj_by_login = $this->ilias_user_service->getExistingIliasUserObjectById($data['id_by_login']);

            if (substr($user_obj_by_login->getMatriculation() ?? '', 0, 7) == 'Evento:') {
                // The user account by login has a different evento number.
                // --> Rename and deactivate conflicting account
                //     and then insert new user account.
                return $this->action_factory->buildRenameExistingAndCreateNewAction(
                    $evento_user,
                    $user_obj_by_login,
                    'login'
                );
            }

            if ($user_obj_by_login->getMatriculation() == $user_obj_by_login->getLogin()) {
                // The user account by login has a matriculation from ldap
                // --> Update user account.
                $result = $this->action_factory->buildUpdateAction(
                    $evento_user,
                    $data['id_by_login']
                );
                $this->addUserToEventoIliasMappingTable($evento_user, $data['id_by_login']);
                return $result;
            }

            if (strlen($user_obj_by_login->getMatriculation() ?? '') != 0) {
                // The user account by login has a matriculation of some kind
                // --> Bail
                return $this->action_factory->buildReportConflict($evento_user);
            }

            // The user account by login has no matriculation
            // --> Update user account.
            $result = $this->action_factory->buildUpdateAction(
                $evento_user,
                $data['id_by_login']
            );
            $this->addUserToEventoIliasMappingTable($evento_user, $data['id_by_login']);
            return $result;
        }

        if (count($data['ids_by_matriculation']) == 0 &&
            $data['id_by_login'] == 0 &&
            count($data['ids_by_email']) == 1) {

            // We couldn't find a user account by matriculation, but we found
            // one by e-mail.
            $user_obj_by_mail = $this->ilias_user_service->getExistingIliasUserObjectById($data['ids_by_email'][0]);

            if (substr($user_obj_by_mail->getMatriculation() ?? '', 0, 7) == 'Evento:') {
                // The user account by e-mail has a different evento number.
                // --> Rename and deactivate conflicting account
                //     and then insert new user account.
                return $this->action_factory->buildRenameExistingAndCreateNewAction(
                    $evento_user,
                    $user_obj_by_mail,
                    'mail'
                );
            }

            if (strlen($user_obj_by_mail->getMatriculation() ?? '') != 0) {
                // The user account by login has a matriculation of some kind
                // --> Bail
                return $this->action_factory->buildReportConflict($evento_user);
            }

            // The user account by login has no matriculation
            // --> Update user account.
            $result = $this->action_factory->buildUpdateAction(
                $evento_user,
                $data['ids_by_email'][0]
            );
            $this->addUserToEventoIliasMappingTable($evento_user, $data['ids_by_email'][0]);
            return $result;
        }

        if (count($data['ids_by_matriculation']) == 1 &&
            $data['id_by_login'] != 0 &&
            in_array($data['id_by_login'], $data['ids_by_matriculation'])) {

            // We found a user account by matriculation and by login.
            // --> Update user account.
            $result = $this->action_factory->buildUpdateAction(
                $evento_user,
                $data['ids_by_matriculation'][0]
            );
            $this->addUserToEventoIliasMappingTable($evento_user, $data['ids_by_matriculation'][0]);
            return $result;
        }

        if (count($data['ids_by_matriculation']) == 1 &&
            $data['id_by_login'] == 0) {

            // We found a user account by matriculation but with the wrong login.
            // The correct login is not taken by another user account.
            // --> Update user account.
            $result = $this->action_factory->buildUpdateAction(
                $evento_user,
                $data['ids_by_matriculation'][0]
            );
            $this->addUserToEventoIliasMappingTable($evento_user, $data['ids_by_matriculation'][0]);
            return $result;
        }

        if (count($data['ids_by_matriculation']) == 1 &&
            $data['id_by_login'] != 0 &&
            !in_array($data['id_by_login'], $data['ids_by_matriculation'])) {

            // We found a user account by matriculation but with the wrong
            // login. The login is taken by another user account.
            // --> Rename and deactivate conflicting account, then update user account.
            $user_obj_by_login = $this->ilias_user_service->getExistingIliasUserObjectById($data['id_by_login']);
            return $this->action_factory->buildRenameExistingAndUpdateDeliveredAction(
                $evento_user,
                $data['ids_by_matriculation'][0],
                $user_obj_by_login,
                'login'
            );
        }

        return $this->action_factory->buildReportError(
            $evento_user,
            $data
        );
    }

    public function determineDeleteAction(int $ilias_id, int $evento_id) : EventoImportAction
    {
        $ilias_user_object = $this->ilias_user_service->getExistingIliasUserObjectById($ilias_id);

        if ($this->ilias_user_service->userWasStudent($ilias_user_object)) {
            return $this->action_factory->buildConvertUserAuth($ilias_user_object, $evento_id);
        }

        return $this->action_factory->buildConvertAuthAndDeactivateUser($ilias_user_object, $evento_id);
    }
}

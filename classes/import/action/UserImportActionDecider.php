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

    public function __construct(
        IliasUserServices $ilias_user_service,
        IliasEventoUserRepository $evento_user_repo,
        UserActionFactory $action_factory
    ) {
        $this->ilias_user_service = $ilias_user_service;
        $this->evento_user_repo = $evento_user_repo;
        $this->action_factory = $action_factory;
    }

    private function addUserToEventoIliasMappingTable(
        EventoUser $evento_user,
        int $ilias_user_id
    ): void {
        $this->evento_user_repo->addNewEventoIliasUser($evento_user->getEventoId(), $ilias_user_id, IliasEventoUserRepository::TYPE_HSLU_AD);
    }

    public function determineImportAction(EventoUser $evento_user): EventoImportAction
    {
        $matched_user_id = $this->evento_user_repo->getIliasUserIdByEventoId($evento_user->getEventoId());

        if ($matched_user_id === null) {
            return $this->matchToIliasUsersAndDetermineAction($evento_user);
        }

        $users_to_rename = [];
        $found_by = '';
        $current_login_of_matched_user = $this->ilias_user_service->getLoginByUserId($matched_user_id);
        // Check if login of delivered user has changed AND the changed login name is already taken
        if ($current_login_of_matched_user != $evento_user->getLoginName()
            && ($additional_user_id_by_login = $this->ilias_user_service->getUserIdByLogin($evento_user->getLoginName())) > 0
        ) {
            $user_to_rename[] = $this->ilias_user_service->getExistingIliasUserObjectById(
                $additional_user_id_by_login
            );
            $found_by = 'login';
        }

        $current_external_account_matched_user = $this->ilias_user_service->getExternalAccountByUserId($matched_user_id);
        if ($evento_user->getEduId() !== null
            && $evento_user->getEduId() !== ''
            && $current_external_account_matched_user !== $evento_user->getEduId()
            && ($additional_user_by_external_account = $this->ilias_user_service->getUserIdByExternalAccount($evento_user->getEduId())) > 0
        ) {
            $user_to_rename[] = $this->ilias_user_service->getExistingIliasUserObjectById(
                $additional_user_by_external_account
            );
            $found_by = $found_by === '' ? 'external_account' : 'login + external_account';
        }

        if ($users_to_rename !== []) {
            return $this->action_factory->buildRenameExistingAndUpdateDeliveredAction(
                $evento_user,
                $matched_user_id,
                $users_to_rename,
                $found_by
            );
        }

        return $this->action_factory->buildUpdateAction($evento_user, $matched_user_id);
    }

    private function matchToIliasUsersAndDetermineAction(
        EventoUser $evento_user
    ): EventoImportAction {
        $data['id_by_external_account'] = $this->ilias_user_service->getUserIdByExternalAccount($evento_user->getEduId());
        $data['id_by_login'] = $this->ilias_user_service->getUserIdByLogin($evento_user->getLoginName());
        $data['ids_by_matriculation'] = $this->ilias_user_service->getUserIdsByEventoId($evento_user->getEventoId());
        $data['ids_by_email'] = $this->ilias_user_service->getUserIdsByEmailAddresses($evento_user->getEmailList());

        /**
         *  We couldn't find a user account neither by matriculation, login
         *  nor e-mail
         *  --> Insert new user account.
         */
        if ($data['ids_by_matriculation'] === []
            && $data['id_by_external_account'] === []
            && $data['id_by_login'] === 0
            && $data['ids_by_email'] === 0) {
            return $this->action_factory->buildCreateAction($evento_user);
        }

        /**
         * This should never happen, but just to be sure: We found more than
         * one account with the same matriculation.
         * --> We don't know what to do with it and thus report an error.
         */
        if (count($data['ids_by_matriculation']) > 1) {
            return $this->action_factory->buildReportError(
                $evento_user,
                $data
            );
        }

        /**
         * We do have this account exactly as she should be or we have at least
         * a user with correct matriculation and the corresponding external_account
         * login are still free to be used
         * --> Update
         */
        if (count($data['ids_by_matriculation']) === 1
            && ($data['ids_by_matriculation'][0] === $data['id_by_external_account']
                || $data['id_by_external_account'] === 0)
            && ($data['ids_by_matriculation'][0] === $data['id_by_login']
                || $data['id_by_login'] === 0)
            ) {
            return $this->action_factory->buildUpdateAction(
                $evento_user,
                $data['ids_by_matriculation'][0]
            );
        }

        /**
         * We do have an account with the corresponding matriculation, but sadly
         * the external account and or the login are taken by another account
         * (as all other cases have been filtered away by the previous condition).
         * --> Remove external account from conflicting account, deactivate
         *     conflicting account, then update new account.
         */
        if (count($data['ids_by_matriculation']) === 1) {
            $user_objs = [];
            if ($data['id_by_external_account'] !== 0) {
                $user_objs['by_external_account'] = $this->ilias_user_service->getExistingIliasUserObjectById($data['id_by_external_account']);
            }

            if ($data['id_by_login'] !== 0
                && $data['id_external_account'] !== $data['id_by_login']) {
                $user_objs['by_login'] = $this->ilias_user_service->getExistingIliasUserObjectById($data['id_by_login']);
            }
            return $this->action_factory->buildRenameExistingAndUpdateDeliveredAction(
                $evento_user,
                $data['ids_by_matriculation'][0],
                $user_objs,
                'login'
            );
        }

        /**
         * We didn't find an account with the corresponding matriculation, but
         * we found an account with the corresponding external account or a
         * corresponding login or we found one account with a corresponding
         * external account and the same corresponding login.
         */
        if ($data['id_by_external_account'] !== 0
                && $data['id_by_login'] === 0
            || $data['id_by_login'] !== 0
                && $data['id_by_external_account'] === 0
            || $data['id_by_external_account'] !== 0
                && $data['id_by_login'] !== 0
                && $data['id_by_external_account'] === $data['id_by_external_account']) {
            $found_user_id = $data['id_by_external_account'] !== 0 ?
                $data['id_by_external_account'] : $data['id_by_login'];

            return $this->buildActionForUserId($found_user_id, $evento_user);
        }

        /**
         * We didn't find an account with the corresponding matriculation, but
         * we found two different accounts: one with the corresponding external
         * account and one with a corresponding login.
         */
        if ($data['id_by_external_account'] !== 0
            && $data['id_by_login'] !== 0
            && $data['id_by_external_account'] !== $data['id_by_login']) {

            $user_by_external_account = $this->ilias_user_service
                ->getExistingIliasUserObjectById($data['id_by_external_account']);
            $user_by_login = $this->ilias_user_service
                ->getExistingIliasUserObjectById($data['id_by_login']);

            // The account by external account and/or the account by login have
            // a matriculation of some kind that doesn't come from Evento.
            // --> Bail
            if ($user_by_external_account->getMatriculation() !== ''
                    && substr($user_by_external_account->getMatriculation(), 0, 7) !== 'Evento:'
                || $user_by_login->getMatriculation() !== ''
                    && substr($user_by_login->getMatriculation(), 0, 7) !== 'Evento:') {
                return $this->action_factory->buildReportConflict($evento_user);
            }

            // The user account by external account has no matriculation
            // --> Remove external account from account by login, deactivate
            //     account by login, then update account by external account.
            if ($user_by_external_account->getMatriculation() === '') {
                $this->addUserToEventoIliasMappingTable($evento_user, $data['id_by_external_account']);
                return $this->action_factory->buildRenameExistingAndUpdateDeliveredAction(
                    $evento_user,
                    $data['id_by_external_account'],
                    [$user_by_login],
                    'login'
                );
            }

            // The user account by external account has a valid matriculation
            // --> Remove external account from account by login and from account
            //     by external account, deactivate account by login and account
            //     by external account, then create new account.
            return $this->action_factory->buildRenameExistingAndCreateNewAction(
                $evento_user,
                [$user_by_external_account, $user_by_login],
                'login'
            );
        }

        /**
         * We didn't find an account by neither corrsponding matriculation
         * nor by the corresponding external account nor the corresponding
         * login, but we found found exactly one, by the corresponding e-mail.
         */
        if (count($data['ids_by_email']) === 1) {
            return $this->buildActionForUserId($data['ids_by_email'][0], $evento_user);
        }

        return $this->action_factory->buildReportError(
            $evento_user,
            $data
        );
    }

    private function buildActionForUserId(
        int $found_user_id,
        EventoUser $evento_user
    ): EventoImportAction {
        $found_user = $this->ilias_user_service
                ->getExistingIliasUserObjectById($found_user_id);

        // The account by external account has a different evento number.
        // --> Remove external account from conflicting account, deactivate
        //     conflicting account, then update new account.
        if (substr($found_user->getMatriculation(), 0, 7) === 'Evento:') {
            return $this->action_factory->buildRenameExistingAndCreateNewAction(
                $evento_user,
                [$found_user],
                'login'
            );
        }

        // The account by external account has a matriculation of some kind
        // --> Bail
        if ($found_user->getMatriculation() !== '') {
            return $this->action_factory->buildReportConflict($evento_user);
        }

        // The user account by external account has no matriculation
        // --> Update
        $this->addUserToEventoIliasMappingTable($evento_user, $found_user_id);
        return $this->action_factory->buildUpdateAction(
            $evento_user,
            $found_user_id
        );
    }

    public function determineDeleteAction(int $ilias_id, int $evento_id) : EventoImportAction
    {
        $ilias_user_object = $this->ilias_user_service->getExistingIliasUserObjectById($ilias_id);

        return $this->action_factory->buildRemoveConnectionToInstitution($ilias_user_object, $evento_id);
    }
}

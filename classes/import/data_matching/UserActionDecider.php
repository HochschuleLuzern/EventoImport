<?php

namespace EventoImport\import\data_matching;

use EventoImport\import\action\user\UserImportAction;
use EventoImport\import\db\UserFacade;
use EventoImport\import\action\user\UserActionFactory;
use EventoImport\import\action\EventoImportAction;

/**
 * Class UserActionDecider
 * @package EventoImport\import\data_matching
 */
class UserActionDecider
{
    /** @var UserFacade */
    private UserFacade $user_facade;

    /** @var UserActionFactory */
    private UserActionFactory $action_factory;

    /**
     * UserActionDecider constructor.
     * @param UserFacade        $user_facade
     * @param UserActionFactory $action_factory
     */
    public function __construct(UserFacade $user_facade, UserActionFactory $action_factory)
    {
        $this->user_facade = $user_facade;
        $this->action_factory = $action_factory;
    }

    /**
     * @param \EventoImport\communication\api_models\EventoUser $evento_user
     * @param int                                               $ilias_user_id
     */
    private function addUserToEventoIliasMappingTable(
        \EventoImport\communication\api_models\EventoUser $evento_user,
        int $ilias_user_id
    ) {
        $ilias_user = $this->user_facade->getExistingIliasUserObject($ilias_user_id);
        $this->user_facade->eventoUserRepository()->addNewEventoIliasUser($evento_user, $ilias_user);
    }

    /**
     * @param \EventoImport\communication\api_models\EventoUser $evento_user
     * @return EventoImportAction
     */
    public function determineImportAction(\EventoImport\communication\api_models\EventoUser $evento_user) : EventoImportAction
    {
        $user_id = $this->user_facade->eventoUserRepository()->getIliasUserIdByEventoId($evento_user->getEventoId());

        if (!is_null($user_id)) {
            return $this->action_factory->buildUpdateAction($evento_user, $user_id);
        }

        return $this->matchEventoUserTheOldWay($evento_user);
    }

    /**
     * @param \EventoImport\communication\api_models\EventoUser $evento_user
     * @return EventoImportAction
     */
    private function matchEventoUserTheOldWay(
        \EventoImport\communication\api_models\EventoUser $evento_user
    ) : EventoImportAction {
        $data['id_by_login'] = $this->user_facade->fetchUserIdByLogin($evento_user->getLoginName());
        $data['ids_by_matriculation'] = $this->user_facade->fetchUserIdsByEventoId($evento_user->getEventoId());
        $data['ids_by_email'] = $this->user_facade->fetchUserIdsByEmail($evento_user->getEmailList());

        $usrId = 0;

        if (count($data['ids_by_matriculation']) == 0 &&
            $data['id_by_login'] == 0 &&
            count($data['ids_by_email']) == 0) {

            // We couldn't find a user account neither by
            // matriculation, login nor e-mail
            // --> Insert new user account.
            return $this->action_factory->buildCreateAction($evento_user);
        //EventoIliasUserMatchingResult::NoMatchingUserResult();
        } else {
            if (count($data['ids_by_matriculation']) == 0 &&
                $data['id_by_login'] != 0) {

                // We couldn't find a user account by matriculation, but we found
                // one by login.

                $user_obj_by_login = $this->user_facade->getExistingIliasUserObject($data['id_by_login']);

                if (substr($user_obj_by_login->getMatriculation(), 0, 7) == 'Evento:') {
                    // The user account by login has a different evento number.
                    // --> Rename and deactivate conflicting account
                    //     and then insert new user account.
                    $result = $this->action_factory->buildRenameExistingAndCreateNewAction(
                        $evento_user,
                        $user_obj_by_login,
                        'login'
                    );
                } else {
                    if ($user_obj_by_login->getMatriculation() == $user_obj_by_login->getLogin()) {
                        // The user account by login has a matriculation from ldap
                        // --> Update user account.
                        $result = $this->action_factory->buildUpdateAction(
                            $evento_user,
                            $data['id_by_login']
                        );
                        $this->addUserToEventoIliasMappingTable($evento_user, $data['id_by_login']);
                    } else {
                        if (strlen($user_obj_by_login->getMatriculation()) != 0) {
                            // The user account by login has a matriculation of some kind
                            // --> Bail
                            $result = $this->action_factory->buildReportConflict($evento_user);
                        } else {
                            // The user account by login has no matriculation
                            // --> Update user account.
                            $result = $this->action_factory->buildUpdateAction(
                                $evento_user,
                                $data['id_by_login']
                            );
                            $this->addUserToEventoIliasMappingTable($evento_user, $data['id_by_login']);
                        }
                    }
                }
            } else {
                if (count($data['ids_by_matriculation']) == 0 &&
                    $data['id_by_login'] == 0 &&
                    count($data['ids_by_email']) == 1) {

                    // We couldn't find a user account by matriculation, but we found
                    // one by e-mail.
                    $user_obj_by_mail = $this->user_facade->getExistingIliasUserObject($data['ids_by_email'][0]);

                    if (substr($user_obj_by_mail->getMatriculation(), 0, 7) == 'Evento:') {
                        // The user account by e-mail has a different evento number.
                        // --> Rename and deactivate conflicting account
                        //     and then insert new user account.
                        $result = $this->action_factory->buildRenameExistingAndCreateNewAction(
                            $evento_user,
                            $user_obj_by_mail,
                            'mail'
                        );
                    } else {
                        if (strlen($user_obj_by_mail->getMatriculation()) != 0) {
                            // The user account by login has a matriculation of some kind
                            // --> Bail
                            $result = $this->action_factory->buildReportConflict($evento_user);
                        } else {
                            // The user account by login has no matriculation
                            // --> Update user account.
                            $result = $this->action_factory->buildUpdateAction(
                                $evento_user,
                                $data['ids_by_email'][0]
                            );
                            $this->addUserToEventoIliasMappingTable($evento_user, $data['ids_by_email'][0]);
                        }
                    }
                } else {
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
                    } else {
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
                        } else {
                            if (count($data['ids_by_matriculation']) == 1 &&
                                $data['id_by_login'] != 0 &&
                                !in_array($data['id_by_login'], $data['ids_by_matriculation'])) {

                                // We found a user account by matriculation but with the wrong
                                // login. The login is taken by another user account.
                                // --> Rename and deactivate conflicting account, then update user account.
                                $user_obj_by_login = $this->user_facade->getExistingIliasUserObject($data['id_by_login']);
                                $result = $this->action_factory->buildRenameExistingAndCreateNewAction(
                                    $evento_user,
                                    $user_obj_by_login,
                                    'login'
                                );
                            } else {
                                $result = $this->action_factory->buildReportError($evento_user);
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param int $ilias_id
     * @param int $evento_id
     * @return EventoImportAction
     */
    public function determineDeleteAction(int $ilias_id, int $evento_id) : EventoImportAction
    {
        $ilias_user_object = $this->user_facade->getExistingIliasUserObject($ilias_id);

        if ($this->user_facade->userWasStudent($ilias_user_object)) {
            return $this->action_factory->buildConvertUserAuth($ilias_user_object, $evento_id);
        } else {
            return $this->action_factory->buildConvertAuthAndDeactivateUser($ilias_user_object, $evento_id);
        }
    }
}

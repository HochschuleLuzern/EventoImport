<?php

namespace EventoImport\import\data_matching;

use EventoImport\import\db\UserFacade;
use Exception;
use EventoImport\import\db\query\IliasUserQuerying;
use ilObjUser;

/**
 * Class EventoUserToIliasUserMatcher
 * See /docs/evento_to_user_matching.md for more informations
 */
class EventoUserToIliasUserMatcher
{
    /** @var IliasUserQuerying */
    public $user_query;

    /** @var \EventoImport\import\db\repository\EventoUserRepository */
    public $evento_user_repo;

    private $user_facade;

    public function __construct(UserFacade $user_facade)
    {
        $this->user_facade = $user_facade;
    }

    private function searchMatchInDB(\EventoImport\communication\api_models\EventoUser $evento_user)
    {
        return $this->user_facade->eventoUserRepository()->getIliasUserIdByEventoId($evento_user->getEventoId());
    }

    private function getUserIdsByEmailAdresses(array $evento_mail_list)
    {
        $user_lists = array();

        // For each mail given from the evento import...
        foreach ($evento_mail_list as $mail_given_from_evento) {

            // ... get all user ids in which a user has this email
            foreach (ilObjUser::getUserIdsByEmail($mail_given_from_evento) as $ilias_id_by_mail) {

                if (!in_array($ilias_id_by_mail, $user_lists)) {
                    $user_lists[] = $ilias_id_by_mail;
                }
            }
        }

        return $user_lists;
    }

    private function compareMatchByLoginWithOthers()
    {
        $number_of_matches_by_evento_id = count($this->matches_by_evento_id);
        $number_of_matches_by_email     = count($this->matches_by_email);

        if ($number_of_matches_by_evento_id == 0 && $number_of_matches_by_email == 0) {

        } else {
            if (
                ($number_of_matches_by_evento_id == 1 && $number_of_matches_by_email == 0 && $this->matches_by_evento_id[0] == $this->match_by_login)
                ||
                ($number_of_matches_by_evento_id == 0 && $number_of_matches_by_email == 1 && $this->matches_by_email[0] == $this->match_by_login)
                ||
                ($number_of_matches_by_evento_id == 1
                    && $number_of_matches_by_email == 1
                    && ($this->matches_by_evento_id[0] == $this->match_by_login && $this->matches_by_email[0] == $this->match_by_login)
                )
            ) {

            } else {
                if (
                    $number_of_matches_by_evento_id == 1
                    && $number_of_matches_by_email > 1
                    && $this->matches_by_evento_id[0] == $this->match_by_login
                ) {

                }
            }
        }
    }

    private function compareMatchesByEventoIdAndMail()
    {
        $number_of_matches_by_evento_id = count($this->matches_by_evento_id);
        $number_of_matches_by_email     = count($this->matches_by_email);

        if ($number_of_matches_by_evento_id == 1
            && $number_of_matches_by_email == 1
            && $this->matches_by_evento_id[0] == $this->matches_by_email[0]
        ) {
            return EventoIliasUserMatchingResult::ExactlyOneMatchingUserResult($this->matches_by_evento_id[0]);
        } else {
            if ($number_of_matches_by_evento_id == 1) {
                if (in_array($this->matches_by_evento_id[0], $this->matches_by_email)) {
                    return EventoIliasUserMatchingResult::ExactlyOneMatchingUserResult($this->matches_by_evento_id[0]);
                } else {
                    throw new Exception("");
                }
            }
        }
    }

    private function searchMatchInExistingUsers(\EventoImport\communication\api_models\EventoUser $evento_user)
    {
        throw new \ILIAS\UI\NotImplementedException('');
        $this->match_by_login       = $this->user_facade->fetchUserIdByLogin($evento_user->getLoginName()); ilObjUser::getUserIdByLogin($evento_user->getLoginName());
        $this->matches_by_evento_id = $this->user_facade->fetchUserIdsByEventoId($evento_user->getEventoId());
        $this->matches_by_email     = $this->user_facade->fetchUserIdsByEmail($evento_user->getEmailList());

        $has_match_by_login             = is_null($this->match_by_login);
        $number_of_matches_by_evento_id = count($this->matches_by_evento_id);
        $number_of_matches_by_email     = count($this->matches_by_email);

        if ($has_match_by_login
            && $number_of_matches_by_evento_id == 0
            && $number_of_matches_by_email == 0) {
            return EventoIliasUserMatchingResult::NoMatchingUserResult();

        } else {
            if (!is_null($this->match_by_login)) {
                $this->compareMatchByLoginWithOthers();

            } else {
                if ($number_of_matches_by_evento_id > 0
                    && $number_of_matches_by_email > 0) {
                    $this->compareMatchesByEventoIdAndMail();
                } else {
                    if ($number_of_matches_by_evento_id > 0) {
                        if ($number_of_matches_by_evento_id == 1) {
                            $this->setMatches($this->matches_by_evento_id[0], true, true);
                        } else {
                            throw new Exception("");
                        }
                    } else {
                        if ($number_of_matches_by_email == 1) {
                            $this->setMatches($this->matches_by_email[0], true, true);
                        } else {
                            throw new Exception("");
                        }
                    }
                }
            }
        }
    }

    public function matchGivenEventoUserToIliasUsers(\EventoImport\communication\api_models\EventoUser $evento_user)
    {
        $user_id = $this->user_facade->eventoUserRepository()->getIliasUserIdByEventoId($evento_user->getEventoId());

        if (!is_null($user_id)) {
            return EventoIliasUserMatchingResult::ExactlyOneMatchingUserResult($user_id);
        }

        return $this->matchEventoUserTheOldWay($evento_user);

        //return $this->searchMatchInExistingUsers($evento_user);
    }

    public function matchEventoUserTheOldWay(\EventoImport\communication\api_models\EventoUser $evento_user) : EventoIliasUserMatchingResult
    {

        $data['id_by_login']          = $this->user_facade->fetchUserIdByLogin($evento_user->getLoginName());
        $data['ids_by_matriculation'] = $this->user_facade->fetchUserIdsByEventoId($evento_user->getEventoId());
        $data['ids_by_email']         = $this->user_facade->fetchUserIdsByEmail($evento_user->getEmailList());

        $usrId = 0;

        if (count($data['ids_by_matriculation']) == 0 &&
            $data['id_by_login'] == 0 &&
            count($data['ids_by_email']) == 0) {

            // We couldn't find a user account neither by
            // matriculation, login nor e-mail
            // --> Insert new user account.
            $result = EventoIliasUserMatchingResult::NoMatchingUserResult();

        } else {
            if (count($data['ids_by_matriculation']) == 0 &&
                $data['id_by_login'] != 0) {

                // We couldn't find a user account by matriculation, but we found
                // one by login.

                $objByLogin = new ilObjUser($data['id_by_login']);
                $objByLogin->read();

                if (substr($objByLogin->getMatriculation(), 0, 7) == 'Evento:') {
                    // The user account by login has a different evento number.
                    // --> Rename and deactivate conflicting account
                    //     and then insert new user account.
                    $changed_user_data['user_id']       = $data['id_by_login'];
                    $changed_user_data['EvtID']         = trim(substr($objByLogin->getMatriculation(), 8));
                    $changed_user_data['new_user_info'] = 'EventoID: ' . $data['EvtID'];
                    $changed_user_data['found_by']      = 'Login';
                    $result                             = EventoIliasUserMatchingResult::ConflictingUserToConvertResult($changed_user_data);

                } else {
                    if ($objByLogin->getMatriculation() == $objByLogin->getLogin()) {
                        // The user account by login has a matriculation from ldap
                        // --> Update user account.
                        $result = EventoIliasUserMatchingResult::ExactlyOneMatchingUserResult($data['id_by_login']);

                    } else {
                        if (strlen($objByLogin->getMatriculation()) != 0) {
                            // The user account by login has a matriculation of some kind
                            // --> Bail
                            $result = EventoIliasUserMatchingResult::MultiUserConflict();

                        } else {
                            // The user account by login has no matriculation
                            // --> Update user account.
                            $result = EventoIliasUserMatchingResult::ExactlyOneMatchingUserResult($data['id_by_login']);
                        }
                    }
                }

            } else {
                if (count($data['ids_by_matriculation']) == 0 &&
                    $data['id_by_login'] == 0 &&
                    count($data['ids_by_email']) == 1) {

                    // We couldn't find a user account by matriculation, but we found
                    // one by e-mail.
                    $objByEmail = new ilObjUser($data['ids_by_email'][0]);
                    $objByEmail->read();

                    if (substr($objByEmail->getMatriculation(), 0, 7) == 'Evento:') {
                        // The user account by e-mail has a different evento number.
                        // --> Rename and deactivate conflicting account
                        //     and then insert new user account.
                        $changed_user_data['user_id']       = $data['ids_by_email'][0];
                        $changed_user_data['EvtID']         = trim(substr($objByEmail->getMatriculation(), 8));
                        $changed_user_data['new_user_info'] = 'EventoID: ' . $data['EvtID'];
                        $changed_user_data['found_by']      = 'E-Mail';
                        $result                             = EventoIliasUserMatchingResult::NoMatchingUserResult();
                    } else {
                        if (strlen($objByEmail->getMatriculation()) != 0) {
                            // The user account by login has a matriculation of some kind
                            // --> Bail
                            $result = EventoIliasUserMatchingResult::MultiUserConflict();
                        } else {
                            // The user account by login has no matriculation
                            // --> Update user account.
                            $result = EventoIliasUserMatchingResult::ExactlyOneMatchingUserResult($data['ids_by_email'][0]);
                        }
                    }

                } else {
                    if (count($data['ids_by_matriculation']) == 1 &&
                        $data['id_by_login'] != 0 &&
                        in_array($data['id_by_login'], $data['ids_by_matriculation'])) {

                        // We found a user account by matriculation and by login.
                        // --> Update user account.
                        $result = EventoIliasUserMatchingResult::ExactlyOneMatchingUserResult($data['ids_by_matriculation'][0]);
                    } else {
                        if (count($data['ids_by_matriculation']) == 1 &&
                            $data['id_by_login'] == 0) {

                            // We found a user account by matriculation but with the wrong login.
                            // The correct login is not taken by another user account.
                            // --> Update user account.
                            $result = EventoIliasUserMatchingResult::ExactlyOneMatchingUserResult($data['ids_by_matriculation'][0]);
                        } else {
                            if (count($data['ids_by_matriculation']) == 1 &&
                                $data['id_by_login'] != 0 &&
                                !in_array($data['id_by_login'], $data['ids_by_matriculation'])) {

                                // We found a user account by matriculation but with the wrong
                                // login. The login is taken by another user account.
                                // --> Rename and deactivate conflicting account, then update user account.
                                $objByLogin = new ilObjUser($data['id_by_login']);
                                $objByLogin->read();

                                $changed_user_data['user_id']       = $data['id_by_login'];
                                $changed_user_data['EvtID']         = trim(substr($objByLogin->getMatriculation(), 8));
                                $changed_user_data['new_user_info'] = 'EventoID: ' . $data['EvtID'];
                                $changed_user_data['found_by']      = 'Login';
                                $result                             = EventoIliasUserMatchingResult::ConflictingUserToConvertResult($changed_user_data);
                            } else {
                                $result = EventoIliasUserMatchingResult::Error();
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }
}
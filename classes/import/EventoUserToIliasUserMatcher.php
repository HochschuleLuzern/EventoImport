<?php

/**
 * Class EventoUserToIliasUserMatcher
 *
 * See /docs/evento_to_user_matching.md for more informations
*/
class EventoUserToIliasUserMatcher
{
    public function __construct(ilDBInterface $db)
    {
        $this->db = $db;
    }

    private function searchMatchInDB(\EventoImport\import\data_models\EventoUser $evento_user)
    {

    }

    private function getUserIdsByEmailAdresses(array $evento_mail_list)
    {
        $user_lists = array();

        // For each mail given from the evento import...
        foreach($evento_mail_list as $mail_given_from_evento) {

            // ... get all user ids in which a user has this email
            foreach(ilObjUser::getUserIdsByEmail($mail_given_from_evento) as $ilias_id_by_mail) {

                if(!in_array($ilias_id_by_mail, $user_lists)) {
                    $user_lists[] = $ilias_id_by_mail;
                }
            }
        }

        return $user_lists;
    }

    private function getUserIdsByEventoId(int $evento_id)
    {
        $list = array();

        $res = $this->db->queryF("SELECT usr_id FROM usr_data WHERE matriculation = %s",
            array("text"), array($evento_id));
        while($user_rec = $this->db->fetchAssoc($res)){
            $list[]=$user_rec["usr_id"];
        }

        return $list;
    }

    private function compareMatchByLoginWithOthers()
    {
        $number_of_matches_by_evento_id = count($this->matches_by_evento_id);
        $number_of_matches_by_email = count($this->matches_by_email);

        if($number_of_matches_by_evento_id == 0 && $number_of_matches_by_email == 0) {

        } else if(
            ($number_of_matches_by_evento_id == 1 && $number_of_matches_by_email == 0 && $this->matches_by_evento_id[0] == $this->match_by_login)
            ||
            ($number_of_matches_by_evento_id == 0 && $number_of_matches_by_email == 1 && $this->matches_by_email[0] == $this->match_by_login)
            ||
            ($number_of_matches_by_evento_id == 1
                && $number_of_matches_by_email == 1
                && ($this->matches_by_evento_id[0] == $this->match_by_login && $this->matches_by_email[0] == $this->match_by_login)
            )
        ) {

        } else if(
               $number_of_matches_by_evento_id == 1
            && $number_of_matches_by_email > 1
            && $this->matches_by_evento_id[0] == $this->match_by_login
        ) {

        }
    }

    private function compareMatchesByEventoIdAndMail()
    {
        $number_of_matches_by_evento_id = count($this->matches_by_evento_id);
        $number_of_matches_by_email = count($this->matches_by_email);

        if($number_of_matches_by_evento_id == 1
            && $number_of_matches_by_email == 1
            && $this->matches_by_evento_id[0] == $this->matches_by_email[0]
        ) {
            return new EventoIliasUserMatchingResult($this->matches_by_evento_id[0], EventoIliasUserMatchingResult::RESULT_EXACTLY_ONE_MATCHING_USER);
        } else if($number_of_matches_by_evento_id == 1) {
            if(in_array($this->matches_by_evento_id[0], $this->matches_by_email)) {
                return new EventoIliasUserMatchingResult($this->matches_by_evento_id[0], EventoIliasUserMatchingResult::RESULT_EXACTLY_ONE_MATCHING_USER);
            } else {
                throw new Exception("");
            }
        }
    }

    private function searchMatchInExistingUsers(\EventoImport\import\data_models\EventoUser $evento_user)
    {
        $this->match_by_login = ilObjUser::getUserIdByLogin($evento_user->getLoginName());
        $this->matches_by_evento_id = $this->getUserIdsByEventoId($evento_user->getEventoId());
        $this->matches_by_email = $this->getUserIdsByEmailAdresses($evento_user->getEmailList());

        $has_match_by_login = is_null($this->match_by_login);
        $number_of_matches_by_evento_id = count($this->matches_by_evento_id);
        $number_of_matches_by_email = count($this->matches_by_email);

        if($has_match_by_login
            && $number_of_matches_by_evento_id == 0
            && $number_of_matches_by_email == 0) {

            return new EventoIliasUserMatchingResult(null, EventoIliasUserMatchingResult::RESULT_NO_MATCHING_USER);

        } else if(!is_null($this->match_by_login)) {
            $this->compareMatchByLoginWithOthers();

        } else if($number_of_matches_by_evento_id > 0
            && $number_of_matches_by_email > 0) {
            $this->compareMatchesByEventoIdAndMail();
        } else if($number_of_matches_by_evento_id > 0) {
            if($number_of_matches_by_evento_id == 1) {
                $this->setMatches($this->matches_by_evento_id[0], true, true);
            } else {
                throw new Exception("");
            }
        } else {
            if($number_of_matches_by_email == 1) {
                $this->setMatches($this->matches_by_email[0], true, true);
            } else {
                throw new Exception("");
            }
        }
    }

    public function matchGivenEventoUserToIliasUsers(\EventoImport\import\data_models\EventoUser $evento_user)
    {
        $user_id = $this->searchMatchInDB($evento_user);

        if(!is_null($user_id)) {
            return new EventoIliasUserMatchingResult($user_id, EventoIliasUserMatchingResult::RESULT_EXACTLY_ONE_MATCHING_USER);
        }

        return $this->searchMatchInExistingUsers($evento_user);


    }
}
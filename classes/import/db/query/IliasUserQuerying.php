<?php

namespace EventoImport\import\db\query;

use ilDBInterface;
use ilObjUser;

class IliasUserQuerying
{
    /** @var ilDBInterface */
    private $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function fetchUserIdsByEventoId(int $evento_id) : array
    {
        $list = array();

        $res = $this->db->queryF("SELECT usr_id FROM usr_data WHERE matriculation = %s",
            array("text"), array("Evento:$evento_id"));
        while ($user_rec = $this->db->fetchAssoc($res)) {
            $list[] = $user_rec["usr_id"];
        }

        return $list;
    }

    public function fetchUserIdsByEmailAdress(string $mail_adress)
    {
        return ilObjUser::getUserIdsByEmail($mail_adress);
    }

    public function fetchUserIdsByEmailAdresses(array $evento_mail_list)
    {
        $user_lists = array();

        // For each mail given from the evento import...
        foreach ($evento_mail_list as $mail_given_from_evento) {

            // ... get all user ids in which a user has this email
            foreach ($this->fetchUserIdsByEmailAdress($mail_given_from_evento) as $ilias_id_by_mail) {

                if (!in_array($ilias_id_by_mail, $user_lists)) {
                    $user_lists[] = $ilias_id_by_mail;
                }
            }
        }

        return $user_lists;
    }
}
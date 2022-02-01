<?php declare(strict_types = 1);

namespace EventoImport\import\db\query;

class IliasUserQuerying
{
    private \ilDBInterface $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function fetchUserIdsByEventoId(int $evento_id) : array
    {
        $list = array();

        $res = $this->db->queryF(
            "SELECT usr_id FROM usr_data WHERE matriculation = %s",
            array("text"),
            array("Evento:$evento_id")
        );
        while ($user_rec = $this->db->fetchAssoc($res)) {
            $list[] = $user_rec["usr_id"];
        }

        return $list;
    }

    public function fetchUserIdsByEmailAdress(string $mail_adress) : array
    {
        return \ilObjUser::getUserIdsByEmail($mail_adress);
    }

    public function fetchUserIdsByEmailAdresses(array $evento_mail_list) : array
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

    public function setTimeLimitForUnlimitedUsersExceptSpecialUsers(int $new_time_limit_ts) : void
    {
        if ($new_time_limit_ts == 0) {
            throw new \InvalidArgumentException("Error in setting time limit for unlimited users: new time limit cannot be 0");
        }

        //no unlimited users
        $q = "UPDATE usr_data set time_limit_unlimited=0, time_limit_until='" . $this->db->quote($new_time_limit_ts, \ilDBConstants::T_INTEGER) . "' WHERE time_limit_unlimited=1 AND login NOT IN ('root','anonymous')";
        $this->db->manipulate($q);
    }

    public function setUserTimeLimitsToAMaxValue(int $until_max_ts) : void
    {
        if ($until_max_ts == 0) {
            throw new \InvalidArgumentException("Error in setting user time limits to max value: Until Max cannot be 0");
        }

        //all users are constraint to a value defined in the configuration
        $q = "UPDATE usr_data set time_limit_until='" . $this->db->quote($until_max_ts, \ilDBConstants::T_INTEGER) . "'"
            . " WHERE time_limit_until>'" . $this->db->quote($until_max_ts, \ilDBConstants::T_INTEGER) . "'";
        $this->db->manipulate($q);
    }

    public function setUserTimeLimitsBelowThresholdToGivenValue(int $min_threshold_in_days, int $new_time_limit_ts) : void
    {
        //all users have at least 90 days of access (needed for Shibboleth)
        $q = "UPDATE `usr_data` SET time_limit_until=time_limit_until+" . $this->db->quote($new_time_limit_ts, \ilDBConstants::T_INTEGER)
            . " WHERE DATEDIFF(FROM_UNIXTIME(time_limit_until),create_date)< " . $this->db->quote($min_threshold_in_days, \ilDBConstants::T_INTEGER);
        $this->db->manipulate($q);
    }
}

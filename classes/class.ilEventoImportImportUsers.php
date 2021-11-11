<?php

/**
 * Copyright (c) 2017 Hochschule Luzern
 * This file is part of the EventoImport-Plugin for ILIAS.
 * EventoImport-Plugin for ILIAS is free software: you can redistribute
 * it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * EventoImport-Plugin for ILIAS is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with EventoImport-Plugin for ILIAS.  If not,
 * see <http://www.gnu.org/licenses/>.
 */

/**
 * Class ilEventoImportImportUsers
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */
class ilEventoImportImportUsers
{
    /** @var \EventoImport\communication\EventoUserImporter */
    private $evento_importer;

    private $user_facade;

    private $user_import_action_decider;

    /** @var ilEventoImportLogger */
    private $evento_logger;

    private $ilDB;
    private $until_max;

    private $auth_mode;

    public function __construct(
        \EventoImport\communication\EventoUserImporter $importer,
        \EventoImport\import\data_matching\UserImportActionDecider $user_import_action_decider,
        ilEventoImportLogger $logger
    ) {
        $this->evento_importer = $importer;
        $this->user_import_action_decider = $user_import_action_decider;
        $this->evento_logger = $logger;
    }

    public function run()
    {
        $this->importUsers();
        //$this->convertDeletedAccounts();
        //$this->setUserTimeLimits();
    }

    private $user_config;

    /**
     * Import Users from Evento
     * Returns the number of rows.
     */
    private function importUsers()
    {
        do {
            try {
                $this->importNextUserPage();
            } catch (Exception $e) {
                $this->evento_logger->logException('User Import', $e->getMessage());
            }
        } while ($this->evento_importer->hasMoreData());
    }

    private function importNextUserPage()
    {
        foreach ($this->evento_importer->fetchNextDataSet() as $data_set) {
            try {
                $evento_user = new \EventoImport\communication\api_models\EventoUser($data_set);

                $action = $this->user_import_action_decider->determineAction($evento_user);
                $action->executeAction();
            } catch (Exception $e) {
                $this->evento_logger->logException('User Import', $e->getMessage());
            }
        }
    }

    /**
     * Convert deleted Users to ILIAS-Account
     * Returns boolean for sucess
     */
    private function convertDeletedAccounts($operation, $deactivate = false)
    {
        $deletedLdapUsers = array();

        $iterator = new ilEventoImporterIterator();

        while (!($result = &$this->evento_importer->getRecords($operation, 'GeloeschteUser', $iterator))['finished']) {
            foreach ($result['data'] as $user) {
                $deletedLdapUsers[] = 'Evento:' . $user['EvtID'];
            }

            if ($result['is_last']) {
                break;
            }
        }

        if (count($deletedLdapUsers) > 0) {
            for ($i = 0; $i <= count($deletedLdapUsers); $i += 100) {
                //hole immer max 100 user aus der ilias db mit bedingung dass diese noch ldap aktiv sind
                $r = $this->ilDB->query("SELECT login,matriculation FROM `usr_data` WHERE auth_mode='" . $this->auth_mode . "' AND matriculation IN ('" . implode(
                    "','",
                    array_slice($deletedLdapUsers, $i, 100)
                ) . "')");
                while ($row = $this->ilDB->fetchAssoc($r)) {
                    //nochmals nachfragen, wenn user wiederhergestellt wurde
                    $eventoid = substr($row['matriculation'], 7);
                    $login = $row['login'];
                    $result = $this->evento_importer->getRecord(
                        'ExistsHSLUDomainUser',
                        array('parameters' => array('login' => $login, 'evtid' => $eventoid))
                    );

                    if ($result->{'ExistsHSLUDomainUserResult'} === false) {
                        //user nicht mehr aktiv in ldap
                        if ($deactivate) {
                            $sql = "UPDATE usr_data SET auth_mode='default', time_limit_until=UNIX_TIMESTAMP() WHERE matriculation LIKE '" . $row['matriculation'] . "'";
                        } else {
                            $sql = "UPDATE usr_data SET auth_mode='default' WHERE matriculation LIKE '" . $row['matriculation'] . "'";
                        }

                        $this->ilDB->manipulate($sql);

                        $this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_CONVERTED, $row);
                    }
                }
            }
        }
    }

    /**
     * User accounts which don't have a time limitation are limited to
     * two years since their creation.
     */
    private function setUserTimeLimits()
    {
        //all users have at least 90 days of access (needed for Shibboleth)
        $q = "UPDATE `usr_data` SET time_limit_until=time_limit_until+7889229 WHERE DATEDIFF(FROM_UNIXTIME(time_limit_until),create_date)<90";
        $r = $this->ilDB->manipulate($q);

        if ($this->until_max != 0) {
            //no unlimited users
            $q = "UPDATE usr_data set time_limit_unlimited=0, time_limit_until='" . $this->until_max . "' WHERE time_limit_unlimited=1 AND login NOT IN ('root','anonymous')";
            $r = $this->ilDB->manipulate($q);

            //all users are constraint to a value defined in the configuration
            $q = "UPDATE usr_data set time_limit_until='" . $this->until_max . "' WHERE time_limit_until>'" . $this->until_max . "'";
            $this->ilDB->manipulate($q);
        }
    }

    private function setMailPreferences($usrId)
    {
        $this->ilDB->manipulateF(
            "UPDATE mail_options SET incoming_type = '2' WHERE user_id = %s",
            array("integer"),
            array($usrId)
        ); //mail nur intern nach export
    }

    /**
     * Change login name of a user
     */
    private function changeLoginName($usr_id, $new_login)
    {
        $q = "UPDATE usr_data SET login = '" . $new_login . "' WHERE usr_id = '" . $usr_id . "'";
        $this->ilDB->manipulate($q);
    }

    private function addPersonalPicture($eventoid, $id)
    {

        // TODO: Implement Picture Method
        // Early return till the new method is implemented
        return;
        // Upload image
        $has_picture_result = $this->evento_importer->getRecord(
            'HasPhoto',
            array('parameters' => array('eventoId' => $eventoid))
        );

        if (isset($has_picture_result->{'HasPhotoResult'}) && $has_picture_result->{'HasPhotoResult'} === true) {
            $picture_result = $this->evento_importer->getRecord(
                'GetPhoto',
                array('parameters' => array('eventoId' => $eventoid))
            );
            $tmp_file = ilUtil::ilTempnam();
            imagepng(imagecreatefromstring($picture_result->{'GetPhotoResult'}), $tmp_file, 0);
            ilObjUser::_uploadPersonalPicture($tmp_file, $id);
            unlink($tmp_file);
        }
    }
}

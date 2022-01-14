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

    /** @var \EventoImport\import\db\UserFacade */
    private $user_facade;

    private $user_import_action_decider;

    /** @var ilEventoImportLogger */
    private $evento_logger;

    private $ilDB;
    private $until_max;

    private $auth_mode;

    public function __construct(
        \EventoImport\communication\EventoUserImporter $importer,
        \EventoImport\import\data_matching\UserActionDecider $user_import_action_decider,
        \EventoImport\import\db\UserFacade $user_facade,
        ilEventoImportLogger $logger
    ) {
        $this->evento_importer = $importer;
        $this->user_import_action_decider = $user_import_action_decider;
        $this->user_facade = $user_facade;
        $this->evento_logger = $logger;
    }

    private function convertOrDeleteNotImportedAccounts()
    {
        // Get list uf users, which were not imported since a certain time
        $list = $this->user_facade->eventoUserRepository()->fetchNotImportedUsers();

        foreach ($list as $evento_id => $ilias_user_id) {
            try {
                // Try to fetch user by ID from evento
                $result = $this->evento_importer->fetchDataRecord($evento_id);

                // If evento does not deliver this user, it can be safely converted / deleted
                if (is_null($result) || (is_array($result) && count($result) < 1)) {
                    $action = $this->user_import_action_decider->determineDeleteAction($ilias_user_id, $evento_id);
                    $action->executeAction();
                }
            } catch (\Exception $e) {
            }
        }
    }

    public function run()
    {
        $this->importUsers();
        $this->convertOrDeleteNotImportedAccounts();
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
        foreach ($this->evento_importer->fetchNextUserDataSet() as $data_set) {
            try {
                $evento_user = new \EventoImport\communication\api_models\EventoUser($data_set);

                $action = $this->user_import_action_decider->determineImportAction($evento_user);
                $action->executeAction();
            } catch (Exception $e) {
                $this->evento_logger->logException('User Import', $e->getMessage());
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
}

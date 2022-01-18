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

    /** @var ilDBInterface */
    private $db;

    private $until_max;


    public function __construct(
        \EventoImport\communication\EventoUserImporter $importer,
        \EventoImport\import\data_matching\UserActionDecider $user_import_action_decider,
        \EventoImport\import\db\UserFacade $user_facade,
        ilEventoImportLogger $logger,
        $until_max,
        ilDBInterface $db = null
    ) {
        global $DIC;

        $this->evento_importer = $importer;
        $this->user_import_action_decider = $user_import_action_decider;
        $this->user_facade = $user_facade;
        $this->evento_logger = $logger;
        $this->db = $db ?? $DIC->database();
    }

    public function run()
    {
        $this->importUsers();
        $this->convertDeletedAccounts();
        $this->setUserTimeLimits();
    }

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
        } while ($this->evento_importer->hasMoreData() && false);
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
     * User accounts which are deleted by evento should either be converted to a local account (students) or deactivate (stuff)
     *
     * Since there is no "getDeletedAccounts"-Method anymore, this Plugin has to find those "not anymore imported"-users
     * by itself. For this reason, every imported account has a last-imported-timestamp. With this value, users which have not
     * been imported since a longer time can be found.
     */
    private function convertDeletedAccounts()
    {
        // Get list uf users, which were not imported since a certain time
        $list = $this->user_facade->eventoUserRepository()->fetchNotImportedUsers();

        foreach ($list as $evento_id => $ilias_user_id) {
            try {
                // Try to fetch user by ID from evento
                // -> this is just to be safe, that the API does not deliver the user anymore
                $result = $this->evento_importer->fetchUserDataRecordById($evento_id);

                // If evento does not deliver this user, it can be safely converted / deleted
                if (is_null($result) || (is_array($result) && count($result) < 1)) {
                    $action = $this->user_import_action_decider->determineDeleteAction($ilias_user_id, $evento_id);
                    $action->executeAction();
                } else {
                    $this->user_facade->eventoUserRepository()->registerUserAsDelivered($result['id']);
                }
            } catch (\Exception $e) {
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
        $r = $this->db->manipulate($q);

        if ($this->until_max != 0) {
            //no unlimited users
            $q = "UPDATE usr_data set time_limit_unlimited=0, time_limit_until='" . $this->until_max . "' WHERE time_limit_unlimited=1 AND login NOT IN ('root','anonymous')";
            $r = $this->db->manipulate($q);

            //all users are constraint to a value defined in the configuration
            $q = "UPDATE usr_data set time_limit_until='" . $this->until_max . "' WHERE time_limit_until>'" . $this->until_max . "'";
            $this->db->manipulate($q);
        }
    }
}

<?php declare(strict_types = 1);

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

use EventoImport\communication\EventoUserImporter;
use EventoImport\import\data_matching\UserActionDecider;
use EventoImport\import\db\UserFacade;

/**
 * Class ilEventoImportImportUsers
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */
class ilEventoImportImportUsers
{
    private $evento_importer;
    private $user_facade;
    private $user_import_action_decider;
    private $evento_logger;
    private $db;

    public function __construct(
        EventoUserImporter $importer,
        UserActionDecider $user_import_action_decider,
        UserFacade $user_facade,
        ilEventoImportLogger $logger,
        ilDBInterface $db
    ) {
        $this->evento_importer = $importer;
        $this->user_import_action_decider = $user_import_action_decider;
        $this->user_facade = $user_facade;
        $this->evento_logger = $logger;
        $this->db = $db;
    }

    public function run()
    {
        $this->importUsers();
        $this->convertDeletedAccounts();
        $this->setUserTimeLimits();
    }

    /**
     * Import Users from Evento
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
     * User accounts which are deleted by evento should either be converted to a local account (students) or deactivate (stuff)
     *
     * Since there is no "getDeletedAccounts"-Method anymore, this Plugin has to find those "not anymore imported"-users
     * by itself. For this reason, every imported account has a last-imported-timestamp. With this value, users which have not
     * been imported since a longer time can be found.
     */
    private function convertDeletedAccounts()
    {
        // Get list uf users, which were not imported since a certain time
        $list = $this->user_facade->eventoUserRepository()->fetchUsersWithLastImportOlderThanOneWeek();

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
        $this->user_facade->setUserTimeLimits();
    }
}

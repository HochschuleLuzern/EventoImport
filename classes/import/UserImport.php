<?php declare(strict_types=1);

namespace EventoImport\import;

use EventoImport\communication\EventoUserImporter;
use EventoImport\import\db\UserFacade;
use EventoImport\import\data_matching\UserActionDecider;

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
class UserImport
{
    private EventoUserImporter $evento_importer;
    private UserFacade $user_facade;
    private UserActionDecider $user_import_action_decider;
    private Logger $evento_logger;
    private \ilDBInterface $db;

    public function __construct(
        EventoUserImporter $importer,
        UserActionDecider $user_import_action_decider,
        UserFacade $user_facade,
        Logger $logger,
        \ilDBInterface $db
    ) {
        $this->evento_importer = $importer;
        $this->user_import_action_decider = $user_import_action_decider;
        $this->user_facade = $user_facade;
        $this->evento_logger = $logger;
        $this->db = $db;
    }

    public function run() : void
    {
        $this->importUsers();
        $this->convertDeletedAccounts();
        $this->setUserTimeLimits();
    }

    private function importUsers() : void
    {
        do {
            try {
                $this->importNextUserPage();
            } catch (\Exception $e) {
                $this->evento_logger->logException('User Import', $e->getMessage());
            }
        } while ($this->evento_importer->hasMoreData());
    }

    private function importNextUserPage() : void
    {
        foreach ($this->evento_importer->fetchNextUserDataSet() as $data_set) {
            try {
                $evento_user = new \EventoImport\communication\api_models\EventoUser($data_set);

                $action = $this->user_import_action_decider->determineImportAction($evento_user);
                $action->executeAction();
            } catch (\Exception $e) {
                $this->evento_logger->logException('User Import', $e->getMessage());
            }
        }
    }

    /**
     * User accounts which are deleted by evento should either be converted to a local account (students) or deactivate (staff)
     * Since there is no "getDeletedAccounts"-Method anymore, this Plugin has to find those "not anymore imported"-users
     * by itself. For this reason, every imported account has a last-imported-timestamp. With this value, users which have not
     * been imported since a longer time can be found.
     */
    private function convertDeletedAccounts()
    {
        $list = $this->user_facade->eventoUserRepository()->fetchUsersWithLastImportOlderThanOneWeek();

        foreach ($list as $evento_id => $ilias_user_id) {
            try {
                // Ensure that the user is not being returned by the api right now
                $result = $this->evento_importer->fetchUserDataRecordById($evento_id);

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

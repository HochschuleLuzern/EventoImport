<?php declare(strict_types=1);

namespace EventoImport\import;

use EventoImport\communication\EventoUserImporter;
use EventoImport\import\data_management\ilias_core_service\IliasUserServices;
use EventoImport\import\action\UserImportActionDecider;
use EventoImport\import\data_management\repository\IliasEventoUserRepository;
use EventoImport\communication\api_models\EventoUser;

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
class UserImportTask
{
    private EventoUserImporter $evento_importer;
    private IliasUserServices $ilias_user_service;
    private IliasEventoUserRepository $evento_user_repo;
    private UserImportActionDecider $user_import_action_decider;
    private Logger $evento_logger;

    public function __construct(
        EventoUserImporter $importer,
        UserImportActionDecider $user_import_action_decider,
        IliasUserServices $ilias_user_service,
        IliasEventoUserRepository $evento_user_repo,
        Logger $logger
    ) {
        $this->evento_importer = $importer;
        $this->user_import_action_decider = $user_import_action_decider;
        $this->ilias_user_service = $ilias_user_service;
        $this->evento_user_repo = $evento_user_repo;
        $this->evento_logger = $logger;
    }

    public function run() : void
    {
        $this->importUsers();
        //$this->convertDeletedAccounts();
        //$this->setUserTimeLimits();
    }

    private function importUsers() : void
    {
        do {
            try {
                $this->importNextUserPage();
            } catch (\ilEventoImportCommunicationException $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->evento_logger->logException('User Import', $e->getMessage());
            }
        } while ($this->evento_importer->hasMoreData());
    }

    private function importNextUserPage() : void
    {
        foreach ($this->evento_importer->fetchNextUserDataSet() as $data_set) {
            try {
                $evento_user = new EventoUser($data_set);

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
        $list = $this->evento_user_repo->getUsersWithLastImportOlderThanOneWeek(IliasEventoUserRepository::TYPE_HSLU_AD);

        foreach ($list as $evento_id => $ilias_user_id) {
            try {
                // Ensure that the user is not being returned by the api right now
                $result = $this->evento_importer->fetchUserDataRecordById($evento_id);

                if (is_null($result)) {
                    $action = $this->user_import_action_decider->determineDeleteAction($ilias_user_id, $evento_id);
                    $action->executeAction();
                } else {
                    $this->evento_user_repo->registerUserAsDelivered($result->getEventoId());
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
        $this->ilias_user_service->setUserTimeLimits();
    }
}

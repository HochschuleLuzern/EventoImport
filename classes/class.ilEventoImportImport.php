<?php declare(strict_types = 1);

/**
 * Copyright (c) 2017 Hochschule Luzern
 *
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

use EventoImport\communication\request_services\RequestClientService;
use EventoImport\communication\ImporterApiSettings;
use EventoImport\communication\request_services\RestClientService;
use EventoImport\communication\EventoUserImporter;
use EventoImport\communication\EventoEventImporter;
use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\communication\EventoAdminImporter;
use EventoImport\import\EventoImportBootstrap;
use EventoImport\import\action\user\UserActionFactory;
use EventoImport\import\action\event\EventActionFactory;
use EventoImport\import\data_matching\UserActionDecider;
use EventoImport\import\data_matching\EventImportActionDecider;
use ILIAS\DI\RBACServices;
use ILIAS\Refinery\Factory;

/**
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImportImport extends ilCronJob
{
    const ID = "crevento_import";
    
    private RBACServices $rbac;
    private ilDBInterface $db;

    private Factory $refinery;
    private \ilEventoImportPlugin $cp;
    private ilSetting $settings;
    private $importUsersConfig;
    private int $page_size;
    private ilEventoImportCronStateChecker $state_checker;

    private EventoImportBootstrap $import_bootstrapping;
    
    public function __construct(
        \ilEventoImportPlugin $cp,
        RBACServices $rbac_services,
        ilDBInterface $db,
        Factory $refinery,
        ilSetting $settings
    ) {
        $this->cp = $cp;
        $this->rbac = $rbac_services;
        $this->db = $db;
        $this->refinery = $refinery;
        $this->settings = $settings;
        $this->state_checker = new ilEventoImportCronStateChecker($this->db);
        
        $this->import_bootstrapping = new EventoImportBootstrap($this->db, $this->rbac, $this->settings);

        $this->page_size = 500;
    }
    
    public function getId() : string
    {
        return self::ID;
    }
    
    public function hasAutoActivation() : bool
    {
        return false;
    }
    
    public function hasFlexibleSchedule() : bool
    {
        return true;
    }
    
    public function getDefaultScheduleType() : int
    {
        return self::SCHEDULE_TYPE_DAILY;
    }
    
    public function getDefaultScheduleValue() : int
    {
        return 1;
    }
    
    public function getTitle() : string
    {
        return $this->cp->txt("title");
    }
    
    public function getDescription() : string
    {
        return $this->cp->txt("description");
    }
    
    public function isManuallyExecutable() : bool
    {
        return true;
    }

    public function hasCustomSettings() : bool
    {
        return true;
    }
    
    public function run() : ilCronJobResult
    {
        try {
            $logger = new ilEventoImportLogger($this->db);

            $api_settings = new ImporterApiSettings($this->settings);

            $data_source = new RestClientService(
                $api_settings->getUrl(),
                $api_settings->getTimeoutAfterRequest(),
                $api_settings->getApikey(),
                $api_settings->getApiSecret()
            );

            if ($this->import_state_checker->wasFullImportAlreadyRunToday()) {
                $this->runHourlyAdminImport($data_source, $api_settings, $logger);
            } else {
                $this->runDailyFullImport($data_source, $api_settings, $logger);
            }

            return new ilEventoImportResult(ilEventoImportResult::STATUS_OK, 'Cron job terminated successfully.');
        } catch (Exception $e) {
            return new ilEventoImportResult(ilEventoImportResult::STATUS_CRASHED, 'Cron job crashed: ' . $e->getMessage());
        }
    }

    public function runDailyFullImport(RequestClientService $data_source, ImporterApiSettings $api_settings, \ilEventoImportLogger $logger) : void
    {
        $user_importer = new EventoUserImporter(
            $data_source,
            new ilEventoImporterIterator($this->page_size),
            $logger,
            $api_settings->getTimeoutFailedRequest(),
            $api_settings->getMaxRetries()
        );

        $event_importer = new EventoEventImporter(
            $data_source,
            new ilEventoImporterIterator($this->page_size),
            $logger,
            $api_settings->getTimeoutFailedRequest(),
            $api_settings->getMaxRetries()
        );

        $user_photo_importer = new EventoUserPhotoImporter(
            $data_source,
            $api_settings->getTimeoutFailedRequest(),
            $api_settings->getMaxRetries(),
            $logger
        );
        
        $user_action_factory = new UserActionFactory(
            $this->import_bootstrapping->userFacade(),
            $this->import_bootstrapping->defaultUserSettings(),
            $user_photo_importer,
            $logger
        );

        $user_import_action_decider = new UserActionDecider(
            $this->import_bootstrapping->userFacade(),
            $user_action_factory
        );

        $importUsers = new ilEventoImportImportUsers(
            $user_importer,
            $user_import_action_decider,
            $this->import_bootstrapping->userFacade(),
            $logger,
            $this->db
        );
        $importUsers->run();
        
        $event_action_factory = new EventActionFactory(
            $this->import_bootstrapping->eventObjectFactory(),
            $this->import_bootstrapping->repositoryFacade(),
            $this->import_bootstrapping->membershipManager(),
            $logger
        );
        $event_action_decider = new EventImportActionDecider(
            $this->import_bootstrapping->repositoryFacade(),
            $event_action_factory
        );

        $import_events = new ilEventoImportImportEventsAndMemberships($event_importer, $event_action_decider, $logger);
        $import_events->run();
    }

    public function runHourlyAdminImport(RequestClientService $data_source, ImporterApiSettings $api_settings, \ilEventoImportLogger $logger) : void
    {
        $admin_importer = new EventoAdminImporter(
            $data_source,
            $logger,
            $api_settings->getTimeoutFailedRequest(),
            $api_settings->getMaxRetries()
        );

        $import_admins = new ilEventoImportImportAdmins(
            $admin_importer,
            $this->import_bootstrapping->membershipManager(),
            $this->import_bootstrapping->eventoEventRepository(),
            $logger
        );

        $import_admins->run();
    }

    public function addCustomSettingsToForm(ilPropertyFormGUI $a_form) : void
    {
        $conf = new ilEventoImportCronConfig($this->settings, $this->cp, $this->rbac);
        $conf->fillCronJobSettingsForm($a_form);
    }
    
    public function saveCustomSettings(ilPropertyFormGUI $a_form) : bool
    {
        $conf = new ilEventoImportCronConfig($this->settings, $this->cp, $this->rbac);

        return $conf->saveCustomCronJobSettings($a_form);
    }
}

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
use EventoImport\import\action\UserImportActionDecider;
use EventoImport\import\action\EventImportActionDecider;
use ILIAS\DI\RBACServices;
use ILIAS\Refinery\Factory;
use EventoImport\import\UserImport;
use EventoImport\import\AdminImport;
use EventoImport\import\EventAndMembershipImport;
use EventoImport\import\Logger;
use EventoImport\communication\ImporterIterator;
use EventoImport\import\ImportFactory;

/**
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImportImport extends ilCronJob
{
    public const ID = "crevento_import";
    
    private RBACServices $rbac;
    private ilDBInterface $db;

    private Factory $refinery;
    private \ilEventoImportPlugin $cp;
    private ilSetting $settings;
    private ilEventoImportCronStateChecker $import_state_checker;

    private ImportFactory $import_factory;

    public function __construct(
        \ilEventoImportPlugin $cp,
        RBACServices $rbac_services,
        ilDBInterface $db,
        Factory $refinery,
        ilSetting $settings
    ) {
        global $DIC;

        $this->cp = $cp;
        $this->rbac = $rbac_services;
        $this->db = $db;
        $this->refinery = $refinery;
        $this->settings = $settings;

        $this->import_state_checker = new ilEventoImportCronStateChecker($this->db);
        $this->import_factory = new ImportFactory($db, $DIC->repositoryTree(), $rbac_services, $settings);
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
            $logger = new Logger($this->db);

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

    public function runDailyFullImport(RequestClientService $data_source, ImporterApiSettings $api_settings, Logger $logger) : void
    {
        $import_users = $this->import_factory->buildUserImport(
            new EventoUserImporter(
                $data_source,
                new ImporterIterator($api_settings->getPageSize()),
                $logger,
                $api_settings->getTimeoutFailedRequest(),
                $api_settings->getMaxRetries()
            ),
            new EventoUserPhotoImporter(
                $data_source,
                $api_settings->getTimeoutFailedRequest(),
                $api_settings->getMaxRetries(),
                $logger
            )
        );

        $import_events = $this->import_factory->buildEventImport(
            new EventoEventImporter(
                $data_source,
                new ImporterIterator($api_settings->getPageSize()),
                $logger,
                $api_settings->getTimeoutFailedRequest(),
                $api_settings->getMaxRetries()
            )
        );

        $import_users->run();
        $import_events->run();
    }

    public function runHourlyAdminImport(RequestClientService $data_source, ImporterApiSettings $api_settings, Logger $logger) : void
    {
        $import_admins = $this->import_factory->buildAdminImport(
            new EventoAdminImporter(
                $data_source,
                $logger,
                $api_settings->getTimeoutFailedRequest(),
                $api_settings->getMaxRetries()
            )
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

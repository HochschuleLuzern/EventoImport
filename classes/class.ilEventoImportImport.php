<?php

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

/**
 * Class ilEventoImportImport
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImportImport extends ilCronJob
{
    
    /**
     * @var string The ID of the cron job
     */
    const ID = "crevento_import";
    
    private $rbac;
    private $db;

    private $refinery;
    private $cp;
    private $settings;
    private $importUsersConfig;
    private $page_size;

    /** @var \EventoImport\import\EventoImportBootstrap */
    private \EventoImport\import\EventoImportBootstrap $import_bootstrapping;

    /**
     * Constructor
     */
    public function __construct(\ILIAS\DI\RBACServices $rbac_services = null, ilDBInterface $db = null)
    {
        global $DIC;
        $this->rbac = $rbac_services ?? $DIC->rbac();
        $this->db = $db ?? $DIC->database();
        $this->refinery = $DIC->refinery();
        //This is a workaround to avoid problems with missing templates
        if (!method_exists($DIC, 'ui') || !method_exists($DIC->ui(), 'factory') || !isset($DIC['ui.factory'])) {
            ilInitialisation::initUIFramework($DIC);
            ilStyleDefinition::setCurrentStyle('Desktop');
        }
        $this->cp = new ilEventoImportPlugin();
        $this->settings = new ilSetting("crevento");
        $this->import_bootstrapping = new \EventoImport\import\EventoImportBootstrap($this->db, $this->rbac);

        $this->page_size = 500;
    }

    /**
     * Retrieve the ID of the cron job
     * @return string
     */
    public function getId()
    {
        return self::ID;
    }
    
    /**
     * Cron job will not be activated on Plugin activation
     *
     * @return bool
     */
    public function hasAutoActivation()
    {
        return false;
    }
    
    /**
     * Cron job schedule can be set in the interface
     *
     * @return bool
     */
    public function hasFlexibleSchedule()
    {
        return true;
    }
    
    /**
     * Cron job schedule is set to daily by default
     *
     * @return int
     */
    public function getDefaultScheduleType()
    {
        return self::SCHEDULE_TYPE_DAILY;
    }
    
    /**
     * Defines the interval between cron job runs in SCHEDULE_TYPE
     *
     * @return array|int
     */
    public function getDefaultScheduleValue()
    {
        return 1;
    }
    
    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->cp->txt("title");
    }
    
    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->cp->txt("description");
    }
    
    /**
     * Cron job can be started manually
     *
     * @return bool
     */
    public function isManuallyExecutable()
    {
        return true;
    }
    
    /**
     * Cron job has custom settings in the cron job admin section
     *
     * @return boolean
     */
    public function hasCustomSettings()
    {
        return true;
    }
    
    public function run()
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

            if ($this->wasFullImportAlreadyRunToday()) {
                $this->runHourlyAdminImport($data_source, $api_settings, $logger);
            } else {
                $this->runDailyFullImport($data_source, $api_settings, $logger);
            }

            return new ilEventoImportResult(ilEventoImportResult::STATUS_OK, 'Cron job terminated successfully.');
        } catch (Exception $e) {
            return new ilEventoImportResult(ilEventoImportResult::STATUS_CRASHED, 'Cron job crashed: ' . $e->getMessage());
        }
    }

    /**
     * @param RequestClientService $data_source
     * @param ImporterApiSettings  $api_settings
     * @param ilEventoImportLogger $logger
     */
    public function runDailyFullImport(RequestClientService $data_source, ImporterApiSettings $api_settings, \ilEventoImportLogger $logger)
    {
        $user_importer = new \EventoImport\communication\EventoUserImporter(
            $data_source,
            new ilEventoImporterIterator($this->page_size),
            $logger,
            $api_settings->getTimeoutFailedRequest(),
            $api_settings->getMaxRetries()
        );

        $event_importer = new \EventoImport\communication\EventoEventImporter(
            $data_source,
            new ilEventoImporterIterator($this->page_size),
            $logger,
            $api_settings->getTimeoutFailedRequest(),
            $api_settings->getMaxRetries()
        );

        $user_photo_importer = new \EventoImport\communication\EventoUserPhotoImporter(
            $data_source,
            $api_settings->getTimeoutFailedRequest(),
            $api_settings->getMaxRetries(),
            $logger
        );

        /* User import */
        $user_action_factory = new \EventoImport\import\action\user\UserActionFactory(
            $this->import_bootstrapping->userFacade(),
            $this->import_bootstrapping->defaultUserSettings(),
            $user_photo_importer,
            $logger
        );

        $user_import_action_decider = new \EventoImport\import\data_matching\UserActionDecider(
            $this->import_bootstrapping->userFacade(),
            $user_action_factory
        );

        $importUsers = new ilEventoImportImportUsers(
            $user_importer,
            $user_import_action_decider,
            $this->import_bootstrapping->userFacade(),
            $logger,
            $this->import_bootstrapping->defaultUserSettings()->getMaxDurationOfAccounts(),
            $this->db
        );
        $importUsers->run();

        /* Event import */
        $event_action_factory = new \EventoImport\import\action\event\EventActionFactory(
            $this->import_bootstrapping->eventObjectFactory(),
            $this->import_bootstrapping->repositoryFacade(),
            $this->import_bootstrapping->membershipManager(),
            $logger
        );
        $event_action_decider = new \EventoImport\import\data_matching\EventImportActionDecider(
            $this->import_bootstrapping->repositoryFacade(),
            $event_action_factory
        );

        $import_events = new ilEventoImportImportEventsAndMemberships($event_importer, $event_action_decider, $logger);
        $import_events->run();
    }

    /**
     * @param RequestClientService $data_source
     * @param ImporterApiSettings  $api_settings
     * @param ilEventoImportLogger $logger
     */
    public function runHourlyAdminImport(RequestClientService $data_source, ImporterApiSettings $api_settings, \ilEventoImportLogger $logger)
    {
        $admin_importer = new \EventoImport\communication\EventoAdminImporter($data_source, $logger, $api_settings->getTimeoutFailedRequest(), $api_settings->getMaxRetries());

        $import_admins = new ilEventoImportImportAdmins(
            $admin_importer,
            $this->import_bootstrapping->membershipManager(),
            $this->import_bootstrapping->eventoEventRepository(),
            $logger
        );

        $import_admins->run();
        ;
    }

    /**
     * @return bool
     */
    private function wasFullImportAlreadyRunToday() : bool
    {
        // Try to get the date from the last run
        $sql = "SELECT * FROM cron_job WHERE job_id = " . $this->db->quote(self::ID, \ilDBConstants::T_TEXT);
        $res = $this->db->query($sql);
        $cron = $this->db->fetchAssoc($res);

        $last_run = $cron['job_result_ts'];
        if (is_null($last_run)) {
            return false;
        }

        return date('d.m.Y H:i') == date('d.m.Y H:i', strtotime($cron['job_result_ts']));
    }

    /**
     * Defines the custom settings form and returns it to plugin slot
     *
     * @param ilPropertyFormGUI $a_form
     */
    public function addCustomSettingsToForm(ilPropertyFormGUI $a_form)
    {
        $conf = new ilEventoImportCronConfig($this->settings, $this->cp);
        $conf->fillCronJobSettingsForm($a_form);
    }
    
    /**
     * Saves the custom settings values
     *
     * @param ilPropertyFormGUI $a_form
     * @return boolean
     */
    public function saveCustomSettings(ilPropertyFormGUI $a_form)
    {
        $conf = new ilEventoImportCronConfig($this->settings, $this->cp);

        return $conf->saveCustomCronJobSettings($a_form);
    }
}

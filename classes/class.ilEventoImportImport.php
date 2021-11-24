<?php

use EventoImport\import\data_matching\EventoUserToIliasUserMatcher;
use EventoImport\import\db\query\IliasUserQuerying;
use ILIAS\DI\RBACServices;
use ILIAS\Refinery;

include_once('./Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportImportUsersConfig.php');


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
    
    /**
     * Constructor
     */
    public function __construct()
    {
        global $DIC;
        $this->rbac = $DIC->rbac();
        $this->db = $DIC->database();
        $this->refinery = $DIC->refinery();
        //This is a workaround to avoid problems with missing templates
        if (!method_exists($DIC, 'ui') || !method_exists($DIC->ui(), 'factory') || !isset($DIC['ui.factory'])) {
            ilInitialisation::initUIFramework($DIC);
            ilStyleDefinition::setCurrentStyle('Desktop');
        }
        $this->cp = new ilEventoImportPlugin();
        $this->settings = new ilSetting("crevento");
        $this->importUsersConfig = new ilEventoImportImportUsersConfig($this->settings, $this->rbac);

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
            
            /*
            $base_url = '';
            $port = 1337;
            $base_path = '';
            $data_source = new \EventoImport\communication\request_services\RestClientService($base_url, $port, $base_path);
            */
            $data_source = new \EventoImport\communication\request_services\FakeRestClientService('', 0, '');

            $user_importer = new \EventoImport\communication\EventoUserImporter(new ilEventoImporterIterator($this->page_size), $this->settings, $logger, $data_source);
            $event_importer = new \EventoImport\communication\EventoEventImporter(new ilEventoImporterIterator($this->page_size), $this->settings, $logger, $data_source);


            /*
            $mailbox_search = new ilRoleMailboxSearch(
                new ilMailRfc822AddressParserFactory()
                );
            $favourites_manager = new ilFavouritesManager();
            */

            /* User import */
            $user_facade = new \EventoImport\import\db\UserFacade(
                new \EventoImport\import\db\query\IliasUserQuerying($this->db),
                new \EventoImport\import\db\repository\EventoUserRepository($this->db),
                new \EventoImport\import\db\repository\EventMembershipRepository($this->db),
                $this->rbac
            );
            $default_user_settings = new \EventoImport\import\settings\DefaultUserSettings($this->settings);
            $user_action_factory = new \EventoImport\import\action\user\UserActionFactory($user_facade, $default_user_settings, $logger);

            $user_import_action_decider = new \EventoImport\import\data_matching\UserActionDecider($user_facade, $user_action_factory);

            $importUsers = new ilEventoImportImportUsers($user_importer, $user_import_action_decider, $user_facade, $logger);
            $importUsers->run();

            /* Event import */
            $event_query = new \EventoImport\import\db\query\IliasEventObjectQuery($this->db);
            $event_repo = new \EventoImport\import\db\repository\IliasEventoEventsRepository($this->db);
            $location_repo = new \EventoImport\import\db\repository\EventLocationsRepository($this->db);

            $repository_facade = new \EventoImport\import\db\RepositoryFacade(
                $event_query,
                $event_repo,
                $location_repo
            );

            $default_event_settings = new \EventoImport\import\settings\DefaultEventSettings($this->settings);
            $ilias_event_object_factory = new \EventoImport\import\IliasEventObjectFactory($repository_facade, $default_event_settings);
            $event_action_factory = new \EventoImport\import\action\event\EventActionFactory($ilias_event_object_factory, $repository_facade, $user_facade, $default_event_settings, $logger);
            $event_action_decider = new \EventoImport\import\data_matching\EventImportActionDecider($repository_facade, $event_action_factory);

            $import_events = new ilEventoImportImportEventsAndMemberships($event_importer, $event_action_decider, $logger);
            //$import_events->run();

            return new ilEventoImportResult(ilEventoImportResult::STATUS_OK, 'Cron job terminated successfully.');
        } catch (Exception $e) {
            return new ilEventoImportResult(ilEventoImportResult::STATUS_CRASHED, 'Cron job crashed: ' . $e->getMessage());
        }
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

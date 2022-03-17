<?php declare(strict_types = 1);

use EventoImport\import\Logger;
use EventoImport\import\ImportTaskFactory;
use EventoImport\config\ConfigurationManager;
use EventoImport\config\CronConfigForm;

/**
 * Copyright (c) 2017 Hochschule Luzern
 *
 * This file is part of the NotifyOnCronFailure-Plugin for ILIAS.

 * NotifyOnCronFailure-Plugin for ILIAS is free software: you can redistribute
 * it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * NotifyOnCronFailure-Plugin for ILIAS is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with NotifyOnCronFailure-Plugin for ILIAS.  If not,
 * see <http://www.gnu.org/licenses/>.
 */

/**
 * Class ilEventoImportPlugin
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImportPlugin extends ilCronHookPlugin
{
    const PLUGIN_NAME = "EventoImport";
    
    public function getPluginName()
    {
        return self::PLUGIN_NAME;
    }
    
    /**
     * @var ilCronJob[]
     */
    protected static $cron_job_instances;
    
    /**
     * @return  ilCronJob[]
     */
    public function getCronJobInstances() : array
    {
        $this->loadCronJobInstance();
        
        return array_values(self::$cron_job_instances);
    }
    
    /**
     * @return  ilCronJob or false on failure
     */
    public function getCronJobInstance($a_job_id)
    {
        $this->loadCronJobInstance();
        if (isset(self::$cron_job_instances[$a_job_id])) {
            return self::$cron_job_instances[$a_job_id];
        } else {
            return false;
        }
    }
    
    protected function loadCronJobInstance()
    {
        global $DIC;
        $db = $DIC->database();
        $rbac = $DIC->rbac();
        $tree = $DIC->repositoryTree();

        //This is a workaround to avoid problems with missing templates
        if (!method_exists($DIC, 'ui') || !method_exists($DIC->ui(), 'factory') || !isset($DIC['ui.factory'])) {
            ilInitialisation::initUIFramework($DIC);
            ilStyleDefinition::setCurrentStyle('Desktop');
        }
        
        if (!isset(self::$cron_job_instances)) {
            $settings = new ilSetting('crevento');
            $cron_config = new CronConfigForm($settings, $this, $rbac);
            $config_manager = new ConfigurationManager($cron_config, $settings, $db);
            $import_factory = new ImportTaskFactory($config_manager, $db, $tree, $rbac);
            $logger = new Logger($db);

            self::$cron_job_instances[ilEventoImportDailyImportCronJob::ID] = new ilEventoImportDailyImportCronJob(
                $this,
                $import_factory,
                $config_manager,
                $logger
            );
            self::$cron_job_instances[ilEventoImportHourlyImportCronJob::ID] = new ilEventoImportHourlyImportCronJob(
                $this,
                $import_factory,
                $config_manager,
                $logger
            );
        }
    }

    protected function beforeUninstall()
    {
        global $DIC;
        $db = $DIC->database();
        
        $drop_table_list = [
            'crnhk_crevento_usrs',
            'crnhk_crevento_mas',
            'crnhk_crevento_subs',
            \EventoImport\db\IliasEventoUserTblDef::TABLE_NAME,
            \EventoImport\db\IliasEventoEventsTblDef::TABLE_NAME,
            \EventoImport\db\IliasParentEventTblDef::TABLE_NAME,
            \EventoImport\db\IliasEventLocationsTblDef::TABLE_NAME,
            \EventoImport\db\IliasEventoEventMembershipsTblDef::TABLE_NAME,
            Logger::TABLE_LOG_USERS,
            Logger::TABLE_LOG_EVENTS,
            Logger::TABLE_LOG_MEMBERSHIPS
        ];

        foreach ($drop_table_list as $key => $table) {
            if ($db->tableExists($table)) {
                $db->dropTable($table);
            }
        }

        return true;
    }
}

<?php
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

require_once './Services/Cron/classes/class.ilCronJob.php';
require_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportPlugin.php';
require_once './Services/Administration/classes/class.ilSetting.php';

/**
 * Class ilNotifyOnCronFailureNotify
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImportImport extends ilCronJob {
	
	/**
	 * @var string The ID of the cron job
	 */
	const ID = "crevento_import";
	
	private $cp;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->cp = new ilEventoImportPlugin();
	}
	
	/**
	 * Retrieve the ID of the cron job
	 * @return string
	 */
	public function getId() {
		return self::ID;
	}
	
	/**
	 * Cron job will not be activated on Plugin activation
	 * 
	 * @return bool
	 */
	public function hasAutoActivation() {
		return false;
	}
	
	/**
	 * Cron job schedule can be set in the interface
	 * 
	 * @return bool
	 */
	public function hasFlexibleSchedule() {
		return true;
	}
	
	/**
	 * Cron job schedule is set to daily by default
	 * 
	 * @return int
	 */
	public function getDefaultScheduleType() {
		return self::SCHEDULE_TYPE_DAILY;
	}
	
	/**
	 * Defines the interval between cron job runs in SCHEDULE_TYPE
	 * 
	 * @return array|int
	 */
	public function getDefaultScheduleValue() {
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
	
	public function run() {
		require_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImporter.php';
		require_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportResult.php';
		require_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportImportUsers.php';
		require_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportImportMemberships.php';
		
		try {
			ilEventoImportImportUsers::run();
			ilEventoImportImportMemberships::run();
			
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
		$settings = new ilSetting("crevento");
		
		include_once 'Services/Form/classes/class.ilSelectInputGUI.php';
		include_once 'Services/LDAP/classes/class.ilLDAPServer.php';
		$ws_item = new ilSelectInputGUI(
				$this->cp->txt('ilias_auth_mode'),
				'crevento_ilias_auth_mode'
				);
		$ws_item->setInfo($this->cp->txt('ilias_auth_mode_desc'));
		$auth_modes = ilAuthUtils::_getAllAuthModes();
		$options = [];
		foreach ($auth_modes as $auth_mode => $auth_name) {
			if(ilLDAPServer::isAuthModeLDAP($auth_mode)) {
				$server = ilLDAPServer::getInstanceByServerId(ilLDAPServer::getServerIdByAuthMode($auth_mode));
				if ($server->isActive()) $options[$auth_name] = $auth_name;
			} else if ($settings->get($auth_name.'_active') || $auth_mode == AUTH_LOCAL) {
				$options[$auth_name] = $auth_name;
			}
		}
		$ws_item->setOptions($options);
		$ws_item->setValue($settings->get('crevento_ilias_auth_mode'));
		$a_form->addItem($ws_item);
		
		include_once 'Services/Form/classes/class.ilNumberInputGUI.php';
		$ws_item = new ilNumberInputGUI(
				$this->cp->txt('account_duration'),
				'crevento_account_duration'
				);
		$ws_item->setInfo($this->cp->txt('account_duration_desc'));
		$ws_item->allowDecimals(false);
		$ws_item->setMinValue(0);
		$ws_item->setRequired(true);
		$ws_item->setValue($settings->get('crevento_account_duration', '0'));
		$a_form->addItem($ws_item);
		
		include_once 'Services/Form/classes/class.ilNumberInputGUI.php';
		$ws_item = new ilNumberInputGUI(
		    $this->cp->txt('max_account_duration'),
		    'crevento_max_account_duration'
		    );
		$ws_item->setInfo($this->cp->txt('max_account_duration_desc'));
		$ws_item->allowDecimals(false);
		$ws_item->setMinValue(0);
		$ws_item->setRequired(true);
		$ws_item->setValue($settings->get('crevento_max_account_duration', '0'));
		$a_form->addItem($ws_item);
		
		$ws_item = new ilNumberInputGUI(
				$this->cp->txt('standard_user_role_id'),
				'crevento_standard_user_role_id'
				);
		$ws_item->setInfo($this->cp->txt('standard_user_role_id_desc'));
		$ws_item->allowDecimals(false);
		$ws_item->setMinValue(0);
		$ws_item->setRequired(true);
		$ws_item->setValue($settings->get('crevento_standard_user_role_id', '109'));
		$a_form->addItem($ws_item);
		
		include_once 'Services/Form/classes/class.ilTextInputGUI.php';
		$ws_item = new ilTextInputGUI(
				$this->cp->txt('ws_user'),
				'crevento_ws_user'
			);
		$ws_item->setInfo($this->cp->txt('ws_user_desc'));
		$ws_item->setRequired(true);
		$ws_item->setValue($settings->get('crevento_ws_user', ""));
		$a_form->addItem($ws_item);
		
		include_once 'Services/Form/classes/class.ilPasswordInputGUI.php';
		$ws_item = new ilPasswordInputGUI(
				$this->cp->txt('ws_password'),
				'crevento_ws_password'
			);
		$ws_item->setInfo($this->cp->txt('ws_password_desc'));
		$ws_item->setRequired(true);
		$ws_item->setRetype(false);
		$ws_item->setValue($settings->get('crevento_ws_password', ""));
		$a_form->addItem($ws_item);
		
		$ws_item = new ilTextInputGUI(
				$this->cp->txt('wsdl'),
				'crevento_wsdl'
				);
		$ws_item->setInfo($this->cp->txt('wsdl_desc'));
		$ws_item->setRequired(true);
		$ws_item->setValue($settings->get('crevento_wsdl', ""));
		$a_form->addItem($ws_item);
		
		$ws_item = new ilNumberInputGUI(
				$this->cp->txt('pagesize'),
				'crevento_pagesize'
				);
		$ws_item->setInfo($this->cp->txt('pagesize_desc'));
		$ws_item->allowDecimals(false);
		$ws_item->setMinValue(0);
		$ws_item->setRequired(true);		
		$ws_item->setValue($settings->get('crevento_pagesize', '1200'));
		$a_form->addItem($ws_item);
		
		$ws_item = new ilNumberInputGUI(
				$this->cp->txt('max_pages'),
				'crevento_max_pages'
				);
		$ws_item->setInfo($this->cp->txt('max_pages_desc'));
		$ws_item->allowDecimals(false);
		$ws_item->setRequired(true);
		$ws_item->setValue($settings->get('crevento_max_pages', '-1'));
		$a_form->addItem($ws_item);
		
		$ws_item = new ilNumberInputGUI(
				$this->cp->txt('max_retries'),
				'crevento_max_retries'
				);
		$ws_item->setInfo($this->cp->txt('max_retries_desc'));
		$ws_item->allowDecimals(false);
		$ws_item->setMinValue(0);
		$ws_item->setRequired(true);
		$ws_item->setValue($settings->get('crevento_max_retries', '2'));
		$a_form->addItem($ws_item);
		
		$ws_item = new ilNumberInputGUI(
				$this->cp->txt('seconds_before_retry'),
				'crevento_seconds_before_retry'
				);
		$ws_item->setInfo($this->cp->txt('seconds_before_retry_desc'));
		$ws_item->allowDecimals(false);
		$ws_item->setMinValue(0);
		$ws_item->setRequired(true);
		$ws_item->setValue($settings->get('crevento_seconds_before_retry', '60'));
		$a_form->addItem($ws_item);
		
		$ws_item = new ilTextInputGUI(
				$this->cp->txt('email_account_changed_subject'),
				'crevento_email_account_changed_subject'
				);
		$ws_item->setInfo($this->cp->txt('email_account_changed_subject_desc'));
		$ws_item->setRequired(true);
		$ws_item->setValue($settings->get('crevento_email_account_changed_subject', ''));
		$a_form->addItem($ws_item);
		
		include_once 'Services/Form/classes/class.ilTextAreaInputGUI.php';
		$ws_item = new ilTextAreaInputGUI(
				$this->cp->txt('email_account_changed_body'),
				'crevento_email_account_changed_body'
				);
		$ws_item->setInfo($this->cp->txt('email_account_changed_body_desc'));
		$ws_item->setRequired(true);
		$ws_item->usePurifier(true);
		$ws_item->setValue($settings->get('crevento_email_account_changed_body', ''));
		$a_form->addItem($ws_item);
	}
	
	/**
	 * Saves the custom settings values
	 * 
	 * @param ilPropertyFormGUI $a_form
	 * @return boolean
	 */
	public function saveCustomSettings(ilPropertyFormGUI $a_form)
	{
		$settings = new ilSetting("crevento");
		
		if ($_POST['crevento_ilias_auth_mode'] != null) {
			$settings->set('crevento_ilias_auth_mode', $_POST['crevento_ilias_auth_mode']);
		}
		
		if ($_POST['crevento_account_duration'] != null) {
			$settings->set('crevento_account_duration', $_POST['crevento_account_duration']);
		}
		
		if ($_POST['crevento_max_account_duration'] != null) {
		    $settings->set('crevento_max_account_duration', $_POST['crevento_max_account_duration']);
		}
		
		if ($_POST['crevento_standard_user_role_id'] != null) {
			$settings->set('crevento_standard_user_role_id', $_POST['crevento_standard_user_role_id']);
		}

		if ($_POST['crevento_ws_user'] != null) {
			$settings->set('crevento_ws_user', $_POST['crevento_ws_user']);
		}
		
		if ($_POST['crevento_ws_password'] != null) {
			$settings->set('crevento_ws_password', $_POST['crevento_ws_password']);
		}
		
		if ($_POST['crevento_wsdl'] != null) {
			$settings->set('crevento_wsdl', $_POST['crevento_wsdl']);
		}
		
		if ($_POST['crevento_pagesize'] != null) {
			$settings->set('crevento_pagesize', $_POST['crevento_pagesize']);
		}
		
		if ($_POST['crevento_max_pages'] != null) {
			$settings->set('crevento_max_pages', $_POST['crevento_max_pages']);
		}
		
		if ($_POST['crevento_max_retries'] != null) {
			$settings->set('crevento_max_retries', $_POST['crevento_max_retries']);
		}
		
		
		if ($_POST['crevento_seconds_before_retry'] != null) {
			$settings->set('crevento_seconds_before_retry', $_POST['crevento_seconds_before_retry']);
		}
		
		if ($_POST['crevento_email_account_changed_subject'] != null) {
			$settings->set('crevento_email_account_changed_subject', $_POST['crevento_email_account_changed_subject']);
		}
		
		if ($_POST['crevento_email_account_changed_body'] != null) {
			$settings->set('crevento_email_account_changed_body', $_POST['crevento_email_account_changed_body']);
		}
	
		return true;
	}
}
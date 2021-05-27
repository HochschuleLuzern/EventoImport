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

/**
 * Class ilEventoImportImport
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
		$ws_item->setSkipSyntaxCheck(true);
		$ws_item->setRequired(true);
		$ws_item->setRetype(false);
		$ws_item->setValue($settings->get('crevento_ws_password', '') == '' ? '' : '(__unchanged__)');
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
		
		if ($a_form->getInput('crevento_ilias_auth_mode') != null) {
			$settings->set('crevento_ilias_auth_mode', $a_form->getInput('crevento_ilias_auth_mode'));
		}
		
		if ($a_form->getInput('crevento_account_duration') != null) {
			$settings->set('crevento_account_duration', $a_form->getInput('crevento_account_duration'));
		}
		
		if ($a_form->getInput('crevento_max_account_duration') != null) {
			$settings->set('crevento_max_account_duration', $a_form->getInput('crevento_max_account_duration'));
		}
		
		if ($a_form->getInput('crevento_standard_user_role_id') != null) {
			$settings->set('crevento_standard_user_role_id', $a_form->getInput('crevento_standard_user_role_id'));
		}

		if ($a_form->getInput('crevento_ws_user') != null) {
			$settings->set('crevento_ws_user', $a_form->getInput('crevento_ws_user'));
		}
		
		if ($a_form->getInput('crevento_ws_password') != null && $a_form->getInput('crevento_ws_password') != '(__unchanged__)') {
			$settings->set('crevento_ws_password', $a_form->getInput('crevento_ws_password'));
		}
		
		if ($a_form->getInput('crevento_wsdl') != null) {
			$settings->set('crevento_wsdl', $a_form->getInput('crevento_wsdl'));
		}
		
		if ($a_form->getInput('crevento_pagesize') != null) {
			$settings->set('crevento_pagesize', $a_form->getInput('crevento_pagesize'));
		}
		
		if ($a_form->getInput('crevento_max_pages') != null) {
			$settings->set('crevento_max_pages', $a_form->getInput('crevento_max_pages'));
		}
		
		if ($a_form->getInput('crevento_max_retries') != null) {
			$settings->set('crevento_max_retries', $a_form->getInput('crevento_max_retries'));
		}
		
		if ($a_form->getInput('crevento_seconds_before_retry') != null) {
			$settings->set('crevento_seconds_before_retry', $a_form->getInput('crevento_seconds_before_retry'));
		}
		
		if ($a_form->getInput('crevento_email_account_changed_subject') != null) {
			$settings->set('crevento_email_account_changed_subject', $a_form->getInput('crevento_email_account_changed_subject'));
		}
		
		if ($a_form->getInput('crevento_email_account_changed_body') != null) {
			$settings->set('crevento_email_account_changed_body', $a_form->getInput('crevento_email_account_changed_body'));
		}
		
		return true;
	}
}
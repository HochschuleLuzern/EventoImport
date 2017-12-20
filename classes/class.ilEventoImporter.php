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

require_once 'Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportLogger.php';

/**
 * Class ilNotifyOnCronFailureResult
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImporter {
	protected static $instance;
	private $evento_logger;

	private $which;
	private $client;
	private $ws_user;
	private $ws_password;
	private $auth_mode;
	private $wsdl;
	private $pagesize;
	private $max_pages;
	private $max_retries;
	private $seconds_before_retry;
	private $soap_client;
	private $token;
	
	private $page;
	
	/**
	 * constructor
	 * @param	string	Webservice User
	 * @param	string	Webservice Password
	 * @param	string	Which data to import: All|Students|Staff|Bachelor|Master|Further|TimeLimit|TutorFurther
	 * @access	public
	 */
	private function __construct() {
		$settings = new ilSetting("crevento");
		
		//Get Settings from dbase
		$this->client = CLIENT_ID;
		$this->ws_user = $settings->get('crevento_ws_user');
		$this->ws_password = $settings->get('crevento_ws_password');
		$this->auth_mode = $settings->get('crevento_ilias_auth_mode');
		$this->wsdl = $settings->get('crevento_wsdl');
		$this->pagesize = (int) $settings->get('crevento_pagesize');
		$this->max_pages = (int) $settings->get('crevento_max_pages');
		$this->max_retries = (int) $settings->get('crevento_max_retries');
		$this->seconds_before_retry = (int) $settings->get('crevento_seconds_before_retry');
		
		$this->evento_logger = ilEventoImportLogger::getInstance();
		
		// Connect to Evento SOAP
		include_once 'Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportSOAPClient.php';
		$this->soap_client = new ilEventoImportSOAPClient($this->wsdl);
		$this->soap_client->setTimeout($this->seconds_before_retry);
		$answer = $this->soap_client->init();
		if ($answer) {
			$this->login();
		} else {
			throw new Exception ('Error while trying to initialize SOAP-Server. '.$this->soap_client->getError());
		}
	
	}
	
	/**
	 * Provides an instance of the importer
	 * @return ilEventoImporter
	 */
	public static function getInstance() {
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Retrieves a login token from the SOAP-Interface
	 * @throws Exception
	 */
	private function login() {
		do {
			$result = $this->soap_client->call('Login', array('parameters' => array('username'=>$this -> ws_user, 'password' => $this->ws_password)));

			if ($this->soap_client->getError()) {
				if (isset($i)) {
					$i++;
				} else {
					$i=1;
				}
			} else if ($result->LoginResult == 'wrong credentials') {
				throw new Exception('The credentials for the SOAP-Server you provided are not correct.');
			} else if ($result->LoginResult == null) {
				throw new Exception('The SOAP-Server did not provided us with a token.');
			} else {
				$this->token = $result->LoginResult;
			}
		} while ($this->token == null && $i < $this->max_retries);
		
		if ($this->token == null) {
			throw new Exception('Error while trying to log into SOAP-Server. '.$this->soap_client->getError());
		}
	}
	
	/**
	 * Retrieves a single record from the SOAP-interface
	 * @param string $operation
	 * @param array $params
	 * @return array or false
	 */
	public function getRecord($operation, $params) {
		try {
			$return = &$this->callWebService($operation, $params);
		} catch (Exception $e) {
			$this->evento_logger->logException($operation, $e->getMessage());
			$return = false;
		} finally {
			return $return;
		}
	}
	
	/**
	 * Retrieves multiple records from the SOAP-Interface
	 * @param string $operation
	 * @param string $dataset
	 * @param ilEventoImporterIterator $iterator
	 * @param array $params
	 * @return array with the records containing if no further records can be retrieved $return['is_last'] is set to true, if no records could be retrieved $return['finished'] is set to true.
	 */
	public function getRecords($operation, $dataset, &$iterator, $params = array()) {
		try {
			if ($this->max_pages == -1 || $iterator->getPage() <= $this->max_pages) {
				$params['parameters']['pagesize'] = $this->pagesize;
				$params['parameters']['pagenumber'] = $iterator->getPage();

				$result = &$this->callWebService($operation, $params);

				if ($result !== false) {
					$result = (new SimpleXMLElement($result->{$operation.'Result'}->any))->{'ds'.$dataset};
			
					foreach ($result->$dataset as $data) {
						foreach ($data as $key => $value) {
							$data_array[$key] = $value->__toString();
						}
					
						$return['data'][] = $data_array;
					}
				}

				if ($result == false || count($return['data']) == 0) {
					$return['finished'] = true;
				} else if (count($return['data']) < $this->pagesize) {
					$return['finished'] = false;
					$return['is_last'] = true;
				} else {
					$return['finished'] = false;
					$return['is_last'] = false; 
				$iterator->nextPage();
				}
			} else {
				$return['finished'] = true;
			}
		} catch (Exception $e) {
			$this->evento_logger->logException($operation, $e->getMessage());
			$return['finished'] = true;
		} finally {
			return $return;
		}
	}
	
	/**
	 * Triggers an action on the SOAP-Interface
	 * @param string $operation
	 * @param array $params
	 * @return boolean true on success, false on failure
	 */
	public function trigger($operation, $params = array()) {
		try {
			$result = &$this->callWebService($operation, $params);
			$result ? $return = true : $return = false;
		} catch (Exception $e) {
			$this->evento_logger->logException($operation, $e->getMessage());
			$return = false;
		} finally {
			return $return;
		}
	}
	
	/**
	 * Calls the SOAP-Interface
	 * @param string $operation
	 * @param array $params
	 * @return array or false
	 */
	private function callWebservice($operation, $params) {
		$i = 1;
		do {
			$params['parameters']['token'] = $this->token;

			$result = &$this->soap_client->call($operation, $params);

			if ($this->soap_client->getError()) {
			    if (strpos($this->soap_client->getError(),'Token') !== false) {
					// Session timed out. Clear token, wait and then retry
					$this->token = null;
					sleep($this->seconds_before_retry);
					$this->login();
					$result = false;
				} else {
					sleep($this->seconds_before_retry);
					$result = false;
				}
			} else if ($result == null || $result == '') {
				$this->evento_logger->logException($operation, "We got no result back from Webservice.");
				$result = false;
			} else {
				return $result;
			}
			
			$this->evento_logger->logException($operation, "We didn't get an answer on the $i try. The error was: {$this->soap_client->getError()}..The connect-Timeout was {$this->soap_client->getTimeout()} and the response-Timeout was {$this->soap_client->getResponseTimeout()}.");
			$i++;

		} while ($i <= $this->max_retries);
		
		$this->evento_logger->logException($operation, "We tried {$this->max_retries} times to fetch information and failed.");
		
		return $result;
	}
}
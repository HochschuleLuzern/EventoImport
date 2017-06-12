<?php
require_once 'Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportLogger.php';

class ilEventoImporter {
	protected static $instance;
	private $evento_logger;

	private $which;
	private $client;
	private $ws_user;
	private $ws_password;
	private $auth_mode;
	private $send_emails_to_users;
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
		$this->send_emails_to_users = $settings->get('crevento_ws_send_emails_to_users');
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
		$this->soap_client->init();
		$this->login();
	
	}
	
	public static function getInstance() {
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
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
	
	public function getRecord($operation, $params) {
		try {
			$return = &$this->callWebService($operation, $params);
		} catch (Exception $e) {
			$this->evento_logger->logException($operation, $e);
		} finally {
			return $return;
		}
	}
	
	public function getRecords($operation, $dataset, &$iterator, $params = array()) {
		try {
			if ($this->max_pages == -1 || $iterator->getPage() <= $this->max_pages) {
				$params['parameters']['pagesize'] = $this->pagesize;
				$params['parameters']['pagenumber'] = $iterator->getPage();

				$result = &$this->callWebService($operation, $params);
				
				$result = (new SimpleXMLElement($result->{$operation.'Result'}->any))->{'ds'.$dataset};
				
				foreach ($result->$dataset as $data) {
					foreach ($data as $key => $value) {
						$data_array[$key] = $value->__toString();
					}
					
					$return['data'][] = $data_array;
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
		} finally {
			return $return;
		}
	}
	
	public function trigger($operation, $params = array()) {
		try {
			$result = &$this->callWebService($operation, $params);
			$result ? $return = true : $return = false;
		} catch (Exception $e) {
			$this->evento_logger->logException($operation, $e->getMessage());
		} finally {
			return $return;
		}
	}
	
	private function callWebservice($operation, $params) {
		do {
			$params['parameters']['token'] = $this->token;

			$result = &$this->soap_client->call($operation, $params);

			if ($this->soap_client->getError()) {
				if (strpos($result,'Token:') !== false) {
					// Session timed out. Clear token, wait and then retry
					$this->token = null;
					sleep($this->numberOfSecondsBeforeRetry);
					$this->login();
				} else if (strpos($result,'Operation timed out') !== false || strpos($result,'deadlock') !== false) {
					sleep($this->numberOfSecondsBeforeRetry);
				} else {
					$this->evento_logger->logException($operation, $this->soap_client->getError());
					$result = false;
				}
				
				if (isset($i)) {
					$i++;
				} else {
					$i=1;
				}
			} else {
				return $result;
			}
		} while ($i < $this->max_retries);
		
		$this->evento_logger->logException($operation, 'Operation timed out');
	}
}
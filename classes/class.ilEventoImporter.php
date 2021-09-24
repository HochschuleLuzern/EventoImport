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
 * Class ilEventoImporter
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

abstract class ilEventoImporter {
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
	private $data_source;
	private $token;
	
	private $page;

    protected $iterator;
	protected $fetch_data_set_method;
    protected $fetch_data_record_method;
	
	public function __construct(ilEventoImporterIterator $iterator, ilSetting $settings, ilEventoImportLogger $logger, \EventoImport\communication\request_services\RestClientService $data_source) {
		//Get Settings from dbase
        $this->iterator = $iterator;
		$this->client = CLIENT_ID;
		$this->ws_user = $settings->get('crevento_ws_user');
		$this->ws_password = $settings->get('crevento_ws_password');
		$this->auth_mode = $settings->get('crevento_ilias_auth_mode');
		$this->wsdl = $settings->get('crevento_wsdl');
		$this->pagesize = (int) $settings->get('crevento_pagesize');
		$this->max_pages = (int) $settings->get('crevento_max_pages');
		$this->max_retries = (int) $settings->get('crevento_max_retries');
		$this->seconds_before_retry = (int) $settings->get('crevento_seconds_before_retry');
		
		$this->evento_logger = $logger;
		
		$this->data_source = $data_source;
		$this->data_source->setTimeout($this->seconds_before_retry);

		$this->has_more_data = true;

		/* TODO: Check if init and login are needed
		$answer = $this->data_source->init();
		if ($answer) {
			$this->login();
		} else {
			throw new Exception ('Error while trying to initialize SOAP-Server. '.$this->data_source->getError());
		}
	    */
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

	public function hasMoreData() : bool
    {
        return $this->has_more_data;
    }

	public function fetchNextDataRecord($id)
    {
        $params = array(
            "id" => (int)$id
        );

        $json_response = $this->data_source->sendRequest('getUser', $params);

        if(strlen($json_response) > 0) {
            $json_response_decoded = json_decode($json_response, true);
            return is_array($json_response_decoded) ? $json_response_decoded : '';
        } else {
            return '';
        }
    }

	public function fetchNextDataSet()
    {
        $params = array(
            "skip" => $this->iterator->getPage() * $this->pagesize,
            "take" => $this->pagesize
        );

        $json_response = $this->data_source->sendRequest('getUsers', $params);

        $json_response_decoded = $this->validateResponseAndGetAsJsonStructure($json_response);

        if(count($json_response_decoded['data']) < 1) {
            $this->has_more_data = false;
            return [];
        } else if(!$json_response_decoded['hasMoreData']) {
            $this->has_more_data = false;
        }

        return $json_response_decoded['data'];
    }

    private function validateResponseAndGetAsJsonStructure(string $json_response)
    {
        $json_response_decoded = json_decode($json_response, true);

        $missing_fields = array();

        if(!isset($json_response_decoded['success'])) {
            $missing_fields[] = '"success"';
        }

        if(!isset($json_response_decoded['hasMoreData'])) {
            $missing_fields[] = '"hasMoreData"';
        }

        if(!isset($json_response_decoded['message'])) {
            $missing_fields[] = '"message"';
        }

        // Data must be set an be an array. If the evento import does not have any data left, the array MUST be empty
        if(!isset($json_response_decoded['data']) || !is_array($json_response_decoded['data'])) {
            $missing_fields[] = '"data"';
        }

        if(count($missing_fields) > 0 ) {
            throw new \Exception('Following fields are missing a correct value: ' . implode(', ', $missing_fields));
        }

        return $json_response_decoded;
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
	    $return = false;
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

			$result = &$this->data_source->call($operation, $params);

			if ($this->data_source->getError()) {
			    if (strpos($this->data_source->getError(),'Token') !== false) {
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
			
			$this->evento_logger->logException($operation, "We didn't get an answer on the $i try. The error was: {$this->data_source->getError()}..The connect-Timeout was {$this->data_source->getTimeout()} and the response-Timeout was {$this->data_source->getResponseTimeout()}.");
			$i++;

		} while ($i <= $this->max_retries);
		
		$this->evento_logger->logException($operation, "We tried {$this->max_retries} times to fetch information and failed.");
		
		return $result;
	}
}
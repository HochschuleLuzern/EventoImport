<?php
require_once 'Services/WebServices/SOAP/classes/class.ilSoapClient.php';

class ilEventoImportSOAPClient extends ilSoapClient {
	private $error_message;
	private $client;
	private $uri;
	
	public function __construct($a_uri = '') {
		$this->uri = $a_uri;
		parent::__construct($a_uri);
	}
	
	public function getError() {
		return $this->error_message;
	}
	
	/**
	 * Init soap client
	 */
	public function init()
	{	
		try {
			$this->setSocketTimeout(true);
			$this->client = new SoapClient(
					$this->uri,
					array(
							'exceptions' => true,
							'trace' => 1,
							'connection_timeout' => (int) $this->getTimeout()
					)
					);
			$return = true;
		} catch (Exception $ex) {
			$this->error_message = $ex->getMessage();
			$this->resetSocketTimeout();
			$return = false;
		} finally {
			$this->resetSocketTimeout();
			return $return;
		}
	}
	
	/**
	 * Call webservice method
	 * @param string $a_operation
	 * @param array $a_params
	 */
	public function call($a_operation, $a_params)
	{
		$this->setSocketTimeout(false);
		try {
			$this->error_message = null;
			$return = $this->client->__call($a_operation, $a_params);
		} catch(Exception $exception) {
			$this->error_message = $exception->getMessage();
			$return = false;
		} finally {
			$this->resetSocketTimeout();
			return $return;
		}
	}
}
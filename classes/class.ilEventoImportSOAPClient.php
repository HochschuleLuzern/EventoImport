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

require_once 'Services/WebServices/SOAP/classes/class.ilSoapClient.php';

/**
 * Class ilNotifyOnCronFailureResult
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

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
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

require_once './Services/Cron/classes/class.ilCronJobResult.php';

/**
 * Class ilNotifyOnCronFailureResult
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */
class ilEventoImportResult extends ilCronJobResult {

	/**
	 * @param      $status  int
	 * @param      $message string
	 * @param null $code    string
	 */
	public function __construct($status, $message) {
		$this->setStatus($status);
		$this->setMessage($message);
	}
}
?>
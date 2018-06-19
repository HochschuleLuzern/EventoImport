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
 * Class ilEventoImportMailNotification
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImportMailNotification extends ilMailNotification {
	private $settings;
	private $oldLogin;
	private $newLogin;
	private $email;
	
	const MAIL_TYPE_USER_NAME_CHANGED = 101;
	
	public function __construct()
	{
		$this->settings = new ilSetting("crevento");
		
		parent::__construct();
	}
	
	public function setUserInformation($recipient, $oldLogin, $newLogin, $email) {
		$this->setRecipients([$recipient]);
		$this->oldLogin = $oldLogin;
		$this->newLogin = $newLogin;
		$this->email = $email;
	}
	
	private function setMainBody () {
		$mainBody = $this->settings->get('crevento_email_account_changed_body');
		
		$mainBody = str_replace('[oldLogin]', $this->oldLogin, $mainBody);
		$mainBody = str_replace('[newLogin]', $this->newLogin, $mainBody);
		$mainBody = str_replace('[email]', $this->email, $mainBody);
		
		return $mainBody;
	}
	
	/**
	 * Send notifications
	 * @return
	 */
	public function send() {
		foreach($this->getRecipients() as $rcp) {
			$this->initLanguage($rcp);
			$this->initMail();
			$this->setSubject($this->settings->get('crevento_email_account_changed_subject'));
			$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));
			$this->appendBody("\n\n");
			$this->appendBody($this->setMainBody());
			$this->getMail()->appendInstallationSignature(true);
			
			$this->sendMail(array($rcp),array('system'));
		}
		return true;
	}
}
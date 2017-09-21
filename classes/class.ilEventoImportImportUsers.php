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

require_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImporter.php';
require_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImporterIterator.php';
require_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportLogger.php';
require_once './Services/User/classes/class.ilObjUser.php';
require_once './Services/Utilities/classes/class.ilUtil.php';
require_once './Services/LDAP/classes/class.ilLDAPServer.php';

/**
 * Class ilNotifyOnCronFailureResult
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImportImportUsers {
	private static $instance;
	private $evento_importer;
	private $evento_logger;
	
	private $ilDB;
	private $rbacadmin;
	private $rbacreview;
	
	private $now;
	private $until;
	private $until_max;
	
	private $auth_mode;
	private $usr_role_id;
	
	private function __construct() {
		$this->evento_importer = ilEventoImporter::getInstance();
        $this->evento_logger = ilEventoImportLogger::getInstance();
		
		global $DIC;
		$this->ilDB = $DIC['ilDB'];
		$this->rbacadmin = $DIC['rbacadmin'];
		$this->rbacreview = $DIC['rbacreview'];
		if (!ilContext::usesTemplate()) {
			ilStyleDefinition::setCurrentStyle('Desktop');
		}
		
		$settings = new ilSetting("crevento");

		$this->now = time();
		
		foreach (["", "_max"] as $duration) {
    		if ($settings->get('crevento'.$duration.'_account_duration') != 0 ) {
    		    $this->{until.$duration} = mktime(date('H'), date('i'), date('s'), date('n') + ($settings->get('crevento'.$duration.'_account_duration')% 12), date('j'), date('Y')+ (intdiv($settings->get('crevento'.$duration.'_account_duration'), 12)));
    		} else {
    			$this->{until.$duration} = 0;
    		}
		}

		$this->auth_mode = $settings->get("crevento_ilias_auth_mode");
		$this->usr_role_id = $settings->get("crevento_standard_user_role_id");
	}
	
	public static function run() {
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}
		
		self::$instance->evento_importer->trigger('UpdateEmployeeTmpTable');
	
		self::$instance->importUsers('GetStudierende', 'Studierende');
		self::$instance->importUsers('GetMitarbeiter', 'Mitarbeiter');
		
		self::$instance->convertDeletedAccounts('GetGeloeschteStudenten', false);
		self::$instance->convertDeletedAccounts('GetGeloeschteMitarbeiter', true);
		
		self::$instance->setUserTimeLimits();
	}
	
	/**
	 * Import Users from Evento
	 *
	 * Returns the number of rows.
	 */
	private function importUsers($operation, $dataset) {
		$iterator = new ilEventoImporterIterator;

		while (!($result = &$this->evento_importer->getRecords($operation, $dataset, $iterator))['finished']) {
			foreach ($result['data'] as $row) {
				$this->importUserData($row);
			}

			if ($result['is_last']) {
				break;
			}
		}
	}
	
	/**
	 * Convert deleted Users to ILIAS-Account
	 *
	 * Returns boolean for sucess
	 */
	private function convertDeletedAccounts($operation, $deactivate = false){
		$deletedLdapUsers=array();
		
		$iterator = new ilEventoImporterIterator;
		
		while (!($result = &$this->evento_importer->getRecords($operation, 'GeloeschteUser', $iterator))['finished']) {		
			foreach($result['data'] as $user){
				$deletedLdapUsers[]='Evento:'.$user['EvtID'];
			}
			
			if ($result['is_last']) {
				break;
			}
		}
		
		if(count($deletedLdapUsers)>0){
			for($i=0; $i<=count($deletedLdapUsers) ; $i+=100){
				//hole immer max 100 user aus der ilias db mit bedingung dass diese noch ldap aktiv sind
				$r = $this->ilDB->query("SELECT login,matriculation FROM `usr_data` WHERE auth_mode='".$this->auth_mode."' AND matriculation IN ('".implode("','",array_slice($deletedLdapUsers,$i,100))."')");
				while ($row = $this->ilDB->fetchAssoc($r))
				{
					//nochmals nachfragen, wenn user wiederhergestellt wurde
					$eventoid=substr($row['matriculation'],7);
					$login=$row['login'];
					$result = $this->evento_importer->getRecord('ExistsHSLUDomainUser',array('parameters'=>array('login'=>$login,'evtid'=>$eventoid)));
					
					if($result->{ExistsHSLUDomainUserResult}===false){
						//user nicht mehr aktiv in ldap
						if ($deactivate) {
							$sql="UPDATE usr_data SET auth_mode='default', time_limit_until=UNIX_TIMESTAMP() WHERE matriculation LIKE '".$row['matriculation']."'";
						} else {
							$sql="UPDATE usr_data SET auth_mode='default' WHERE matriculation LIKE '".$row['matriculation']."'";
						}
						
						$this->ilDB->manipulate($sql); 
						
						$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_CONVERTED, $row);
					}
				}
			}
		}
	}
	
	/**
	 * User accounts which don't have a time limitation are limited to
	 * two years since their creation.
	 */
	private function setUserTimeLimits() {
		//all users have at least 90 days of access (needed for Shibboleth)
		$q="UPDATE `usr_data` SET time_limit_until=time_limit_until+7889229 WHERE DATEDIFF(FROM_UNIXTIME(time_limit_until),create_date)<90";
		$r = $this->ilDB->manipulate($q);
		
		if ($this->until != 0) {
			//no unlimited users
			$q = "UPDATE usr_data set time_limit_unlimited=0 WHERE time_limit_unlimited=1 AND login NOT IN ('root','anonymous')";
			$r = $this->ilDB->manipulate($q);
			
			//all users are constraint to a value defined in the configuration
			$q = "UPDATE usr_data set time_limit_until='".$this->until_max."' WHERE time_limit_until>'".$this->until_max."'";
			$this->ilDB->manipulate($q);
		}
	}
	
	/**
	 *
	 * @param associative array $data with keys 'Login', 'FirstName', 'LastName',
	 * 'Password', 'Gender', 'PictureFilename', 'Email', 'Email2', 'Email3'.
	 * @return Returns a message describing the action that was taken.
	 */
	private function importUserData($data) {
		$data['id_by_login'] = ilObjUser::getUserIdByLogin($data['Login']);
		$data['ids_by_matriculation'] = self::_getUserIdsByMatriculation('Evento:'.$data['EvtID']);
		$data['ids_by_email'] = strlen(trim($data['Email'])) == 0 ? array() : $this->_reallyGetUserIdsByEmail($data['Email']);
		
		foreach (strlen(trim($data['Email2'])) == 0 ? array() : $this->_reallyGetUserIdsByEmail($data['Email2']) as $id) {
			if (! in_array($id, $data['ids_by_email'])) {
				$data['ids_by_email'][] = $id;
			}
		}
		
		foreach (strlen(trim($data['Email3'])) == 0 ? array() : $this->_reallyGetUserIdsByEmail($data['Email3']) as $id) {
			if (! in_array($id, $data['ids_by_email'])) {
				$data['ids_by_email'][] = $id;
			}
			
			
		}
		
		$usrId = 0;

		if (count($data['ids_by_matriculation']) == 0 &&
				$data['id_by_login'] == 0 &&
				count($data['ids_by_email']) == 0) {
	
			// We couldn't find a user account neither by
			// matriculation, login nor e-mail
			// --> Insert new user account.
			$action = 'new';
	
		} else if (count($data['ids_by_matriculation']) == 0 &&
				$data['id_by_login'] != 0) {

			// We couldn't find a user account by matriculation, but we found
			// one by login.
	
			$objByLogin = new ilObjUser($data['id_by_login']);
			$objByLogin->read();
	
			if (substr($objByLogin->getMatriculation(),0,7) == 'Evento:') {
				// The user account by login has a different evento number.
				// --> Rename and deactivate conflicting account
				//     and then insert new user account.
				$changed_user_data['user_id'] = $data['id_by_login'];
				$changed_user_data['EvtID'] = trim(substr($objByLogin->getMatriculation(), 8));
				$changed_user_data['new_user_info'] = 'EventoID: '.$data['EvtID'];
				$changed_user_data['found_by'] = 'Login';
				$this->renameAndDeactivateUser($changed_user_data);
				$action = 'new';
	
			} else if ($objByLogin->getMatriculation() == $objByLogin->getLogin()) {
				// The user account by login has a matriculation from ldap
				// --> Update user account.
				$action = 'update';
				$usrId = $data['id_by_login'];
	
			} else if (strlen($objByLogin->getMatriculation()) != 0) {
				// The user account by login has a matriculation of some kind
				// --> Bail
				$action = 'conflict';
	
			} else {
				// The user account by login has no matriculation
				// --> Update user account.
				$action = 'update';
				$usrId = $data['id_by_login'];
			}

		} else if (count($data['ids_by_matriculation']) == 0 &&
				$data['id_by_login'] == 0 &&
				count($data['ids_by_email']) == 1) {
	
			// We couldn't find a user account by matriculation, but we found
			// one by e-mail.
			$objByEmail = new ilObjUser($data['ids_by_email'][0]);
			$objByEmail->read();

			if (substr($objByEmail->getMatriculation(),0,7) == 'Evento:') {
				// The user account by e-mail has a different evento number.
				// --> Rename and deactivate conflicting account
				//     and then insert new user account.
				$changed_user_data['user_id'] = $data['ids_by_email'][0];
				$changed_user_data['EvtID'] = trim(substr($objByEmail->getMatriculation(), 8));
				$changed_user_data['new_user_info'] = 'EventoID: '.$data['EvtID'];
				$changed_user_data['found_by'] = 'E-Mail';
				$this->renameAndDeactivateUser($changed_user_data);
				$action = 'new';
			} else if (strlen($objByEmail->getMatriculation()) != 0) {
				// The user account by login has a matriculation of some kind
				// --> Bail
				$action = 'conflict';
			} else {
				// The user account by login has no matriculation
				// --> Update user account.
				$action = 'update';
				$usrId = $data['ids_by_email'][0];
			}
		
		} else if (count($data['ids_by_matriculation']) == 1 &&
				$data['id_by_login'] != 0 &&
				in_array($data['id_by_login'], $data['ids_by_matriculation'])) {
		
			// We found a user account by matriculation and by login.
			// --> Update user account.
			$action = 'update';
			$usrId = $data['ids_by_matriculation'][0];
		} else if (count($data['ids_by_matriculation']) == 1 &&
				$data['id_by_login'] == 0) {
		
			// We found a user account by matriculation but with the wrong login.
			// The correct login is not taken by another user account.
			// --> Update user account.
			$action = 'update';
			$usrId = $data['ids_by_matriculation'][0];
		} else if (count($data['ids_by_matriculation']) == 1 &&
				$data['id_by_login'] != 0 &&
				!in_array($data['id_by_login'], $data['ids_by_matriculation'])) {
		
			// We found a user account by matriculation but with the wrong
			// login. The login is taken by another user account.
			// --> Rename and deactivate conflicting account, then update user account.
			$objByLogin = new ilObjUser($data['id_by_login']);
			$objByLogin->read();
			
			$changed_user_data['user_id'] = $data['id_by_login'];
			$changed_user_data['EvtID'] = trim(substr($objByLogin->getMatriculation(), 8));
			$changed_user_data['new_user_info'] = 'EventoID: '.$data['EvtID'];
			$changed_user_data['found_by'] = 'Login';
			$this->renameAndDeactivateUser($changed_user_data);
			$action = 'update';
			$usrId = $data['ids_by_matriculation'][0];
		} else {
			$action = 'error';
		}
		
		// perform action
		switch ($action) {
			case 'new' :
				$this->insertUser($data);
				break;
			case 'update' :
				$this->updateUser($usrId, $data);
				break;
			case 'conflict':
				$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_NOTICE_CONFLICT, $data);
				break;
			case 'error':
				$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_ERROR_ERROR, $data);
				break;
		}
	}
	
	private function insertUser($data) {
		$userObj = new ilObjUser();
	
		$userObj->setLogin($data['Login']);
		$userObj->setFirstname($data['FirstName']);
		$userObj->setLastname($data['LastName']);
		$userObj->setGender(($data['Gender']=='F') ? 'f':'m');
		$userObj->setEmail($data['Email']);
		$userObj->setTitle($userObj->getFullname());
		$userObj->setDescription($userObj->getEmail());
		$userObj->setMatriculation('Evento:'.$data['EvtID']);
		$userObj->setExternalAccount($data['EvtID'].'@hslu.ch');
		$userObj->setAuthMode($this->auth_mode);
		if(!(ilLDAPServer::isAuthModeLDAP($this->auth_mode))) $userObj->setPasswd(strtolower($data['Password']));
	
		$userObj->setActive(true);
		$userObj->setTimeLimitFrom($this->now);
		if ($this->until == 0) {
			$userObj->setTimeLimitUnlimited(true);
		} else {
			$userObj->setTimeLimitUnlimited(false);
			$userObj->setTimeLimitUntil($this->until);
		}
	
		$userObj->create();
	
		//insert user data in table user_data
		$userObj->saveAsNew(false);
	
		// Set default prefs
		$userObj->setPref('hits_per_page','100'); //100 hits per page
		$userObj->setPref('show_users_online','associated'); //nur Leute aus meinen Kursen zeigen
	
		$userObj->setPref('public_profile','y'); //profil standard öffentlich
		$userObj->setPref('public_upload','y'); //profilbild öffentlich
		$userObj->setPref('public_email','y'); //profilbild öffentlich
	
		$userObj->writePrefs();
	
		// update mail preferences
		$this->setMailPreferences($userObj->getId());
	
		// Assign user to global user role
		$this->rbacadmin->assignUser($this->usr_role_id, $userObj->getId());
	
		$this->addPersonalPicture($data['EvtID'], $userObj->getId());
		
		$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_CREATED, $data);
	}
	
	private function updateUser($usrId, $data) {
		$user_updated = false;
		$userObj = new ilObjUser($usrId);
		$userObj->read();
		$userObj->readPrefs();
		
		
	
		if ($userObj->getFirstname() != $data['FirstName'] 
				|| $userObj->getlastname() != $data['LastName']
				|| $userObj->getGender() != strtolower($data['Gender'])
				|| $userObj->getMatriculation() != ('Evento:'.$data['EvtID'])
				|| $userObj->getAuthMode() != $this->auth_mode
				|| !$userObj->getActive()
				) {
			$user_updated = true;
			
			$data[old_data]['FirstName'] = $userObj->getFirstname();
			$data[old_data]['LastName'] = $userObj->getLastname();
			$data[old_data]['Gender'] = $userObj->getGender();
			$data[old_data]['Matriculation'] = $userObj->getMatriculation();
			$data[old_data]['AuthMode'] = $userObj->getAuthMode();
			$data[old_data]['Active'] = (string) $userObj->getActive();
		}

		$userObj->setFirstname($data['FirstName']);	
		$userObj->setLastname($data['LastName']);
		$userObj->setGender(($data['Gender']=='F') ? 'f':'m');

		
		$userObj->setTitle($userObj->getFullname());
		$userObj->setDescription($userObj->getEmail());
		$userObj->setMatriculation('Evento:'.$data['EvtID']);
		$userObj->setExternalAccount($data['EvtID'].'@hslu.ch');
		$userObj->setAuthMode($this->auth_mode);
		
		if(ilLDAPServer::isAuthModeLDAP($this->auth_mode)) $userObj->setPasswd('');
	
		$userObj->setActive(true);
		
		if ($this->until == 0) {
			$userObj->setTimeLimitUnlimited(true);
		} else {
			$userObj->setTimeLimitUnlimited(false);
			
			if ($userObj->getTimeLimitFrom() == 0 ||
					$userObj->getTimeLimitFrom() > $this->now) {
				$userObj->setTimeLimitFrom($this->now);
			}
	
			$userObj->setTimeLimitUntil($this->until);
		}

		$userObj->setPref('public_profile','y'); //profil standard öffentlich
		$userObj->setPref('public_upload','y'); //profilbild öffentlich
		$userObj->setPref('public_email','y'); //profilbild öffentlich
		$userObj->setPasswd('', IL_PASSWD_PLAIN);
		$userObj->update();
	
		// Assign user to global user role
		if ($this->rbacreview->isAssigned($userObj->getId(), $this->usr_role_id)) {
			$this->rbacadmin->assignUser($this->usr_role_id, $userObj->getId());
		}
	
		// Upload image
		if (strpos(ilObjUser::_getPersonalPicturePath($userObj->getId(), "small", false),'/no_photo') !== false) {
			$this->addPersonalPicture($data['EvtID'], $userObj->getId());
		}
	
		$oldLogin = $userObj->getLogin();
		
		if ($oldLogin != $data['Login']) {
			$data['oldLogin'] = $oldLogin;
			$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_RENAMED, $data);
								
			$this->changeLoginName($userObj->getId(),$data['Login']);
								
			include_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportMailNotification.php';
			$mail = new ilEventoimportMailNotification();
			$mail->setType($mail::MAIL_TYPE_USER_NAME_CHANGED);
			$mail->setUserInformation($userObj->id, $oldLogin, $data['Login'], $userObj->getEmail());
			$mail->send();											
		} else if ($user_updated) {
			$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_UPDATED, $data);
		}
	}
	
	private function renameAndDeactivateUser($data) {
		$userObj = new ilObjUser($data['user_id']);
		$userObj->read();
		
		$data['Login'] = date('Ymd').'_'.$userObj->getLogin();
		$data['FirstName'] = $userObj->getFirstname();
		$data['LastName'] = $userObj->getLastname();
		$data['Gender'] = $userObj->getGender();
		$data['Matriculation'] = $userObj->getMatriculation();
		
		$userObj->setActive(false);
		$userObj->update();
		$userObj->setLogin($data['Login']);
		$userObj->updateLogin($userObj->getLogin());
		$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_RENAMED, $data);
	}
	
	private function setMailPreferences($usrId) {
		$this->ilDB->manipulateF("UPDATE mail_options SET incoming_type = '2' WHERE user_id = %s", array("integer"), array($usrId)); //mail nur intern nach export
	}

	/**
	 * Change login name of a user
	 */
	private function changeLoginName($usr_id, $new_login) {	
		$q="UPDATE usr_data SET login = '".$new_login."' WHERE usr_id = '".$usr_id."'";
		$this->ilDB->manipulate($q);		
	}
	
	private function addPersonalPicture($eventoid, $id) {
		// Upload image
		$has_picture_result = $this->evento_importer->getRecord('HasPhoto', array('parameters'=>array('eventoId'=>$eventoid)));
		
		if (isset($has_picture_result->{HasPhotoResult}) && $has_picture_result->{HasPhotoResult} === true) {
			$picture_result = $this->evento_importer->getRecord('GetPhoto', array('parameters'=>array('eventoId'=>$eventoid)));
			$tmp_file = ilUtil::ilTempnam();
			imagepng(imagecreatefromstring($picture_result->{GetPhotoResult}), $tmp_file, 0);
			ilObjUser::_uploadPersonalPicture($tmp_file, $id);
		}
	}
	
	/**
	 * lookup id by matriculation
	 */
	public static function _getUserIdsByMatriculation($matriculation) {
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}
		
		$res = self::$instance->ilDB->queryF("SELECT usr_id FROM usr_data WHERE matriculation = %s",
				array("text"), array($matriculation));
		$ids=array();
		while($user_rec = self::$instance->ilDB->fetchAssoc($res)){
			$ids[]=$user_rec["usr_id"];
		}
		return $ids;
	}
	
	/**
	 * lookup id by email
	 */
	private function _reallyGetUserIdsByEmail($a_email) {
		$res = $this->ilDB->queryF("SELECT usr_id FROM usr_data WHERE email = %s AND active=1",
				array("text"), array($a_email));
		$ids=array();
		while($user_rec = $this->ilDB->fetchAssoc($res)){
			$ids[]=$user_rec["usr_id"];
		}
		return $ids;
	}
}
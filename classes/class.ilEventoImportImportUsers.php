<?php
use ILIAS\DI\RBACServices;

include_once('./Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/trait.ilEventoImportGetUserIdsByMatriculation.php');

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
 * Class ilEventoImportImportUsers
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImportImportUsers {
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
	private $user_config;
	
	use ilEventoImportGetUserIdsByMatriculation;
	
	public function __construct(
	    \EventoImport\communication\EventoUserImporter $importer,
	    ilEventoImportLogger $logger,
	    ilDBInterface $db,
	    RBACServices $rbac,
	    ilEventoImportImportUsersConfig $user_config
	    ) {
    		$this->evento_importer = $importer;
            $this->evento_logger = $logger;
    		$this->evento_user_repo = new \EventoImport\import\db_repository\EventoUserRepository($db);

    		$this->ilDB = $db;
    		$this->rbacadmin = $rbac->admin();
    		$this->rbacreview = $rbac->review();

    		$this->user_config = $user_config;
    
    		$this->now = time();
    		
    		foreach ($user_config->setupDurations() as $var_name => $duration) {
    		    $this->{$var_name} = $duration;
    		}
    
    		$this->auth_mode = $user_config->getIliasAuthMode();
    		$this->usr_role_id = $user_config->getStandardUserRoleId();
	}
	
	public function run() {

		$this->importUsers();
		//$this->convertDeletedAccounts();


/*
 *
| crevento | crevento_account_duration                      | 12                                                          |
| crevento | crevento_convert_deactivate_Mitarbeitende      | 1                                                           |
| crevento | crevento_convert_operation_Mitarbeitende       | GetGeloeschteMitarbeiter                                    |
| crevento | crevento_convert_operation_Studierende         | GetGeloeschteStudenten                                      |
| crevento | crevento_email_account_changed_subject         | Änderung Ihrer Zugangsdaten für die Lernplattform ILIAS     |
| crevento | crevento_ilias_auth_mode                       | ldap_1                                                      |
| crevento | crevento_import_additional_roles_Mitarbeitende | 5491816                                                     |
| crevento | crevento_import_additional_roles_Studierende   | 5491819                                                     |
| crevento | crevento_import_operation_Mitarbeitende        | GetMitarbeiter                                              |
| crevento | crevento_import_operation_Studierende          | GetStudierende                                              |
| crevento | crevento_import_selector_Mitarbeitende         | Mitarbeiter                                                 |
| crevento | crevento_import_selector_Studierende           | Studierende
 *
 *
 */
/*
		foreach ($this->user_config->getImportTypes() as $import_type) {
    		$parameters = $this->user_config->getFunctionParametersForOperation('import', $import_type);
    		$this->importUsers($parameters['operation']['value'], $parameters['selector']['value'], $parameters['additional_roles']['value']);
		}
		
		foreach ($this->user_config->getImportTypes() as $import_type) {
		    $parameters = $this->user_config->getFunctionParametersForOperation('convert', $import_type);
		    $this->convertDeletedAccounts($parameters['operation']['value'], $parameters['deactivate']['value']);
		}
*/
		
		$this->setUserTimeLimits();
	}

	private function getUserIdFromEventoUserTable(int $evento_id) : ?int
    {
        $query = "SELECT user_id FROM crnhk_crevento_users WHERE evento_id = " . $this->ilDB->quote($evento_id, 'integer');
        $results = $this->ilDB->query($query);
        if($row = $this->ilDB->fetchAssoc($results)) {
            return $row['user_id'];
        }

        return null;
    }

    private function fetchAllMatchingUserIds(\EventoImport\import\data_models\EventoUser $evento_user)
    {
        $user_lists = array();
        $user_lists['id_by_login'] = ilObjUser::getUserIdByLogin($evento_user->getLoginName());
        $user_lists['ids_by_matriculation'] = $this->getUserIdsByMatriculation('Evento:'.$evento_user->getEventoId());
        $user_lists['ids_by_email'] = array();

        // For each mail given from the evento import...
        foreach($evento_user->getEmailList() as $mail_given_from_evento) {

            // ... get all user ids in which a user has this email
            foreach($this->reallyGetUserIdsByEmail($mail_given_from_evento) as $ilias_id_by_mail) {

                if(!in_array($ilias_id_by_mail, $user_lists['ids_by_email'])) {
                    $user_lists['ids_by_email'][] = $ilias_id_by_mail;
                }
            }
        }

        if(count($user_lists['id_by_login']) == 0
            && count($user_lists['ids_by_matriculation']) == 0
            && count($user_lists['ids_by_email'] == 0)
        ) {
            return array();
        }
    }
	
	/**
	 * Import Users from Evento
	 *
	 * Returns the number of rows.
	 */
	private function importUsers() {
	    // $operation = 'GetMitarbeiter', $dataselector = 'Mitarbeiter', $additional_roles = 'RoleIdOfMitarbeitende usw.'
		$iterator = new ilEventoImporterIterator();
        $user_matcher = new EventoUserToIliasUserMatcher(new IliasUserQuerying($this->ilDB));

		do {
            try {
                foreach($this->evento_importer->fetchNextDataSet() as $data_set) {

                    try {
                        $evento_user = new \EventoImport\import\data_models\EventoUser($data_set);
                        $result = $user_matcher->matchEventoUserTheOldWay($evento_user);//$this->determineActionForGivenEventoUser($evento_user);

                        switch($result->getResultType()) {
                            case EventoIliasUserMatchingResult::RESULT_NO_MATCHING_USER:
                                $this->insertUser($evento_user);
                                $this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_CREATED, $data_set);
                                break;

                            case EventoIliasUserMatchingResult::RESULT_EXACTLY_ONE_MATCHING_USER:
                                $this->updateUser($result->getMatchedUserId(), $evento_user);
                                $this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_UPDATED, $data_set);
                                break;

                            case EventoIliasUserMatchingResult::RESULT_ONE_CONFLICTING_USER:
                                //$this->renameAndDeactivateUser($user_match_result->getMatchedUserId());
                                $this->insertUser($evento_user);
                                break;

                            case EventoIliasUserMatchingResult::RESULT_CONFLICT_OF_ACCOUNTS:
                                $this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_NOTICE_CONFLICT, $data_set);
                                break;

                            case EventoIliasUserMatchingResult::RESULT_ERROR:
                            default:
                                $this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_ERROR_ERROR, $data_set);
                                break;
                        }

                    } catch(Exception $e) {
                        $result = EventoIliasUserMatchingResult::Error();
                    }
                }
            } catch(Exception $e) {}

        } while($this->evento_importer->hasMoreData());

	}

	private function determineActionForGivenEventoUser(\EventoImport\import\data_models\EventoUser $evento_user)
    {
        $data['id_by_login'] = ilObjUser::getUserIdByLogin($evento_user->getLoginName());
        $data['ids_by_matriculation'] = $this->getUserIdsByMatriculation('Evento:'.$evento_user->getEventoId());
        $data['ids_by_email'] = [];

        // For each mail given from the evento import...
        foreach($evento_user->getEmailList() as $mail_given_from_evento) {

            // ... get all user ids in which a user has this email
            foreach($this->reallyGetUserIdsByEmail($mail_given_from_evento) as $ilias_id_by_mail) {

                if(!in_array($ilias_id_by_mail, $data['ids_by_email'])) {
                    $data['ids_by_email'][] = $ilias_id_by_mail;
                }
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

        if(isset($usrId) && $usrId > 0) {
            return ['action' => $action, 'user_id' => $usrId];
        } else {
            return ['action' => $action];
        }
    }



	/**
	 * Convert deleted Users to ILIAS-Account
	 *
	 * Returns boolean for sucess
	 */
	private function convertDeletedAccounts($operation, $deactivate = false){
		$deletedLdapUsers=array();
		
		$iterator = new ilEventoImporterIterator();
		
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
					
					if($result->{'ExistsHSLUDomainUserResult'}===false){
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
		
		if ($this->until_max != 0) {
			//no unlimited users
			$q = "UPDATE usr_data set time_limit_unlimited=0, time_limit_until='".$this->until_max."' WHERE time_limit_unlimited=1 AND login NOT IN ('root','anonymous')";
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
	private function importUserData($data, $additional_roles) {
		$data['id_by_login'] = ilObjUser::getUserIdByLogin($data['Login']);
		$data['ids_by_matriculation'] = $this->getUserIdsByMatriculation('Evento:'.$data['EvtID']);
		$data['ids_by_email'] = strlen(trim($data['Email'])) == 0 ? array() : $this->reallyGetUserIdsByEmail($data['Email']);
		
		foreach (strlen(trim($data['Email2'])) == 0 ? array() : $this->reallyGetUserIdsByEmail($data['Email2']) as $id) {
			if (! in_array($id, $data['ids_by_email'])) {
				$data['ids_by_email'][] = $id;
			}
		}
		
		foreach (strlen(trim($data['Email3'])) == 0 ? array() : $this->reallyGetUserIdsByEmail($data['Email3']) as $id) {
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
				$this->insertUser($data, $additional_roles);
				break;
			case 'update' :
				$this->updateUser($usrId, $data, $additional_roles);
				break;
			case 'conflict':
				$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_NOTICE_CONFLICT, $data);
				break;
			case 'error':
				$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_ERROR_ERROR, $data);
				break;
		}
	}
	
	private function insertUser(\EventoImport\import\data_models\EventoUser $evento_user) {
        //echo "Created User: " . $evento_user->getLoginName() . "\n";
        //return;
        $userObj = new ilObjUser();

        $userObj->setLogin($evento_user->getLoginName());
        $userObj->setFirstname($evento_user->getFirstName());
        $userObj->setLastname($evento_user->getLastName());
        $userObj->setGender(($evento_user->getGender() =='F') ? 'f':'m');
        $userObj->setEmail($evento_user->getEmailList()[0]);
        if(isset($evento_user->getEmailList()[1])){
            $userObj->setSecondEmail($evento_user->getEmailList()[1]);
        };
        $userObj->setTitle($userObj->getFullname());
        $userObj->setDescription($userObj->getEmail());
        $userObj->setMatriculation('Evento:'. $evento_user->getEventoId());
        $userObj->setExternalAccount($evento_user->getEventoId().'@hslu.ch');
        $userObj->setAuthMode($this->auth_mode);

//        if(!(ilLDAPServer::isAuthModeLDAP($this->auth_mode))){ $userObj->setPasswd(strtolower($evento_user['Password'])) ; }

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
		
		$this->assignUserToAdditionalRoles($userObj->getId(), $evento_user->getRoles());
		
		$this->addPersonalPicture($evento_user->getEventoId(), $userObj->getId());
		
		//$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_CREATED, $evento_user);
	}
	
	private function updateUser($usrId, \EventoImport\import\data_models\EventoUser $evento_user) {
		$user_updated = false;
		$userObj = new ilObjUser($usrId);
		$userObj->read();
		$userObj->readPrefs();
		
		
	
		if ($userObj->getFirstname() != $evento_user->getFirstName()
				|| $userObj->getlastname() != $evento_user->getLastName()
				|| $userObj->getGender() != strtolower($evento_user->getGender())
		        //|| $userObj->getSecondEmail() != $evento_user->getEmailList()[0]
				|| $userObj->getMatriculation() != ('Evento:'. $evento_user->getEventoId())
				|| $userObj->getAuthMode() != $this->auth_mode
				|| !$userObj->getActive()
				) {
			$user_updated = true;

            $old_user_data = array();
            $old_user_data['old_data']['FirstName']     = $userObj->getFirstname();
            $old_user_data['old_data']['LastName']      = $userObj->getLastname();
            $old_user_data['old_data']['Gender']        = $userObj->getGender();
            $old_user_data['old_data']['SecondEmail']   = $userObj->getSecondEmail();
            $old_user_data['old_data']['Matriculation'] = $userObj->getMatriculation();
            $old_user_data['old_data']['AuthMode']      = $userObj->getAuthMode();
            $old_user_data['old_data']['Active']        = (string) $userObj->getActive();
		}

		$userObj->setFirstname($evento_user->getFirstName());
		$userObj->setLastname($evento_user->getLastName());
		$userObj->setGender(($evento_user->getGender()=='F') ? 'f':'m');
		//$userObj->setSecondEmail($evento_user['Email']);
		
		$userObj->setTitle($userObj->getFullname());
		$userObj->setDescription($userObj->getEmail());
		$userObj->setMatriculation('Evento:'. $evento_user->getEventoId());
		$userObj->setExternalAccount($evento_user->getEventoId().'@hslu.ch');
		$userObj->setAuthMode($this->auth_mode);
		
		//if(ilLDAPServer::isAuthModeLDAP($this->auth_mode)) $userObj->setPasswd('');
	
		$userObj->setActive(true);
		
		// Reset login attempts over night -> needed since login attempts are limited to 8
		$userObj->setLoginAttempts(0);
		
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
		$userObj->setPref('public_email','y'); //e-mail öffentlich
		$userObj->setPasswd('', IL_PASSWD_PLAIN);
		$userObj->update();
	
		// Assign user to global user role
		if (!$this->rbacreview->isAssigned($userObj->getId(), $this->usr_role_id)) {
			$this->rbacadmin->assignUser($this->usr_role_id, $userObj->getId());
		}
		
		$this->assignUserToAdditionalRoles($userObj->getId(), $evento_user->getRoles());
	
		// Upload image
		if (strpos(ilObjUser::_getPersonalPicturePath($userObj->getId(), "small", false),'data:image/svg+xml') !== false) {
			$this->addPersonalPicture($evento_user->getEventoId(), $userObj->getId());
		}
	
		$oldLogin = $userObj->getLogin();
		
		if ($oldLogin != $evento_user->getLoginName()) {
            //$evento_user['oldLogin'] = $oldLogin;
			//$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_RENAMED, $evento_user);
								
			$this->changeLoginName($userObj->getId(), $evento_user->getLoginName());
								
			include_once './Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportMailNotification.php';
			$mail = new ilEventoimportMailNotification();
			$mail->setType($mail::MAIL_TYPE_USER_NAME_CHANGED);
			$mail->setUserInformation($userObj->id, $oldLogin, $evento_user->getLoginName(), $userObj->getEmail());
			$mail->send();											
		} else if ($user_updated) {
			//$this->evento_logger->log(ilEventoImportLogger::CREVENTO_USR_UPDATED, $evento_user);
		}
	}
	
	private function assignUserToAdditionalRoles($user_id, $additional_roles)
    {
	    foreach ($additional_roles as $role_code_by_evento) {

	        if(isset($this->role_map[$role_code_by_evento])) {
                $role_id = $this->role_map[$role_code_by_evento];

                if (!$this->rbacreview->isAssigned($user_id, $role_id)) {
                    $this->rbacadmin->assignUser($role_id, $user_id);
                }
            }
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

	    // TODO: Implement Picture Method
	    // Early return till the new method is implemented
	    return;
		// Upload image
		$has_picture_result = $this->evento_importer->getRecord('HasPhoto', array('parameters'=>array('eventoId'=>$eventoid)));
		
		if (isset($has_picture_result->{'HasPhotoResult'}) && $has_picture_result->{'HasPhotoResult'} === true) {
			$picture_result = $this->evento_importer->getRecord('GetPhoto', array('parameters'=>array('eventoId'=>$eventoid)));
			$tmp_file = ilUtil::ilTempnam();
			imagepng(imagecreatefromstring($picture_result->{'GetPhotoResult'}), $tmp_file, 0);
			ilObjUser::_uploadPersonalPicture($tmp_file, $id);
			unlink($tmp_file);
		}
	}
	
	/**
	 * lookup id by email
	 */
	private function reallyGetUserIdsByEmail($a_email) {
		$res = $this->ilDB->queryF("SELECT usr_id FROM usr_data WHERE email = %s AND active=1",
				array("text"), array($a_email));
		$ids=array();
		while($user_rec = $this->ilDB->fetchAssoc($res)){
			$ids[]=$user_rec["usr_id"];
		}
		return $ids;
	}
}

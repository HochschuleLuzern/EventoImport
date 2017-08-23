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

require_once 'Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImporter.php';
require_once 'Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImporterIterator.php';
require_once 'Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportImportUsers.php';
require_once 'Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportLogger.php';
require_once 'Services/AccessControl/classes/class.ilObjRole.php';
require_once 'Services/Mail/classes/Address/Type/class.ilMailRoleAddressType.php';
require_once 'Services/Object/classes/class.ilObject.php';

/**
 * Class ilNotifyOnCronFailureResult
 * 
 * Retrieves Events and Memberships from SOAP-Interface and imports them into ILIAS
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImportImportMemberships {
	/**
	 * @var ilEventoImportImportMembership Instance of itself
	 */
	private static $instance;
	
	/**
	 * @var ilEventoImporter Instance of the Importer
	 */
	private $evento_importer;
	/**
	 * @var ilEventoImportLogger Instance of the Logger
	 */
	private $evento_logger;
	
	/**
	 * @var ilDB Instance of the ILIAS Database
	 */
	private $ilDB;
	
	/**
	 * @var rbacreview Instance of the Access Controller
	 */
	private $rbacreview;
	
	/**
	 * @var rbacadmin Instance of the Access Administrator
	 */
	private $rbacadmin;
	
	/**
	 * Different caches to avoid to much database traffic
	 */
	private $roleToObjectCache = array();
	private $parentRolesCache = array();
	private $localRoleCache = array();
	
	/**
	 * Constructor
	 */
	private function __construct() {
		$this->evento_importer = ilEventoImporter::getInstance();
		$this->evento_logger = ilEventoImportLogger::getInstance();
		
		global $DIC;
		$this->ilDB = $DIC['ilDB'];
		$this->rbacreview = $DIC['rbacreview'];
		$this->rbacadmin = $DIC['rbacadmin'];
	}
	
	/**
	 * Runs the cron job
	 */
	static function run() {
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}
		
		self::$instance->updateEvents('GetModulanlaesse');
		self::$instance->updateEvents('GetHauptAnlaesse');
	}
	
	/**
	 * Retrieves events from evento and assigns users to the corresponding
	 * groups and courses in ILIAS.
	 *
	 * @param string Type of event to retrieve, GetModulAnlaesse for Bachelor, GetHauptAnlasse for Master Events
	 */
	private function updateEvents($operation) {
		$iterator = new ilEventoImporterIterator;
		while (!($result = &$this->evento_importer->getRecords($operation, 'Anlaesse', $iterator))['finished']) {
			foreach ($result['data'] as $row) {
				if (preg_match('/^(HSLU|DK|SA|M|TA|W|I)(\\.[A-Z0-9]([A-Za-z0-9\\-+_&]*[A-Za-z0-9])?){2,}$/', $row['AnlassBezKurz'])) {
					$searchName = '#member@['.$row['AnlassBezKurz'].']';
					$roleIds = ilMailRoleAddressType::searchRolesByMailboxAddressList($searchName);
					
					if (count($roleIds) == 1) {
						$row['role_id'] = $roleIds[0];
						
						$r = $this->ilDB->query("SELECT * FROM crnhk_crevento_mas WHERE evento_id = '{$row['AnlassBezKurz']}'");
						
						if (count($mas = $this->ilDB->fetchAll($r)) == 0) {
							$first_import = true;
						}
						
						if (($ref_ids = $this->rbacreview->getFoldersAssignedToRole($row['role_id'], true)) > 0) {
							$row['ref_id'] = $ref_ids[0];
						} else {
							$row['ref_id'] = "null";
						}
						
						$row['subscribed_users'] = $this->importEventSubscriptions('GetAnmeldungenByAnlassID', $row['AnlassID'], $row['role_id']);
						
						if (count($row['subscribed_users']) > 0) {
							$row['number_of_subs'] = count($row['subscribedUsers']);
							
							if ($first_import) {
								$message = ilEventoImportLogger::CREVENTO_MA_FIRST_IMPORT;
							} else {
								$message = ilEventoImportLogger::CREVENTO_MA_SUBS_UPDATED;
							}
						} else {
							$row['number_of_subs'] = 0;
							if ($first_import) {
								$message = ilEventoImportLogger::CREVENTO_MA_FIRST_IMPORT_NO_SUBS;
							} else {
								$message = ilEventoImportLogger::CREVENTO_MA_NO_SUBS;
							}
						}
						
						if ((!strtotime($row['EndDatum']) || strtotime($row['EndDatum']) > time())  && 
								($row['number_removed_subs'] = $this->removeFromRoleWithParents($row['subscribed_users'], $row['role_id'], $row['ref_id'])) > 0) {
							$message = ilEventoImportLogger::CREVENTO_MA_SUBS_UPDATED;
						}
						
						$this->evento_logger->log($message, $row);
						
						$row['obj_id'] = $this->rbacreview->getObjectOfRole($roleIds[0]);
						$this->updateObjectDescription($row);
					} else if (count($roleIds == 0)) {
						$this->evento_logger->log(ilEventoImportLogger::CREVENTO_MA_NOTICE_MISSING_IN_ILIAS, $row);
					} else {
						$this->evento_logger->log(ilEventoImportLogger::CREVENTO_MA_NOTICE_DUPLICATE_IN_ILIAS, $row);
					}
				} else {
					$this->evento_logger->log(ilEventoImportLogger::CREVENTO_MA_NOTICE_NAME_INVALID, $row);
				}
			}
			
			if ($result['is_last']) {
				break;
			}
		}
	}
	
	/**
	 * Assigns the users to the corresponding role in ILIAS
	 * 
	 * @param string $operation
	 * @param string $object_id
	 * @param string $role_id
	 * @return array Contains ids of all subscribed users
	 */
	private function importEventSubscriptions($operation, $object_id, $role_id) {
		$subscribedUsers = [];
		
		$iterator = new ilEventoImporterIterator;
		
		while (!($result = &$this->evento_importer->getRecords($operation, 'Anmeldungen', $iterator, array('parameters'=>array('anlassid'=>$object_id))))['finished']) {			
			foreach ($result['data'] as $row) {
				if ($role_id != null) {
					$idsByMatriculation = array(0);
					$idsByMatriculation = ilEventoImportImportUsers::_getUserIdsByMatriculation('Evento:'.$row['EvtID']);
					if (count($idsByMatriculation) != 0) {
						$this->assignToRoleWithParents($idsByMatriculation[0], $role_id);
						$subscribedUsers[] = $idsByMatriculation[0];
					}
				}
			}
			
			if ($result['is_last']) {
				break;
			}
		}
		
		return $subscribedUsers;
	}
	
	/**
	 * Updates the description in ILIAS if it is empty
	 * 
	 * @param array $data Contains data describing the event
	 */
	private function updateObjectDescription($data) {
		$description = $this->toFormattedAnlassBezLang($data['AnlassBezLang']);
		if (strlen($description) == 0) {
			return;
		}
		
		$q = "UPDATE object_data ".
				"SET ".
				"description = ".$this->ilDB->quote($description)." ".
				"WHERE ".
				"obj_id = ".$this->ilDB->quote($data['obj_id'])." AND ".
				"description = ''".
				"";
		
		$r = $this->ilDB->manipulate($q);

		if ($r == 1) {
			require_once 'Services/Object/classes/class.ilObjectFactory.php';
			$obj = ilObjectFactory::getInstanceByObjId($data['obj_id']);
			if ($obj != null) {
				$obj->setDescription($description);
				$obj->update();
			}
		}
	}
	
	/**
	 * Assigns a user to a role and to all parent roles.
	 * @param string $user_id
	 * @param string $role_id
	 */
	private function assignToRoleWithParents($user_id, $role_id) {
		$this->assignToRole($user_id, $role_id);
		
		$parent_role_ids = $this->getParentRoleIds($role_id);
		foreach ($parent_role_ids as $parent_role_id)
		{
			$this->assignToRole($user_id, $parent_role_id);
		}
	}
	
	/**
	 * Assigns a user to a role.
	 * 
	 * @param string $user_id
	 * @param string $role_id
	 */
	private function assignToRole($user_id, $role_id) {
		// If it is a course role, use the ilCourseMember object to assign
		// the user to the role
		
		if (!($assigned = $this->rbacreview->isAssigned($user_id, $role_id)) && $this->rbacadmin->assignUser($role_id, $user_id)) {
			if (in_array($role_id,$this->roleToObjectCache)) {
				$obj_id = $this->roleToObjectCache[$role_id];
			} else {
				$obj_id = $this->rbacreview->getObjectOfRole($role_id);
				$this->roleToObjectCache[$role_id]=$obj_id;
			}
			switch($type = ilObject::_lookupType($obj_id))
			{
				case 'grp':
				case 'crs':
					$ref_ids = ilObject::_getAllReferences($obj_id);
					$ref_id = current((array) $ref_ids);
					if($ref_id)
					{
						ilObjUser::_addDesktopItem($user_id,$ref_id,$type);
					}
					break;
				default:
					break;
			}
			
			$this->evento_logger->log(ilEventoImportLogger::CREVENTO_SUB_CREATED, array("usr_id" => $user_id, "role_id" => $role_id));
		} else if (!$assigned) {
			$this->evento_logger->log(ilEventoImportLogger::CREVENTO_SUB_ERROR_CREATING, array("usr_id" => $user_id, "role_id" => $role_id));
		} else {
			$r = $this->ilDB->queryF("SELECT 1 FROM crnhk_crevento_subs WHERE role_id=%s AND usr_id=%s LIMIT 1", array("integer", "integer"), array($role_id, $user_id));
			if ($this->ilDB->numRows($r) != 1) {
				$this->evento_logger->log(ilEventoImportLogger::CREVENTO_SUB_ADDED, array("usr_id" => $user_id, "role_id" => $role_id));
			}
		}
	}
	
	/**
	 * Remove all users that where removed from an event in Evento from the corresponding role in ILIAS and all parent roles.
	 *
	 * @param string $user_ids All users subscribed to an event
	 * @param string $role_id Role id of the event
	 * @param string $ref_id Reference id of the corresponding ILIAS course or group
	 */
	private function removeFromRoleWithParents($user_ids, $role_id, $ref_id) {
		$user_ids = $this->getDeletedUsersInRole($role_id, $user_ids);
		if ($user_ids !== false) {
			$parent_role_ids = $this->getParentRoleIds($role_id);
			
			foreach ($user_ids as $user_id) {
				$this->removeFromRole($user_id, $role_id, $ref_id, false);
			
				foreach ($parent_role_ids as $parent_role_id) {
					if (($parent_ref_id = $this->rbacreview->getFoldersAssignedToRole($parent_role_id, true)) > 0) {
						$this->removeFromRole($user_id, $parent_role_id, $parent_ref_id, true);
					}	
				}
			}		
		} else {
			return 0;
		}
		
		return count($user_ids);
	}
	
	/**
	 * Removes a user from a role, the subtree underneath the object is checked for other existing role assingments of the user that are comming from evento
	 * 
	 * @param string $user_id
	 * @param string $role_id
	 * @param string $ref_id
	 * @param boolean $check_subtree
	 */
	private function removeFromRole($user_id, $role_id, $ref_id, $check_subtree) {
		if ((!$check_subtree || ($check_subtree && !$this->userHasEventoRoleInSubtree($user_id , $ref_id))) && ($deass_success = $this->rbacadmin->deassignUser($role_id, $user_id))) {
			if (in_array($role_id,$this->roleToObjectCache)) {
				$obj_id = $this->roleToObjectCache[$role_id];
			} else {
				$obj_id = $this->rbacreview->getObjectOfRole($role_id);
				$this->roleToObjectCache[$role_id]=$obj_id;
			}
			switch($type = ilObject::_lookupType($obj_id)) {
				case 'grp':
				case 'crs':
					$ref_ids = ilObject::_getAllReferences($obj_id);
					$ref_id = current((array) $ref_ids);
					if($ref_id)
					{
						ilObjUser::_dropDesktopItem($user_id, $ref_id, $type);
					}
					break;
				default:
					break;
			}
			
			$this->evento_logger->log(ilEventoImportLogger::CREVENTO_SUB_REMOVED, array("usr_id" => $user_id, "role_id" => $role_id));
		} else if (!$deass_success) {
			$this->evento_logger->log(ilEventoImportLogger::CREVENTO_SUB_ERROR_REMOVING,  array("usr_id" => $user_id, "role_id" => $role_id));
		}
	}
	
	
	/**
	 * Get array of parent role ids from cache.
	 * If necessary, create a new cache entry.
	 * 
	 * @param string $role_id
	 * @return array
	 */
	private function getParentRoleIds($role_id)	{
		if (! array_key_exists($role_id, $this->parentRolesCache)) {
			$parent_role_ids = array();
			
			$role_obj = $this->getRoleObject($role_id);
			$short_role_title = substr($role_obj->getTitle(),0,12);
			$folders = $this->rbacreview->getFoldersAssignedToRole($role_id, true);
			if (count($folders) > 0) {
				$all_parent_role_ids = $this->rbacreview->getParentRoleIds($folders[0]);
				foreach ($all_parent_role_ids as $parent_role_id => $parent_role_data) {
					if ($parent_role_id != $role_id) {
						switch (substr($parent_role_data['title'],0,12)) {
							case 'il_crs_admin' :
							case 'il_grp_admin' :
								if ($short_role_title == 'il_crs_admin' || $short_role_title == 'il_grp_admin') {
									$parent_role_ids[] = $parent_role_id;
								}
								break;
							case 'il_crs_tutor' :
							case 'il_grp_tutor' :
								if ($short_role_title == 'il_crs_tutor' || $short_role_title == 'il_grp_tutor') {
									$parent_role_ids[] = $parent_role_id;
								}
								break;
							case 'il_crs_membe' :
							case 'il_grp_membe' :
								if ($short_role_title == 'il_crs_membe' || $short_role_title == 'il_grp_membe') {
									$parent_role_ids[] = $parent_role_id;
								}
								break;
							default :
								break;
						}
					}
				}
			}
			$this->parentRolesCache[$role_id] = $parent_role_ids;
		}
		return $this->parentRolesCache[$role_id];
	}
	
	/**
	 * Returns the parent object of the role folder object which contains the specified role.
	 * 
	 * @param string $role_id
	 * @return ilObjRole
	 */
	private function getRoleObject($role_id) {
		if (array_key_exists($role_id, $this->localRoleCache)) {
			return $this->localRoleCache[$role_id];
		}
		else {
			$role_obj = new ilObjRole($role_id, false);
			$role_obj->read();
			$this->localRoleCache[$role_id] = $role_obj;
			return $role_obj;
		}
		
	}
	
	private function getDeletedUsersInRole($role_id, $user_ids) {
		$deleted_users = false;
		
		$r = $this->ilDB->queryF("SELECT usr_id FROM crnhk_crevento_subs WHERE role_id=%s AND update_info_code!=%s", array("integer", "integer"), array($role_id, ilEventoImportLogger::CREVENTO_SUB_REMOVED));
		while(($row = $this->ilDB->fetchAssoc($r))) {
			if (!(in_array($row['usr_id'], $user_ids))) {
				$deleted_users[] = $row['usr_id'];
			}
		}
		
		return $deleted_users;
		
	}
	
	private function userHasEventoRoleInSubtree($user_id, $ref_id) {
		$roles = $this->rbacreview->getAssignableRolesInSubtree($ref_id);
		
		foreach ($roles as $role) {
			$r = $this->ilDB->query("SELECT * FROM crnhk_crevento_mas WHERE role_id = '$role'");
			if ($this->ilDB->fetchAll($r) > 0) {
				$evento_roles[] = $role;
			}
		}
		
		return $this->rbacreview->isAssignedToAtLeastOneGivenRole($user_id, $evento_roles);
	}
	
	/**
	 * Formats the KursBezLang value.
	 */
	private function toFormattedAnlassBezLang($value) {
		
		// Remove the prefix from the description.
		$value = preg_replace('/^([a-zA-Z0-9._]+_|[a-zA-Z0-9]+\.)/u','',$value);
		
		// Remove the suffix from the description.
		$value = preg_replace('/(\.[a-zA-Z0-9]+| [A-Z]S [0-9]{4})$/u','',$value);
		
		return $value;
	}
}
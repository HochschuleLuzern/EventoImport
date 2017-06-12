<?php
require_once 'Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImporter.php';
require_once 'Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImporterIterator.php';
require_once 'Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportImportUsers.php';
require_once 'Customizing/global/plugins/Services/Cron/CronHook/EventoImport/classes/class.ilEventoImportLogger.php';
require_once 'Services/AccessControl/classes/class.ilObjRole.php';
require_once 'Services/Mail/classes/Address/Type/class.ilMailRoleAddressType.php';
require_once 'Services/Object/classes/class.ilObject.php';

class ilEventoImportImportMemberships {
	private static $instance;
	
	private $evento_importer;
	private $evento_logger;
	
	private $ilDB;
	private $rbacreview;
	private $rbacadmin;
	
	private $roleToObjectCache = array();
	private $parentRolesCache = array();
	private $localRoleCache = array();
	
	private function __construct() {
		$this->evento_importer = ilEventoImporter::getInstance();
		$this->evento_logger = ilEventoImportLogger::getInstance();
		
		global $DIC;
		$this->ilDB = $DIC['ilDB'];
		$this->rbacadmin = $DIC['rbacadmin'];
	}
	
	static function run() {
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}
		
		self::$instance->updateEvents('GetModulanlaesse');
		self::$instance->updateEvents('GetHauptAnlaesse');
	}
	
	/**
	 * Imports events from evento and optionally assigns users to the corresponding
	 * groups and courses in ILIAS.
	 *
	 * param $aFile the filename into which to write log data
	 * param $aRequest the name of the Soap-Request for Evento
	 * param $aDataset the name of the dataset object returned by Evento.
	 * param $aTitle the title used on the log data
	 *
	 * Returns false on failure, returns the number of events in case of success.
	 */
	private function updateEvents($operation) {
		$iterator = new ilEventoImporterIterator;
		while (!($result = &$this->evento_importer->getRecords($operation, 'Anlaesse', $iterator))['finished']) {
			foreach ($result['data'] as $row) {
				if (preg_match('/^(HSLU|DK|SA|M|TA|W)(\\.[A-Z0-9]([A-Za-z0-9\\-+_&]*[A-Za-z0-9])?){2,}$/', $row['AnlassBezKurz'])) {
					$searchName = '#member@['.$row['AnlassBezKurz'].']';
					$roleIds = ilMailRoleAddressType::searchRolesByMailboxAddressList($searchName);
					
					if (count($roleIds) == 1) {
						$row['role_id'] = $roleIds[0];
						
						$r = $this->ilDB->queryF("SELECT * FROM crnhk_crevento_mas WHERE evento_id = '{$row['AnlassBezKurz']}'");
						
						if (count($mas = $this->ilDB->fetchAll($r)) == 0) {
							if (($ref_ids = $this->rbacreview->getFoldersAssignedToRole($role_id, true)) > 0) {
								$row['ref_id'] = $ref_ids[0];
							} else {
								$row['ref_id'] = "null";
							}
							
							$this->evento_logger->log(ilEventoImportLogger::CREVENTO_MA_FIRST_IMPORT, $row);
							$result = ilEventoImportLogger::CREVENTO_MA_FIRST_IMPORT;
						} else {
							$row['ref_id'] = $mas['ref_id'];
						}
						
						$row['subscribed_users'] = $this->importEventSubscriptions('GetAnmeldungenByAnlassID', $row['AnlassID'], $row['role_id']);
						
						if ($subscribedUsers != false) {
							$row['number_of_subs'] = count($subscribedUsers);
							
							if ($this->MAActive($roleId)) $row['number_removed_subs']  = $this->removeFromRoleWithParents($subscribedUsers, $row['role_id']. $row['ref_id']);
							
							if ($result == ilEventoImportLogger::CREVENTO_MA_FIRST_IMPORT) {
								$this->evento_logger->log(ilEventoImportLogger::CREVENTO_MA_FIRST_IMPORT, $row);
							} else {
								$this->evento_logger->log(ilEventoImportLogger::CREVENTO_MA_SUBS_UPDATED, $row);
							}
						} else {
							if ($result != ilEventoImportLogger::CREVENTO_MA_FIRST_IMPORT) {
								$this->evento_logger->log(ilEventoImportLogger::CREVENTO_MA_NO_SUBS, $row);
							}
						}
						
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
				
				if ($result['is_last']) {
					break;
				}				
			}
		}
	}
	
	/**
	 * Returns the number of rows.
	 */
	private function importEventSubscriptions($operation, $object_id, $role_id) {
		$subscribedUsers = false;
		
		while (!($result = &$this->evento_importer->getRecords($operation, 'Anmeldungen', array('parameters'=>array('anlassid'=>$object_id))))['finished']) {			
			foreach ($result['data'] as $row) {
				if ($roleId != null) {
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
		if (!$this->ilDB->isError($r) && !$this->ilDB->isError($r->result)) {
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
	 */
	private function assignToRole($user_id, $role_id) {
		// If it is a course role, use the ilCourseMember object to assign
		// the user to the role
		
		if (!$this->rbacreview->isAssigned($user_id, $role_id) && $this->rbacadmin->assignUser($role_id, $user_id)) {
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
						ilObjUser::_addDesktopItem($a_user_id,$ref_id,$type);
					}
					break;
				default:
					break;
			}
			
			$this->evento_logger->log(ilEventoImportLogger::CREVENTO_SUB_CREATED, array("usr_id" => $user_id, "obj_id" => $role_id));
		} else if (!$this->rbacreview->isAssigned($user_id, $role_id)) {
			$this->evento_logger->log(ilEventoImportLogger::CREVENTO_SUB_ERROR_CREATING, array("usr_id" => $user_id, "obj_id" => $role_id));
		}
	}
	
	private function removeFromRoleWithParents($user_ids, $role_id, $ref_id) {
		$user_ids = $this->getDeletedUsersInRole($role_id, $user_ids);
		$parent_role_ids = $this->getParentRoleIds($role_id);
		
		foreach ($user_ids as $user_id) {
			$this->removeFromRole($user_id, $role_id, $ref_id, false);
		
			foreach ($parent_role_ids as $parent_role_id) {
				if (($parent_ref_id = $this->rbacreview->getFoldersAssignedToRole($parent_role_id, true)) > 0) {
					$this->removeFromRole($user_id, $parent_role_id, $parent_ref_id, true);
				}	
			}
		}
		
		return count($user_ids);
	}
	
	/**
	 * Assigns a user to a role.
	 */
	private function removeFromRole($user_id, $role_id, $ref_id, $check_subtree) {
	
		// If it is a course role, use the ilCourseMember object to assign
		// the user to the role
		
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
						ilObjUser::_dropDesktopItem($a_user_id,$ref_id,$type);
					}
					break;
				default:
					break;
			}
			
			$this->evento_logger->log(ilEventoImportLogger::CREVENTO_SUB_REMOVED, array("usr_id" => $user_id, "obj_id" => $role_id));
		} else if (!$deass_success) {
			$this->evento_logger->log(ilEventoImportLogger::CREVENTO_SUB_ERROR_REMOVING,  array("usr_id" => $user_id, "obj_id" => $role_id));
		}
	}
	
	
	/**
	 * Get array of parent role ids from cache.
	 * If necessary, create a new cache entry.
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
	
	private function MAActive () {
		include_once('./Services/Utilities/classes/class.ilUtil.php');
		
		$ref_id = $this->rbacreview->getObjectReferenceOfRole($role_id);
		
		$r = $this->ilDB->query("Select end_date FROM crnhk_crevento_mas WHERE ref_id='$ref_id';");
		return ((ilUtil::date_mysql2time($this->ilDB->fetchAssoc($r))['end_date']) > time());
		
	}
}
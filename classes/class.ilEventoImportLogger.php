<?php

require_once './Services/Logging/classes/public/class.ilLoggerFactory.php';

class ilEventoImportLogger {
	private static $instance;
	
	private $ilDB;
	
	const CREVENTO_SUB_CREATED = 101;
	const CREVENTO_SUB_UPDATED = 102;
	const CREVENTO_SUB_REMOVED = 103;
	const CREVENTO_SUB_ERROR_CREATING = 121;
	const CREVENTO_SUB_ERROR_REMOVING = 123;
	const CREVENTO_MA_FIRST_IMPORT = 205;
	const CREVENTO_MA_NOTICE_NAME_INVALID = 211;
	const CREVENTO_MA_NOTICE_MISSING_IN_ILIAS = 212;
	const CREVENTO_MA_NOTICE_DUPLICATE_IN_ILIAS = 213;
	const CREVENTO_MA_SUBS_UPDATED = 231;
	const CREVENTO_MA_NO_SUBS = 232;
	const CREVENTO_USR_CREATED = 301;
	const CREVENTO_USR_UPDATED = 302;
	const CREVENTO_USR_RENAMED = 303;
	const CREVENTO_USR_CONVERTED = 304;
	const CREVENTO_USR_NOTICE_CONFLICT = 313;
	const CREVENTO_USR_ERROR_ERROR = 324;
	
	
	private function __construct() {
		global $DIC;
		$this->ilDB = $DIC['ilDB'];
	}
	
	public static function getInstance() {
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public function log($result, $data) {
		if ($result < 200) {
			$r = $this->ilDB->queryF("SELECT * FROM crnhk_crevento_subs WHERE usr_id = %s AND obj_id = %s", array("integer", "integer"), array($data['usr_id'], $data['obj_id']));
			
			if (count($this->ilDB->fetchAll($r)) == 0) {
				$q = "INSERT INTO crnhk_crevento_subs (usr_id, obj_id, last_import_date, update_info_code) VALUES ('{$data['usr_id']}', '{$data['obj_id']}', '".date("Y-m-d H:i:s")."', '$result')";
			} else {
				$q = "UPDATE crnhk_crevento_subs SET last_import_date = '".date("Y-m-d H:i:s")."', update_info_code = '$result' WHERE usr_id = '{$data['usr_id']}' AND obj_id = '{$data['obj_id']}'";
			}		
		} else if ($result < 300) {
			if (!isset($data['role_id'])) {
				$data['role_id'] = "null";
			}
			
			$r = $this->ilDB->query("SELECT * FROM crnhk_crevento_mas WHERE evento_id = '{$data['AnlassBezKurz']}'");
			
			if ($data['EndDatum'] != "") {
				$data['EndDatum'] = date('Y-m-d H:i:s', strtotime($data['EndDatum']));
			}
			
			if (count($this->ilDB->fetchAll($r)) == 0) {
				$q = "INSERT INTO crnhk_crevento_mas (evento_id, ref_id, role_id, end_date, last_import_date, update_info_code) VALUES ('{$data['AnlassBezKurz']}', '{$data['ref_id']}', '{$data['role_id']}', '{$data['EndDatum']}','".date("Y-m-d H:i:s")."', '$result')";
			} else {
				$q = "UPDATE crnhk_crevento_mas SET ref_id = '{$data['ref_id']}', role_id = '{$row['role_id']}', end_date = '{$data['EndDatum']}', last_import_date = '".date("Y-m-d H:i:s")."', update_info_code = '$result' WHERE evento_id = '{$data['AnlassBezKurz']}'";
			}
		} else {
			$r = $this->ilDB->query("SELECT * FROM crnhk_crevento_usrs WHERE evento_id = '{$data['EvtID']}'");

			if (count($this->ilDB->fetchAll($r)) == 0) {
				$q = "INSERT INTO crnhk_crevento_usrs (evento_id, usrname, last_import_data, last_import_date, update_info_code) VALUES ('{$data['EvtID']}', '{$data['Login']}', ".$this->ilDB->quote(serialize($data), 'text').", '".date("Y-m-d H:i:s")."', '$result')";
			} else {
				$q = "UPDATE crnhk_crevento_usrs SET usrname = '{$data['Login']}', last_import_data = ".$this->ilDB->quote(serialize($data), 'text')." ,last_import_date = '".date("Y-m-d H:i:s")."', update_info_code = '$result' WHERE evento_id = '{$data['EvtID']}'";
			}
		}
		
		$this->ilDB->manipulate($q);
	}
	
	public function logException($operation, $message) {
		ilLoggerFactory::getRootLogger()->error("EventoImport failed while $opartion due to $message");
	}
}
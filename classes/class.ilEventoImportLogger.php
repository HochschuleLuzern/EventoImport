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
 * Class ilEventoImportLogger
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImportLogger
{
    private $ilDB;

    const CREVENTO_SUB_CREATED = 101;
    const CREVENTO_SUB_UPDATED = 102;
    const CREVENTO_SUB_REMOVED = 103;
    const CREVENTO_SUB_ADDED = 104;
    const CREVENTO_SUB_ERROR_CREATING = 121;
    const CREVENTO_SUB_ERROR_REMOVING = 123;

    /**************************
     * Event Codes
     **************************/

    // First import of Event
    const CREVENTO_MA_SINGLE_EVENT_CREATED = 201;
    const CREVENTO_MA_EVENT_WITH_PARENT_EVENT_CREATED = 202;
    const CREVENTO_MA_EVENT_IN_EXISTING_PARENT_EVENT_CREATED = 203;
    const CREVENTO_MA_EXISTING_ILIAS_COURSE_AS_EVENT_MARKED = 204;


    const CREVENTO_MA_FIRST_IMPORT = 205;
    const CREVENTO_MA_FIRST_IMPORT_NO_SUBS = 206;

    const CREVENTO_MA_NOTICE_NAME_INVALID = 211;
    const CREVENTO_MA_NOTICE_MISSING_IN_ILIAS = 212;
    const CREVENTO_MA_NOTICE_DUPLICATE_IN_ILIAS = 213;
    const CREVENTO_MA_NON_ILIAS_EVENT = 214;
    const CREVENTO_MA_SUBS_UPDATED = 231;
    const CREVENTO_MA_NO_SUBS = 232;

    const CREVENTO_MA_EVENT_LOCATION_UNKNOWN = 233; // New code

    /**************************
     * User Codes
     **************************/
    const CREVENTO_USR_CREATED = 301;
    const CREVENTO_USR_UPDATED = 302;
    const CREVENTO_USR_RENAMED = 303;
    const CREVENTO_USR_CONVERTED = 304;
    const CREVENTO_USR_NOTICE_CONFLICT = 313;
    const CREVENTO_USR_ERROR_ERROR = 324;
    
    
    public function __construct(ilDBInterface $db)
    {
        $this->ilDB = $db;
    }

    public function logUserImport($log_info_code, $evento_id, $username, $import_data)
    {
        if ($log_info_code < 300 || $log_info_code >= 400) {
            $this->logException("log", "Tried to log user import, info code of other import given instead: " . $log_info_code);
            return;
        }

        $r = $this->ilDB->query("SELECT 1 FROM crnhk_crevento_usrs WHERE evento_id = " . $this->ilDB->quote($evento_id, \ilDBConstants::T_INTEGER) . ' LIMIT 1');

        if (count($this->ilDB->fetchAll($r)) == 0) {
            $this->ilDB->insert(
                'crnhk_crevento_usrs',
                [
                    'evento_id' => [\ilDBConstants::T_INTEGER, $evento_id],
                    'usrname' => [\ilDBConstants::T_TEXT, $username],
                    'last_import_data' => [\ilDBConstants::T_TEXT, serialize($import_data)],
                    'last_import_date' => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")],
                    'update_info_code' => [\ilDBConstants::T_INTEGER, $log_info_code],
                ]
            );
        } else {
            $this->ilDB->update(
                'crnhk_crevento_usrs',
                [
                    'usrname' => [\ilDBConstants::T_TEXT, $username],
                    'last_import_data' => [\ilDBConstants::T_TEXT, serialize($import_data)],
                    'last_import_date' => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")],
                    'update_info_code' => [\ilDBConstants::T_INTEGER, $log_info_code],
                ],
                [
                    'evento_id' => [\ilDBConstants::T_INTEGER, $evento_id]
                ]
            );
        }
    }
    
    public function log($result, $data)
    {
        if ($result < 200) {
            $r = $this->ilDB->queryF("SELECT * FROM crnhk_crevento_subs WHERE usr_id = %s AND role_id = %s", array("integer", "integer"), array($data['usr_id'], $data['role_id']));
            
            if (count($this->ilDB->fetchAll($r)) == 0) {
                $q = "INSERT INTO crnhk_crevento_subs (usr_id, role_id, last_import_date, update_info_code) VALUES ('{$data['usr_id']}', '{$data['role_id']}', '" . date("Y-m-d H:i:s") . "', '$result')";
            } else {
                $q = "UPDATE crnhk_crevento_subs SET last_import_date = '" . date("Y-m-d H:i:s") . "', update_info_code = '$result' WHERE usr_id = '{$data['usr_id']}' AND role_id = '{$data['role_id']}'";
            }
        } elseif ($result < 300) {
            if (!isset($data['role_id'])) {
                $data['role_id'] = "null";
            }
            
            if ($data['EndDatum'] != "") {
                $data['EndDatum'] = date('Y-m-d H:i:s', strtotime($data['EndDatum']));
            }
            
            $r = $this->ilDB->query("SELECT * FROM crnhk_crevento_mas WHERE evento_id = '{$data['AnlassBezKurz']}'");
            
            if (count($this->ilDB->fetchAll($r)) == 0) {
                $q = "INSERT INTO crnhk_crevento_mas (evento_id, ref_id, role_id, end_date, number_of_subs, last_import_data, last_import_date, update_info_code) VALUES ('{$data['AnlassBezKurz']}', '{$data['ref_id']}', '{$data['role_id']}', '{$data['EndDatum']}', '{$data['number_of_subs']}', " . $this->ilDB->quote(serialize($data), 'text') . ",'" . date("Y-m-d H:i:s") . "', '$result')";
            } else {
                $q = "UPDATE crnhk_crevento_mas SET ref_id = '{$data['ref_id']}', role_id = '{$data['role_id']}', end_date = '{$data['EndDatum']}', number_of_subs = '{$data['number_of_subs']}', last_import_data = " . $this->ilDB->quote(serialize($data), 'text') . " ,last_import_date = '" . date("Y-m-d H:i:s") . "', update_info_code = '$result' WHERE evento_id = '{$data['AnlassBezKurz']}'";
            }
        } else {
            $r = $this->ilDB->query("SELECT * FROM crnhk_crevento_usrs WHERE evento_id = '{$data['id']}'");

            if (count($this->ilDB->fetchAll($r)) == 0) {
                $q = "INSERT INTO crnhk_crevento_usrs (evento_id, usrname, last_import_data, last_import_date, update_info_code) VALUES ('{$data['id']}', '{$data['loginName']}', " . $this->ilDB->quote(serialize($data), 'text') . ", '" . date("Y-m-d H:i:s") . "', '$result')";
            } else {
                $q = "UPDATE crnhk_crevento_usrs SET usrname = '{$data['loginName']}', last_import_data = " . $this->ilDB->quote(serialize($data), 'text') . " ,last_import_date = '" . date("Y-m-d H:i:s") . "', update_info_code = '$result' WHERE evento_id = '{$data['id']}'";
            }
        }
        
        $this->ilDB->manipulate($q);
    }
    
    public function logException($operation, $message)
    {
        ilLoggerFactory::getRootLogger()->error("EventoImport failed while $operation due to '$message'");
    }

    public function logEventImport(int $log_info_code, int $evento_id, ?int $ref_id, array $import_data)
    {
        if ($log_info_code < 200 || $log_info_code >= 300) {
            $this->logException("log", "Tried to log user import, info code of other import given instead: " . $log_info_code);
            return;
        }

        $r = $this->ilDB->query("SELECT 1 FROM crnhk_crevento_mas WHERE evento_id = " . $this->ilDB->quote($evento_id, \ilDBConstants::T_INTEGER) . ' LIMIT 1');

        if (count($this->ilDB->fetchAll($r)) == 0) {
            $this->ilDB->insert(
                'crnhk_crevento_mas',
                [
                    'evento_id' => [\ilDBConstants::T_INTEGER, $evento_id],
                    'ref_id' => [\ilDBConstants::T_TEXT, $ref_id],
                    'last_import_data' => [\ilDBConstants::T_TEXT, serialize($import_data)],
                    'last_import_date' => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")],
                    'update_info_code' => [\ilDBConstants::T_INTEGER, $log_info_code],
                ]
            );
        } else {
            $this->ilDB->update(
                'crnhk_crevento_mas',
                [
                    'ref_id' => [\ilDBConstants::T_TEXT, $ref_id],
                    'last_import_data' => [\ilDBConstants::T_TEXT, serialize($import_data)],
                    'last_import_date' => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")],
                    'update_info_code' => [\ilDBConstants::T_INTEGER, $log_info_code],
                ],
                [
                    'evento_id' => [\ilDBConstants::T_INTEGER, $evento_id]
                ]
            );
        }
    }
}

<?php declare(strict_types=1);
/**
 * Copyright (c) 2017 Hochschule Luzern
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

namespace EventoImport\import;

use ilDBInterface;
use ilLoggerFactory;
use EventoImport\communication\api_models\EventoEvent;

/**
 * Class ilEventoImportLogger
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */
class Logger
{
    private \ilDBInterface $ilDB;

    #const CREVENTO_SUB_CREATED = 101;
    #const CREVENTO_SUB_UPDATED = 102;
    const CREVENTO_SUB_REMOVED = 103;
    const CREVENTO_SUB_NEWLY_ADDED = 104;
    const CREVENTO_SUB_ALREADY_ASSIGNED = 105;
    const CREVENTO_SUB_ALREADY_DEASSIGNED = 106;
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

    const CREVENTO_MA_ILIAS_OBJECT_FOR_EVENT_REMOVED = 207;

    const CREVENTO_MA_NOTICE_NAME_INVALID = 211;
    const CREVENTO_MA_NOTICE_MISSING_IN_ILIAS = 212;
    const CREVENTO_MA_NOTICE_DUPLICATE_IN_ILIAS = 213;
    const CREVENTO_MA_NON_ILIAS_EVENT = 214;

    // Delete event
    const CREVENTO_MA_UNMARK_EVENT = 220;
    const CREVENTO_MA_DELETE_SINGLE_EVENT = 221;
    const CREVENTO_MA_DELETE_SUBGROUP_EVENT = 222;
    const CREVENTO_MA_DELETE_EVENT_WITH_PARENT = 223;

    // Update of existing events
    const CREVENTO_MA_SUBS_UPDATED = 231;
    const CREVENTO_MA_NO_SUBS = 232;
    const CREVENTO_MA_SINGLE_EVENT_TO_MULTI_GROUP_CONVERTED = 233;

    // Errors
    const CREVENTO_MA_EVENT_LOCATION_UNKNOWN = 240; // New code

    /**************************
     * User Codes
     **************************/
    const CREVENTO_USR_CREATED = 301;
    const CREVENTO_USR_UPDATED = 302;
    const CREVENTO_USR_RENAMED = 303;
    const CREVENTO_USR_CONVERTED = 304;
    const CREVENTO_USR_NOTICE_CONFLICT = 313;
    const CREVENTO_USR_ERROR_ERROR = 324;

    const TABLE_LOG_USERS = 'crevento_log_users';
    const TABLE_LOG_EVENTS = 'crevento_log_events';
    const TABLE_LOG_MEMBERSHIPS = 'crevento_log_members';

    public function __construct(ilDBInterface $db)
    {
        $this->ilDB = $db;
    }

    public function logUserImport($log_info_code, $evento_id, $username, $import_data)
    {
        if ($log_info_code < 300 || $log_info_code >= 400) {
            $this->logException(
                "log",
                "Tried to log user import, info code of other import given instead: " . $log_info_code
            );
            return;
        }

        $r = $this->ilDB->query("SELECT 1 FROM " . self::TABLE_LOG_USERS . " WHERE evento_id = " . $this->ilDB->quote(
            $evento_id,
            \ilDBConstants::T_INTEGER
        ) . ' LIMIT 1');

        if (count($this->ilDB->fetchAll($r)) == 0) {
            $this->ilDB->insert(
                self::TABLE_LOG_USERS,
                [
                    'evento_id' => [\ilDBConstants::T_INTEGER, $evento_id],
                    'usrname' => [\ilDBConstants::T_TEXT, $username],
                    'last_import_data' => [\ilDBConstants::T_TEXT, json_encode($import_data)],
                    'last_import_date' => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")],
                    'update_info_code' => [\ilDBConstants::T_INTEGER, $log_info_code],
                ]
            );
        } else {
            $this->ilDB->update(
                self::TABLE_LOG_USERS,
                [
                    'usrname' => [\ilDBConstants::T_TEXT, $username],
                    'last_import_data' => [\ilDBConstants::T_TEXT, json_encode($import_data)],
                    'last_import_date' => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")],
                    'update_info_code' => [\ilDBConstants::T_INTEGER, $log_info_code],
                ],
                [
                    'evento_id' => [\ilDBConstants::T_INTEGER, $evento_id]
                ]
            );
        }
    }

    public function logException($operation, $message)
    {
        ilLoggerFactory::getRootLogger()->error("EventoImport failed while $operation due to '$message'");
    }

    public function logEventImport(int $log_info_code, int $evento_id, ?int $ref_id, array $import_data)
    {
        if ($log_info_code < 200 || $log_info_code >= 300) {
            $this->logException(
                "log",
                "Tried to log user import, info code of other import given instead: " . $log_info_code
            );
            return;
        }

        /*
         * Split away the employee-list and student-list from imported_data if the array key is set.
         * This is necessary, since the serialization of imported_data can be over 4000 chars (max. string length) with long user lists.
         * The alternative would be to convert the column from varchar to clob. But this approach should work pretty good as well.
         */
        if (isset($import_data['api_data']) && isset($import_data['api_data'][EventoEvent::JSON_EMPLOYEES])) {
            $employees_list = $import_data['api_data'][EventoEvent::JSON_EMPLOYEES];
            unset($import_data['api_data'][EventoEvent::JSON_EMPLOYEES]);
        } else {
            $employees_list = [];
        }

        if (isset($import_data['api_data']) && isset($import_data['api_data'][EventoEvent::JSON_STUDENTS])) {
            $students_list = $import_data['api_data'][EventoEvent::JSON_STUDENTS];
            unset($import_data['api_data'][EventoEvent::JSON_STUDENTS]);
        } else {
            $students_list = [];
        }

        $r = $this->ilDB->query("SELECT 1 FROM " . self::TABLE_LOG_EVENTS . " WHERE evento_id = " . $this->ilDB->quote(
            $evento_id,
            \ilDBConstants::T_INTEGER
        ) . ' LIMIT 1');

        if (count($this->ilDB->fetchAll($r)) == 0) {
            $this->ilDB->insert(
                self::TABLE_LOG_EVENTS,
                [
                    'evento_id' => [\ilDBConstants::T_INTEGER, $evento_id],
                    'ref_id' => [\ilDBConstants::T_INTEGER, $ref_id],
                    'last_import_data' => [\ilDBConstants::T_TEXT, json_encode($import_data)],
                    'last_import_date' => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")],
                    'update_info_code' => [\ilDBConstants::T_INTEGER, $log_info_code],
                    'last_import_employees' => [\ilDBConstants::T_TEXT, json_encode($employees_list)],
                    'last_import_students' => [\ilDBConstants::T_TEXT, json_encode($students_list)]
                ]
            );
        } else {
            $this->ilDB->update(
                self::TABLE_LOG_EVENTS,
                [
                    'ref_id' => [\ilDBConstants::T_INTEGER, $ref_id],
                    'last_import_data' => [\ilDBConstants::T_TEXT, json_encode($import_data)],
                    'last_import_date' => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")],
                    'update_info_code' => [\ilDBConstants::T_INTEGER, $log_info_code],
                    'last_import_employees' => [\ilDBConstants::T_TEXT, json_encode($employees_list)],
                    'last_import_students' => [\ilDBConstants::T_TEXT, json_encode($students_list)]
                ],
                [
                    'evento_id' => [\ilDBConstants::T_INTEGER, $evento_id]
                ]
            );
        }
    }

    public function logEventMembership(int $log_info_code, int $evento_event_id, int $evento_user_id, int $role_type)
    {
        if ($log_info_code < 100 || $log_info_code >= 200) {
            $this->logException(
                "log",
                "Tried to log membership import, info code of other import given instead: " . $log_info_code
            );
            return;
        }
        try {
            $r = $this->ilDB->query('SELECT 1 FROM ' . self::TABLE_LOG_MEMBERSHIPS
                . ' WHERE evento_event_id = ' . $this->ilDB->quote($evento_event_id, \ilDBConstants::T_INTEGER)
                . ' AND evento_user_id = ' . $this->ilDB->quote($evento_user_id, \ilDBConstants::T_INTEGER)
                . ' LIMIT 1');

            if (count($this->ilDB->fetchAll($r)) == 0) {
                $this->ilDB->insert(
                    self::TABLE_LOG_MEMBERSHIPS,
                    [
                        'evento_event_id' => [\ilDBConstants::T_INTEGER, $evento_event_id],
                        'evento_user_id' => [\ilDBConstants::T_INTEGER, $evento_user_id],
                        'role_type' => [\ilDBConstants::T_INTEGER, $role_type],
                        'last_import_date' => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")],
                        'update_info_code' => [\ilDBConstants::T_INTEGER, $log_info_code],
                    ]
                );
            } else {
                $this->ilDB->update(
                    self::TABLE_LOG_MEMBERSHIPS,
                    [
                        'role_type' => [\ilDBConstants::T_INTEGER, $role_type],
                        'last_import_date' => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")],
                        'update_info_code' => [\ilDBConstants::T_INTEGER, $log_info_code],
                    ],
                    [
                        'evento_event_id' => [\ilDBConstants::T_INTEGER, $evento_event_id],
                        'evento_user_id' => [\ilDBConstants::T_INTEGER, $evento_user_id]
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logException('Log Membership', $e->getMessage() . $e->getTraceAsString());
        }
    }
}

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
 * along with EventoImport-Plugin for ILIAS.  If not,
 * see <http://www.gnu.org/licenses/>.
 */
?>
	 
<#1>

<?php
/**
 * We need three tables to track the imports:

 * In crnhk_crevento_usrs we track all users we import from evento.

 * In crnhk_crevento_mas we track all courses and groups (mas = Modulanlässe,
 * no better name found) that exist in evento.

 * In crnhk_crevento_subs we track all subscriptions to courses and groups
 * imported from evento. Only subscriptions in this table will also be deleted
 * through evento.

 * The update_info_code is defined in hte class ilEventoImportLogger
 */
    if (!$ilDB->tableExists('crnhk_crevento_usrs')) {
        $fields = array(
            'evento_id' => array(
                'type' => 'integer',
                'length' => 8,
                'notnull' => true
            ),
            'usrname' => array(
                'type' => 'text',
                'length' => 50,
                'notnull' => true
            ),
            'last_import_data' => array(
                'type' => 'text',
                'length' => 4000,
                'notnull' => false
            ),
            'last_import_date' => array(
                'type' => 'timestamp',
                'notnull' => true
            ),
            'update_info_code' => array(
                'type' => 'integer',
                'length' => 2,
                'notnull' => true
            )
        );
        
        $ilDB->createTable("crnhk_crevento_usrs", $fields);
        $ilDB->addPrimaryKey("crnhk_crevento_usrs", array("evento_id"));
    }

    if (!$ilDB->tableExists('crnhk_crevento_subs')) {
        $fields = array(
            'usr_id' => array(
                'type' => 'integer',
                'length' => 8,
                'notnull' => true
            ),
            'role_id' => array(
                'type' => 'integer',
                'length' => 8,
                'notnull' => true
            ),
            'last_import_date' => array(
                'type' => 'timestamp',
                'notnull' => true
            ),
            'update_info_code' => array(
                'type' => 'integer',
                'length' => 2,
                'notnull' => true
            )
        );

        $ilDB->createTable("crnhk_crevento_subs", $fields);
        $ilDB->addPrimaryKey('crnhk_crevento_subs', array('usr_id', 'role_id'));
    }

    if (!$ilDB->tableExists('crnhk_crevento_mas')) {
        $fields = array(
            'evento_id' => array(
                'type' => 'text',
                'length' => 50,
                'notnull' => true
            ),
            'ref_id' => array(
                'type' => 'integer',
                'length' => 8,
                'notnull' => false
            ),
            'role_id' => array(
                'type' => 'integer',
                'length' => 8,
                'notnull' => false
            ),
            'end_date' => array(
                'type' => 'timestamp',
                'notnull' => false
            ),
            'number_of_subs' => array(
                'type' => 'integer',
                'length' => 8,
                'notnull' => false
            ),
            'last_import_data' => array(
                'type' => 'text',
                'length' => 4000,
                'notnull' => false
            ),
            'last_import_date' => array(
                'type' => 'timestamp',
                'notnull' => true
            ),
            'update_info_code' => array(
                'type' => 'integer',
                'length' => 2,
                'notnull' => true
            )
        );
    
        $ilDB->createTable("crnhk_crevento_mas", $fields);
        $ilDB->addPrimaryKey("crnhk_crevento_mas", array("evento_id"));
    }
?>
<#2>
<?php

$table_name = \EventoImport\db\IliasEventoUserTblDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = array(
        \EventoImport\db\IliasEventoUserTblDef::COL_EVENTO_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoUserTblDef::COL_ILIAS_USER_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoUserTblDef::COL_LAST_TIME_DELIVERED => array(
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoUserTblDef::COL_ACCOUNT_TYPE => array(
            'type' => ilDBConstants::T_TEXT,
            'length' => 15,
            'notnull' => true
        ),

    );

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, [\EventoImport\db\IliasEventoUserTblDef::COL_EVENTO_ID]);
    $ilDB->addUniqueConstraint($table_name, [\EventoImport\db\IliasEventoUserTblDef::COL_ILIAS_USER_ID], 'usr');
}

?>
<#3>
<?php

$table_name = \EventoImport\db\IliasEventoEventsTblDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = array(
        \EventoImport\db\IliasEventoEventsTblDef::COL_EVENTO_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_EVENTO_TITLE => array(
            'type' => ilDBConstants::T_TEXT,
            'length' => 255,
            'notnull' => true,
            'fixed' => false
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_EVENTO_DESCRIPTION => array(
            'type' => ilDBConstants::T_TEXT,
            'length' => 128,
            'notnull' => true,
            'fixed' => false
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_EVENTO_TYPE => array(
            'type' => ilDBConstants::T_TEXT,
            'length' => 25,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_WAS_AUTOMATICALLY_CREATED => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 1,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_START_DATE => array(
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_END_DATE => array(
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => false
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_LAST_TIME_DELIVERED => array(
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_ILIAS_TYPE => array(
            'type' => ilDBConstants::T_TEXT,
            'length' => 4,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_REF_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_OBJ_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_ADMIN_ROLE_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_STUDENT_ROLE_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventsTblDef::COL_PARENT_EVENT_KEY => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => false
        )
    );

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, array(\EventoImport\db\IliasEventoEventsTblDef::COL_EVENTO_ID));
}

?>
<#4>
<?php

$table_name = \EventoImport\db\IliasEventLocationsTblDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = array(
        \EventoImport\db\IliasEventLocationsTblDef::COL_DEPARTMENT_NAME => array(
            'type' => ilDBConstants::T_TEXT,
            'length' => 30,
            'notnull' => true,
            'fixed' => false
        ),
        \EventoImport\db\IliasEventLocationsTblDef::COL_EVENT_KIND => array(
            'type' => ilDBConstants::T_TEXT,
            'length' => 30,
            'notnull' => true,
            'fixed' => false
        ),
        \EventoImport\db\IliasEventLocationsTblDef::COL_YEAR => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 2,
            'notnull' => true,
        ),
        \EventoImport\db\IliasEventLocationsTblDef::COL_REF_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        )
    );

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, array(\EventoImport\db\IliasEventLocationsTblDef::COL_DEPARTMENT_NAME, \EventoImport\db\IliasEventLocationsTblDef::COL_EVENT_KIND, \EventoImport\db\IliasEventLocationsTblDef::COL_YEAR));
}

?>
<#5>
<?php

$table_name = \EventoImport\db\IliasEventoEventMembershipsTblDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = array(
        \EventoImport\db\IliasEventoEventMembershipsTblDef::COL_EVENTO_EVENT_ID => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasEventoEventMembershipsTblDef::COL_ROLE_TYPE => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true
        )
    );

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, array(\EventoImport\db\IliasEventoEventMembershipsTblDef::COL_EVENTO_EVENT_ID,
                                            \EventoImport\db\IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID));
}

?>
<#6>
<?php

$table_name = \EventoImport\db\IliasParentEventTblDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = array(
        \EventoImport\db\IliasParentEventTblDef::COL_GROUP_UNIQUE_KEY => array(
            'type' => ilDBConstants::T_TEXT,
            'length' => 100,
            'notnull' => true
        ),
        \EventoImport\db\IliasParentEventTblDef::COL_GROUP_EVENTO_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasParentEventTblDef::COL_TITLE => array(
            'type' => ilDBConstants::T_TEXT,
            'length' => 100,
            'notnull' => true
        ),
        \EventoImport\db\IliasParentEventTblDef::COL_REF_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasParentEventTblDef::COL_ADMIN_ROLE_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ),
        \EventoImport\db\IliasParentEventTblDef::COL_STUDENT_ROLE_ID => array(
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        )
    );

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, array(\EventoImport\db\IliasParentEventTblDef::COL_GROUP_UNIQUE_KEY));
}
?>
<#7>
<?php
$table_name = 'crevento_log_users';
if (!$ilDB->tableExists($table_name)) {
    $fields = array(
        'evento_id' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ),
        'usrname' => array(
            'type' => 'text',
            'length' => 50,
            'notnull' => true
        ),
        'last_import_data' => array(
            'type' => 'text',
            'length' => 4000,
            'notnull' => false
        ),
        'last_import_date' => array(
            'type' => 'timestamp',
            'notnull' => true
        ),
        'update_info_code' => array(
            'type' => 'integer',
            'length' => 2,
            'notnull' => true
        )
    );

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, array("evento_id"));
}

$table_name = 'crevento_log_members';
if (!$ilDB->tableExists($table_name)) {
    $fields = array(
        'evento_event_id' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ),
        'evento_user_id' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ),
        'role_type' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true
        ),
        'last_import_date' => array(
            'type' => 'timestamp',
            'notnull' => true
        ),
        'update_info_code' => array(
            'type' => 'integer',
            'length' => 2,
            'notnull' => true
        )
    );

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, array('evento_event_id', 'evento_user_id'));
}

$table_name = 'crevento_log_events';
if (!$ilDB->tableExists($table_name)) {
    $fields = array(
        'evento_id' => array(
            'type' => 'text',
            'length' => 50,
            'notnull' => true
        ),
        'ref_id' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => false
        ),
        'last_import_data' => array(
            'type' => 'text',
            'length' => 4000,
            'notnull' => false
        ),
        'last_import_date' => array(
            'type' => 'timestamp',
            'notnull' => true
        ),
        'update_info_code' => array(
            'type' => 'integer',
            'length' => 2,
            'notnull' => true
        )
    );

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, array("evento_id"));
}
?>

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
        $fields = [
            'evento_id' => [
                'type' => 'integer',
                'length' => 8,
                'notnull' => true
            ],
            'usrname' => [
                'type' => 'text',
                'length' => 50,
                'notnull' => true
            ],
            'last_import_data' => [
                'type' => 'text',
                'length' => 4000,
                'notnull' => false
            ],
            'last_import_date' => [
                'type' => 'timestamp',
                'notnull' => true
            ],
            'update_info_code' => [
                'type' => 'integer',
                'length' => 2,
                'notnull' => true
            ]
        ];

        $ilDB->createTable("crnhk_crevento_usrs", $fields);
        $ilDB->addPrimaryKey("crnhk_crevento_usrs", ["evento_id"]);
    }

    if (!$ilDB->tableExists('crnhk_crevento_subs')) {
        $fields = [
            'usr_id' => [
                'type' => 'integer',
                'length' => 8,
                'notnull' => true
            ],
            'role_id' => [
                'type' => 'integer',
                'length' => 8,
                'notnull' => true
            ],
            'last_import_date' => [
                'type' => 'timestamp',
                'notnull' => true
            ],
            'update_info_code' => [
                'type' => 'integer',
                'length' => 2,
                'notnull' => true
            ]
        ];

        $ilDB->createTable("crnhk_crevento_subs", $fields);
        $ilDB->addPrimaryKey('crnhk_crevento_subs', ['usr_id', 'role_id']);
    }

    if (!$ilDB->tableExists('crnhk_crevento_mas')) {
        $fields = [
            'evento_id' => [
                'type' => 'text',
                'length' => 50,
                'notnull' => true
            ],
            'ref_id' => [
                'type' => 'integer',
                'length' => 8,
                'notnull' => false
            ],
            'role_id' => [
                'type' => 'integer',
                'length' => 8,
                'notnull' => false
            ],
            'end_date' => [
                'type' => 'timestamp',
                'notnull' => false
            ],
            'number_of_subs' => [
                'type' => 'integer',
                'length' => 8,
                'notnull' => false
            ],
            'last_import_data' => [
                'type' => 'text',
                'length' => 4000,
                'notnull' => false
            ],
            'last_import_date' => [
                'type' => 'timestamp',
                'notnull' => true
            ],
            'update_info_code' => [
                'type' => 'integer',
                'length' => 2,
                'notnull' => true
            ]
        ];

        $ilDB->createTable("crnhk_crevento_mas", $fields);
        $ilDB->addPrimaryKey("crnhk_crevento_mas", ["evento_id"]);
    }
?>
<#2>
<?php

$table_name = \EventoImport\db\IliasEventoUserTblDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = [
        \EventoImport\db\IliasEventoUserTblDef::COL_EVENTO_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoUserTblDef::COL_ILIAS_USER_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoUserTblDef::COL_LAST_TIME_DELIVERED => [
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoUserTblDef::COL_ACCOUNT_TYPE => [
            'type' => ilDBConstants::T_TEXT,
            'length' => 15,
            'notnull' => true
        ],

    ];

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, [\EventoImport\db\IliasEventoUserTblDef::COL_EVENTO_ID]);
    $ilDB->addUniqueConstraint($table_name, [\EventoImport\db\IliasEventoUserTblDef::COL_ILIAS_USER_ID], 'usr');
}

?>
<#3>
<?php

$table_name = \EventoImport\db\IliasEventoEventsTblDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = [
        \EventoImport\db\IliasEventoEventsTblDef::COL_EVENTO_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_EVENTO_TITLE => [
            'type' => ilDBConstants::T_TEXT,
            'length' => 255,
            'notnull' => true,
            'fixed' => false
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_EVENTO_DESCRIPTION => [
            'type' => ilDBConstants::T_TEXT,
            'length' => 128,
            'notnull' => true,
            'fixed' => false
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_EVENTO_TYPE => [
            'type' => ilDBConstants::T_TEXT,
            'length' => 25,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_WAS_AUTOMATICALLY_CREATED => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 1,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_START_DATE => [
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_END_DATE => [
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => false
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_LAST_TIME_DELIVERED => [
            'type' => ilDBConstants::T_TIMESTAMP,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_ILIAS_TYPE => [
            'type' => ilDBConstants::T_TEXT,
            'length' => 4,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_REF_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_OBJ_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_ADMIN_ROLE_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_STUDENT_ROLE_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventsTblDef::COL_PARENT_EVENT_KEY => [
            'type' => ilDBConstants::T_TEXT,
            'length' => 100,
            'notnull' => false
        ]
    ];

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, [\EventoImport\db\IliasEventoEventsTblDef::COL_EVENTO_ID]);
}

?>
<#4>
<?php

$table_name = \EventoImport\db\IliasEventLocationsTblDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = [
        \EventoImport\db\IliasEventLocationsTblDef::COL_DEPARTMENT_NAME => [
            'type' => ilDBConstants::T_TEXT,
            'length' => 30,
            'notnull' => true,
            'fixed' => false
        ],
        \EventoImport\db\IliasEventLocationsTblDef::COL_EVENT_KIND => [
            'type' => ilDBConstants::T_TEXT,
            'length' => 30,
            'notnull' => true,
            'fixed' => false
        ],
        \EventoImport\db\IliasEventLocationsTblDef::COL_YEAR => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 2,
            'notnull' => true,
        ],
        \EventoImport\db\IliasEventLocationsTblDef::COL_REF_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ]
    ];

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, [\EventoImport\db\IliasEventLocationsTblDef::COL_DEPARTMENT_NAME, \EventoImport\db\IliasEventLocationsTblDef::COL_EVENT_KIND, \EventoImport\db\IliasEventLocationsTblDef::COL_YEAR]);
}

?>
<#5>
<?php

$table_name = \EventoImport\db\IliasEventoEventMembershipsTblDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = [
        \EventoImport\db\IliasEventoEventMembershipsTblDef::COL_EVENTO_EVENT_ID => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasEventoEventMembershipsTblDef::COL_ROLE_TYPE => [
            'type' => 'integer',
            'length' => 1,
            'notnull' => true
        ]
    ];

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, [\EventoImport\db\IliasEventoEventMembershipsTblDef::COL_EVENTO_EVENT_ID,
                                            \EventoImport\db\IliasEventoEventMembershipsTblDef::COL_EVENTO_USER_ID]);
}

?>
<#6>
<?php

$table_name = \EventoImport\db\IliasParentEventTblDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = [
        \EventoImport\db\IliasParentEventTblDef::COL_GROUP_UNIQUE_KEY => [
            'type' => ilDBConstants::T_TEXT,
            'length' => 100,
            'notnull' => true
        ],
        \EventoImport\db\IliasParentEventTblDef::COL_GROUP_EVENTO_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasParentEventTblDef::COL_TITLE => [
            'type' => ilDBConstants::T_TEXT,
            'length' => 100,
            'notnull' => true
        ],
        \EventoImport\db\IliasParentEventTblDef::COL_REF_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasParentEventTblDef::COL_ADMIN_ROLE_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\IliasParentEventTblDef::COL_STUDENT_ROLE_ID => [
            'type' => ilDBConstants::T_INTEGER,
            'length' => 8,
            'notnull' => true
        ]
    ];

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, [\EventoImport\db\IliasParentEventTblDef::COL_GROUP_UNIQUE_KEY]);
}
?>
<#7>
<?php
$table_name = 'crevento_log_users';
if (!$ilDB->tableExists($table_name)) {
    $fields = [
        'evento_id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        'usrname' => [
            'type' => 'text',
            'length' => 50,
            'notnull' => true
        ],
        'last_import_data' => [
            'type' => 'text',
            'length' => 4000,
            'notnull' => false
        ],
        'last_import_date' => [
            'type' => 'timestamp',
            'notnull' => true
        ],
        'update_info_code' => [
            'type' => 'integer',
            'length' => 2,
            'notnull' => true
        ]
    ];

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, ["evento_id"]);
}

$table_name = 'crevento_log_members';
if (!$ilDB->tableExists($table_name)) {
    $fields = [
        'evento_event_id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        'evento_user_id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        'role_type' => [
            'type' => 'integer',
            'length' => 1,
            'notnull' => true
        ],
        'last_import_date' => [
            'type' => 'timestamp',
            'notnull' => true
        ],
        'update_info_code' => [
            'type' => 'integer',
            'length' => 2,
            'notnull' => true
        ]
    ];

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, ['evento_event_id', 'evento_user_id']);
}

$table_name = 'crevento_log_events';
if (!$ilDB->tableExists($table_name)) {
    $fields = [
        'evento_id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        'ref_id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => false
        ],
        'last_import_data' => [
            'type' => 'text',
            'length' => 4000,
            'notnull' => false
        ],
        'last_import_date' => [
            'type' => 'timestamp',
            'notnull' => true
        ],
        'update_info_code' => [
            'type' => 'integer',
            'length' => 2,
            'notnull' => true
        ]
    ];

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, ["evento_id"]);
}
?>
<#8>
<?php

if ($ilDB->tableExists('crnhk_crevento_subs')) {
    $ilDB->dropTable('crnhk_crevento_subs');
}

if ($ilDB->tableExists('crnhk_crevento_mas')) {
    $ilDB->dropTable('crnhk_crevento_mas');
}

if ($ilDB->tableExists('crnhk_crevento_usrs')) {
    $ilDB->dropTable('crnhk_crevento_usrs');
}
?>
<#9>
<?php
if (!$ilDB->tableColumnExists('crevento_log_events', 'last_import_employees')) {
    $ilDB->addTableColumn(
        'crevento_log_events',
        'last_import_employees',
        [
            'type' => 'text',
            'length' => 4000,
            'notnull' => false
        ]
    );
}

if (!$ilDB->tableColumnExists('crevento_log_events', 'last_import_students')) {
    $ilDB->addTableColumn(
        'crevento_log_events',
        'last_import_students',
        [
            'type' => 'text',
            'length' => 4000,
            'notnull' => false
        ]
    );
}
?>
<#10>
<?php

$table_name = \EventoImport\db\LocalVisitorRolesTblDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = [
        \EventoImport\db\LocalVisitorRolesTblDef::COL_LOCAL_ROLE_ID => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\LocalVisitorRolesTblDef::COL_DEPARTMENT_LOCATION_NAME => [
            'type' => 'text',
            'length' => 50,
            'notnull' => true
        ],
        \EventoImport\db\LocalVisitorRolesTblDef::COL_KIND_LOCATION_NAME => [
            'type' => 'text',
            'length' => 50,
            'notnull' => true
        ],
        \EventoImport\db\LocalVisitorRolesTblDef::COL_LOCATION_REF_ID => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        \EventoImport\db\LocalVisitorRolesTblDef::COL_DEPARTMENT_API_NAME => [
            'type' => 'text',
            'length' => 50,
            'notnull' => true
        ]
    ];

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, [\EventoImport\db\LocalVisitorRolesTblDef::COL_LOCAL_ROLE_ID]);
}
?>
<#11>
<?php
$table_name = \EventoImport\db\HiddenAdminsTableDef::TABLE_NAME;
if (!$ilDB->tableExists($table_name)) {
    $fields = [
        \EventoImport\db\HiddenAdminsTableDef::COL_OBJECT_REF_ID => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],

        \EventoImport\db\HiddenAdminsTableDef::COL_HIDDEN_ADMIN_ROLE_ID => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ]
    ];

    $ilDB->createTable($table_name, $fields);
    $ilDB->addPrimaryKey($table_name, [\EventoImport\db\HiddenAdminsTableDef::COL_OBJECT_REF_ID]);
}
?>

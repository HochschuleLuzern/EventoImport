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
	if(!$ilDB->tableExists('crnhk_crevento_usrs')) {
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

	if(!$ilDB->tableExists('crnhk_crevento_subs'))
	{
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

	if(!$ilDB->tableExists('crnhk_crevento_mas'))
	{
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
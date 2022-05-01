<?php

namespace EventoImport\import\data_management\repository\model;

use PHPUnit\Framework\TestCase;

class IliasEventoEventTest extends TestCase
{
    public function setUp() : void
    {
        parent::setUp();
    }

    public function testConstructor()
    {
        // Arrange
        $evento_id = 1;
        $evento_title = 'evento_title';
        $evento_description = 'evento_description';
        $evento_event_type = 'evento_event_type';
        $was_automatically_created = true;
        $start_date = new \DateTime();
        $end_date = new \DateTime();
        $ilias_type = 'ilisa_type';
        $ref_id = 2;
        $obj_id = 3;
        $admin_role_id = 4;
        $student_role_id = 5;
        $parent_event_ref_id = 6;

        // Act
        $model = new IliasEventoEvent($evento_id, $evento_title, $evento_description, $evento_event_type, $was_automatically_created, $start_date, $end_date, $ilias_type, $ref_id, $obj_id, $admin_role_id, $student_role_id, $parent_event_ref_id);

        // Assert
        $this->assertEquals($evento_id, $model->getEventoEventId());
        $this->assertEquals($evento_title, $model->getEventoTitle());
        $this->assertEquals($evento_description, $model->getEventoDescription());
        $this->assertEquals($evento_event_type, $model->getEventoType());
        $this->assertEquals($was_automatically_created, $model->wasAutomaticallyCreated());
        $this->assertEquals($start_date, $model->getStartDate());
        $this->assertEquals($end_date, $model->getEndDate());
        $this->assertEquals($ilias_type, $model->getIliasType());
        $this->assertEquals($ref_id, $model->getRefId());
        $this->assertEquals($obj_id, $model->getObjId());
        $this->assertEquals($admin_role_id, $model->getAdminRoleId());
        $this->assertEquals($student_role_id, $model->getStudentRoleId());
        $this->assertEquals($parent_event_ref_id, $model->getParentEventKey());
    }
}

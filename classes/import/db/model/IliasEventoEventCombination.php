<?php

namespace EventoImport\import\db\model;

class IliasEventoEventCombination
{
    public const EVENTO_TYPE_MODULANLASS = 'Modulanlass';
    public const EVENTO_TYPE_KURS = 'Kurs';

    private $evento_event_id;
    private $parent_event_ref_id;
    private $ref_id;
    private $obj_id;
    private $admin_role_id;
    private $student_role_id;
    private $evento_event_type;
    private $was_automatically_created;
    private $start_date;
    private $end_date;
    private $ilias_type;

    public function __construct(int $evento_event_id, int $parent_event_ref_id, int $admin_role_id, int $student_role_id, string $evento_event_type, bool $was_automatically_created, $start_date, $end_date, string $ilias_type)
    {
        $this->evento_event_id           = $evento_event_id;
        $this->parent_event_ref_id       = $parent_event_ref_id;
        $this->admin_role_id             = $admin_role_id;
        $this->student_role_id           = $student_role_id;
        $this->evento_event_type         = $evento_event_type;
        $this->was_automatically_created = $was_automatically_created;
        $this->start_date                = $start_date;
        $this->end_date                  = $end_date;
        $this->ilias_type                = $ilias_type;
    }

    public function getEventoEventId() : int
    {
        return $this->evento_event_id;
    }

    public function getParentEventRefId() : int
    {
        return $this->parent_event_ref_id;
    }

    public function getRefId() : int
    {
        return $this->ref_id;
    }

    public function getObjId() : int
    {
        return $this->obj_id;
    }

    public function getAdminRoleId() : int
    {
        return $this->admin_role_id;
    }

    public function getStudentRoleId() : int
    {
        return $this->student_role_id;
    }

    public function getEventoType() : string
    {
        return $this->evento_event_type;
    }

    public function wasAutomaticallyCreated() : bool
    {
        return $this->was_automatically_created;
    }

    public function getStartDate()
    {
        return $this->start_date;
    }

    public function getEndDate()
    {
        return $this->end_date;
    }

    public function iliasType() : string
    {
        return $this->ilias_type;
    }
}
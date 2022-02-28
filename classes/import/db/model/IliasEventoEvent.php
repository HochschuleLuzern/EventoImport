<?php declare(strict_types = 1);

namespace EventoImport\import\db\model;

class IliasEventoEvent
{
    public const EVENTO_TYPE_MODULANLASS = 'Modulanlass';
    public const EVENTO_TYPE_KURS = 'Kurs';

    private int $evento_event_id;
    private string $evento_title;
    private string $evento_description;
    private string $evento_event_type;
    private bool $was_automatically_created;
    private ?\DateTime $start_date;
    private ?\DateTime $end_date;
    private string $ilias_type;
    private ?string $parent_event_key;
    private int $ref_id;
    private int $obj_id;
    private int $admin_role_id;
    private int $student_role_id;

    public function __construct(
        int $evento_event_id,
        string $evento_title,
        string $evento_description,
        string $evento_event_type,
        bool $was_automatically_created,
        ?\DateTime $start_date,
        ?\DateTime $end_date,
        string $ilias_type,
        int $ref_id,
        int $obj_id,
        int $admin_role_id,
        int $student_role_id,
        string $parent_event_key = null
    ) {
        // id
        $this->evento_event_id = $evento_event_id;

        // evento event values
        $this->evento_title = $evento_title;
        $this->evento_description = $evento_description;
        $this->evento_event_type = $evento_event_type;
        $this->was_automatically_created = $was_automatically_created;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->ilias_type = $ilias_type;

        // foreign keys
        $this->parent_event_key = $parent_event_key;
        $this->ref_id = $ref_id;
        $this->obj_id = $obj_id;
        $this->admin_role_id = $admin_role_id;
        $this->student_role_id = $student_role_id;
    }

    public function getEventoEventId() : int
    {
        return $this->evento_event_id;
    }

    public function getParentEventKey() : ?string
    {
        return $this->parent_event_key;
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
        return $this->start_date;// ? $this->start_date->getTimestamp() : null;
    }

    public function getEndDate()
    {
        return $this->end_date;// ? $this->start_date->getTimestamp() : null;
    }

    public function getIliasType() : string
    {
        return $this->ilias_type;
    }

    public function getEventoTitle() : string
    {
        return $this->evento_title;
    }

    public function getEventoDescription() : string
    {
        return $this->evento_description;
    }

    public function isSubGroupEvent() : bool
    {
        return !is_null($this->getParentEventKey()) && ($this->getIliasType() === 'grp');
    }
}

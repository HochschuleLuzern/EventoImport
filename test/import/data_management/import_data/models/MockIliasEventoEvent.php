<?php declare(strict_types=1);

class MockIliasEventoEvent extends \EventoImport\import\data_management\repository\model\IliasEventoEvent
{
    public $data;

    public function __construct(array $data)
    {
        parent::__construct(0, '', '', '', false, null, null, '', 0, 0, 0, 0, null);
        $this->data = $data;
    }

    public function getEventoEventId() : int
    {
        return $this->data['evento_id'];
    }

    public function getParentEventKey() : ?string
    {
        return $this->data['parent_event_key'];
    }

    public function getRefId() : int
    {
        return $this->data['ref_id'];
    }

    public function getObjId() : int
    {
        return $this->data['obj_id'];
    }

    public function getAdminRoleId() : int
    {
        return $this->data['admin_role_id'];
    }

    public function getStudentRoleId() : int
    {
        return $this->data['student_role_id'];
    }

    public function getEventoType() : string
    {
        return $this->data['evento_type'];
    }

    public function wasAutomaticallyCreated() : bool
    {
        return $this->data['was_automatically_created'];
    }

    public function getStartDate() : ?\DateTime
    {
        return $this->data['start_date'];
    }

    public function getEndDate() : ?\DateTime
    {
        return $this->data['end_date'];
    }

    public function getIliasType() : string
    {
        return $this->data['ilias_type'];
    }

    public function getEventoTitle() : string
    {
        return $this->data['evento_title'];
    }

    public function getEventoDescription() : string
    {
        return $this->data['evento_description'];
    }
}

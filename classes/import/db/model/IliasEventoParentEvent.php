<?php

namespace EventoImport\import\db\model;

class IliasEventoParentEvent
{
    private $ref_id;
    private $title;
    private $admin_role_id;
    private $student_role_id;

    public function __construct(string $title, int $ref_id, int $admin_role_id, int $student_role_id)
    {
        $this->title           = $title;
        $this->ref_id          = $ref_id;
        $this->admin_role_id   = $admin_role_id;
        $this->student_role_id = $student_role_id;
    }

    /**
     * @return string
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getRefId() : int
    {
        return $this->ref_id;
    }

    /**
     * @return int
     */
    public function getAdminRoleId() : int
    {
        return $this->admin_role_id;
    }

    /**
     * @return int
     */
    public function getStudentRoleId() : int
    {
        return $this->student_role_id;
    }

}
<?php

namespace EventoImport\import\db\model;

class IliasEventoParentEvent
{
    private $group_unique_key;
    private $group_evento_id;
    private $ref_id;
    private $title;
    private $admin_role_id;
    private $student_role_id;

    public function __construct(
        string $group_unique_key,
        int $group_evento_id,
        string $title,
        int $ref_id,
        int $admin_role_id,
        int $student_role_id
    ) {
        $this->group_unique_key = $group_unique_key;
        $this->group_evento_id = $group_evento_id;
        $this->title = $title;
        $this->ref_id = $ref_id;
        $this->admin_role_id = $admin_role_id;
        $this->student_role_id = $student_role_id;
    }

    public function getGroupUniqueKey() : string
    {
    }

    /**
     * @return int
     */
    public function getGroupEventoId() : int
    {
        return $this->group_evento_id;
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

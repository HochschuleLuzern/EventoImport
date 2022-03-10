<?php declare(strict_types = 1);

namespace EventoImport\import\data_management\repository\model;

class IliasEventoParentEvent
{
    private string $group_unique_key;
    private int $group_evento_id;
    private int $ref_id;
    private string $title;
    private int $admin_role_id;
    private int $student_role_id;

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
        return $this->group_unique_key;
    }

    public function getGroupEventoId() : int
    {
        return $this->group_evento_id;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function getRefId() : int
    {
        return $this->ref_id;
    }

    public function getAdminRoleId() : int
    {
        return $this->admin_role_id;
    }

    public function getStudentRoleId() : int
    {
        return $this->student_role_id;
    }
}

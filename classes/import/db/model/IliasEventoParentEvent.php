<?php

namespace EventoImport\import\db\model;

/**
 * Class IliasEventoParentEvent
 * @package EventoImport\import\db\model
 */
class IliasEventoParentEvent
{
    /** @var string */
    private string $group_unique_key;

    /** @var int */
    private int $group_evento_id;

    /** @var int */
    private int $ref_id;

    /** @var string */
    private string $title;

    /** @var int */
    private int $admin_role_id;

    /** @var int */
    private int $student_role_id;

    /**
     * IliasEventoParentEvent constructor.
     * @param string $group_unique_key
     * @param int    $group_evento_id
     * @param string $title
     * @param int    $ref_id
     * @param int    $admin_role_id
     * @param int    $student_role_id
     */
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

    /**
     * @return string
     */
    public function getGroupUniqueKey() : string
    {
        return $this->group_unique_key;
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

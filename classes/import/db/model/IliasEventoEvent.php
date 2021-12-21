<?php

namespace EventoImport\import\db\model;

/**
 * Class IliasEventoEvent
 * @package EventoImport\import\db\model
 */
class IliasEventoEvent
{
    public const EVENTO_TYPE_MODULANLASS = 'Modulanlass';
    public const EVENTO_TYPE_KURS = 'Kurs';

    /** @var int */
    private int $evento_event_id;

    /** @var string */
    private string $evento_title;

    /** @var string */
    private string $evento_description;

    /** @var string */
    private string $evento_event_type;

    /** @var bool */
    private bool $was_automatically_created;

    /** @var \DateTime|null  */
    private ?\DateTime $start_date;

    /** @var \DateTime|null */
    private ?\DateTime $end_date;

    /** @var string */
    private string $ilias_type;

    /** @var string */
    private ?string $parent_event_key;

    /** @var int */
    private int $ref_id;

    /** @var int */
    private int $obj_id;

    /** @var int */
    private int $admin_role_id;

    /** @var int */
    private int $student_role_id;

    /**
     * IliasEventoEvent constructor.
     * @param int            $evento_event_id
     * @param string         $evento_title
     * @param string         $evento_description
     * @param string         $evento_event_type
     * @param bool           $was_automatically_created
     * @param \DateTime|null $start_date
     * @param \DateTime|null $end_date
     * @param string         $ilias_type
     * @param int            $ref_id
     * @param int            $obj_id
     * @param int            $admin_role_id
     * @param int            $student_role_id
     * @param string|null    $parent_event_key
     */
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

    /**
     * @return int
     */
    public function getEventoEventId() : int
    {
        return $this->evento_event_id;
    }

    /**
     * @return string|null
     */
    public function getParentEventKey() : ?string
    {
        return $this->parent_event_key;
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
    public function getObjId() : int
    {
        return $this->obj_id;
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

    /**
     * @return string
     */
    public function getEventoType() : string
    {
        return $this->evento_event_type;
    }

    /**
     * @return bool
     */
    public function wasAutomaticallyCreated() : bool
    {
        return $this->was_automatically_created;
    }

    /**
     * @return \DateTime|null
     */
    public function getStartDate()
    {
        return $this->start_date;// ? $this->start_date->getTimestamp() : null;
    }

    /**
     * @return \DateTime|null
     */
    public function getEndDate()
    {
        return $this->end_date;// ? $this->start_date->getTimestamp() : null;
    }

    /**
     * @return string
     */
    public function getIliasType() : string
    {
        return $this->ilias_type;
    }

    /**
     * @return string
     */
    public function getEventoTitle() : string
    {
        return $this->evento_title;
    }

    /**
     * @return string
     */
    public function getEventoDescription() : string
    {
        return $this->evento_description;
    }
}

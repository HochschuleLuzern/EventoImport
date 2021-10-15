<?php

namespace EventoImport\import\db\repository;

class ParentEventRepository
{
    public const TABLE_NAME = 'crevento_parent_events';
    public const COL_REF_ID = 'ref_id';
    public const COL_TITLE = 'title';
    public const COL_DESCRIPTION = 'description';
    public const COL_ADMIN_ROLE_ID = 'admin_role_id';
    public const COL_STUDENT_ROLE_ID = 'student_role_id';

    /** @var \ilDBInterface */
    public $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }
}
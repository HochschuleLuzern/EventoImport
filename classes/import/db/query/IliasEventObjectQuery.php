<?php

namespace EventoImport\import\db\query;

class IliasEventObjectQuery
{
    /** @var \ilDBInterface */
    private $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function fetchAllEventableObjectsForGivenTitle(string $getName)
    {
        return [];
        $query = "SELECT obj_id " . $this->db->quote($getName, \ilDBConstants::T_TEXT);
    }
}
<?php

namespace EventoImport\import;

class IliasEventObjectQuery
{
    private $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function fetchAllEventableObjectsForGivenTitle(string $getName)
    {
        $query = "SELECT obj_id";
    }
}
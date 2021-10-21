<?php

namespace EventoImport\repository;

class RepositoryTreeToDepartmentLocationMapper
{
    public function __construct(\ilTree $tree)
    {
        $this->tree = $tree;
    }
}
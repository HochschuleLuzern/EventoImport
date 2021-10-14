<?php

namespace EventoImport\import\db\repository;

use EventoImport\communication\api_models\EventoEvent;

class DepartmentLocationRepository
{
    public const TABLE_NAME = 'crnhk_crevento_event_locations';
    const COL_DEPARTMENT_NAME = 'department';
    const COL_EVENT_KIND = 'kind';
    const COL_YEAR = 'year';

    private $db;
    private $cache;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
        $cache = array();
    }

    private function addToCache(int $ref_id, string $department, string $kind, int $year)
    {
        if(!isset($this->cache[$department])) {
            $this->cache[$department] = array($kind => array($year => $ref_id));
        } else if(!isset($this->cache[$department][$kind])) {
            $this->cache[$department][$kind] = array($year => $ref_id);
        } else {
            $this->cache[$department][$kind][$year] = $ref_id;
        }
    }

    private function checkCache(string $department, string $kind, int $year) : ?int
    {
        if(isset($this->cache[$department])
            && isset($this->cache[$department][$kind])
            && isset($this->cache[$department][$kind][$year])) {
                return $this->cache[$department][$kind][$year];
        } else {
            return null;
        }
    }

    public function fetchRefIdForEventoObject(EventoEvent $evento_event)
    {
        return 1286;

        $department = $evento_event->getDepartment();
        $kind = $evento_event->getKind();
        $year = (int)$evento_event->getStartDate()->format('Y');

        $cached_value = $this->checkCache($department, $kind, $year);

        if($cached_value != null) {
            return $cached_value;
        }

/*
        $query = 'SELECT ref_id FROM ' . self::TABLE_NAME . ' WHERE '
            . self::COL_DEPARTMENT_NAME . ' = ' . $this->db->quote($department, \ilDBConstants::T_TEXT)
            . ' AND '
            . self::COL_EVENT_KIND . ' = ' . $this->db->quote($kind, \ilDBConstants::T_TEXT)
            . ' AND '
            . self::COL_YEAR . ' = ' . $this->db->quote($year, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);

        if($row = $this->db->fetchAssoc($result)) {
            $ref_id = $row['ref_id'];
            $this->addToCache($ref_id, $department, $kind, $year);

            return $ref_id;
        }
*/
        return null;
    }

    public function fetchKindCategoryRefId(string $department, string $kind) : ?int
    {
        return 1286;
        /*
        $query = 'SELECT ref_id FROM ' . self::TABLE_NAME . ' WHERE '
            . self::COL_DEPARTMENT_NAME . ' = ' . $this->db->quote($department, \ilDBConstants::T_TEXT)
            . ' AND '
            . self::COL_EVENT_KIND . ' = ' . $this->db->quote($kind, \ilDBConstants::T_TEXT);

        $result = $this->db->query($query);*/
    }
}
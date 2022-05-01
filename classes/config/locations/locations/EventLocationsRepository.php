<?php declare(strict_types = 1);

namespace EventoImport\config\locations;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\db\IliasEventLocationsTblDef;

class EventLocationsRepository
{
    private \ilDBInterface $db;
    private array $cache;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
        $this->cache = array();
    }

    public function addNewLocation(string $department, string $kind, int $year, int $ref_id) : void
    {
        $this->db->insert(
            IliasEventLocationsTblDef::TABLE_NAME,
            [
                IliasEventLocationsTblDef::COL_DEPARTMENT_NAME => [\ilDBConstants::T_TEXT, $department],
                IliasEventLocationsTblDef::COL_EVENT_KIND => [\ilDBConstants::T_TEXT, $kind],
                IliasEventLocationsTblDef::COL_YEAR => [\ilDBConstants::T_INTEGER, $year],
                IliasEventLocationsTblDef::COL_REF_ID => [\ilDBConstants::T_INTEGER, $ref_id],
            ]
        );
    }

    public function purgeLocationTable() : void
    {
        $query = "DELETE FROM " . IliasEventLocationsTblDef::TABLE_NAME;
        $this->db->manipulate($query);
    }

    private function addToCache(int $ref_id, string $department, string $kind, int $year) : void
    {
        if (!isset($this->cache[$department])) {
            $this->cache[$department] = array($kind => array($year => $ref_id));
        } elseif (!isset($this->cache[$department][$kind])) {
            $this->cache[$department][$kind] = array($year => $ref_id);
        } else {
            $this->cache[$department][$kind][$year] = $ref_id;
        }
    }

    private function checkCache(string $department, string $kind, int $year) : ?int
    {
        if (isset($this->cache[$department])
            && isset($this->cache[$department][$kind])
            && isset($this->cache[$department][$kind][$year])) {
            return $this->cache[$department][$kind][$year];
        } else {
            return null;
        }
    }

    public function getRefIdForEventoObject(EventoEvent $evento_event) : ?int
    {
        $department = $evento_event->getDepartment();
        $kind = $evento_event->getKind();
        $year = (int) $evento_event->getStartDate()->format('Y');

        $cached_value = $this->checkCache($department, $kind, $year);

        if ($cached_value != null) {
            return $cached_value;
        }

        $query = 'SELECT ref_id FROM ' . IliasEventLocationsTblDef::TABLE_NAME . ' WHERE '
            . IliasEventLocationsTblDef::COL_DEPARTMENT_NAME . ' = ' . $this->db->quote($department, \ilDBConstants::T_TEXT)
            . ' AND '
            . IliasEventLocationsTblDef::COL_EVENT_KIND . ' = ' . $this->db->quote($kind, \ilDBConstants::T_TEXT)
            . ' AND '
            . IliasEventLocationsTblDef::COL_YEAR . ' = ' . $this->db->quote($year, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);

        if ($row = $this->db->fetchAssoc($result)) {
            $ref_id = (int) $row['ref_id'];
            $this->addToCache($ref_id, $department, $kind, $year);

            return $ref_id;
        }

        return null;
    }

    public function getAllLocationsAsTableRows() : array
    {
        $query = "SELECT " . IliasEventLocationsTblDef::COL_DEPARTMENT_NAME . ", " . IliasEventLocationsTblDef::COL_EVENT_KIND . ", " . IliasEventLocationsTblDef::COL_YEAR . ", " . IliasEventLocationsTblDef::COL_REF_ID
            . " FROM " . IliasEventLocationsTblDef::TABLE_NAME;
        $result = $this->db->query($query);

        $locations = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $locations[] = $row;
        }

        return $locations;
    }

    public function getAllLocationsAsHirarchicalArray() : array
    {
        $locations = [];
        foreach ($this->getAllLocationsAsTableRows() as $row) {
            $dep = $row[IliasEventLocationsTblDef::COL_DEPARTMENT_NAME];
            $kind = $row[IliasEventLocationsTblDef::COL_EVENT_KIND];
            $year = $row[IliasEventLocationsTblDef::COL_YEAR];
            $ref = (int) $row[IliasEventLocationsTblDef::COL_REF_ID];

            if (!isset($locations[$dep])) {
                $locations[$dep] = [$kind => [$year => $ref]];
            } elseif (!isset($locations[$dep][$kind])) {
                $locations[$dep][$kind] = [$year => $ref];
            } elseif (!isset($locations[$dep][$kind][$year])) {
                $locations[$dep][$kind][$year] = $ref;
            } else {
                $locations[$dep][$kind][$year] = $ref;
            }
        }

        return $locations;
    }
}

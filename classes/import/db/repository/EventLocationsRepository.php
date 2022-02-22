<?php declare(strict_types = 1);

namespace EventoImport\import\db\repository;

use EventoImport\communication\api_models\EventoEvent;

class EventLocationsRepository
{
    public const TABLE_NAME = 'crevento_locations';
    public const COL_DEPARTMENT_NAME = 'department';
    public const COL_EVENT_KIND = 'kind';
    public const COL_YEAR = 'year';
    public const COL_REF_ID = 'ref_id';

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
            self::TABLE_NAME,
            [
                self::COL_DEPARTMENT_NAME => [\ilDBConstants::T_TEXT, $department],
                self::COL_EVENT_KIND => [\ilDBConstants::T_TEXT, $kind],
                self::COL_YEAR => [\ilDBConstants::T_INTEGER, $year],
                self::COL_REF_ID => [\ilDBConstants::T_INTEGER, $ref_id],
            ]
        );
    }

    public function purgeLocationTable() : void
    {
        $query = "DELETE FROM " . self::TABLE_NAME;
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

        $query = 'SELECT ref_id FROM ' . self::TABLE_NAME . ' WHERE '
            . self::COL_DEPARTMENT_NAME . ' = ' . $this->db->quote($department, \ilDBConstants::T_TEXT)
            . ' AND '
            . self::COL_EVENT_KIND . ' = ' . $this->db->quote($kind, \ilDBConstants::T_TEXT)
            . ' AND '
            . self::COL_YEAR . ' = ' . $this->db->quote($year, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);

        if ($row = $this->db->fetchAssoc($result)) {
            $ref_id = $row['ref_id'];
            $this->addToCache($ref_id, $department, $kind, $year);

            return $ref_id;
        }

        return null;
    }

    public function getAllLocations() : array
    {
        $query = "SELECT " . self::COL_DEPARTMENT_NAME . ", " . self::COL_EVENT_KIND . ", " . self::COL_YEAR . ", " . self::COL_REF_ID
            . " FROM " . self::TABLE_NAME;
        $result = $this->db->query($query);

        $locations = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $locations[] = $row;
        }

        return $locations;
    }
}

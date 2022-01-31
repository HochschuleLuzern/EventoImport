<?php declare(strict_types = 1);

namespace EventoImport\import\db\query;

use EventoImport\communication\api_models\EventoEvent;

class IliasEventObjectQuery
{
    private \ilDBInterface $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function fetchAllEventableObjectsForGivenTitle(string $getName)
    {
        return [];
        $query = "SELECT obj_id " . $this->db->quote($getName, \ilDBConstants::T_TEXT);
    }

    public function searchPossibleParentEventForEvent(EventoEvent $event)
    {
        $query = 'SELECT obj_id FROM object_data WHERE title=' . $this->db->quote($event->getGroupName(), \ilDBConstants::T_TEXT) . " AND type = 'crs'";
        $result = $this->db->query($query);

        return $this->db->numRows($result) == 1 ? $this->db->fetchAssoc($result)['obj_id'] : null;
    }
}

<?php

namespace EventoImport\import\db\query;

use EventoImport\communication\api_models\EventoEvent;

/**
 * Class IliasEventObjectQuery
 * @package EventoImport\import\db\query
 */
class IliasEventObjectQuery
{
    /** @var \ilDBInterface */
    private \ilDBInterface $db;

    /**
     * IliasEventObjectQuery constructor.
     * @param \ilDBInterface $db
     */
    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $getName
     * @return array
     */
    public function fetchAllEventableObjectsForGivenTitle(string $getName)
    {
        return [];
        $query = "SELECT obj_id " . $this->db->quote($getName, \ilDBConstants::T_TEXT);
    }

    /**
     * @param EventoEvent $event
     * @return mixed|null
     */
    public function searchPossibleParentEventForEvent(EventoEvent $event)
    {
        $query = 'SELECT obj_id FROM object_data WHERE title=' . $this->db->quote($event->getGroupName(), \ilDBConstants::T_TEXT) . " AND type = 'crs'";
        $result = $this->db->query($query);

        return $this->db->numRows($result) == 1 ? $this->db->fetchAssoc($result)['obj_id'] : null;
    }
}

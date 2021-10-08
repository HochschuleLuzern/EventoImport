<?php

namespace EventoImport\import\db_repository;

use EventoImport\import\data_models\EventoEvent;
use EventoImport\import\data_models\IliasEventoEventCombination;

class IliasEventoEventsRepository
{

    public const TABLE_NAME = 'crevento_mapped_evnto_events';

    public const COL_EVENTO_ID = 'evento_id';
    public const COL_PARENT_REF_ID = 'parent_ref_id';
    public const COL_REF_ID = 'ref_id';
    public const COL_OBJ_ID = 'obj_id';
    public const COL_ADMIN_ROLE_ID = 'admin_role_id';
    public const COL_STUDENT_ROLE_ID = 'student_role_id';
    public const COL_EVENTO_TYPE = 'evento_type';
    public const COL_WAS_AUTOMATICALLY_CREATED = 'was_automatically_created';
    public const COL_START_DATE = 'start_date';
    public const COL_END_DATE = 'end_date';
    public const COL_ILIAS_TYPE = 'ilias_type';


    /** @var \ilDBInterface */
    private $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    private function buildIliasEventoEventFromRow($row)
    {
        return new IliasEventoEventCombination(
            $row[self::COL_EVENTO_ID],
            $row[self::COL_PARENT_REF_ID],
            $row[self::COL_REF_ID],
            $row[self::COL_OBJ_ID],
            $row[self::COL_ADMIN_ROLE_ID],
            $row[self::COL_STUDENT_ROLE_ID],
            $row[self::COL_EVENTO_TYPE],
            $row[self::COL_WAS_AUTOMATICALLY_CREATED],
            $row[self::COL_START_DATE],
            $row[self::COL_END_DATE],
            $row[self::COL_ILIAS_TYPE]
        );
    }

    public function addNewEventoIliasEvent(EventoEvent $event, \ilContainer $ilias_event_obj)
    {
        $this->db->insert(
        // INSERT INTO
            self::TABLE_NAME,

            // VALUES
            array(
                self::COL_EVENTO_ID => array(\ilDBConstants::T_INTEGER, $event->getEventoId())
                //self::COL_ILIAS_USER_ID => array(\ilDBConstants::T_INTEGER, $ilias_event_obj->getId())
            )
        );
    }

    public function getEventByEventoId(int $evento_id) : ?IliasEventoEventCombination
    {
        $query = "SELECT * FROM " . self::TABLE_NAME . " WHERE " . self::COL_EVENTO_ID . " = " . $this->db->quote($evento_id, \ilDBConstants::T_INTEGER);
        $result = $this->db->query($query);
        if($row = $this->db->fetchAssoc($result)) {
            return $this->buildIliasEventoEventFromRow($row);
        }

        return null;
    }
}
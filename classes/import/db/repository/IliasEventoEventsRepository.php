<?php

namespace EventoImport\import\db\repository;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoEvent;

class IliasEventoEventsRepository
{

    public const TABLE_NAME = 'crevento_evnto_events';

    public const COL_EVENTO_ID = 'evento_id';
    public const COL_PARENT_EVENT_REF_ID = 'parent_event_ref_id';
    public const COL_REF_ID = 'ref_id';
    public const COL_OBJ_ID = 'obj_id';
    public const COL_ADMIN_ROLE_ID = 'admin_role_id';
    public const COL_STUDENT_ROLE_ID = 'student_role_id';
    public const COL_EVENTO_TITLE = 'evento_title';
    public const COL_EVENTO_DESCRIPTION = 'evento_description';
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
        return new IliasEventoEvent(
            $row[self::COL_EVENTO_ID],
            $row[self::COL_EVENTO_TITLE],
            $row[self::COL_EVENTO_DESCRIPTION],
            $row[self::COL_EVENTO_TYPE],
            $row[self::COL_WAS_AUTOMATICALLY_CREATED],
            $row[self::COL_START_DATE],
            $row[self::COL_END_DATE],
            $row[self::COL_ILIAS_TYPE],
            $row[self::COL_REF_ID],
            $row[self::COL_OBJ_ID],
            $row[self::COL_ADMIN_ROLE_ID],
            $row[self::COL_STUDENT_ROLE_ID],
        );
    }

    public function addNewEventoIliasEvent(IliasEventoEvent $ilias_evento_event)
    {
        $this->db->insert(
        // INSERT INTO
            self::TABLE_NAME,

            // VALUES
            array(
                // id
                self::COL_EVENTO_ID                 => array(\ilDBConstants::T_INTEGER, $ilias_evento_event->getEventoEventId()),

                // evento values
                self::COL_EVENTO_TITLE              => array(\ilDBConstants::T_TEXT, $ilias_evento_event->getEventoTitle()),
                self::COL_EVENTO_DESCRIPTION        => array(\ilDBConstants::T_TEXT, $ilias_evento_event->getEventoDescription()),
                self::COL_EVENTO_TYPE               => array(\ilDBConstants::T_TEXT, $ilias_evento_event->getEventoType()),
                self::COL_WAS_AUTOMATICALLY_CREATED => array(\ilDBConstants::T_INTEGER, $ilias_evento_event->wasAutomaticallyCreated()),
                self::COL_START_DATE                => array(\ilDBConstants::T_TIMESTAMP, $ilias_evento_event->getStartDate()),
                self::COL_END_DATE                  => array(\ilDBConstants::T_TIMESTAMP, $ilias_evento_event->getEndDate()),
                self::COL_ILIAS_TYPE                => array(\ilDBConstants::T_TEXT, $ilias_evento_event->iliasType()),

                // foreign keys
                self::COL_REF_ID                    => array(\ilDBConstants::T_INTEGER, $ilias_evento_event->getRefId()),
                self::COL_OBJ_ID                    => array(\ilDBConstants::T_INTEGER, $ilias_evento_event->getObjId()),
                self::COL_ADMIN_ROLE_ID             => array(\ilDBConstants::T_INTEGER, $ilias_evento_event->getAdminRoleId()),
                self::COL_STUDENT_ROLE_ID           => array(\ilDBConstants::T_INTEGER, $ilias_evento_event->getStudentRoleId()),
                self::COL_PARENT_EVENT_REF_ID       => array(\ilDBConstants::T_INTEGER, $ilias_evento_event->getParentEventRefId())
            )
        );
    }

    public function getEventByEventoId(int $evento_id) : ?IliasEventoEvent
    {
        $query  = "SELECT * FROM " . self::TABLE_NAME . " WHERE " . self::COL_EVENTO_ID . " = " . $this->db->quote($evento_id, \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);
        if ($row = $this->db->fetchAssoc($result)) {
            return $this->buildIliasEventoEventFromRow($row);
        }

        return null;
    }
}
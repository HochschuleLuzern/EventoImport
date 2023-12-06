<?php

namespace EventoImport\import\data_management\repository;

use EventoImport\import\data_management\repository\model\IliasEventoEvent;
use EventoImport\db\IliasEventoEventsTblDef;
use EventoImport\import\data_management\repository\model\IliasEventoParentEvent;
use EventoImport\db\IliasParentEventTblDef;

class IliasEventoEventObjectRepository
{
    private \ilDBInterface $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function addNewEventoIliasEvent(IliasEventoEvent $ilias_evento_event)
    {
        $this->db->insert(
            // INSERT INTO
            IliasEventoEventsTblDef::TABLE_NAME,

            // VALUES
            [
                // id
                IliasEventoEventsTblDef::COL_EVENTO_ID => [\ilDBConstants::T_INTEGER,
                                                                $ilias_evento_event->getEventoEventId()
                ],

                // evento values
                IliasEventoEventsTblDef::COL_EVENTO_TITLE => [\ilDBConstants::T_TEXT,
                                                                   $ilias_evento_event->getEventoTitle()
                ],
                IliasEventoEventsTblDef::COL_EVENTO_DESCRIPTION => [\ilDBConstants::T_TEXT,
                                                                         $ilias_evento_event->getEventoDescription()
                ],
                IliasEventoEventsTblDef::COL_EVENTO_TYPE => [\ilDBConstants::T_TEXT,
                                                                  $ilias_evento_event->getEventoType()
                ],
                IliasEventoEventsTblDef::COL_WAS_AUTOMATICALLY_CREATED => [\ilDBConstants::T_INTEGER,
                                                                                $ilias_evento_event->wasAutomaticallyCreated()
                ],
                IliasEventoEventsTblDef::COL_START_DATE => [\ilDBConstants::T_TIMESTAMP,
                                                                 $this->dateTimeToDBFormatOrNull($ilias_evento_event->getStartDate())
                ],
                IliasEventoEventsTblDef::COL_END_DATE => [\ilDBConstants::T_TIMESTAMP,
                                                               $this->dateTimeToDBFormatOrNull($ilias_evento_event->getEndDate())
                ],
                IliasEventoEventsTblDef::COL_LAST_TIME_DELIVERED => [\ilDBConstants::T_TIMESTAMP, date("Y-m-d H:i:s")],
                IliasEventoEventsTblDef::COL_ILIAS_TYPE => [\ilDBConstants::T_TEXT, $ilias_evento_event->getIliasType()],

                // foreign keys
                IliasEventoEventsTblDef::COL_REF_ID => [\ilDBConstants::T_INTEGER, $ilias_evento_event->getRefId()],
                IliasEventoEventsTblDef::COL_OBJ_ID => [\ilDBConstants::T_INTEGER, $ilias_evento_event->getObjId()],
                IliasEventoEventsTblDef::COL_ADMIN_ROLE_ID => [\ilDBConstants::T_INTEGER, $ilias_evento_event->getAdminRoleId()],
                IliasEventoEventsTblDef::COL_STUDENT_ROLE_ID => [\ilDBConstants::T_INTEGER, $ilias_evento_event->getStudentRoleId()],
                IliasEventoEventsTblDef::COL_PARENT_EVENT_KEY => [\ilDBConstants::T_TEXT, $ilias_evento_event->getParentEventKey()]
            ]
        );
    }

    public function addNewParentEvent(IliasEventoParentEvent $parent_event) : void
    {
        $this->db->insert(
        // INSERT INTO
            IliasParentEventTblDef::TABLE_NAME,

            // VALUES
            [
                // id
                IliasParentEventTblDef::COL_GROUP_UNIQUE_KEY => [\ilDBConstants::T_TEXT, $parent_event->getGroupUniqueKey()],
                IliasParentEventTblDef::COL_GROUP_EVENTO_ID => [\ilDBConstants::T_INTEGER, $parent_event->getGroupEventoId()],

                // foreign keys
                IliasParentEventTblDef::COL_TITLE => [\ilDBConstants::T_TEXT, $parent_event->getTitle()],
                IliasParentEventTblDef::COL_REF_ID => [\ilDBConstants::T_INTEGER, $parent_event->getRefId()],
                IliasParentEventTblDef::COL_ADMIN_ROLE_ID => [\ilDBConstants::T_INTEGER, $parent_event->getAdminRoleId()],
                IliasParentEventTblDef::COL_STUDENT_ROLE_ID => [\ilDBConstants::T_INTEGER, $parent_event->getStudentRoleId()],
            ]
        );
    }

    public function getEventByEventoId(int $evento_id) : ?IliasEventoEvent
    {
        $query = "SELECT * FROM " . IliasEventoEventsTblDef::TABLE_NAME . " WHERE " . IliasEventoEventsTblDef::COL_EVENTO_ID . " = " . $this->db->quote(
            $evento_id,
            \ilDBConstants::T_INTEGER
        );

        $result = $this->db->query($query);
        if ($row = $this->db->fetchAssoc($result)) {
            return $this->buildIliasEventoEventFromRow($row);
        }

        return null;
    }

    public function getParentEventbyGroupUniqueKey(string $group_unique_key) : ?IliasEventoParentEvent
    {
        $query = 'SELECT * FROM ' . IliasParentEventTblDef::TABLE_NAME . ' WHERE ' . IliasParentEventTblDef::COL_GROUP_UNIQUE_KEY . ' = ' . $this->db->quote(
            $group_unique_key,
            \ilDBConstants::T_TEXT
        );
        $result = $this->db->query($query);

        if ($row = $this->db->fetchAssoc($result)) {
            return $this->buildParentEventObjectFromRow($row);
        }

        return null;
    }

    public function getParentEventForName(string $name) : ?IliasEventoParentEvent
    {
        $query = 'SELECT * FROM ' . IliasParentEventTblDef::TABLE_NAME . ' WHERE ' . IliasParentEventTblDef::COL_TITLE . ' = ' . $this->db->quote($name, \ilDBConstants::T_TEXT);
        $result = $this->db->query($query);

        if ($row = $this->db->fetchAssoc($result)) {
            return $this->buildParentEventObjectFromRow($row);
        }

        return null;
    }

    public function updateIliasEventoEvent(IliasEventoEvent $updated_obj)
    {
        $this->db->update(
            // INSERT INTO
            IliasEventoEventsTblDef::TABLE_NAME,

            // VALUES
            [
                // evento values
                IliasEventoEventsTblDef::COL_EVENTO_TITLE => [\ilDBConstants::T_TEXT, $updated_obj->getEventoTitle()],
                IliasEventoEventsTblDef::COL_EVENTO_DESCRIPTION => [\ilDBConstants::T_TEXT, $updated_obj->getEventoDescription()],
                IliasEventoEventsTblDef::COL_EVENTO_TYPE => [\ilDBConstants::T_TEXT, $updated_obj->getEventoType()],
                IliasEventoEventsTblDef::COL_WAS_AUTOMATICALLY_CREATED => [\ilDBConstants::T_INTEGER, $updated_obj->wasAutomaticallyCreated()],
                IliasEventoEventsTblDef::COL_START_DATE => [\ilDBConstants::T_TIMESTAMP, $this->dateTimeToDBFormatOrNull($updated_obj->getStartDate())],
                IliasEventoEventsTblDef::COL_END_DATE => [\ilDBConstants::T_TIMESTAMP, $this->dateTimeToDBFormatOrNull($updated_obj->getEndDate())],
                IliasEventoEventsTblDef::COL_ILIAS_TYPE => [\ilDBConstants::T_TEXT, $updated_obj->getIliasType()],

                // foreign keys
                IliasEventoEventsTblDef::COL_REF_ID => [\ilDBConstants::T_INTEGER, $updated_obj->getRefId()],
                IliasEventoEventsTblDef::COL_OBJ_ID => [\ilDBConstants::T_INTEGER, $updated_obj->getObjId()],
                IliasEventoEventsTblDef::COL_ADMIN_ROLE_ID => [\ilDBConstants::T_INTEGER, $updated_obj->getAdminRoleId()],
                IliasEventoEventsTblDef::COL_STUDENT_ROLE_ID => [\ilDBConstants::T_INTEGER, $updated_obj->getStudentRoleId()],
                IliasEventoEventsTblDef::COL_PARENT_EVENT_KEY => [\ilDBConstants::T_TEXT, $updated_obj->getParentEventKey()]
            ],

            // WHERE
            [
                IliasEventoEventsTblDef::COL_EVENTO_ID => [\ilDBConstants::T_INTEGER, $updated_obj->getEventoEventId()]
            ]
        );
    }

    public function removeIliasEventoEvent(IliasEventoEvent $ilias_evento_event)
    {
        $query = 'DELETE FROM ' . IliasEventoEventsTblDef::TABLE_NAME
            . ' WHERE ' . IliasEventoEventsTblDef::COL_EVENTO_ID . ' = ' . $this->db->quote($ilias_evento_event->getEventoEventId(), \ilDBConstants::T_INTEGER);
        $this->db->query($query);
    }

    public function getNumberOfChildEventsForParentEventKey(IliasEventoParentEvent $parent_event) : int
    {
        $query = 'SELECT count(1) as cnt FROM ' . IliasEventoEventsTblDef::TABLE_NAME
            . " WHERE " . IliasEventoEventsTblDef::COL_PARENT_EVENT_KEY . ' = ' . $this->db->quote($parent_event->getGroupUniqueKey(), \ilDBConstants::T_TEXT);
        $res = $this->db->query($query);
        $data = $this->db->fetchAssoc($res);

        return (int) $data['cnt'];
    }

    public function removeParentEventIfItHasNoChildEvent(IliasEventoParentEvent $parent_event)
    {
        if ($this->getNumberOfChildEventsForParentEventKey($parent_event) <= 1) {
            $this->removeParentEvent($parent_event);
        }
    }

    private function removeParentEvent(IliasEventoParentEvent $parent_event)
    {
        $query = 'DELETE FROM ' . IliasParentEventTblDef::TABLE_NAME
            . ' WHERE ' . IliasParentEventTblDef::COL_GROUP_UNIQUE_KEY . ' = ' . $this->db->quote($parent_event->getGroupUniqueKey(), \ilDBConstants::T_TEXT);
        $this->db->manipulate($query);
    }

    private function buildIliasEventoEventFromRow(array $row)
    {
        return new IliasEventoEvent(
            $row[IliasEventoEventsTblDef::COL_EVENTO_ID],
            $row[IliasEventoEventsTblDef::COL_EVENTO_TITLE],
            $row[IliasEventoEventsTblDef::COL_EVENTO_DESCRIPTION],
            $row[IliasEventoEventsTblDef::COL_EVENTO_TYPE],
            $row[IliasEventoEventsTblDef::COL_WAS_AUTOMATICALLY_CREATED],
            $this->toDateTimeOrNull($row[IliasEventoEventsTblDef::COL_START_DATE]),
            $this->toDateTimeOrNull($row[IliasEventoEventsTblDef::COL_END_DATE]),
            $row[IliasEventoEventsTblDef::COL_ILIAS_TYPE],
            $row[IliasEventoEventsTblDef::COL_REF_ID],
            $row[IliasEventoEventsTblDef::COL_OBJ_ID],
            $row[IliasEventoEventsTblDef::COL_ADMIN_ROLE_ID],
            $row[IliasEventoEventsTblDef::COL_STUDENT_ROLE_ID],
            isset($row[IliasEventoEventsTblDef::COL_PARENT_EVENT_KEY]) ? $row[IliasEventoEventsTblDef::COL_PARENT_EVENT_KEY] : null
        );
    }

    private function buildParentEventObjectFromRow(array $row) : IliasEventoParentEvent
    {
        return new IliasEventoParentEvent(
            $row[IliasParentEventTblDef::COL_GROUP_UNIQUE_KEY],
            $row[IliasParentEventTblDef::COL_GROUP_EVENTO_ID],
            $row[IliasParentEventTblDef::COL_TITLE],
            $row[IliasParentEventTblDef::COL_REF_ID],
            $row[IliasParentEventTblDef::COL_ADMIN_ROLE_ID],
            $row[IliasParentEventTblDef::COL_STUDENT_ROLE_ID],
        );
    }

    private function dateTimeToDBFormatOrNull(?\DateTime $date_time) : ?string
    {
        if (is_null($date_time)) {
            return null;
        }

        return $date_time->format('Y-m-d H:i:s');
    }

    private function toDateTimeOrNull(?string $db_value)
    {
        if (is_null($db_value)) {
            return null;
        } else {
            $date_time = new \DateTime($db_value);
            if ($date_time->format('Y') < 1) {
                return null;
            }
            return $date_time;
        }
    }

    public function getActiveEventsWithLastImportOlderThanOneWeek() : array
    {
        $query = "SELECT * "
            . " FROM " . IliasEventoEventsTblDef::TABLE_NAME
            . " WHERE " . IliasEventoEventsTblDef::COL_END_DATE . " > " . $this->db->quote(date("Y-m-d"), \ilDBConstants::T_DATETIME)
            . " AND " . IliasEventoEventsTblDef::COL_LAST_TIME_DELIVERED . " < " . $this->db->quote(date("Y-m-d", strtotime("-1 week")), \ilDBConstants::T_DATETIME)
            . " AND " . IliasEventoEventsTblDef::COL_WAS_AUTOMATICALLY_CREATED . " = 1";

        $result = $this->db->query($query);

        $data = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $data[] = $this->buildIliasEventoEventFromRow($row);
        }

        return $data;
    }

    public function registerEventAsDelivered(int $event_id)
    {
        $this->db->update(
            IliasEventoEventsTblDef::TABLE_NAME,
            [
                IliasEventoEventsTblDef::COL_LAST_TIME_DELIVERED => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")]
            ],
            [
                IliasEventoEventsTblDef::COL_EVENTO_ID => [\ilDBConstants::T_INTEGER, $event_id]
            ]
        );
    }

    public function iliasEventoEventHasParentEvent(IliasEventoEvent $ilias_evento_event) : bool
    {
        $query = "SELECT " . IliasParentEventTblDef::COL_GROUP_UNIQUE_KEY
            . " FROM " . IliasParentEventTblDef::TABLE_NAME
            . " WHERE " . IliasParentEventTblDef::COL_GROUP_UNIQUE_KEY . " = " . $this->db->quote($ilias_evento_event->getParentEventKey(), \ilDBConstants::T_TEXT);
        $result = $this->db->query($query);

        if ($row = $this->db->fetchAssoc($result)) {
            return true;
        } else {
            return false;
        }
    }

    public function getIliasEventoEventsByTitle(string $title, bool $like_search) : array
    {
        $query = "SELECT * " . " FROM " . IliasEventoEventsTblDef::TABLE_NAME . " WHERE ";
        $query .= $like_search
            ? $this->db->like('evento_title', \ilDBConstants::T_TEXT, $title . '%')
            : IliasEventoEventsTblDef::COL_EVENTO_ID . " = " . $this->db->quote($title, \ilDBConstants::T_TEXT);

        $result = $this->db->query($query);

        $events = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $events[] = $this->buildIliasEventoEventFromRow($row);
        }

        return $events;
    }
}

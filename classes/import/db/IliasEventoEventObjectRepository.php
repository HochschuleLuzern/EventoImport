<?php

namespace EventoImport\import\db;

use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\table_definition\IliasEventoEventsTblDef;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\db\table_definition\IliasParentEventsTblDef;

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
            array(
                // id
                IliasEventoEventsTblDef::COL_EVENTO_ID => array(\ilDBConstants::T_INTEGER,
                                                                $ilias_evento_event->getEventoEventId()
                ),

                // evento values
                IliasEventoEventsTblDef::COL_EVENTO_TITLE => array(\ilDBConstants::T_TEXT,
                                                                   $ilias_evento_event->getEventoTitle()
                ),
                IliasEventoEventsTblDef::COL_EVENTO_DESCRIPTION => array(\ilDBConstants::T_TEXT,
                                                                         $ilias_evento_event->getEventoDescription()
                ),
                IliasEventoEventsTblDef::COL_EVENTO_TYPE => array(\ilDBConstants::T_TEXT,
                                                                  $ilias_evento_event->getEventoType()
                ),
                IliasEventoEventsTblDef::COL_WAS_AUTOMATICALLY_CREATED => array(\ilDBConstants::T_INTEGER,
                                                                                $ilias_evento_event->wasAutomaticallyCreated()
                ),
                IliasEventoEventsTblDef::COL_START_DATE => array(\ilDBConstants::T_TIMESTAMP,
                                                                 $this->dateTimeToDBFormatOrNull($ilias_evento_event->getStartDate())
                ),
                IliasEventoEventsTblDef::COL_END_DATE => array(\ilDBConstants::T_TIMESTAMP,
                                                               $this->dateTimeToDBFormatOrNull($ilias_evento_event->getEndDate())
                ),
                IliasEventoEventsTblDef::COL_ILIAS_TYPE => array(\ilDBConstants::T_TEXT, $ilias_evento_event->getIliasType()),

                // foreign keys
                IliasEventoEventsTblDef::COL_REF_ID => array(\ilDBConstants::T_INTEGER, $ilias_evento_event->getRefId()),
                IliasEventoEventsTblDef::COL_OBJ_ID => array(\ilDBConstants::T_INTEGER, $ilias_evento_event->getObjId()),
                IliasEventoEventsTblDef::COL_ADMIN_ROLE_ID => array(\ilDBConstants::T_INTEGER,
                                                                    $ilias_evento_event->getAdminRoleId()
                ),
                IliasEventoEventsTblDef::COL_STUDENT_ROLE_ID => array(\ilDBConstants::T_INTEGER,
                                                                      $ilias_evento_event->getStudentRoleId()
                ),
                IliasEventoEventsTblDef::COL_PARENT_EVENT_KEY => array(\ilDBConstants::T_TEXT,
                                                                       $ilias_evento_event->getParentEventKey()
                )
            )
        );
    }

    public function addNewParentEvent(IliasEventoParentEvent $parent_event) : void
    {
        $this->db->insert(
        // INSERT INTO
            IliasParentEventsTblDef::TABLE_NAME,

            // VALUES
            [
                // id
                IliasParentEventsTblDef::COL_GROUP_UNIQUE_KEY => [\ilDBConstants::T_TEXT, $parent_event->getGroupUniqueKey()],

                // foreign keys
                IliasParentEventsTblDef::COL_TITLE => [\ilDBConstants::T_TEXT, $parent_event->getTitle()],
                IliasParentEventsTblDef::COL_REF_ID => [\ilDBConstants::T_INTEGER, $parent_event->getRefId()],
                IliasParentEventsTblDef::COL_ADMIN_ROLE_ID => [\ilDBConstants::T_INTEGER, $parent_event->getAdminRoleId()],
                IliasParentEventsTblDef::COL_STUDENT_ROLE_ID => [\ilDBConstants::T_INTEGER,
                                                                 $parent_event->getStudentRoleId()
                ],
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
        $query = 'SELECT * FROM ' . IliasParentEventsTblDef::TABLE_NAME . ' WHERE ' . IliasParentEventsTblDef::COL_GROUP_UNIQUE_KEY . ' = ' . $this->db->quote(
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
        $query = 'SELECT * FROM ' . IliasParentEventsTblDef::TABLE_NAME . ' WHERE ' . IliasParentEventsTblDef::COL_TITLE . ' = ' . $this->db->quote($name, \ilDBConstants::T_TEXT);
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
            array(
                // evento values
                IliasEventoEventsTblDef::COL_EVENTO_TITLE => array(\ilDBConstants::T_TEXT, $updated_obj->getEventoTitle()),
                IliasEventoEventsTblDef::COL_EVENTO_DESCRIPTION => array(\ilDBConstants::T_TEXT, $updated_obj->getEventoDescription()),
                IliasEventoEventsTblDef::COL_EVENTO_TYPE => array(\ilDBConstants::T_TEXT, $updated_obj->getEventoType()),
                IliasEventoEventsTblDef::COL_WAS_AUTOMATICALLY_CREATED => array(\ilDBConstants::T_INTEGER, $updated_obj->wasAutomaticallyCreated()),
                IliasEventoEventsTblDef::COL_START_DATE => array(\ilDBConstants::T_TIMESTAMP, $this->dateTimeToDBFormatOrNull($updated_obj->getStartDate())),
                IliasEventoEventsTblDef::COL_END_DATE => array(\ilDBConstants::T_TIMESTAMP, $this->dateTimeToDBFormatOrNull($updated_obj->getEndDate())),
                IliasEventoEventsTblDef::COL_ILIAS_TYPE => array(\ilDBConstants::T_TEXT, $updated_obj->getIliasType()),

                // foreign keys
                IliasEventoEventsTblDef::COL_REF_ID => array(\ilDBConstants::T_INTEGER, $updated_obj->getRefId()),
                IliasEventoEventsTblDef::COL_OBJ_ID => array(\ilDBConstants::T_INTEGER, $updated_obj->getObjId()),
                IliasEventoEventsTblDef::COL_ADMIN_ROLE_ID => array(\ilDBConstants::T_INTEGER, $updated_obj->getAdminRoleId()),
                IliasEventoEventsTblDef::COL_STUDENT_ROLE_ID => array(\ilDBConstants::T_INTEGER, $updated_obj->getStudentRoleId()),
                IliasEventoEventsTblDef::COL_PARENT_EVENT_KEY => array(\ilDBConstants::T_INTEGER, $updated_obj->getParentEventKey())
            ),

            // WHERE
            array(
                IliasEventoEventsTblDef::COL_EVENTO_ID => array(\ilDBConstants::T_INTEGER, $updated_obj->getEventoEventId())
            )
        );
    }

    public function removeEventoEvent(IliasEventoEvent $ilias_evento_event)
    {
        $query = 'DELETE FROM ' . IliasEventoEventsTblDef::TABLE_NAME
            . ' WHERE ' . IliasEventoEventsTblDef::COL_EVENTO_ID . ' = ' . $this->db->quote($ilias_evento_event->getEventoEventId(), \ilDBConstants::T_INTEGER);
        $this->db->query($query);
    }

    public function removeParentEventIfItHasNoChildEvent(string $parent_event_key)
    {
        $query = 'SELECT count(1) as cnt FROM ' . IliasEventoEventsTblDef::TABLE_NAME
            . " WHERE " . IliasEventoEventsTblDef::COL_PARENT_EVENT_KEY . ' = ' . $this->db->quote($parent_event_key, \ilDBConstants::T_TEXT);
        $res = $this->db->query($query);
        $data = $this->db->fetchAssoc($res);
        if (isset($data['cnt']) && ((int) $data['cnt']) <= 1) {
            $this->removeParentEvent($parent_event_key);
        }
    }

    private function removeParentEvent(string $parent_event_key)
    {
        $query = 'DELETE FROM ' . IliasParentEventsTblDef::TABLE_NAME . ' WHERE ' . IliasParentEventsTblDef::COL_GROUP_UNIQUE_KEY . ' = ' . $this->db->quote($parent_event_key, \ilDBConstants::T_TEXT);
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
            $row[IliasParentEventsTblDef::COL_GROUP_UNIQUE_KEY],
            $row[IliasParentEventsTblDef::COL_GROUP_EVENTO_ID],
            $row[IliasParentEventsTblDef::COL_TITLE],
            $row[IliasParentEventsTblDef::COL_REF_ID],
            $row[IliasParentEventsTblDef::COL_ADMIN_ROLE_ID],
            $row[IliasParentEventsTblDef::COL_STUDENT_ROLE_ID],
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
}

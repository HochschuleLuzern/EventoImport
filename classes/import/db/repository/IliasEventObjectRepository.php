<?php

namespace EventoImport\import\db;

use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\table_definitions\IliasEventoEvents;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\db\table_definitions\IliasParentEvents;

class IliasEventObjectRepository
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
            IliasEventoEvents::TABLE_NAME,

            // VALUES
            array(
                // id
                IliasEventoEvents::COL_EVENTO_ID => array(\ilDBConstants::T_INTEGER,
                                                          $ilias_evento_event->getEventoEventId()
                ),

                // evento values
                IliasEventoEvents::COL_EVENTO_TITLE => array(\ilDBConstants::T_TEXT,
                                                             $ilias_evento_event->getEventoTitle()
                ),
                IliasEventoEvents::COL_EVENTO_DESCRIPTION => array(\ilDBConstants::T_TEXT,
                                                                   $ilias_evento_event->getEventoDescription()
                ),
                IliasEventoEvents::COL_EVENTO_TYPE => array(\ilDBConstants::T_TEXT,
                                                            $ilias_evento_event->getEventoType()
                ),
                IliasEventoEvents::COL_WAS_AUTOMATICALLY_CREATED => array(\ilDBConstants::T_INTEGER,
                                                                          $ilias_evento_event->wasAutomaticallyCreated()
                ),
                IliasEventoEvents::COL_START_DATE => array(\ilDBConstants::T_TIMESTAMP,
                                                           $this->dateTimeToDBFormatOrNull($ilias_evento_event->getStartDate())
                ),
                IliasEventoEvents::COL_END_DATE => array(\ilDBConstants::T_TIMESTAMP,
                                                         $this->dateTimeToDBFormatOrNull($ilias_evento_event->getEndDate())
                ),
                IliasEventoEvents::COL_ILIAS_TYPE => array(\ilDBConstants::T_TEXT, $ilias_evento_event->getIliasType()),

                // foreign keys
                IliasEventoEvents::COL_REF_ID => array(\ilDBConstants::T_INTEGER, $ilias_evento_event->getRefId()),
                IliasEventoEvents::COL_OBJ_ID => array(\ilDBConstants::T_INTEGER, $ilias_evento_event->getObjId()),
                IliasEventoEvents::COL_ADMIN_ROLE_ID => array(\ilDBConstants::T_INTEGER,
                                                              $ilias_evento_event->getAdminRoleId()
                ),
                IliasEventoEvents::COL_STUDENT_ROLE_ID => array(\ilDBConstants::T_INTEGER,
                                                                $ilias_evento_event->getStudentRoleId()
                ),
                IliasEventoEvents::COL_PARENT_EVENT_KEY => array(\ilDBConstants::T_TEXT,
                                                                 $ilias_evento_event->getParentEventKey()
                )
            )
        );
    }

    public function addNewParentEvent(IliasEventoParentEvent $parent_event) : void
    {
        $this->db->insert(
        // INSERT INTO
            IliasParentEvents::TABLE_NAME,

            // VALUES
            [
                // id
                IliasParentEvents::COL_GROUP_UNIQUE_KEY => [\ilDBConstants::T_TEXT, $parent_event->getGroupUniqueKey()],

                // foreign keys
                IliasParentEvents::COL_TITLE => [\ilDBConstants::T_TEXT, $parent_event->getTitle()],
                IliasParentEvents::COL_REF_ID => [\ilDBConstants::T_INTEGER, $parent_event->getRefId()],
                IliasParentEvents::COL_ADMIN_ROLE_ID => [\ilDBConstants::T_INTEGER, $parent_event->getAdminRoleId()],
                IliasParentEvents::COL_STUDENT_ROLE_ID => [\ilDBConstants::T_INTEGER,
                                                           $parent_event->getStudentRoleId()
                ],
            ]
        );
    }

    public function getEventByEventoId(int $evento_id) : ?IliasEventoEvent
    {
        $query = "SELECT * FROM " . IliasEventoEvents::TABLE_NAME . " WHERE " . IliasEventoEvents::COL_EVENTO_ID . " = " . $this->db->quote($evento_id,
                \ilDBConstants::T_INTEGER);

        $result = $this->db->query($query);
        if ($row = $this->db->fetchAssoc($result)) {
            return $this->buildIliasEventoEventFromRow($row);
        }

        return null;
    }

    public function getParentEventbyGroupUniqueKey(string $group_unique_key) : ?IliasEventoParentEvent
    {
        $query = 'SELECT * FROM ' . IliasParentEvents::TABLE_NAME . ' WHERE ' . IliasParentEvents::COL_GROUP_UNIQUE_KEY . ' = ' . $this->db->quote($group_unique_key,
                \ilDBConstants::T_TEXT);
        $result = $this->db->query($query);

        if ($row = $this->db->fetchAssoc($result)) {
            return $this->buildParentEventObjectFromRow($row);
        }

        return null;
    }

    public function getParentEventForName(string $name) : ?IliasEventoParentEvent
    {
        $query = 'SELECT * FROM ' . IliasParentEvents::TABLE_NAME . ' WHERE ' . IliasParentEvents::COL_TITLE . ' = ' . $this->db->quote($name, \ilDBConstants::T_TEXT);
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
            IliasEventoEvents::TABLE_NAME,

            // VALUES
            array(
                // evento values
                IliasEventoEvents::COL_EVENTO_TITLE => array(\ilDBConstants::T_TEXT, $updated_obj->getEventoTitle()),
                IliasEventoEvents::COL_EVENTO_DESCRIPTION => array(\ilDBConstants::T_TEXT, $updated_obj->getEventoDescription()),
                IliasEventoEvents::COL_EVENTO_TYPE => array(\ilDBConstants::T_TEXT, $updated_obj->getEventoType()),
                IliasEventoEvents::COL_WAS_AUTOMATICALLY_CREATED => array(\ilDBConstants::T_INTEGER, $updated_obj->wasAutomaticallyCreated()),
                IliasEventoEvents::COL_START_DATE => array(\ilDBConstants::T_TIMESTAMP, $this->dateTimeToDBFormatOrNull($updated_obj->getStartDate())),
                IliasEventoEvents::COL_END_DATE => array(\ilDBConstants::T_TIMESTAMP, $this->dateTimeToDBFormatOrNull($updated_obj->getEndDate())),
                IliasEventoEvents::COL_ILIAS_TYPE => array(\ilDBConstants::T_TEXT, $updated_obj->getIliasType()),

                // foreign keys
                IliasEventoEvents::COL_REF_ID => array(\ilDBConstants::T_INTEGER, $updated_obj->getRefId()),
                IliasEventoEvents::COL_OBJ_ID => array(\ilDBConstants::T_INTEGER, $updated_obj->getObjId()),
                IliasEventoEvents::COL_ADMIN_ROLE_ID => array(\ilDBConstants::T_INTEGER, $updated_obj->getAdminRoleId()),
                IliasEventoEvents::COL_STUDENT_ROLE_ID => array(\ilDBConstants::T_INTEGER, $updated_obj->getStudentRoleId()),
                IliasEventoEvents::COL_PARENT_EVENT_KEY => array(\ilDBConstants::T_INTEGER, $updated_obj->getParentEventKey())
            ),

            // WHERE
            array(
                IliasEventoEvents::COL_EVENTO_ID => array(\ilDBConstants::T_INTEGER, $updated_obj->getEventoEventId())
            )
        );
    }

    public function removeEventoEvent(IliasEventoEvent $ilias_evento_event)
    {
        $query = 'DELETE FROM ' . IliasEventoEvents::TABLE_NAME
            . ' WHERE ' . IliasEventoEvents::COL_EVENTO_ID . ' = ' . $this->db->quote($ilias_evento_event->getEventoEventId(), \ilDBConstants::T_INTEGER);
        $this->db->query($query);
    }

    public function removeParentEventIfItHasNoChildEvent(string $parent_event_key)
    {
        $query = 'SELECT count(1) as cnt FROM ' . IliasEventoEvents::TABLE_NAME
            . " WHERE " . IliasEventoEvents::COL_PARENT_EVENT_KEY . ' = ' . $this->db->quote($parent_event_key, \ilDBConstants::T_TEXT);
        $res = $this->db->query($query);
        $data = $this->db->fetchAssoc($res);
        if (isset($data['cnt']) && ((int) $data['cnt']) <= 1) {
            $this->removeParentEvent($parent_event_key);
        }
    }

    private function removeParentEvent(string $parent_event_key)
    {
        $query = 'DELETE FROM ' . IliasParentEvents::TABLE_NAME . ' WHERE ' . IliasParentEvents::COL_GROUP_UNIQUE_KEY . ' = ' . $this->db->quote($parent_event_key, \ilDBConstants::T_TEXT);
        $this->db->manipulate($query);
    }

    private function buildIliasEventoEventFromRow(array $row)
    {
        return new IliasEventoEvent(
            $row[IliasEventoEvents::COL_EVENTO_ID],
            $row[IliasEventoEvents::COL_EVENTO_TITLE],
            $row[IliasEventoEvents::COL_EVENTO_DESCRIPTION],
            $row[IliasEventoEvents::COL_EVENTO_TYPE],
            $row[IliasEventoEvents::COL_WAS_AUTOMATICALLY_CREATED],
            $this->toDateTimeOrNull($row[IliasEventoEvents::COL_START_DATE]),
            $this->toDateTimeOrNull($row[IliasEventoEvents::COL_END_DATE]),
            $row[IliasEventoEvents::COL_ILIAS_TYPE],
            $row[IliasEventoEvents::COL_REF_ID],
            $row[IliasEventoEvents::COL_OBJ_ID],
            $row[IliasEventoEvents::COL_ADMIN_ROLE_ID],
            $row[IliasEventoEvents::COL_STUDENT_ROLE_ID],
            isset($row[IliasEventoEvents::COL_PARENT_EVENT_KEY]) ? $row[IliasEventoEvents::COL_PARENT_EVENT_KEY] : null
        );
    }

    private function buildParentEventObjectFromRow(array $row) : IliasEventoParentEvent
    {
        return new IliasEventoParentEvent(
            $row[IliasParentEvents::COL_GROUP_UNIQUE_KEY],
            $row[IliasParentEvents::COL_GROUP_EVENTO_ID],
            $row[IliasParentEvents::COL_TITLE],
            $row[IliasParentEvents::COL_REF_ID],
            $row[IliasParentEvents::COL_ADMIN_ROLE_ID],
            $row[IliasParentEvents::COL_STUDENT_ROLE_ID],
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
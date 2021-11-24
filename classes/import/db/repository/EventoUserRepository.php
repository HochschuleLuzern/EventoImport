<?php

namespace EventoImport\import\db\repository;

use EventoImport\communication\api_models\EventoUser;

class EventoUserRepository
{
    public const TABLE_NAME = 'crevento_evnto_usrs';

    public const COL_EVENTO_ID = 'evento_id';
    public const COL_ILIAS_USER_ID = 'ilias_user_id';
    public const COL_LAST_TIME_DELIVERED = 'last_time_delivered';

    /** @var \ilDBInterface */
    private $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function addNewEventoIliasUser(EventoUser $evento_user, \ilObjUser $ilias_user)
    {
        $this->db->insert(
            // INSERT INTO
            self::TABLE_NAME,

            // VALUES
            array(
                self::COL_EVENTO_ID => array(\ilDBConstants::T_INTEGER, $evento_user->getEventoId()),
                self::COL_ILIAS_USER_ID => array(\ilDBConstants::T_INTEGER, $ilias_user->getId()),
                self::COL_LAST_TIME_DELIVERED => array(\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s"))
            )
        );
    }

    public function getIliasUserIdByEventoId(int $evento_id) : ?int
    {
        $query = 'SELECT ' . self::COL_ILIAS_USER_ID . ' FROM ' . self::TABLE_NAME
            . ' WHERE ' . self::COL_EVENTO_ID . '=' . $this->db->quote($evento_id, \ilDBConstants::T_INTEGER);
        $result = $this->db->query($query);

        if ($data = $this->db->fetchAssoc($result)) {
            return $data[self::COL_ILIAS_USER_ID];
        }

        return null;
    }

    public function getListOfIliasUserIdsByEventoIds(array $evento_ids) : array
    {
        $query = 'SELECT ' . self::COL_ILIAS_USER_ID . ' FROM ' . self::TABLE_NAME
            . ' WHERE ' . $this->db->in(self::COL_EVENTO_ID, $evento_ids, false, \ilDBConstants::T_INTEGER);
        $result = $this->db->query($query);

        $user_ids = array();
        while ($data = $this->db->fetchAssoc($result)) {
            $user_ids[] = $data[self::COL_ILIAS_USER_ID];
        }

        return $user_ids;
    }

    public function userWasImported(int $evento_id)
    {
        $this->db->update(
            self::TABLE_NAME,
            [
                self::COL_LAST_TIME_DELIVERED => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")]
            ],
            [
                self::COL_EVENTO_ID => [\ilDBConstants::T_INTEGER, $evento_id]
            ]
        );
    }

    public function fetchNotImportedUsers()
    {
        $query = 'SELECT ' . self::COL_EVENTO_ID . ', ' . self::COL_ILIAS_USER_ID . ' FROM ' . self::TABLE_NAME
            . ' WHERE ' . self::COL_LAST_TIME_DELIVERED . ' < ' . $this->db->quote(date("Y-m-d", strtotime("-1 week")), \ilDBConstants::T_DATETIME);

        $result = $this->db->query($query);

        $not_imported_users = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $not_imported_users[$row[self::COL_EVENTO_ID]] = (int) $row[self::COL_ILIAS_USER_ID];
        }

        return $not_imported_users;
    }

    public function deleteEventoUser(int $evento_id)
    {
        $query = "DELETE FROM " . self::TABLE_NAME . " WHERE " . self::COL_EVENTO_ID . " = " . $this->db->quote($evento_id, \ilDBConstants::T_INTEGER);
        $this->db->manipulate($query);
    }
}

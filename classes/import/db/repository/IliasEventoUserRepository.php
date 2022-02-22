<?php declare(strict_types = 1);

namespace EventoImport\import\db\repository;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\db\table_definition\IliasEventoUser;

class IliasEventoUserRepository
{
    public const TABLE_NAME = 'crevento_evnto_usrs';

    public const COL_EVENTO_ID = 'evento_id';
    public const COL_ILIAS_USER_ID = 'ilias_user_id';
    public const COL_LAST_TIME_DELIVERED = 'last_time_delivered';
    private \ilDBInterface $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function addNewEventoIliasUser(EventoUser $evento_user, \ilObjUser $ilias_user) : void
    {
        $this->db->insert(
            // INSERT INTO
            IliasEventoUser::TABLE_NAME,

            // VALUES
            array(
                IliasEventoUser::COL_EVENTO_ID => array(\ilDBConstants::T_INTEGER, $evento_user->getEventoId()),
                IliasEventoUser::COL_ILIAS_USER_ID => array(\ilDBConstants::T_INTEGER, $ilias_user->getId()),
                IliasEventoUser::COL_LAST_TIME_DELIVERED => array(\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s"))
            )
        );
    }

    public function getIliasUserIdByEventoId(int $evento_id) : ?int
    {
        $query = 'SELECT ' . IliasEventoUser::COL_ILIAS_USER_ID . ' FROM ' . IliasEventoUser::TABLE_NAME
              . ' WHERE ' . IliasEventoUser::COL_EVENTO_ID . '=' . $this->db->quote($evento_id, \ilDBConstants::T_INTEGER);
        $result = $this->db->query($query);

        if ($data = $this->db->fetchAssoc($result)) {
            return (int) $data[IliasEventoUser::COL_ILIAS_USER_ID];
        }

        return null;
    }

    public function getListOfIliasUserIdsByEventoIds(array $evento_ids) : array
    {
        $query = 'SELECT ' . IliasEventoUser::COL_ILIAS_USER_ID . ' FROM ' . IliasEventoUser::TABLE_NAME
              . ' WHERE ' . $this->db->in(IliasEventoUser::COL_EVENTO_ID, $evento_ids, false, \ilDBConstants::T_INTEGER);
        $result = $this->db->query($query);

        $user_ids = array();
        while ($data = $this->db->fetchAssoc($result)) {
            $user_ids[] = $data[IliasEventoUser::COL_ILIAS_USER_ID];
        }

        return $user_ids;
    }

    public function registerUserAsDelivered(int $evento_id) : void
    {
        $this->db->update(
            IliasEventoUser::TABLE_NAME,
            [
                IliasEventoUser::COL_LAST_TIME_DELIVERED => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")]
            ],
            [
                IliasEventoUser::COL_EVENTO_ID => [\ilDBConstants::T_INTEGER, $evento_id]
            ]
        );
    }

    public function getUsersWithLastImportOlderThanOneWeek() : array
    {
        $query = 'SELECT ' . IliasEventoUser::COL_EVENTO_ID . ', ' . IliasEventoUser::COL_ILIAS_USER_ID
            . ' FROM ' . IliasEventoUser::TABLE_NAME
            . ' WHERE ' . IliasEventoUser::COL_LAST_TIME_DELIVERED . ' < ' . $this->db->quote(date("Y-m-d", strtotime("-1 week")), \ilDBConstants::T_DATETIME);

        $result = $this->db->query($query);

        $not_imported_users = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $not_imported_users[$row[IliasEventoUser::COL_EVENTO_ID]] = (int) $row[IliasEventoUser::COL_ILIAS_USER_ID];
        }

        return $not_imported_users;
    }

    public function deleteEventoIliasUserConnectionByEventoId(int $evento_id) : void
    {
        $query = "DELETE FROM " . IliasEventoUser::TABLE_NAME
              . " WHERE " . IliasEventoUser::COL_EVENTO_ID . " = " . $this->db->quote($evento_id, \ilDBConstants::T_INTEGER);
        $this->db->manipulate($query);
    }
}

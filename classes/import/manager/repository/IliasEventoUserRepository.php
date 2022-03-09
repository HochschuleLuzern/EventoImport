<?php declare(strict_types = 1);

namespace EventoImport\import\manager\db;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\manager\db\table_definition\IliasEventoUserTblDef;
use EventoImport\import\manager\db\model\IliasEventoUser;

class IliasEventoUserRepository
{
    public const TYPE_EDU_ID = 'edu_id';
    public const TYPE_HSLU_AD = 'hslu_ad';

    private \ilDBInterface $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function addNewEventoIliasUser(EventoUser $evento_user, \ilObjUser $ilias_user, string $account_type) : IliasEventoUser
    {
        $this->db->insert(
            // INSERT INTO
            IliasEventoUserTblDef::TABLE_NAME,

            // VALUES
            array(
                IliasEventoUserTblDef::COL_EVENTO_ID => array(\ilDBConstants::T_INTEGER, $evento_user->getEventoId()),
                IliasEventoUserTblDef::COL_ILIAS_USER_ID => array(\ilDBConstants::T_INTEGER, $ilias_user->getId()),
                IliasEventoUserTblDef::COL_LAST_TIME_DELIVERED => array(\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")),
                IliasEventoUserTblDef::COL_ACCOUNT_TYPE => array(\ilDBConstants::T_TEXT, $account_type)
            )
        );

        return new IliasEventoUser($evento_user->getEventoId(), $ilias_user->getId(), $account_type);
    }

    public function getIliasUserIdByEventoId(int $evento_id) : ?int
    {
        $query = 'SELECT ' . IliasEventoUserTblDef::COL_ILIAS_USER_ID . ' FROM ' . IliasEventoUserTblDef::TABLE_NAME
              . ' WHERE ' . IliasEventoUserTblDef::COL_EVENTO_ID . '=' . $this->db->quote($evento_id, \ilDBConstants::T_INTEGER);
        $result = $this->db->query($query);

        if ($data = $this->db->fetchAssoc($result)) {
            return (int) $data[IliasEventoUserTblDef::COL_ILIAS_USER_ID];
        }

        return null;
    }

    public function getListOfIliasUserIdsByEventoIds(array $evento_ids) : array
    {
        $query = 'SELECT ' . IliasEventoUserTblDef::COL_ILIAS_USER_ID . ' FROM ' . IliasEventoUserTblDef::TABLE_NAME
              . ' WHERE ' . $this->db->in(IliasEventoUserTblDef::COL_EVENTO_ID, $evento_ids, false, \ilDBConstants::T_INTEGER);
        $result = $this->db->query($query);

        $user_ids = array();
        while ($data = $this->db->fetchAssoc($result)) {
            $user_ids[] = $data[IliasEventoUserTblDef::COL_ILIAS_USER_ID];
        }

        return $user_ids;
    }

    public function registerUserAsDelivered(int $evento_id) : void
    {
        $this->db->update(
            IliasEventoUserTblDef::TABLE_NAME,
            [
                IliasEventoUserTblDef::COL_LAST_TIME_DELIVERED => [\ilDBConstants::T_DATETIME, date("Y-m-d H:i:s")]
            ],
            [
                IliasEventoUserTblDef::COL_EVENTO_ID => [\ilDBConstants::T_INTEGER, $evento_id]
            ]
        );
    }

    public function getUsersWithLastImportOlderThanOneWeek() : array
    {
        $query = 'SELECT ' . IliasEventoUserTblDef::COL_EVENTO_ID . ', ' . IliasEventoUserTblDef::COL_ILIAS_USER_ID
            . ' FROM ' . IliasEventoUserTblDef::TABLE_NAME
            . ' WHERE ' . IliasEventoUserTblDef::COL_LAST_TIME_DELIVERED . ' < ' . $this->db->quote(date("Y-m-d", strtotime("-1 week")), \ilDBConstants::T_DATETIME);

        $result = $this->db->query($query);

        $not_imported_users = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $not_imported_users[$row[IliasEventoUserTblDef::COL_EVENTO_ID]] = (int) $row[IliasEventoUserTblDef::COL_ILIAS_USER_ID];
        }

        return $not_imported_users;
    }

    public function deleteEventoIliasUserConnectionByEventoId(int $evento_id) : void
    {
        $query = "DELETE FROM " . IliasEventoUserTblDef::TABLE_NAME
              . " WHERE " . IliasEventoUserTblDef::COL_EVENTO_ID . " = " . $this->db->quote($evento_id, \ilDBConstants::T_INTEGER);
        $this->db->manipulate($query);
    }
}

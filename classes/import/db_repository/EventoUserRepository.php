<?php

namespace EventoImport\import\db_repository;

use EventoImport\import\data_models\EventoUser;

class EventoUserRepository
{
    public const TABLE_NAME = 'crevento_mapped_evnto_usrs';

    public const COL_EVENTO_ID = 'evento_id';
    public const COL_ILIAS_USER_ID = 'ilias_user_id';

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
                self::COL_ILIAS_USER_ID => array(\ilDBConstants::T_INTEGER, $ilias_user->getId())
            )
        );
    }

    public function getIliasUserIdByEventoId(int $evento_id) : ?int
    {
        $query = 'SELECT '.self::COL_ILIAS_USER_ID.' FROM ' . self::TABLE_NAME
            . ' WHERE ' . self::COL_EVENTO_ID . '=' . $this->db->quote($evento_id, \ilDBConstants::T_INTEGER);
        $result = $this->db->query($query);

        if($data = $this->db->fetchAssoc($result)) {
            return $data[self::COL_ILIAS_USER_ID];
        }

        return null;
    }

    public function getListOfIliasUserIdsByEventoIds(array $evento_ids) : array
    {
        $query = 'SELECT '.self::COL_ILIAS_USER_ID.' FROM ' . self::TABLE_NAME
            . ' WHERE '  . $this->db->in(self::COL_EVENTO_ID, $evento_ids, false, \ilDBConstants::T_INTEGER);
        $result = $this->db->query($query);

        $user_ids = array();
        while($data = $this->db->fetchAssoc($result)) {
            $user_ids[] = $data[self::COL_ILIAS_USER_ID];
        }

        return $user_ids;
    }
}
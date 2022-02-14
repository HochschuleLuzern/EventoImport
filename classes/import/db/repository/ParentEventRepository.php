<?php declare(strict_types = 1);

namespace EventoImport\import\db\repository;

use EventoImport\import\db\model\IliasEventoParentEvent;

class ParentEventRepository
{
    public const TABLE_NAME = 'crevento_parent_events';

    public const COL_GROUP_UNIQUE_KEY = 'group_unique_key';
    public const COL_GROUP_EVENTO_ID = 'group_evento_id';
    public const COL_REF_ID = 'ref_id';
    public const COL_TITLE = 'title';
    public const COL_ADMIN_ROLE_ID = 'admin_role_id';
    public const COL_STUDENT_ROLE_ID = 'student_role_id';

    private $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    private function buildParentEventObjectFromRow($row) : IliasEventoParentEvent
    {

    }

    public function addNewParentEvent(\EventoImport\import\db\model\IliasEventoParentEvent $parent_event) : void
    {

    }

    public function fetchParentEventByGroupUniqueKey(string $group_unique_key) : ?IliasEventoParentEvent
    {

    }

    public function fetchParentEventForName(string $name) : ?IliasEventoParentEvent
    {

    }

    public function fetchParentEventForRefId(int $ref_id)
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE ' . self::COL_REF_ID . ' = ' . $this->db->quote($ref_id, \ilDBConstants::T_INTEGER);
        $result = $this->db->query($query);

        if ($row = $this->db->fetchAssoc($result)) {
            return $this->buildParentEventObjectFromRow($row);
        }

        return null;
    }

    public function removeParentEventIfItHasNoChildEvent(string $parent_event_key)
    {

    }

    private function removeParentEvent(string $parent_event_key)
    {

    }
}

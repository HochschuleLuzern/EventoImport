<?php declare(strict_types = 1);

namespace EventoImport\import\db\repository;

use EventoImport\import\db\model\IliasEventoEvent;

class IliasEventoEventsRepository
{
    public const TABLE_NAME = 'crevento_evnto_events';

    public const COL_EVENTO_ID = 'evento_id';
    public const COL_PARENT_EVENT_KEY = 'parent_event_key';
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

    private $db;

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
    }

    private function toDateTimeOrNull(?string $db_value)
    {

    }

    private function buildIliasEventoEventFromRow($row)
    {

    }


    public function addNewEventoIliasEvent(IliasEventoEvent $ilias_evento_event)
    {

    }

    public function getEventByEventoId(int $evento_id) : ?IliasEventoEvent
    {

    }

    private function dateTimeToDBFormatOrNull(?\DateTime $date_time) : ?string
    {
    }

    public function updateIliasEventoEvent(IliasEventoEvent $updated_obj)
    {

    }

    public function removeEventoEvent(IliasEventoEvent $ilias_evento_event)
    {

    }
}

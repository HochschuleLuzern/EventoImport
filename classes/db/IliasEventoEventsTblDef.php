<?php

namespace EventoImport\db;

class IliasEventoEventsTblDef
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
}

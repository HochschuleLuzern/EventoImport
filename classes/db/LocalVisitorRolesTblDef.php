<?php declare(strict_types=1);

namespace EventoImport\db;

class LocalVisitorRolesTblDef
{
    public const TABLE_NAME = 'crevento_visitor_roles';

    public const COL_LOCAL_ROLE_ID = 'local_role_obj_id';
    public const COL_LOCATION_REF_ID = 'location_ref_id';
    public const COL_DEPARTMENT_API_NAME = 'department_api_name';
    public const COL_KIND_LOCATION_NAME = 'kind_location_name';
    public const COL_DEPARTMENT_LOCATION_NAME = 'department_location_name';
}
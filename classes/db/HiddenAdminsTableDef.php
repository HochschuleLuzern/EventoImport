<?php declare(strict_types=1);

namespace EventoImport\db;

class HiddenAdminsTableDef
{
    public const TABLE_NAME = 'crevento_hidden_admins';

    public const COL_OBJECT_REF_ID = 'ref_id';
    public const COL_HIDDEN_ADMIN_ROLE_ID = 'hidden_admin_role_id';
}
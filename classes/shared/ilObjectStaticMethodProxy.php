<?php

namespace CourseWizard\shared;

class ilObjectStaticMethodProxy
{
    public function lookupObjectTitleByObjId(int $obj_id)
    {
        return \ilObject::_lookupTitle($obj_id);
    }

    public function lookupObjectIdByRefId(int $ref_id)
    {
        return \ilObject::_lookupObjectId($ref_id);
    }

    public function lookupObjectTypeByObjId(int $obj_id)
    {
        return \ilObject::_lookupType($obj_id, false);
    }

    public function lookupObjectTypeByRefId(int $ref_id)
    {
        return \ilObject::_lookupType($ref_id, true);
    }
}

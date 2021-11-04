<?php

namespace EventoImport\import\settings;

class DefaultEventSettings
{
    private $default_object_owner_id;
    private $default_sort_mode;

    public function __construct(\ilSetting $setting)
    {
        $this->default_object_owner_id = 6;
        $this->default_sort_mode = \ilContainer::SORT_TITLE;
    }

    public function getDefaultObjectOwnerId()
    {
        return $this->default_object_owner_id;
    }

    public function getDefaultSortMode()
    {
        return $this->default_sort_mode;
    }
}
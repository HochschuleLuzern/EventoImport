<?php declare(strict_types = 1);

namespace EventoImport\config;

class DefaultEventSettings
{
    private int $default_object_owner_id;
    private int $default_sort_mode;
    private int $default_sort_direction;
    private bool $default_online_status;

    public function __construct(\ilSetting $settings)
    {
        $this->default_object_owner_id = (int) $settings->get(CronConfigForm::CONF_EVENT_OWNER_ID, "6");
        $this->default_sort_mode = \ilContainer::SORT_TITLE;
        $this->default_sort_direction = \ilContainer::SORT_DIRECTION_ASC;
        $this->default_online_status = true;
    }

    public function getDefaultObjectOwnerId() : int
    {
        return $this->default_object_owner_id;
    }

    public function getDefaultSortMode() : int
    {
        return $this->default_sort_mode;
    }

    public function getDefaultSortDirection() : int
    {
        return $this->default_sort_direction;
    }

    public function isDefaultOnline() : bool
    {
        return $this->default_online_status;
    }
}

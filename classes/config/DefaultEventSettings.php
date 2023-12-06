<?php declare(strict_types = 1);

namespace EventoImport\config;

class DefaultEventSettings
{
    private const CONF_EVENT_OWNER_ID = 'crevento_object_owner_id';

    private \ilSetting $settings;

    private int $default_object_owner_id;
    private int $default_sort_mode;
    private int $default_sort_direction;
    private bool $default_online_status;
    private int $default_sort_new_items_order;
    private int $default_sort_new_items_position;

    public function __construct(\ilSetting $settings)
    {
        $this->settings = $settings;

        $this->default_object_owner_id = (int) $this->settings->get(self::CONF_EVENT_OWNER_ID, "6");
        $this->default_sort_mode = \ilContainer::SORT_MANUAL;
        $this->default_sort_new_items_order = \ilContainer::SORT_NEW_ITEMS_ORDER_CREATION;
        $this->default_sort_new_items_position = \ilContainer::SORT_NEW_ITEMS_POSITION_BOTTOM;
        $this->default_sort_direction = \ilContainer::SORT_DIRECTION_ASC;
        $this->default_online_status = true;
    }

    public function getDefaultObjectOwnerId(): int
    {
        return $this->default_object_owner_id;
    }

    public function setDefaultObjectOwnerId(int $default_object_owner_id): void
    {
        $this->default_object_owner_id = $default_object_owner_id;
    }

    public function getDefaultSortMode(): int
    {
        return $this->default_sort_mode;
    }

    public function getDefaultSortNewItemsOrder(): int
    {
        return $this->default_sort_new_items_order;
    }

    public function getDefaultSortNewItemsPosition(): int
    {
        return $this->default_sort_new_items_position;
    }

    public function getDefaultSortDirection(): int
    {
        return $this->default_sort_direction;
    }

    public function isDefaultOnline(): bool
    {
        return $this->default_online_status;
    }

    public function saveCurrentConfigurationToSettings(): void
    {
        $this->settings->set(self::CONF_EVENT_OWNER_ID, (string) $this->getDefaultObjectOwnerId());
    }
}

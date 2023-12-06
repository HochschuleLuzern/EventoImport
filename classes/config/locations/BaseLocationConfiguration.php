<?php declare(strict_types=1);

namespace EventoImport\config\locations;

use EventoImport\config\CronConfigForm;

class BaseLocationConfiguration
{
    public const CONF_LOCATIONS = 'crevento_location_settings';
    private const CONF_KEY_DEPARTMENTS = 'departments';
    private const CONF_KEY_KINDS = 'kinds';

    private array $configured_departments = [];
    private array $configured_kinds = [];

    private \ilSetting $settings;

    public function __construct(\ilSetting $settings)
    {
        $this->settings = $settings;

        $json = $this->settings->get(self::CONF_LOCATIONS, null);

        if (is_null($json)) {
            $this->configured_departments = [];
            $this->configured_kinds = [];
            return;
        }

        $location_settings = json_decode($json, true);
        if (!is_null($location_settings)) {
            if (isset($location_settings[self::CONF_KEY_DEPARTMENTS]) && is_array($location_settings[self::CONF_KEY_DEPARTMENTS])) {
                $this->setDepartmentLocationList($location_settings[self::CONF_KEY_DEPARTMENTS]);
            }

            if (isset($location_settings[self::CONF_KEY_KINDS]) && is_array($location_settings[self::CONF_KEY_KINDS])) {
                $this->setKindLocationList($location_settings[self::CONF_KEY_KINDS]);
            }
        }
    }

    public function getDepartmentLocationList() : array
    {
        return $this->configured_departments;
    }

    public function setDepartmentLocationList(array $department_locations)
    {
        $this->configured_departments = [];
        foreach ($department_locations as $location_name) {
            if (is_string($location_name) ) {
                $location_name = trim($location_name);
                if ($location_name != '') {
                    $this->configured_departments[] = trim($location_name);
                }
            }
        }
    }

    public function getKindLocationList() : array
    {
        return $this->configured_kinds;
    }

    public function setKindLocationList(array $kind_locations)
    {
        $this->configured_kinds = [];
        foreach ($kind_locations as $location_name) {
            if (is_string($location_name) ) {
                $location_name = trim($location_name);
                if ($location_name != '') {
                    $this->configured_kinds[] = trim($location_name);
                }
            }
        }
    }

    public function saveCurrentConfigurationToSettings()
    {
        $this->settings->set(
            self::CONF_LOCATIONS,
            json_encode(
                [
                    self::CONF_KEY_DEPARTMENTS => $this->configured_departments,
                    self::CONF_KEY_KINDS => $this->configured_kinds
                ]
            )
        );
    }
}
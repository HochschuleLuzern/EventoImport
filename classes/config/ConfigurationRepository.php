<?php declare(strict_type=1);

namespace EventoImport\config;

use EventoImport\config\EventLocationsRepository;
use EventoImport\config\EventLocations;

class ConfigurationRepository
{
    private \ilSetting $settings;
    private \ilDBInterface $db;

    public function __construct(\ilSetting $settings, \ilDBInterface $db)
    {
        $this->settings = $settings;
        $this->db = $db;
    }

    public function getApiConfiguration() : ImporterApiSettings
    {
        return new ImporterApiSettings($this->settings);
    }

    public function getDefaultEventConfiguration() : DefaultEventSettings
    {
        return new DefaultEventSettings($this->settings);
    }

    public function getDefaultUserConfiguration() : DefaultUserSettings
    {
        return new DefaultUserSettings($this->settings);
    }

    public function getConfiguredEventLocations() : EventLocations
    {
        return new EventLocations(
            new EventLocationsRepository($this->db)
        );
    }
}

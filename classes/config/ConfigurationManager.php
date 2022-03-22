<?php declare(strict_type=1);

namespace EventoImport\config;

use EventoImport\config\locations\EventLocationsRepository;
use EventoImport\config\locations\RepositoryLocationSeeker;
use EventoImport\config\locations\EventLocationCategoryBuilder;

class ConfigurationManager
{
    private CronConfigForm $cron_config_form;
    private \ilSetting $settings;
    private \ilDBInterface $db;

    private ImporterApiSettings $api_settings;
    private DefaultEventSettings $default_event_settings;
    private DefaultUserSettings $default_user_settings;
    private EventLocations $event_locations;

    public function __construct(CronConfigForm $cron_config_form, \ilSetting $settings, \ilDBInterface $db, \ilTree $tree)
    {
        $this->cron_config_form = $cron_config_form;
        $this->settings = $settings;
        $this->db = $db;

        $this->api_settings = new ImporterApiSettings($this->settings);
        $this->default_event_settings = new DefaultEventSettings($this->settings);
        $this->default_user_settings = new DefaultUserSettings($this->settings);
        $this->event_locations = new EventLocations(
            new EventLocationsRepository($this->db),
            new RepositoryLocationSeeker($tree, 1),
            new EventLocationCategoryBuilder()
        );
    }

    public function getApiConfiguration() : ImporterApiSettings
    {
        return $this->api_settings;
    }

    public function getDefaultEventConfiguration() : DefaultEventSettings
    {
        return $this->default_event_settings;
    }

    public function getDefaultUserConfiguration() : DefaultUserSettings
    {
        return $this->default_user_settings;
    }

    public function getConfiguredEventLocations() : EventLocations
    {
        return $this->event_locations;
    }

    public function form() : CronConfigForm
    {
        return $this->cron_config_form;
    }
}

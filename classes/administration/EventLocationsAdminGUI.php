<?php declare(strict_types = 1);

namespace EventoImport\administration;

use EventoImport\import\manager\db\EventLocationsRepository;
use ILIAS\DI\UIServices;

class EventLocationsAdminGUI
{
    private $parent_gui;
    private \ilSetting $settings;
    private EventLocationsRepository $location_repo;
    private \ilCtrl $ctrl;
    private UIServices $ui_services;

    public function __construct($parent_gui, \ilSetting $settings, EventLocationsRepository $location_repo, \ilCtrl $ctrl, UIServices $ui_services)
    {
        $this->parent_gui = $parent_gui;
        $this->settings = $settings;
        $this->location_repo = $location_repo;
        $this->ctrl = $ctrl;
        $this->ui_services = $ui_services;
    }

    public function getEventLocationsPanelHTML() : string
    {
        // Reload tree
        $ui_factory = $this->ui_services->factory();
        $ui_components = [];

        // Show Location settings from the cron-job
        $json_settings = $this->settings->get('crevento_location_settings');
        $locations_settings = json_decode($json_settings, true);
        if (!is_array($locations_settings)) {
            $locations_settings = [];
        }

        $locations_ui_comp = [];
        foreach ($locations_settings as $location_title => $location_values) {
            $locations_ui_comp[] = $ui_factory->legacy(htmlspecialchars($location_title));
            $locations_ui_comp[] = $ui_factory->listing()->unordered($location_values);
        }
        $ui_components[] = $ui_factory->panel()->sub('Location Settings', $locations_ui_comp);

        // Show action button to reload repository locations
        $link = $this->ctrl->getLinkTarget($this->parent_gui, 'reload_repo_locations');
        $reload_btn = $ui_factory->button()->standard("Reload Repository Locations", $link);
        $ui_components[] = $ui_factory->panel()->sub('Reload Locations', $reload_btn);

        // Show table of current registered locations
        $locations = $this->location_repo->getAllLocations();
        $locations_table = $ui_factory->legacy($this->locationsToHTMLTable($locations));
        $ui_components[] = $ui_factory->panel()->sub('Current registered lcoations', $locations_table);

        $main_panel = $ui_factory->panel()->standard('Event Locations', $ui_components);
        return $this->ui_services->renderer()->render($main_panel);
    }

    private function locationsToHTMLTable(array $locations) : string
    {
        $saved_locations_string = "<table style='width: 100%'>";
        if (count($locations) > 0) {
            $saved_locations_string .= "<tr>";
            foreach ($locations[0] as $key => $value) {
                $saved_locations_string .= "<th><b>" . htmlspecialchars($key) . "</b></th>";
            }
            $saved_locations_string .= "<tr>";
        }
        foreach ($locations as $location) {
            $saved_locations_string .= "<tr>";
            foreach ($location as $key => $value) {
                $saved_locations_string .= "<td>" . htmlspecialchars($value) . "</td>";
            }
            $saved_locations_string .= '</tr>';
        }

        $saved_locations_string .= "</table>";
        return $saved_locations_string;
    }
}

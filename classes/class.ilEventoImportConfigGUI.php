<?php

/**
 * Class ilEventoImportConfigGUI
 */
class ilEventoImportConfigGUI extends ilPluginConfigGUI
{
    private const ALLOWED_PATH_SCHEMA_ELEMENTS = ['department', 'kind', 'year'];

    private $settings;
    private $tree;
    private $tpl;
    private $ctrl;
    private $hard_coded_department_mapping;

    public function __construct()
    {
        global $DIC;
        $this->settings = new ilSetting("crevento");
        $this->tree = $DIC->repositoryTree();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();

        $this->hard_coded_department_mapping = [
            "Hochschule Luzern" => "HSLU",
            "Design & Kunst" => "DK",
            "Informatik" => "I",
            "Musik" => "M",
            "Soziale Arbeit" => "SA",
            "Technik & Architektur" => "TA",
            "Wirtschaft" => "W"
        ];
    }

    private function locationsToHTMLTable(array $locations) : string
    {
        $saved_locations_string = "<table style='width: 100%'>";
        if (count($locations) > 0) {
            $saved_locations_string .= "<tr>";
            foreach ($locations[0] as $key => $value) {
                $saved_locations_string .= "<th><b>$key</b></th>";
            }
            $saved_locations_string .= "<tr>";
        }
        foreach ($locations as $location) {

            $saved_locations_string .= "<tr>";
            foreach ($location as $key => $value) {
                $saved_locations_string .= "<td>$value</td>";
            }
            $saved_locations_string .= '</tr>';
        }

        $saved_locations_string .= "</table>";
        return $saved_locations_string;
    }

    private function fetchRefIdForObjTitle(int $root_ref_id, string $searched_obj_title) : ?int
    {
        foreach ($this->tree->getChildsByType($root_ref_id, 'cat') as $child_node) {
            $child_ref = $child_node['child'];
            $obj_id = ilObject::_lookupObjectId($child_ref);
            if (ilObject::_lookupTitle($obj_id) == $searched_obj_title) {
                return $child_ref;
            }
        }

        return null;
    }

    private function getMappedDepartmentName(string $ilias_department_cat) : string
    {
        if(isset($this->hard_coded_department_mapping[$ilias_department_cat])) {
            return $this->hard_coded_department_mapping[$ilias_department_cat];
        } else {
            return $ilias_department_cat;
        }
    }

    private function reloadRepositoryLocations()
    {
        global $DIC;

        $json_settings = $this->settings->get('crevento_location_settings');
        $locations_settings = json_decode($json_settings, true);

        $location_repository = new \EventoImport\import\db\repository\EventLocationsRepository($DIC->database());
        $location_repository->purgeLocationTable();
        $repository_root_ref_id = 1;
        foreach ($locations_settings['departments'] as $department) {
            $department_ref_id = $this->fetchRefIdForObjTitle($repository_root_ref_id, $department);
            if ($department_ref_id) {
                foreach ($locations_settings['kinds'] as $kind) {
                    $kind_ref_id = $this->fetchRefIdForObjTitle($department_ref_id, $kind);
                    if ($kind_ref_id) {
                        foreach ($locations_settings['years'] as $year) {
                            $destination_ref_id = $this->fetchRefIdForObjTitle($kind_ref_id, $year);
                            if ($destination_ref_id) {
                                $location_repository->addNewLocation($this->getMappedDepartmentName($department), $kind, $year, $destination_ref_id);
                            }
                        }
                    }
                }
            }
        }

        return $location_repository->fetchAllLocations();
    }

    public function performCommand($cmd)
    {
        switch ($cmd) {
            case 'configure':
                global $DIC;
                $link = $DIC->ctrl()->getLinkTarget($this, 'reload_repo_locations');
                $link_btn = $DIC->ui()->factory()->link()->standard('Reload Repository Locations', $link);
                $this->tpl->setContent($DIC->ui()->renderer()->render($link_btn));
                break;

            case 'reload_repo_locations':
                $locations = $this->reloadRepositoryLocations();
                $saved_locations_string = $this->locationsToHTMLTable($locations);


                ilUtil::sendSuccess('Repository tree reloaded successfully. Following locations are currently saved:<br>'.$saved_locations_string, true);
                $this->ctrl->redirect($this, 'configure');
                break;
        }
    }
}

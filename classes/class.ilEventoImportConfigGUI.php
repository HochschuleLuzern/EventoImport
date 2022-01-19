<?php

use EventoImport\administration\EventoImportApiTesterGUI;

/**
 * Class ilEventoImportConfigGUI
 *
 * This class currently does not contain any configuration in it
 */
class ilEventoImportConfigGUI extends ilPluginConfigGUI
{
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

    public function performCommand($cmd)
    {
        switch ($cmd) {
            case 'configure':
                $content = $this->getFunctionalityBoardAsString();
                $this->tpl->setContent($content);
                break;

            case 'reload_repo_locations':
                $locations = $this->reloadRepositoryLocations();
                $saved_locations_string = $this->locationsToHTMLTable($locations);

                $message = $this->buildMessageForNextPage('Repository tree reloaded successfully. Following locations are currently saved:', $saved_locations_string);
                ilUtil::sendSuccess($message, true);
                $this->ctrl->redirect($this, 'configure');
                break;

            case 'fetch_data_set_users':
            case 'fetch_data_set_events':
                try {
                    $api_tester_gui = new EventoImportApiTesterGUI($this);
                    $output = $api_tester_gui->fetchDataSetFromFormInput($cmd);

                    if (strlen($output) > 0) {
                        ilUtil::sendSuccess($output, true);
                    }
                } catch (Exception $e) {
                    ilUtil::sendFailure('Exception: ' . print_r([$e->getMessage(), $e->getTraceAsString()], true));
                }

                $this->ctrl->redirect($this, 'configure');
                break;

            case 'fetch_record_user':
            case 'fetch_record_event':
            case 'fetch_user_photo':
            case 'fetch_ilias_admins_for_event':

                try {
                    $api_tester_gui = new EventoImportApiTesterGUI($this);
                    $output = $api_tester_gui->fetchDataRecordFromFormInput($cmd);

                    if (strlen($output) > 0) {
                        ilUtil::sendSuccess($output, true);
                    }
                } catch (Exception $e) {
                    ilUtil::sendFailure('Exception: ' . print_r([$e->getMessage(), $e->getTraceAsString()], true));
                }

                $this->ctrl->redirect($this, 'configure');
                break;

            case 'fetch_all_ilias_admins':
                try {
                    $api_tester_gui = new EventoImportApiTesterGUI($this);
                    $output = $api_tester_gui->fetchParameterlessDataset($cmd);

                    if (strlen($output) > 0) {
                        ilUtil::sendSuccess($output, true);
                    }
                } catch (Exception $e) {
                    ilUtil::sendFailure('Exception: ' . print_r([$e->getMessage(), $e->getTraceAsString()], true));
                }

                $this->ctrl->redirect($this, 'configure');
                break;

            default:
                ilUtil::sendFailure('Command not found', true);
                $this->ctrl->redirect($this, 'configure');
                break;
        }
    }


    private function getFunctionalityBoardAsString()
    {
        global $DIC;
        $ui_factory = $DIC->ui()->factory();

        // Reload tree
        $ui_components = [];
        $link = $this->ctrl->getLinkTarget($this, 'reload_repo_locations');
        $ui_components[] = $ui_factory->button()->standard("Reload Repository Locations", $link);

        $api_tester_gui = new EventoImportApiTesterGUI($this);

        return $DIC->ui()->renderer()->render($ui_components) . $api_tester_gui->getApiTesterFormAsString();
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
        if (isset($this->hard_coded_department_mapping[$ilias_department_cat])) {
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
}

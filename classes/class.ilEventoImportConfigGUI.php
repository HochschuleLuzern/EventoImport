<?php declare(strict_types = 1);

use EventoImport\administration\EventoImportApiTesterGUI;
use EventoImport\administration\EventLocationsBuilder;
use EventoImport\administration\EventLocationsAdminGUI;
use ILIAS\DI\UIServices;
use EventoImport\administration\EventoImportApiTester;

/**
 * Class ilEventoImportConfigGUI
 *
 * This class currently does not contain any configuration in it
 */
class ilEventoImportConfigGUI extends ilPluginConfigGUI
{
    private ilSetting $settings;
    private ilTree $tree;
    private ilGlobalPageTemplate $tpl;
    private ilCtrl $ctrl;
    private UIServices $ui_services;
    private ilDBInterface $db;

    public function __construct()
    {
        global $DIC;

        $this->settings = new ilSetting("crevento");
        $this->tree = $DIC->repositoryTree();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->ui_services = $DIC->ui();
        $this->db = $DIC->database();
    }

    public function performCommand($cmd)
    {
        switch ($cmd) {
            case 'configure':
                $api_tester_gui = new EventoImportApiTesterGUI(
                    $this,
                    new EventoImportApiTester($this->settings, $this->db),
                    $this->settings,
                    $this->ui_services,
                    $this->ctrl,
                    $this->tree
                );
                $api_tester_html = $api_tester_gui->getApiTesterFormAsString();

                $locations_gui = new EventLocationsAdminGUI($this, $this->settings, new \EventoImport\import\manager\db\EventLocationsRepository($this->db), $this->ctrl, $this->ui_services);
                $locations_html = $locations_gui->getEventLocationsPanelHTML();

                $this->tpl->setContent($api_tester_html . $locations_html);
                break;

            case 'reload_repo_locations':
                $json_settings = $this->settings->get('crevento_location_settings');
                $locations_settings = json_decode($json_settings, true);

                $locations_builder = new EventLocationsBuilder(new \EventoImport\import\manager\db\EventLocationsRepository($this->db), $this->tree);
                $diff = $locations_builder->rebuildRepositoryLocationsTable($locations_settings);

                \ilUtil::sendSuccess("Event Locats reloaded successfully. Added $diff new locations", true);
                $this->ctrl->redirect($this, 'configure');
                break;

            case 'fetch_data_set_users':
            case 'fetch_data_set_events':
                try {
                    $api_tester_gui = new EventoImportApiTesterGUI(
                        $this,
                        new EventoImportApiTester($this->settings, $this->db),
                        $this->settings,
                        $this->ui_services,
                        $this->ctrl,
                        $this->tree
                    );
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
                    $api_tester_gui = new EventoImportApiTesterGUI(
                        $this,
                        new EventoImportApiTester($this->settings, $this->db),
                        $this->settings,
                        $this->ui_services,
                        $this->ctrl,
                        $this->tree
                    );
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
                    $api_tester_gui = new EventoImportApiTesterGUI(
                        $this,
                        new EventoImportApiTester($this->settings, $this->db),
                        $this->settings,
                        $this->ui_services,
                        $this->ctrl,
                        $this->tree
                    );
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
}

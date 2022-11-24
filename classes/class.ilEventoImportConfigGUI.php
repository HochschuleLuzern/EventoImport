<?php declare(strict_types = 1);

use EventoImport\administration\EventoImportApiTesterGUI;
use EventoImport\administration\EventLocationsBuilder;
use EventoImport\administration\EventLocationsAdminGUI;
use ILIAS\DI\UIServices;
use EventoImport\administration\EventoImportApiTester;
use EventoImport\administration\AdminScriptPageGUI;
use EventoImport\config\locations\EventLocationsRepository;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ilEventoImportConfigGUI
 *
 * This class currently does not contain any configuration in it
 */
class ilEventoImportConfigGUI extends ilPluginConfigGUI
{
    const TAB_MAIN = 'main';


    private ilSetting $settings;
    private ilTree $tree;
    private ilGlobalPageTemplate $tpl;
    private ilCtrl $ctrl;
    private UIServices $ui_services;
    private ilDBInterface $db;
    private ilTabsGUI $tabs;
    private ServerRequestInterface $request;
    private ILIAS\Refinery\Factory $refinery;

    public function __construct()
    {
        global $DIC;

        $this->settings = new ilSetting("crevento");
        $this->tree = $DIC->repositoryTree();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->ui_services = $DIC->ui();
        $this->db = $DIC->database();
        $this->tabs = $DIC->tabs();
        $this->request = $DIC->http()->request();
        $this->refinery = $DIC->refinery();
    }

    private function addPageTabs()
    {
        $query_params = $this->request->getQueryParams();

        $link = $this->ctrl->getLinkTargetByClass(ilEventoImportConfigGUI::class, ilObjComponentSettingsGUI::CMD_CONFIGURE);
        $this->tabs->addTab(self::TAB_MAIN, $this->plugin_object->txt('confpage_tab_main'), $link);

        if (isset($query_params['ref_id'])) {
            $this->ctrl->setParameter($this, 'ref_id', $query_params['ref_id']);
        }
        $link = $this->ctrl->getLinkTargetByClass(AdminScriptPageGUI::CTRL_UI_ROUTE, AdminScriptPageGUI::SHOW_SCRIPT);
        $this->tabs->addTab(AdminScriptPageGUI::TAB_ADMIN_SCRIPTS, $this->plugin_object->txt('confpage_tab_admin_scripts'), $link);

        $this->tabs->activateTab(self::TAB_MAIN);
    }

    public function performCommand($cmd)
    {
        $this->addPageTabs();

        switch ($cmd) {
            case ilObjComponentSettingsGUI::CMD_CONFIGURE:
                $api_tester_gui = new EventoImportApiTesterGUI(
                    $this,
                    new EventoImportApiTester($this->settings, $this->db),
                    $this->settings,
                    $this->ui_services,
                    $this->ctrl,
                    $this->tree,
                    $this->request,
                    $this->refinery
                );
                $api_tester_html = $api_tester_gui->getApiTesterFormAsString();

                $locations_gui = new EventLocationsAdminGUI($this, $this->settings, new EventLocationsRepository($this->db), $this->ctrl, $this->ui_services);

                $locations_html = $locations_gui->getEventLocationsPanelHTML();

                $this->tpl->setContent($api_tester_html . $locations_html);
                break;

            case 'by_id':
            case 'data_set':
            case 'parameterless':
                try {
                    $api_tester_gui = new EventoImportApiTesterGUI(
                        $this,
                        new EventoImportApiTester($this->settings, $this->db),
                        $this->settings,
                        $this->ui_services,
                        $this->ctrl,
                        $this->tree,
                        $this->request,
                        $this->refinery
                    );

                    $output = $api_tester_gui->getApiDataAsString($cmd);

                    if (strlen($output) > 0) {
                        ilUtil::sendSuccess($output, true);
                    }
                } catch (Exception $e) {
                    ilUtil::sendFailure('Exception: ' . print_r([$e->getMessage(), $e->getTraceAsString()], true));
                }

                $this->ctrl->redirect($this, 'configure');
                break;

            case 'reload_repo_locations':
                $json_settings = $this->settings->get('crevento_location_settings');
                $locations_settings = json_decode($json_settings, true);

                $locations_builder = new EventLocationsBuilder(new EventLocationsRepository($this->db), $this->tree);
                $diff = $locations_builder->rebuildRepositoryLocationsTable($locations_settings);

                \ilUtil::sendSuccess("Event Locats reloaded successfully. Added $diff new locations", true);
                $this->ctrl->redirect($this, 'configure');
                break;

            case 'show_missing_repo_locations':
                $json_settings = $this->settings->get('crevento_location_settings');
                $locations_settings = json_decode($json_settings, true);

                $locations_builder = new EventLocationsBuilder(new EventLocationsRepository($this->db), $this->tree);
                $location_lists = $locations_builder->getListOfMissingKindCategories($locations_settings);

                $f = $this->ui_services->factory();

                if (count($location_lists) > 0) {
                    $link_create = $this->ctrl->getLinkTarget($this, 'create_repo_locations');
                    $link_cancel = $this->ctrl->getLinkTarget($this, 'configure');

                    $ui_comps = $f->panel()->standard(
                        "Missing Locations",
                        [
                            $f->listing()->unordered($location_lists),
                            $f->button()->standard('Create missing locations', $link_create),
                            $f->button()->standard('Cancel', $link_cancel)
                        ]
                    );
                } else {
                    $link_cancel = $this->ctrl->getLinkTarget($this, 'configure');

                    $ui_comps = $f->panel()->standard(
                        "Missing Locations",
                        [
                            $f->legacy("All configured location combinations exist in repository tree<br>"),
                            $f->button()->standard('Go back to config page', $link_cancel)
                        ]
                    );
                }

                $this->tpl->setContent($this->ui_services->renderer()->render($ui_comps));
                break;

            case 'create_repo_locations':
                $json_settings = $this->settings->get('crevento_location_settings');
                $locations_settings = json_decode($json_settings, true);

                $locations_builder = new EventLocationsBuilder(new EventLocationsRepository($this->db), $this->tree);
                $location_lists = $locations_builder->buildCategoryObjectsForConfiguredKinds($locations_settings);

                $ui_comps = [];
                foreach ($location_lists as $title => $list) {
                    $f = $this->ui_services->factory();
                    $ui_comps[] = $f->legacy(strip_tags($title));
                    $ui_comps[] = $f->listing()->unordered($list);
                }

                $locations_builder->rebuildRepositoryLocationsTable($locations_settings);

                \ilUtil::sendSuccess($this->ui_services->renderer()->render($ui_comps), true);
                $this->ctrl->redirect($this, 'configure');

                break;

            default:
                ilUtil::sendFailure('Command not found', true);
                $this->ctrl->redirect($this, 'configure');
                break;
        }
    }
}

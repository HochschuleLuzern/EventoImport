<?php declare(strict_types=1);

namespace EventoImport\administration;

use ILIAS\DI\UIServices;
use EventoImport\administration\scripts\AdminScriptInterface;
use Psr\Http\Message\ServerRequestInterface;
use EventoImport\administration\scripts\LookupEventByEventoTitle;
use EventoImport\administration\scripts\ReAddRemovedEventParticipants;
use EventoImport\import\data_management\ilias_core\MembershipablesEventInTreeSeeker;
use EventoImport\administration\scripts\SwitchIliasObjectForEventoEvent;

/**
 * Class AdminScriptPageGUI
 * @author
 * @ilCtrl_isCalledBy    EventoImport\administration\AdminScriptPageGUI: ilUIPluginRouterGUI
 */
class AdminScriptPageGUI
{
    const CTRL_UI_ROUTE = [\ilUIPluginRouterGUI::class, \ilEventoImportUIRoutingGUI::class];
    const TAB_ADMIN_SCRIPTS = 'admin_scripts';
    const SHOW_SCRIPT = 'show_scripts';

    private array $scripts;

    private \ilEventoImportPlugin $plugin_object;
    private \ilGlobalPageTemplate $tpl;
    private UIServices $ui_services;
    private \ilCtrl $ctrl;
    private \ilTabsGUI $tabs;
    private \ilLocatorGUI $locator;
    private \ilDBInterface $db;
    private ServerRequestInterface $request;
    private \ilTree $tree;

    public function __construct(\ilEventoImportPlugin $plugin_object, \ilGlobalPageTemplate $tpl, UIServices $ui_services, \ilCtrl $ctrl, \ilTabsGUI $tabs, \ilLocatorGUI $locator, \ilDBInterface $db, ServerRequestInterface $request, \ilTree $tree)
    {
        $this->plugin_object = $plugin_object;
        $this->tpl = $tpl;
        $this->ui_services = $ui_services;
        $this->ctrl = $ctrl;
        $this->tabs = $tabs;
        $this->locator = $locator;
        $this->db = $db;
        $this->request = $request;
        $this->tree = $tree;

        $this->scripts = [
            new LookupEventByEventoTitle($this->db, $this->ctrl),
            new ReAddRemovedEventParticipants($this->db, $this->ctrl, $this->request, new MembershipablesEventInTreeSeeker($this->tree)),
            new SwitchIliasObjectForEventoEvent($this->db, $this->ctrl, $this->request, $this->tree)
        ];
    }

    private function initHeaderGUI()
    {
        global $DIC, $ilLocator, $lng, $ilDB;

        /* Add breadcrumbs */
        //$ilLocator->addRepositoryItems($this->ref_id);
        $this->tpl->setLocator();

        $this->tpl->setTitle("Plugin: EventoImport");
        if (isset($query_params['ref_id'])) {
            $ref_id = $query_params['ref_id'];
        } else {
            $ref_id = 31;
        }

        $this->tpl->setTitleIcon(\ilObject::_getIcon("", "big", \ilObject::_lookupType($ref_id, true)));

        $this->ctrl->setParameterByClass(\ilObjComponentSettingsGUI::class, \ilObjComponentSettingsGUI::P_CTYPE, $this->plugin_object->getComponentType());
        $this->ctrl->setParameterByClass(\ilObjComponentSettingsGUI::class, \ilObjComponentSettingsGUI::P_CNAME, $this->plugin_object->getComponentName());
        $this->ctrl->setParameterByClass(\ilObjComponentSettingsGUI::class, \ilObjComponentSettingsGUI::P_SLOT_ID, $this->plugin_object->getSlotId());
        $this->ctrl->setParameterByClass(\ilObjComponentSettingsGUI::class, \ilObjComponentSettingsGUI::P_PLUGIN_NAME, $this->plugin_object->getPluginName());
        $this->ctrl->setParameterByClass(\ilObjComponentSettingsGUI::class, 'ref_id', 31);

        $this->tabs->setBackTarget('Plugins', $this->ctrl->getLinkTargetByClass([\ilAdministrationGUI::class, \ilObjComponentSettingsGUI::class], 'listPlugins'));
        $link = $this->ctrl->getLinkTargetByClass([\ilAdministrationGUI::class, \ilObjComponentSettingsGUI::class, \ilEventoImportConfigGUI::class], \ilObjComponentSettingsGUI::CMD_CONFIGURE);

        $this->tabs->addTab(\ilEventoImportConfigGUI::TAB_MAIN, $this->plugin_object->txt('confpage_tab_main'), $link);

        $link = $this->ctrl->getLinkTargetByClass(AdminScriptPageGUI::CTRL_UI_ROUTE, AdminScriptPageGUI::SHOW_SCRIPT);
        $this->tabs->addTab(AdminScriptPageGUI::TAB_ADMIN_SCRIPTS, $this->plugin_object->txt('confpage_tab_admin_scripts'), $link);

        $this->tabs->activateTab(AdminScriptPageGUI::TAB_ADMIN_SCRIPTS);
    }

    public function executeCommandAndRenderGUI()
    {
        $this->initHeaderGUI();

        $params = $this->request->getQueryParams();

        $executed_script = isset($params['script']) ? $params['script'] : null;

        $f = $this->ui_services->factory();
        $r = $this->ui_services->renderer();
        $comps = [];
        /** @var AdminScriptInterface $script */
        foreach($this->scripts as $script) {
            $comps[] = $f->panel()->standard(
                $script->getTitle(),
                $f->legacy($script->getParameterFormUI()->getHTML())
            );

            if(!is_null($executed_script) && $script->getScriptId() == $executed_script) {
                try {
                    $modal = $script->getResultModalFromRequest(
                        $this->ctrl->getCmd(),
                        $f
                    );
                    $comps[] = $modal->withOnLoad($modal->getShowSignal());
                } catch (\InvalidArgumentException $e) {
                    \ilUtil::sendFailure('Script failed becauses of invalid Argument(s) with following message: ' . $e->getMessage());
                }
            }
        }

        $this->tpl->setContent($r->render($comps));
        $this->tpl->printToStdout();
    }
}
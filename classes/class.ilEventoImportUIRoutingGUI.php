<?php declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ilEventoImportUIRoutingGUI
 * @author
 * @ilCtrl_isCalledBy    ilEventoImportUIRoutingGUI: ilUIPluginRouterGUI
 */
class ilEventoImportUIRoutingGUI
{
    private const GUI_ADMIN_SCRIPTS = 'admin_scripts';

    private ilGlobalPageTemplate $tpl;
    private ilCtrl $ctrl;
    private ServerRequestInterface $request;

    private array $gui_classes;
    private \ILIAS\DI\UIServices $ui_services;
    private ilDBInterface $db;
    private ilTabsGUI $tabs;
    private ilTree $tree;
    private \ILIAS\DI\RBACServices $rbac_services;
    private ilObjUser $user;
    private $error;

    private ilEventoImportPlugin $plugin;

    public function __construct()
    {
        global $DIC;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->request = $DIC->http()->request();
        $this->ui_services = $DIC->ui();
        $this->db = $DIC->database();
        $this->tabs = $DIC->tabs();
        $this->tree = $DIC->repositoryTree();
        $this->rbac_services = $DIC->rbac();
        $this->user = $DIC->user();
        $this->error = $DIC["ilErr"];

        $this->plugin = new \ilEventoImportPlugin($this->db, $DIC['component.repository']);

        $this->gui_classes = [];
        $this->gui_classes[self::GUI_ADMIN_SCRIPTS] = function () {
            return new \EventoImport\administration\AdminScriptPageGUI(
                $this->plugin,
                $this->tpl,
                $this->ui_services,
                $this->ctrl,
                $this->tabs,
                $this->db,
                $this->request,
                $this->tree,
                $this->rbac_services,
                $this->user,
                $this->error
            );
        };
    }

    public function executeCommand()
    {
        if (isset($this->gui_classes[self::GUI_ADMIN_SCRIPTS])) {
            $this->gui_classes[self::GUI_ADMIN_SCRIPTS]()->executeCommandAndRenderGUI();
        }
    }
}
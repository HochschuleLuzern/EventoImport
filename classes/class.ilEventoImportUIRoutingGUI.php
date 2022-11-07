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

    public function __construct()
    {
        global $DIC;
        $this->plugin = new \ilEventoImportPlugin();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->request = $DIC->http()->request();
        $this->ui_services = $DIC->ui();
        $this->locator = $DIC["ilLocator"];
        $this->db = $DIC->database();
        $this->tabs = $DIC->tabs();

        $this->gui_classes = [];
        $this->gui_classes[self::GUI_ADMIN_SCRIPTS] = function () {
            return new \EventoImport\administration\AdminScriptPageGUI(
                $this->plugin,
                $this->tpl,
                $this->ui_services,
                $this->ctrl,
                $this->tabs,
                $this->locator,
                $this->db,
                $this->request
            );
        };
    }

    public function executeCommand()
    {
        if(isset($this->gui_classes[self::GUI_ADMIN_SCRIPTS])) {
            $this->gui_classes[self::GUI_ADMIN_SCRIPTS]()->executeCommandAndRenderGUI();
        }
    }
}
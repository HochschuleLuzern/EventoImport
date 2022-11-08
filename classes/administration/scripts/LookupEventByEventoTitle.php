<?php declare(strict_types=1);

namespace EventoImport\administration\scripts;

use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\DI\UIServices;
use ILIAS\UI\Factory;
use EventoImport\administration\AdminScriptPageGUI;
use EventoImport\db\IliasEventoEventsTblDef;
use EventoImport\import\data_management\repository\IliasEventoEventObjectRepository;
use EventoImport\communication\EventoEventImporter;
use EventoImport\communication\request_services\RestClientService;
use EventoImport\config\ImporterApiSettings;
use EventoImport\communication\ImporterIterator;
use EventoImport\import\Logger;
use ILIAS\UI\Component\Modal\Modal;
use EventoImport\import\data_management\repository\model\IliasEventoEvent;

class LookupEventByEventoTitle implements AdminScriptInterface
{
    use AdminScriptCommonMethods;

    private const FORM_TITLE = 'evento_title';

    private const CMD_LOOKUP_IN_DB = 'lookup_title';
    private const CMD_LOOKUP_ON_API = 'lookup_on_api';

    private \ilDBInterface $db;
    private \ilCtrl $ctrl;

    public function __construct(\ilDBInterface $db, \ilCtrl $ctrl)
    {
        $this->db = $db;
        $this->ctrl = $ctrl;
    }

    public function getTitle() : string
    {
        return "Lookup Event by Evento Title";
    }

    public function getScriptId() : string
    {
        return 'get_event_by_evento_title';
    }

    public function getParameterFormUI() : \ilPropertyFormGUI
    {
        $this->ctrl->setParameterByClass(\ilEventoImportUIRoutingGUI::class, 'script', $this->getScriptId());
        $url = $this->ctrl->getFormActionByClass(\ilEventoImportUIRoutingGUI::class);

        $form = new \ilPropertyFormGUI();
        $form->setFormAction($url);

        $txt = new \ilTextInputGUI('Evento Title', self::FORM_TITLE);
        $form->addItem($txt);

        $form->addCommandButton(self::CMD_LOOKUP_IN_DB, 'Lookup in DB');
        $form->addCommandButton(self::CMD_LOOKUP_ON_API, 'Lookup on API');

        return $form;
    }

    public function getResultModalFromRequest(string $cmd, Factory $f) : Modal
    {
        $form = $this->getParameterFormUI();
        if(!$form->checkInput()) {
            return $this->buildModal($this->getTitle(), "Invalid Form Input!", $f);
        }

        $title = $form->getInput(self::FORM_TITLE);

        switch($cmd) {
            case self::CMD_LOOKUP_IN_DB:
                $repo = new IliasEventoEventObjectRepository($this->db);
                $events = $repo->getIliasEventoEventsByTitle($title, true);

                if (count($events) > 0) {
                    $display_api_responses = "<b>Following Events found in DB:</b><br>";
                    /** @var IliasEventoEvent $ilias_evento_event */
                    foreach ($events as $ilias_evento_event) {
                        $id = $ilias_evento_event->getEventoEventId();
                        $title = htmlspecialchars($ilias_evento_event->getEventoTitle());
                        $display_api_responses .= "$title -> $id<br>";
                    }

                    return $this->buildModal($this->getTitle() . ': '.htmlspecialchars($title), $display_api_responses, $f);
                } else {
                    return $this->buildModal($this->getTitle() . ': '.htmlspecialchars($title), "No ID found for title " . htmlspecialchars($title), $f);
                }

            case self::CMD_LOOKUP_ON_API:
                $repo = new IliasEventoEventObjectRepository($this->db);
                $events = $repo->getIliasEventoEventsByTitle($title, true);

                $importer = $this->buildEventImporter($this->db);

                $display_top = "API Response to the following IDs: ";
                $display_api_responses = '<br>';
                /** @var IliasEventoEvent $ilias_evento_event */
                foreach ($events as $ilias_evento_event) {
                    $id = $ilias_evento_event->getEventoEventId();
                    $title = $ilias_evento_event->getEventoTitle();

                    $display_top .= '<a href=#crevento_' . $id . '>' . $title . ' ('. $id . ')</a>, ';

                    $response = $importer->fetchEventDataRecordById($id);

                    if (is_null($response)) {
                        $display_api_responses .= 'No Event found for Title='.htmlspecialchars($title).' and ID='.$id.'<br>';
                    } else {
                        $display_api_responses .= '<pre id="crevento_'.$id.'">' . htmlspecialchars(print_r($response->getDecodedApiData(), true)) . '</pre>';
                    }
                }

                return $this->buildModal($this->getTitle() . ': '.htmlspecialchars($title), $display_top . $display_api_responses, $f);

            default:
                return $this->buildModal($this->getTitle() . ': '.htmlspecialchars($title), "Unknown Command", $f);
        }
    }
}
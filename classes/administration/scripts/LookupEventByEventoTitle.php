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

class LookupEventByEventoTitle implements AdminScriptInterface
{
    private const FORM_TITLE = 'evento_title';

    private const CMD_LOOKUP_IN_DB = 'lookup_title';
    private const CMD_LOOKUP_ON_API = 'lookup_on_api';

    private \ilDBInterface $db;

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
                $evento_ids = $this->getEventoIdsByTitle($title);

                if (count($evento_ids) > 0) {
                    $display_string = "Evento IDs found in DB:<br>";
                    foreach ($evento_ids as $title => $id) {
                        $display_string .= "$title -> $id<br>";
                    }

                    return $this->buildModal($this->getTitle() . ': '.htmlspecialchars($title), $display_string, $f);
                } else {
                    return $this->buildModal($this->getTitle() . ': '.htmlspecialchars($title), "No ID found for title " . htmlspecialchars($title), $f);
                }

            case self::CMD_LOOKUP_ON_API:
                $evento_ids = $this->getEventoIdsByTitle($title);

                $importer = $this->buildEventImporter();

                $display_string = "API Response to the following IDs: ";
                foreach ($evento_ids as $title => $id) {
                    $id = (int)$id;
                    $display_string .= '<a href=#crevento_' . $id . '>' . $id . '</a>, ';
                }

                foreach ($evento_ids as $title => $id) {
                    $id = (int)$id;
                    $response = $importer->fetchEventDataRecordById($id);

                    if (is_null($response)) {
                        $display_string .= 'No Event found for Title='.htmlspecialchars($title).' and ID='.$id.'<br>';
                    } else {
                        $display_string .= '<pre id="crevento_'.$id.'">' . htmlspecialchars(print_r($response->getDecodedApiData(), true)) . '</pre>';
                    }
                }

                return $this->buildModal($this->getTitle() . ': '.htmlspecialchars($title), $display_string, $f);

            default:
                return $this->buildModal($this->getTitle() . ': '.htmlspecialchars($title), "Unknown Command", $f);
        }
    }

    private function getEventoIdsByTitle(string $title) : array
    {
        $sql = "SELECT evento_id, evento_title FROM crevento_evnto_events WHERE " . $this->db->like('evento_title', \ilDBConstants::T_TEXT, $title . '%');
        $result = $this->db->query($sql);

        $events = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $events[$row['evento_title']] = $row['evento_id'];
        }

        return $events;
    }

    private function buildEventImporter()
    {
        $api_settings = new ImporterApiSettings(new \ilSetting('crevento'));
        return new EventoEventImporter(
            new RestClientService(
                $api_settings->getUrl(),
                $api_settings->getTimeoutFailedRequest(),
                $api_settings->getApikey(),
                $api_settings->getApiSecret()
            ),
            new ImporterIterator(500),
            new Logger($this->db),
            $api_settings->getTimeoutAfterRequest(),
            $api_settings->getMaxRetries()
        );
    }

    private function buildModal(string $title, string $content, Factory $f) : Modal
    {
        return $f->modal()->lightbox($f->modal()->lightboxTextPage($content, $title));
    }
}
<?php declare(strict_types = 1);

use EventoImport\communication\EventoEventImporter;
use EventoImport\import\data_matching\EventImportActionDecider;
use EventoImport\communication\api_models\EventoEvent;

class ilEventoImportImportEventsAndMemberships
{
    private $evento_importer;
    private $event_import_action_decider;
    private $logger;

    public function __construct(
        EventoEventImporter $evento_importer,
        EventImportActionDecider $event_import_action_decider,
        ilEventoImportLogger $logger
    ) {
        $this->evento_importer = $evento_importer;
        $this->event_import_action_decider = $event_import_action_decider;
        $this->logger = $logger;
    }

    public function run()
    {
        $this->importEvents();
    }

    private function importEvents()
    {
        do {
            try {
                $this->importNextEventPage();
            } catch (\Exception $e) {
                $this->logger->logException('Importing Event Page', $e->getMessage());
            }
        } while ($this->evento_importer->hasMoreData());
    }

    private function importNextEventPage()
    {
        foreach ($this->evento_importer->fetchNextEventDataSet() as $data_set) {
            try {
                $evento_event = new EventoEvent($data_set);

                $action = $this->event_import_action_decider->determineAction($evento_event);
                $action->executeAction();
            } catch (\Exception $e) {
                $this->logger->logException('Importing Event', $e->getMessage());
            }
        }
    }
}

<?php

use EventoImport\import\db\MembershipManager;
use EventoImport\communication\EventoAdminImporter;
use EventoImport\import\db\repository\IliasEventoEventsRepository;

class ilEventoImportImportAdmins
{
    private EventoAdminImporter $evento_importer;
    private MembershipManager $membership_manager;
    private IliasEventoEventsRepository $ilias_event_repo;
    private \ilEventoImportLogger $logger;

    public function __construct(
        EventoAdminImporter $evento_importer,
        MembershipManager $membership_manager,
        IliasEventoEventsRepository $ilias_event_repo,
        ilEventoImportLogger $logger
    ) {
        $this->evento_importer = $evento_importer;
        $this->membership_manager = $membership_manager;
        $this->ilias_event_repo = $ilias_event_repo;
        $this->logger = $logger;
    }

    public function run()
    {
        foreach ($this->evento_importer->fetchAllIliasAdmins() as $data_set) {
            try {
                $event_admin_list = new \EventoImport\communication\api_models\EventoEventIliasAdmins($data_set);
                $ilias_evento_event = $this->ilias_event_repo->getEventByEventoId($event_admin_list->getEventoId());

                $this->membership_manager->addEventAdmins($event_admin_list, $ilias_evento_event);
            } catch (\Exception $e) {
                $this->logger->logException('Importing Event', $e->getMessage());
            }
        }
    }
}

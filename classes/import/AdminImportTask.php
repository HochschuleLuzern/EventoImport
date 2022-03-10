<?php declare(strict_types=1);

namespace EventoImport\import;

use EventoImport\import\data_management\MembershipManager;
use EventoImport\communication\EventoAdminImporter;
use EventoImport\import\data_management\repository\repository\IliasEventoEventsRepository;
use EventoImport\communication\api_models\EventoEventIliasAdmins;
use EventoImport\import\Logger;
use EventoImport\import\data_management\repository\IliasEventoEventObjectRepository;

class AdminImportTask
{
    private EventoAdminImporter $evento_importer;
    private MembershipManager $membership_manager;
    private IliasEventoEventObjectRepository $ilias_event_repo;
    private \EventoImport\import\Logger $logger;

    public function __construct(
        EventoAdminImporter $evento_importer,
        MembershipManager $membership_manager,
        IliasEventoEventObjectRepository $ilias_event_repo,
        Logger $logger
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
                $event_admin_list = new EventoEventIliasAdmins($data_set);
                $ilias_evento_event = $this->ilias_event_repo->getEventByEventoId($event_admin_list->getEventoId());

                $this->membership_manager->addEventAdmins($event_admin_list, $ilias_evento_event);
            } catch (\Exception $e) {
                $this->logger->logException('Importing Event', $e->getMessage());
            }
        }
    }
}

<?php declare(strict_types=1);

namespace EventoImport\import;

use EventoImport\import\data_management\MembershipManager;
use EventoImport\communication\EventoAdminImporter;
use EventoImport\communication\api_models\EventoEventIliasAdmins;
use EventoImport\import\data_management\repository\IliasEventoEventObjectRepository;
use EventoImport\import\data_management\HiddenAdminManager;

class AdminImportTask
{
    private EventoAdminImporter $evento_importer;
    private HiddenAdminManager $hidden_admin_manager;
    private IliasEventoEventObjectRepository $ilias_event_repo;
    private \EventoImport\import\Logger $logger;

    public function __construct(
        EventoAdminImporter $evento_importer,
        HiddenAdminManager $hidden_admin_manager,
        IliasEventoEventObjectRepository $ilias_event_repo,
        Logger $logger
    ) {
        $this->evento_importer = $evento_importer;
        $this->hidden_admin_manager = $hidden_admin_manager;
        $this->ilias_event_repo = $ilias_event_repo;
        $this->logger = $logger;
    }

    public function run()
    {
        foreach ($this->evento_importer->fetchAllIliasAdmins() as $data_set) {
            try {
                $event_admin_list = new EventoEventIliasAdmins($data_set);
                $ilias_evento_event = $this->ilias_event_repo->getEventByEventoId($event_admin_list->getEventoId());

                if (!is_null($ilias_evento_event)) {
                    $this->hidden_admin_manager->synchronizeEventAdmins($event_admin_list, $ilias_evento_event);
                }
            } catch (\Exception $e) {
                $this->logger->logException('Admin Import', $e->getMessage(), $e->getTraceAsString());
            }
        }
    }
}

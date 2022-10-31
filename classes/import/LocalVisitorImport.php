<?php declare(strict_types=1);

namespace EventoImport\import;

use EventoImport\communication\EventoEmployeeImporter;
use EventoImport\communication\api_models\EventoUserShort;
use EventoImport\config\local_roles\LocalVisitorRoleRepository;
use EventoImport\import\data_management\repository\IliasEventoUserRepository;
use EventoImport\import\data_management\UserManager;
use EventoImport\config\local_roles\LocalVisitorRole;

class LocalVisitorImport
{
    private EventoEmployeeImporter $evento_importer;
    private LocalVisitorRoleRepository $visitor_role_repo;
    private UserManager $user_manager;
    private \EventoImport\import\Logger $logger;

    public function __construct(
        EventoEmployeeImporter $evento_importer,
        LocalVisitorRoleRepository $visitor_role_repo,
        UserManager $user_manager,
        Logger $logger
    ) {
        $this->evento_importer = $evento_importer;
        $this->visitor_role_repo = $visitor_role_repo;
        $this->user_manager = $user_manager;
        $this->logger = $logger;
    }

    public function run()
    {
        foreach($this->visitor_role_repo->getAllVisitorRoles() as $visitor_role) {
            $this->fetchEmployeesAndSyncWithVisitorRole($visitor_role);
        }

    }

    private function fetchEmployeesAndSyncWithVisitorRole(LocalVisitorRole $visitor_role)
    {
        $ilias_evento_user_list = [];
        foreach ($this->evento_importer->fetchEmployees($visitor_role->getDepartmentApiName(), $visitor_role->getKindLocationName()) as $data_set) {
            try {
                $evento_user = new EventoUserShort($data_set);
                $ilias_evento_user = $this->user_manager->getIliasEventoUserForEventoUser($evento_user);

                if (!is_null($ilias_evento_user)) {
                    $ilias_evento_user_list[] = $ilias_evento_user;
                }
            } catch (\Exception $e) {
            }
        }

        $visitor_role->synchronizeWithIliasEventoUserList($ilias_evento_user_list);
    }
}
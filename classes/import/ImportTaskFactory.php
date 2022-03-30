<?php declare(strict_types = 1);

namespace EventoImport\import;

use EventoImport\communication\EventoUserImporter;
use EventoImport\import\action\UserImportActionDecider;
use EventoImport\import\data_management\ilias_core_service\IliasUserServices;
use ILIAS\DI\RBACServices;
use EventoImport\import\data_management\repository\IliasEventoUserRepository;
use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\import\action\user\UserActionFactory;
use EventoImport\communication\EventoEventImporter;
use EventoImport\import\action\EventImportActionDecider;
use EventoImport\import\action\event\EventActionFactory;
use EventoImport\import\data_management\ilias_core_service\IliasEventObjectService;
use EventoImport\import\data_management\MembershipManager;
use EventoImport\import\data_management\repository\IliasEventoEventMembershipRepository;
use EventoImport\import\data_management\repository\IliasEventoEventObjectRepository;
use EventoImport\config\locations\EventLocationsRepository;
use EventoImport\communication\EventoAdminImporter;
use EventoImport\import\data_management\ilias_core\MembershipablesEventInTreeSeeker;
use EventoImport\config\EventLocations;
use EventoImport\import\data_management\EventManager;
use EventoImport\import\data_management\UserManager;
use EventoImport\config\ConfigurationManager;
use EventoImport\config\locations\RepositoryLocationSeeker;
use EventoImport\config\locations\EventLocationCategoryBuilder;

class ImportTaskFactory
{
    private ConfigurationManager $config_manager;
    private \ilDBInterface $db;
    private RBACServices $rbac;
    private Logger $logger;
    private \ilTree $tree;

    public function __construct(ConfigurationManager $config_manager, \ilDBInterface $db, \ilTree $tree, RBACServices $rbac)
    {
        $this->config_manager = $config_manager;
        $this->db = $db;
        $this->tree = $tree;
        $this->rbac = $rbac;
        $this->logger = new Logger($db);
    }

    public function buildUserImport(EventoUserImporter $user_importer, EventoUserPhotoImporter $user_photo_importer) : UserImportTask
    {
        $user_settings = $this->config_manager->getDefaultUserConfiguration();
        $ilias_user_service = new IliasUserServices($user_settings, $this->db, $this->rbac);
        $evento_user_repo = new IliasEventoUserRepository($this->db);

        return new UserImportTask(
            $user_importer,
            new UserImportActionDecider(
                $ilias_user_service,
                $evento_user_repo,
                new UserActionFactory(
                    new UserManager(
                        $ilias_user_service,
                        $evento_user_repo,
                        $user_settings
                    ),
                    $user_photo_importer,
                    $this->logger
                )
            ),
            $ilias_user_service,
            $evento_user_repo,
            $this->logger
        );
    }

    public function buildEventImport(EventoEventImporter $event_importer) : EventAndMembershipImportTask
    {
        $event_settings = $this->config_manager->getDefaultEventConfiguration();
        $user_settings = $this->config_manager->getDefaultUserConfiguration();
        $event_obj_service = new IliasEventObjectService($event_settings, $this->db, $this->tree);
        $evento_event_obj_repo = new IliasEventoEventObjectRepository($this->db);
        $event_locations = new EventLocations(
            new EventLocationsRepository($this->db),
            new RepositoryLocationSeeker($this->tree, 1),
            new EventLocationCategoryBuilder()
        );
        $event_manager = new EventManager(
            $event_obj_service,
            $evento_event_obj_repo,
            $event_locations
        );

        return new EventAndMembershipImportTask(
            $event_importer,
            new EventImportActionDecider(
                $event_manager,
                new EventActionFactory(
                    $event_manager,
                    new MembershipManager(
                        new MembershipablesEventInTreeSeeker($this->tree),
                        new IliasEventoEventMembershipRepository($this->db),
                        new UserManager(
                            new IliasUserServices($user_settings, $this->db, $this->rbac),
                            new IliasEventoUserRepository($this->db),
                            $user_settings
                        ),
                        new \ilFavouritesManager(),
                        $this->logger,
                        $this->rbac
                    ),
                    $this->logger
                ),
                $event_locations
            ),
            $evento_event_obj_repo,
            $this->logger
        );
    }

    public function buildAdminImport(EventoAdminImporter $admin_importer) : AdminImportTask
    {
        $user_settings = $this->config_manager->getDefaultUserConfiguration();
        return new AdminImportTask(
            $admin_importer,
            new MembershipManager(
                new MembershipablesEventInTreeSeeker($this->tree),
                new IliasEventoEventMembershipRepository($this->db),
                new UserManager(
                    new IliasUserServices($user_settings, $this->db, $this->rbac),
                    new IliasEventoUserRepository($this->db),
                    $user_settings
                ),
                new \ilFavouritesManager(),
                $this->logger,
                $this->rbac
            ),
            new IliasEventoEventObjectRepository($this->db),
            $this->logger
        );
    }
}

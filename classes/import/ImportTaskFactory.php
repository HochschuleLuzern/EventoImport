<?php declare(strict_types = 1);

namespace EventoImport\import;

use EventoImport\communication\EventoUserImporter;
use EventoImport\import\action\UserImportActionDecider;
use EventoImport\import\data_management\ilias_core_service\IliasUserServices;
use ILIAS\DI\RBACServices;
use EventoImport\import\data_management\repository\IliasEventoUserRepository;
use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\import\action\user\UserActionFactory;
use EventoImport\config\DefaultUserSettings;
use EventoImport\communication\EventoEventImporter;
use EventoImport\import\action\EventImportActionDecider;
use EventoImport\import\action\event\EventActionFactory;
use EventoImport\import\data_management\ilias_core_service\IliasEventObjectService;
use EventoImport\config\DefaultEventSettings;
use EventoImport\import\data_management\MembershipManager;
use EventoImport\import\data_management\repository\IliasEventoEventMembershipRepository;
use EventoImport\import\data_management\repository\IliasEventoEventObjectRepository;
use EventoImport\config\EventLocationsRepository;
use EventoImport\communication\EventoAdminImporter;
use EventoImport\import\data_management\ilias_core\MembershipablesEventInTreeSeeker;
use EventoImport\config\EventLocations;
use EventoImport\import\data_management\EventManager;
use EventoImport\import\data_management\UserManager;

class ImportTaskFactory
{
    private \ilDBInterface $db;
    private RBACServices $rbac;
    private \ilSetting $setting;
    private Logger $logger;

    public function __construct(\ilDBInterface $db, \ilTree $tree, RBACServices $rbac, \ilSetting $setting)
    {
        $this->db = $db;
        $this->tree = $tree;
        $this->rbac = $rbac;
        $this->setting = $setting;
        $this->logger = new Logger($db);
    }

    public function buildUserImport(EventoUserImporter $user_importer, EventoUserPhotoImporter $user_photo_importer) : UserImportTask
    {
        $user_settings = new DefaultUserSettings($this->setting);
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
        $event_obj_service = new IliasEventObjectService(new DefaultEventSettings($this->setting), $this->db, $this->tree);
        $evento_event_obj_repo = new IliasEventoEventObjectRepository($this->db);

        return new EventAndMembershipImportTask(
            $event_importer,
            new EventImportActionDecider(
                $event_obj_service,
                $evento_event_obj_repo,
                new EventActionFactory(
                    new EventManager(
                        $event_obj_service,
                        $evento_event_obj_repo,
                        new EventLocations(
                            new EventLocationsRepository($this->db)
                        )
                    ),
                    new MembershipManager(
                        new MembershipablesEventInTreeSeeker($this->tree),
                        new IliasEventoEventMembershipRepository($this->db),
                        new UserManager(
                            new IliasUserServices(new DefaultUserSettings($this->setting), $this->db, $this->rbac),
                            new IliasEventoUserRepository($this->db),
                            new DefaultUserSettings($this->setting)
                        ),
                        new \ilFavouritesManager(),
                        $this->logger,
                        $this->rbac
                    ),
                    $this->logger
                ),
                new EventLocationsRepository($this->db)
            ),
            $evento_event_obj_repo,
            $this->logger
        );
    }

    public function buildAdminImport(EventoAdminImporter $admin_importer) : AdminImportTask
    {
        return new AdminImportTask(
            $admin_importer,
            new MembershipManager(
                new MembershipablesEventInTreeSeeker($this->tree),
                new IliasEventoEventMembershipRepository($this->db),
                new UserManager(
                    new IliasUserServices(new DefaultUserSettings($this->setting), $this->db, $this->rbac),
                    new IliasEventoUserRepository($this->db),
                    new DefaultUserSettings($this->setting)
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

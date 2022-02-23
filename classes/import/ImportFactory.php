<?php declare(strict_types = 1);

namespace EventoImport\import;

use EventoImport\communication\EventoUserImporter;
use EventoImport\import\action\UserImportActionDecider;
use EventoImport\import\service\IliasUserServices;
use ILIAS\DI\RBACServices;
use EventoImport\import\db\IliasEventoUserRepository;
use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\import\action\user\UserActionFactory;
use EventoImport\import\settings\DefaultUserSettings;
use EventoImport\communication\EventoEventImporter;
use EventoImport\import\action\EventImportActionDecider;
use EventoImport\import\action\event\EventActionFactory;
use EventoImport\import\service\IliasEventObjectService;
use EventoImport\import\settings\DefaultEventSettings;
use EventoImport\import\service\MembershipManager;
use EventoImport\import\db\IliasEventoEventMembershipRepository;
use EventoImport\import\db\IliasEventoEventObjectRepository;
use EventoImport\import\db\EventLocationsRepository;
use EventoImport\communication\EventoAdminImporter;
use EventoImport\import\db\query\MembershipablesInTreeSeeker;

class ImportFactory
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

    public function buildUserImport(EventoUserImporter $user_importer, EventoUserPhotoImporter $user_photo_importer) : UserImport
    {
        $ilias_user_service = new IliasUserServices($this->db, $this->rbac);
        $evento_user_repo = new IliasEventoUserRepository($this->db);

        return new UserImport(
            $user_importer,
            new UserImportActionDecider(
                $ilias_user_service,
                $evento_user_repo,
                new UserActionFactory(
                    $ilias_user_service,
                    $evento_user_repo,
                    new DefaultUserSettings(
                        $this->setting
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

    public function buildEventImport(EventoEventImporter $event_importer) : EventAndMembershipImport
    {
        $event_obj_service = new IliasEventObjectService(new DefaultEventSettings($this->setting), $this->db);
        $event_obj_repo = new IliasEventoEventObjectRepository($this->db);

        return new EventAndMembershipImport(
            $event_importer,
            new EventImportActionDecider(
                $event_obj_service,
                $event_obj_repo,
                new EventActionFactory(
                    $event_obj_repo,
                    $event_obj_service,
                    new MembershipManager(
                        new MembershipablesInTreeSeeker($this->tree),
                        new IliasEventoEventMembershipRepository($this->db),
                        new IliasEventoUserRepository($this->db),
                        new \ilFavouritesManager(),
                        $this->logger,
                        $this->rbac
                    ),
                    $this->logger
                ),
                new EventLocationsRepository($this->db)
            ),
            $this->logger
        );
    }

    public function buildAdminImport(EventoAdminImporter $admin_importer) : AdminImport
    {
        return new AdminImport(
            $admin_importer,
            new MembershipManager(
                new MembershipablesInTreeSeeker($this->tree),
                new IliasEventoEventMembershipRepository($this->db),
                new IliasEventoUserRepository($this->db),
                new \ilFavouritesManager(),
                $this->logger,
                $this->rbac
            ),
            new IliasEventoEventObjectRepository($this->db),
            $this->logger
        );
    }
}

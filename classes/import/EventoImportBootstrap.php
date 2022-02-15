<?php declare(strict_types = 1);

namespace EventoImport\import;

use ILIAS\DI\RBACServices;
use EventoImport\import\db\repository\IliasEventoUserRepository;
use EventoImport\import\db\repository\IliasEventoEventsRepository;
use EventoImport\import\db\query\IliasUserQuerying;
use EventoImport\import\db\IliasUserServices;
use EventoImport\import\db\query\IliasEventObjectQuery;
use EventoImport\import\db\IliasEventObjectService;
use EventoImport\import\db\repository\EventMembershipRepository;
use EventoImport\import\db\repository\EventLocationsRepository;
use EventoImport\import\db\repository\ParentEventRepository;
use EventoImport\import\settings\DefaultEventSettings;
use EventoImport\import\db\MembershipManager;
use EventoImport\import\settings\DefaultUserSettings;

class EventoImportBootstrap
{
    /*********************
     ** General objects **
     *********************/
    private \ilDBInterface $db;
    private RBACServices $rbac_services;
    private \ilSetting $settings;
    private \EventoImport\import\Logger $logger;

    /***************************
     ** User related objects **
     ***************************/
    private IliasEventoUserRepository $evento_user_repository;
    private IliasUserQuerying $user_query;
    private IliasUserServices $user_facade;

    /***************************
     ** Event related objects **
     ***************************/
    private IliasEventoEventsRepository $evento_event_repository;
    private IliasEventObjectQuery $event_query;
    private EventLocationsRepository $location_repository;
    private ParentEventRepository $parent_event_repo;
    private IliasEventObjectService $repository_facade;
    private IliasEventObjectFactory $event_object_factory;
    private DefaultEventSettings $default_event_settings;

    /*********************************
     *** Membership related objects **
     ********************************/
    private EventMembershipRepository $membership_repo;
    private MembershipManager $membership_manager;

    public function __construct(\ilDBInterface $db, RBACServices $rbac_services, \ilSetting $settings)
    {
        $this->db = $db;
        $this->rbac_services = $rbac_services;
        $this->settings = $settings;
    }

    public function logger() : \EventoImport\import\Logger
    {
        if (!isset($this->logger)) {
            $this->logger = new \EventoImport\import\Logger($this->db);
        }
        return $this->logger;
    }

    public function eventoUserRepository() : IliasEventoUserRepository
    {
        if (!isset($this->evento_user_repository)) {
            $this->evento_user_repository = new IliasEventoUserRepository($this->db);
        }
        return $this->evento_user_repository;
    }

    public function iliasUserQuery() : IliasUserQuerying
    {
        if (!isset($this->user_query)) {
            $this->user_query = new IliasUserQuerying($this->db);
        }
        return $this->user_query;
    }

    public function membershipRepo() : EventMembershipRepository
    {
        if (!isset($this->membership_repo)) {
            $this->membership_repo = new EventMembershipRepository($this->db);
        }
        return $this->membership_repo;
    }

    public function userFacade() : IliasUserServices
    {
        if (!isset($this->user_facade)) {
            $this->user_facade = new IliasUserServices(
                $this->db,
                $this->rbac_services
            );
        }
        return $this->user_facade;
    }

    public function defaultUserSettings() : DefaultUserSettings
    {
        if (!isset($this->default_user_settings)) {
            $this->default_user_settings = new DefaultUserSettings($this->settings);
        }
        return $this->default_user_settings;
    }

    public function eventoEventRepository() : IliasEventoEventsRepository
    {
        if (!isset($this->evento_event_repository)) {
            $this->evento_event_repository = new IliasEventoEventsRepository($this->db);
        }
        return $this->evento_event_repository;
    }

    public function iliasEventObjectQuery() : IliasEventObjectQuery
    {
        if (!isset($this->event_query)) {
            $this->event_query = new IliasEventObjectQuery($this->db);
        }
        return $this->event_query;
    }

    public function eventLocationRepository() : EventLocationsRepository
    {
        if (!isset($this->location_repository)) {
            $this->location_repository = new EventLocationsRepository($this->db);
        }
        return $this->location_repository;
    }

    public function parentEventRepository() : ParentEventRepository
    {
        if (!isset($this->parent_event_repo)) {
            $this->parent_event_repo = new ParentEventRepository($this->db);
        }
        return $this->parent_event_repo;
    }

    public function repositoryFacade() : IliasEventObjectService
    {
        if (!isset($this->repository_facade)) {
            $this->repository_facade = new IliasEventObjectService(
                $this->iliasEventObjectQuery(),
                $this->eventoEventRepository(),
                $this->eventLocationRepository(),
                $this->parentEventRepository()
            );
        }
        return $this->repository_facade;
    }

    public function defaultEventSettings() : DefaultEventSettings
    {
        if (!isset($this->default_event_settings)) {
            $this->default_event_settings = new DefaultEventSettings($this->settings);
        }
        return $this->default_event_settings;
    }

    public function eventObjectFactory() : IliasEventObjectFactory
    {
        if (!isset($this->event_object_factory)) {
            $this->event_object_factory = new IliasEventObjectFactory(
                $this->repositoryFacade(),
                $this->defaultEventSettings()
            );
        }
        return $this->event_object_factory;
    }

    public function membershipManager() : MembershipManager
    {
        if (!isset($this->membership_manager)) {
            $this->membership_manager = new MembershipManager(
                $this->membershipRepo(),
                $this->eventoUserRepository(),
                $this->eventoEventRepository(),
                new \ilFavouritesManager(),
                $this->logger(),
                $this->rbac_services
            );
        }
        return $this->membership_manager;
    }
}

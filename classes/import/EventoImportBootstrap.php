<?php declare(strict_types = 1);

namespace EventoImport\import;

use EventoImport\import\db\repository\EventoUserRepository;
use EventoImport\import\db\repository\IliasEventoEventsRepository;
use EventoImport\import\db\query\IliasUserQuerying;
use EventoImport\import\db\UserFacade;
use EventoImport\import\db\query\IliasEventObjectQuery;
use EventoImport\import\db\RepositoryFacade;
use ILIAS\DI\RBACServices;
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
    /** @var \ilDBInterface */
    private \ilDBInterface $db;

    /** @var RBACServices */
    private RBACServices $rbac_services;

    /** @var \ilSetting */
    private \ilSetting $settings;

    /** @var \ilEventoImportLogger */
    private \ilEventoImportLogger $logger;

    /*** User related objects ***/
    /** @var EventoUserRepository */
    private EventoUserRepository $evento_user_repository;

    /** @var IliasUserQuerying */
    private IliasUserQuerying $user_query;

    /** @var UserFacade */
    private UserFacade $user_facade;

    /***************************
     ** Event related objects **
     ***************************/
    /** @var IliasEventoEventsRepository */
    private IliasEventoEventsRepository $evento_event_repository;

    /** @var IliasEventObjectQuery */
    private IliasEventObjectQuery $event_query;

    /** @var EventLocationsRepository */
    private EventLocationsRepository $location_repository;

    /** @var ParentEventRepository */
    private ParentEventRepository $parent_event_repo;

    /** @var RepositoryFacade */
    private RepositoryFacade $repository_facade;

    /** @var IliasEventObjectFactory */
    private IliasEventObjectFactory $event_object_factory;

    /** @var DefaultEventSettings */
    private DefaultEventSettings $default_event_settings;

    /*****************
     *** Membership **
     *****************/
    /** @var EventMembershipRepository */
    private EventMembershipRepository $membership_repo;

    /** @var MembershipManager */
    private MembershipManager $membership_manager;

    public function __construct(\ilDBInterface $db = null, RBACServices $rbac_services = null, \ilSetting $settings = null)
    {
        global $DIC;
        $this->db = $db ?? $DIC->database();
        $this->rbac_services = $rbac_services ?? $DIC->rbac();
        $this->settings = $settings ?? new \ilSetting('crevento');
    }

    public function logger() : \ilEventoImportLogger
    {
        if (!isset($this->logger)) {
            $this->logger = new \ilEventoImportLogger($this->db);
        }
        return $this->logger;
    }

    public function eventoUserRepository() : EventoUserRepository
    {
        if (!isset($this->evento_user_repository)) {
            $this->evento_user_repository = new EventoUserRepository($this->db);
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

    public function userFacade() : UserFacade
    {
        if (!isset($this->user_facade)) {
            $this->user_facade = new UserFacade(
                $this->iliasUserQuery(),
                $this->eventoUserRepository(),
                $this->membershipRepo(),
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

    public function repositoryFacade() : RepositoryFacade
    {
        if (!isset($this->repository_facade)) {
            $this->repository_facade = new RepositoryFacade(
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

<?php

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

class EventoImportBootstrap
{
    // General objects
    private \ilDBInterface $db;
    private RBACServices $rbac_services;

    // User related objects
    private EventoUserRepository $evento_user_repository;
    private IliasUserQuerying $user_query;
    private UserFacade $user_facade;

    private EventMembershipRepository $membership_repo;

    // Event related objects
    private IliasEventoEventsRepository $evento_event_repository;
    private IliasEventObjectQuery $event_query;
    private EventLocationsRepository $location_repository;
    private ParentEventRepository $parent_event_repo;
    private RepositoryFacade $repository_facade;

    public function __construct(\ilDBInterface $db = null, RBACServices $rbac_services = null)
    {
        global $DIC;
        $this->db = $db ?? $DIC->database();
        $this->rbac_services = $rbac_services ?? $DIC->rbac();
    }

    public function eventoUserRepository() : EventoUserRepository
    {
        if (is_null($this->evento_user_repository)) {
            $this->evento_user_repository = new EventoUserRepository($this->db);
        }
        return $this->evento_user_repository;
    }

    public function iliasUserQuery() : IliasUserQuerying
    {
        if (is_null($this->user_query)) {
            $this->user_query = new IliasUserQuerying($this->db);
        }
        return $this->user_query;
    }

    public function membershipRepo() : EventMembershipRepository
    {
        if (is_null($this->membership_repo)) {
            $this->membership_repo = new EventMembershipRepository($this->db);
        }
        return $this->membership_repo;
    }

    public function userFacade() : UserFacade
    {
        if (is_null($this->user_facade)) {
            $this->user_facade = new UserFacade(
                $this->iliasUserQuery(),
                $this->eventoUserRepository(),
                $this->membershipRepo(),
                $this->rbac_services
            );
        }
        return $this->user_facade;
    }

    public function eventoEventRepository() : IliasEventoEventsRepository
    {
        if (is_null($this->evento_event_repository)) {
            $this->evento_event_repository = new IliasEventoEventsRepository($this->db);
        }
        return $this->evento_event_repository;
    }

    public function iliasEventObjectQuery() : IliasEventObjectQuery
    {
        if (is_null($this->event_query)) {
            $this->event_query = new IliasEventObjectQuery($this->db);
        }
        return $this->event_query;
    }

    public function eventLocationRepository() : EventLocationsRepository
    {
        if (is_null($this->location_repository)) {
            $this->location_repository = new EventLocationsRepository($this->db);
        }
        return $this->location_repository;
    }

    public function parentEventRepository() : ParentEventRepository
    {
        if (is_null($this->parent_event_repo)) {
            $this->parent_event_repo = new ParentEventRepository($this->db);
        }
        return $this->parent_event_repo;
    }

    public function repositoryFacade() : RepositoryFacade
    {
        if (is_null($this->repository_facade)) {
            $this->repository_facade = new RepositoryFacade(
                $this->iliasEventObjectQuery(),
                $this->eventoEventRepository(),
                $this->eventLocationRepository(),
                $this->parentEventRepository()
            );
        }
    }
}

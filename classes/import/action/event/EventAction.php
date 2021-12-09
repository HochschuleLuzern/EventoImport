<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\action\EventoImportAction;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\IliasEventWrapper;
use EventoImport\communication\api_models\EventoUserShort;
use EventoImport\import\db\repository\EventMembershipRepository;
use EventoImport\import\db\MembershipManager;

abstract class EventAction implements EventoImportAction
{
    /**
     * @var MembershipManager
     */
    protected $membership_manager;
    protected $evento_event;
    protected $repository_facade;
    protected $event_object_factory;
    protected $logger;
    protected $rbac_services;
    protected $event_settings;
    /**
     * @var UserFacade
     */
    protected $user_facade;

    protected $log_code;

    public function __construct(EventoEvent $evento_event, IliasEventObjectFactory $event_object_factory, int $log_code, \EventoImport\import\settings\DefaultEventSettings $event_settings, RepositoryFacade $repository_facade, \EventoImport\import\db\UserFacade $user_facade, MembershipManager $membership_manager, \ilEventoImportLogger $logger, \ILIAS\DI\RBACServices $rbac_services)
    {
        $this->evento_event = $evento_event;
        $this->log_code = $log_code;
        $this->repository_facade = $repository_facade;
        $this->user_facade = $user_facade;
        $this->membership_manager = $membership_manager;
        $this->event_object_factory = $event_object_factory;
        $this->logger = $logger;
        $this->rbac_services = $rbac_services;
        $this->event_settings = $event_settings;
    }

    protected function synchronizeUsersInRole(IliasEventWrapper $ilias_event)
    {
        $this->membership_manager->synchronizeMembershipsWithEvent($this->evento_event, $ilias_event->getIliasEventoEventObj());
    }
}

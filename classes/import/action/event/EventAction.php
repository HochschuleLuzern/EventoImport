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
    /** @var MembershipManager */
    protected MembershipManager $membership_manager;

    /** @var EventoEvent */
    protected EventoEvent $evento_event;

    /** @var RepositoryFacade */
    protected RepositoryFacade $repository_facade;

    /** @var IliasEventObjectFactory */
    protected IliasEventObjectFactory $event_object_factory;

    /** @var \ilEventoImportLogger */
    protected \ilEventoImportLogger $logger;

    /** @var \ILIAS\DI\RBACServices */
    protected \ILIAS\DI\RBACServices $rbac_services;

    /** @var \EventoImport\import\settings\DefaultEventSettings */
    protected \EventoImport\import\settings\DefaultEventSettings $event_settings;

    /** @var UserFacade */
    protected UserFacade $user_facade;

    /** @var int */
    protected int $log_code;

    /**
     * EventAction constructor.
     * @param EventoEvent                                        $evento_event
     * @param IliasEventObjectFactory                            $event_object_factory
     * @param int                                                $log_code
     * @param \EventoImport\import\settings\DefaultEventSettings $event_settings
     * @param RepositoryFacade                                   $repository_facade
     * @param UserFacade                                         $user_facade
     * @param MembershipManager                                  $membership_manager
     * @param \ilEventoImportLogger                              $logger
     * @param \ILIAS\DI\RBACServices                             $rbac_services
     */
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

    /**
     * @param IliasEventWrapper $ilias_event
     */
    protected function synchronizeUsersInRole(IliasEventWrapper $ilias_event)
    {
        $this->membership_manager->synchronizeMembershipsWithEvent($this->evento_event, $ilias_event->getIliasEventoEventObj());
    }
}

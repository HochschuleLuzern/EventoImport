<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\db\MembershipManager;

/**
 * Class CreateEventInParentEvent
 * @package EventoImport\import\action\event
 */
class CreateEventInParentEvent implements EventAction
{
    private EventoEvent $evento_event;
    private IliasEventoParentEvent $parent_event;
    private IliasEventObjectFactory $event_object_factory;
    private RepositoryFacade $repository_facade;
    private MembershipManager $membership_manager;
    private \ilEventoImportLogger $logger;
    private int $log_code;

    /**
     * CreateEventInParentEvent constructor.
     * @param EventoEvent             $evento_event
     * @param IliasEventoParentEvent  $parent_event
     * @param IliasEventObjectFactory $event_object_factory
     * @param RepositoryFacade        $repository_facade
     * @param MembershipManager       $membership_manager
     * @param \ilEventoImportLogger   $logger
     */
    public function __construct(EventoEvent $evento_event, IliasEventoParentEvent $parent_event, IliasEventObjectFactory $event_object_factory, RepositoryFacade $repository_facade, MembershipManager $membership_manager, \ilEventoImportLogger $logger)
    {
        $this->evento_event = $evento_event;
        $this->parent_event = $parent_event;
        $this->event_object_factory = $event_object_factory;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = \ilEventoImportLogger::CREVENTO_MA_EVENT_IN_EXISTING_PARENT_EVENT_CREATED;
    }

    public function executeAction() : void
    {
        $event_sub_group = $this->event_object_factory->buildNewGroupObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $this->parent_event->getRefId(),
        );

        $ilias_event = $this->repository_facade->addNewIliasEvent($this->evento_event, $event_sub_group);

        $this->membership_manager->syncMemberships($this->evento_event, $ilias_event);

        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $event_sub_group->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}

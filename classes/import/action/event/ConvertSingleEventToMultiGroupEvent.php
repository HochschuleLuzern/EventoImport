<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\MembershipManager;
use EventoImport\import\db\model\IliasEventoEvent;

class ConvertSingleEventToMultiGroupEvent implements EventAction
{
    private EventoEvent $evento_event;
    private IliasEventoEvent $ilias_event;
    private \ilContainer $current_event_object;
    private IliasEventObjectFactory $event_object_factory;
    private RepositoryFacade $repository_facade;
    private MembershipManager $membership_manager;
    private \ilEventoImportLogger $logger;
    private int $log_code;

    /**
     * ConvertSingleEventToMultiGroupEvent constructor.
     * @param EventoEvent             $evento_event
     * @param IliasEventoEvent        $ilias_event
     * @param \ilContainer            $current_event_object
     * @param IliasEventObjectFactory $event_object_factory
     * @param RepositoryFacade        $repository_facade
     * @param MembershipManager       $membership_manager
     * @param \ilEventoImportLogger   $logger
     */
    public function __construct(EventoEvent $evento_event, IliasEventoEvent $ilias_event, \ilContainer $current_event_object, IliasEventObjectFactory $event_object_factory, RepositoryFacade $repository_facade, MembershipManager $membership_manager, \ilEventoImportLogger $logger)
    {
        $this->evento_event = $evento_event;
        $this->ilias_event = $ilias_event;
        $this->current_event_object = $current_event_object;
        $this->event_object_factory = $event_object_factory;
        $this->repository_facade = $repository_facade;
        $this->membership_manager = $membership_manager;
        $this->logger = $logger;
        $this->log_code = \ilEventoImportLogger::CREVENTO_MA_SINGLE_EVENT_TO_MULTI_GROUP_CONVERTED;
    }

    public function executeAction() : void
    {
        // Change the event object to be the parent event object
        if ($this->evento_event->getName() == $this->current_event_object->getTitle()) {
            $this->current_event_object->setTitle($this->evento_event->getGroupName());
            $this->current_event_object->update();
        }

        // Create first subgroup which now is the new event object
        $event_sub_group = $this->event_object_factory->buildNewGroupObject($this->evento_event, $this->evento_event->getDescription(), $this->current_event_object->getRefId());

        // Update DB-Entries
        $this->repository_facade->addNewIliasEventoParentEvent($this->evento_event, $this->current_event_object);
        $updated_ilias_event = $this->repository_facade->updateIliasEventoEvent($this->evento_event, $this->ilias_event, $event_sub_group);

        $this->membership_manager->syncMemberships($this->evento_event, $updated_ilias_event);

        $this->logger->logEventImport(
            $this->log_code,
            $this->evento_event->getEventoId(),
            $updated_ilias_event->getRefId(),
            ['api_data' => $this->evento_event->getDecodedApiData()]
        );
    }
}

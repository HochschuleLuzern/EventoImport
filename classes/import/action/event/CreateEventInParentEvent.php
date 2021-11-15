<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;

class CreateEventInParentEvent extends EventAction
{
    private $parent_event;

    public function __construct(EventoEvent $evento_event, IliasEventoParentEvent $parent_event, IliasEventObjectFactory $event_object_factory, \EventoImport\import\settings\DefaultEventSettings $event_settings, RepositoryFacade $repository_facade, UserFacade $user_facade, \ilEventoImportLogger $logger, \ILIAS\DI\RBACServices $rbac_services)
    {
        parent::__construct($evento_event, $event_object_factory, $event_settings, $repository_facade, $user_facade, $logger, $rbac_services);

        $this->parent_event = $parent_event;
    }

    public function executeAction()
    {
        $event_sub_group = $this->event_object_factory->buildNewGroupObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $this->parent_event->getRefId(),
        );

        $parent_event_ilias_obj = new \ilObjCourse($this->parent_event->getRefId(), true);

        $event_wrapper = $this->repository_facade->addNewEventToExistingMultiGroupEvent($this->evento_event, $parent_event_ilias_obj, $event_sub_group);
        $this->synchronizeUsersInRole($event_wrapper);
    }
}

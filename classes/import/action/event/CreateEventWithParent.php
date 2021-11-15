<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;

class CreateEventWithParent extends EventAction
{
    private int $destination_ref_id;

    public function __construct(EventoEvent $evento_event, int $destination_ref_id, IliasEventObjectFactory $event_object_factory, \EventoImport\import\settings\DefaultEventSettings $event_settings, RepositoryFacade $repository_facade, UserFacade $user_facade, \ilEventoImportLogger $logger, \ILIAS\DI\RBACServices $rbac_services)
    {
        parent::__construct($evento_event, $event_object_factory, $event_settings, $repository_facade, $user_facade, $logger, $rbac_services);
        $this->destination_ref_id = $destination_ref_id;
    }

    public function executeAction()
    {
        $parent_event_crs_obj = $this->event_object_factory->buildNewCourseObject(
            $this->evento_event->getGroupName(),
            $this->evento_event->getDescription(),
            $this->destination_ref_id,
        );

        $event_sub_group = $this->event_object_factory->buildNewGroupObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $parent_event_crs_obj->getRefId(),
        );

        $event_wrapper = $this->repository_facade->addNewMultiEventCourseAndGroup($this->evento_event, $parent_event_crs_obj, $event_sub_group);
        $this->synchronizeUsersInRole($event_wrapper);
    }
}

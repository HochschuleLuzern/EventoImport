<?php

namespace EventoImport\import\action\event;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\db\UserFacade;
use EventoImport\import\IliasEventObjectFactory;
use EventoImport\import\settings\DefaultEventSettings;

class CreateSingleEvent extends EventAction
{
    private $destination_ref_id;

    public function __construct(EventoEvent $evento_event, int $destination_ref_id, IliasEventObjectFactory $event_object_factory, DefaultEventSettings $event_settings, RepositoryFacade $repository_facade, UserFacade $user_facade, \ilEventoImportLogger $logger, \ILIAS\DI\RBACServices $rbac_services)
    {
        parent::__construct($evento_event, $event_object_factory, $event_settings, $repository_facade, $user_facade, $logger, $rbac_services);

        $this->destination_ref_id = $destination_ref_id;
    }

    public function executeAction()
    {
        $course_object = $this->event_object_factory->buildNewCourseObject(
            $this->evento_event->getName(),
            $this->evento_event->getDescription(),
            $this->event_settings->getDefaultObjectOwnerId(), // owner_user_id,
            $this->destination_ref_id,
            $this->event_settings->getDefaultSortMode()
        ); // sort_mode);

        $ilias_event = $this->repository_facade->addNewSingleEventCourse($this->evento_event, $course_object);
        $this->synchronizeUsersInRole($ilias_event);
    }
}
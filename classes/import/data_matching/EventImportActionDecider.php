<?php

namespace EventoImport\import\data_matching;

use EventoImport\import\action\event\EventActionFactory;
use EventoImport\import\db\RepositoryFacade;

class EventImportActionDecider
{
    private $repository_facade;
    private $event_action_factory;

    public function __construct(RepositoryFacade $repository_facade, EventActionFactory $event_action_factory)
    {
        $this->repository_facade = $repository_facade;
        $this->event_action_factory = $event_action_factory;
    }


    public function determineAction(\EventoImport\communication\api_models\EventoEvent $evento_event)
    {
        $ilias_event = $this->repository_facade->getIliasEventByEventoIdOrReturnNull($evento_event->getEventoId());
        if (!is_null($ilias_event) && $ilias_event->getIliasEventoEventObj()->wasAutomaticallyCreated() == $evento_event->hasCreateCourseFlag()) {
            return $this->event_action_factory->updateExistingEvent($evento_event, $ilias_event);
        }

        // Has create flag
        if ($evento_event->hasCreateCourseFlag()) {
            $destination_ref_id = $this->repository_facade->departmentLocationRepository()->fetchRefIdForEventoObject($evento_event);

            if ($destination_ref_id === null) {
                return $this->event_action_factory->reportUnknownLocationForEvent($evento_event);
            }

            if (!$evento_event->hasGroupMemberFlag()) {
                // Is single Group
                return $this->event_action_factory->createSingleEvent($evento_event, $destination_ref_id);
            }

            // Is MultiGroup
            $parent_event = $this->repository_facade->searchPossibleParentEventForEvent($evento_event);
            if (!is_null($parent_event)) {
                // Parent event in multi group exists
                return $this->event_action_factory->createEventInParentEvent($evento_event, $parent_event);
            }

            // Parent event in multi group has also to be created
            return $this->event_action_factory->createEventWithParent($evento_event, $destination_ref_id);
        }
        // Has no create flag
        else {
            $matched_course = $this->repository_facade->searchExactlyOneMatchingCourseByTitle($evento_event);

            if (!is_null($matched_course)) {
                return $this->event_action_factory->convertExistingIliasObjToEvent($evento_event, $matched_course);
            } else {
                return $this->event_action_factory->reportNonIliasEvent($evento_event);
            }
        }
    }
}

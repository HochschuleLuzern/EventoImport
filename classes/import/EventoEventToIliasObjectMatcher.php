<?php

namespace EventoImport\import;

use EventoImport\import\db_repository\IliasEventoEventsRepository;

class EventoEventToIliasObjectMatcher
{
    private $event_repo;
    private $ilias_event_query;

    public function __construct(IliasEventObjectQuery $ilias_event_query, IliasEventoEventsRepository $event_repo)
    {
        $this->ilias_event_query = $ilias_event_query;
        $this->event_repo = $event_repo;
    }

    public function searchExactlyOneMatchingCourseByTitle(data_models\EventoEvent $evento_event) : ?\ilContainer
    {
        $object_list = $this->ilias_event_query->fetchAllEventableObjectsForGivenTitle($evento_event->getName());

        if(count($object_list) == 1) {
            return $object_list[0];
        }
        else {
            return null;
        }
    }
}
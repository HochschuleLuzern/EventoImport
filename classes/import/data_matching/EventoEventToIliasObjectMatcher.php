<?php

namespace EventoImport\import\data_matching;

use EventoImport\import\db\repository\IliasEventoEventsRepository;
use EventoImport\import\db\query\IliasEventObjectQuery;

class EventoEventToIliasObjectMatcher
{
    private $event_repo;
    private $ilias_event_query;

    public function __construct(IliasEventObjectQuery $ilias_event_query, IliasEventoEventsRepository $event_repo)
    {
        $this->ilias_event_query = $ilias_event_query;
        $this->event_repo = $event_repo;
    }

    public function searchExactlyOneMatchingCourseByTitle(EventoImport\communication\api_models\EventoEvent $evento_event) : ?\ilContainer
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
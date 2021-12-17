<?php

namespace EventoImport\import\data_matching;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\repository\IliasEventoEventsRepository;
use EventoImport\import\db\query\IliasEventObjectQuery;

/**
 * Class EventoEventToIliasObjectMatcher
 * @package EventoImport\import\data_matching
 */
class EventoEventToIliasObjectMatcher
{
    /** @var IliasEventoEventsRepository */
    private $event_repo;

    /** @var IliasEventObjectQuery */
    private $ilias_event_query;

    /**
     * EventoEventToIliasObjectMatcher constructor.
     * @param IliasEventObjectQuery       $ilias_event_query
     * @param IliasEventoEventsRepository $event_repo
     */
    public function __construct(IliasEventObjectQuery $ilias_event_query, IliasEventoEventsRepository $event_repo)
    {
        $this->ilias_event_query = $ilias_event_query;
        $this->event_repo = $event_repo;
    }

    /**
     * @param EventoEvent $evento_event
     * @return \ilContainer|null
     */
    public function searchExactlyOneMatchingCourseByTitle(EventoEvent $evento_event) : ?\ilContainer
    {
        $object_list = $this->ilias_event_query->fetchAllEventableObjectsForGivenTitle($evento_event->getName());

        if (count($object_list) == 1) {
            return $object_list[0];
        } else {
            return null;
        }
    }
}

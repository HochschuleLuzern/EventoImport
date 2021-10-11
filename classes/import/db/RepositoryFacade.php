<?php

namespace EventoImport\import\db;

use EventoImport\import\db\query\IliasEventObjectQuery;
use EventoImport\import\db\repository\IliasEventoEventsRepository;

class RepositoryFacade
{
    /**
     * @var IliasEventObjectQuery
     */
    private IliasEventObjectQuery $event_object_query;
    private IliasEventoEventsRepository $event_repo;

    public function __construct(IliasEventObjectQuery $event_objects_query = null, IliasEventoEventsRepository $event_repo = null)
    {
        global $DIC;

        $this->event_object_query = $event_objects_query ?? new IliasEventObjectQuery($DIC->database());
        $this->event_repo = $event_repo ?? new IliasEventoEventsRepository($DIC->database());
    }

    public function fetchAllEventableObjectsForGivenTitle(string $name)
    {
        $this->event_object_query->fetchAllEventableObjectsForGivenTitle($name);
    }

    public function getEventCourseOfEvent()
    {
        global $DIC;
    }

    public function iliasEventoEventRepository() : IliasEventoEventsRepository
    {
        return $this->event_repo;
    }


}
<?php

namespace EventoImport\import\db;

use EventoImport\import\db\query\IliasEventObjectQuery;
use EventoImport\import\db\repository\DepartmentLocationRepository;
use EventoImport\import\db\repository\IliasEventoEventsRepository;

class RepositoryFacade
{
    /**
     * @var IliasEventObjectQuery
     */
    private $event_object_query;
    private $event_repo;
    private $location_repo;

    public function __construct($event_objects_query = null, $event_repo = null, $location_repo = null)
    {
        global $DIC;

        $this->event_object_query = $event_objects_query ?? new IliasEventObjectQuery($DIC->database());
        $this->event_repo = $event_repo ?? new IliasEventoEventsRepository($DIC->database());
        $this->location_repo = $location_repo ?? new DepartmentLocationRepository($DIC->database());
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

    public function departmentLocationRepository() : DepartmentLocationRepository
    {
        return $this->location_repo;
    }


}
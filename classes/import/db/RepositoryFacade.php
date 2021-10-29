<?php

namespace EventoImport\import\db;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\model\IliasEventoParentEvent;
use EventoImport\import\db\query\IliasEventObjectQuery;
use EventoImport\import\db\repository\EventLocationsRepository;
use EventoImport\import\db\repository\IliasEventoEventsRepository;
use EventoImport\import\db\repository\ParentEventRepository;
use EventoImport\import\IliasEventWrapper;
use EventoImport\import\IliasEventWrapperEventWithParent;
use EventoImport\import\IliasEventWrapperSingleEvent;

class RepositoryFacade
{
    /**
     * @var IliasEventObjectQuery
     */
    private $event_object_query;
    private $event_repo;
    private $location_repo;
    private $parent_event_repo;

    public function __construct($event_objects_query = null, $event_repo = null, $location_repo = null)
    {
        global $DIC;

        $this->event_object_query = $event_objects_query ?? new IliasEventObjectQuery($DIC->database());
        $this->event_repo         = $event_repo ?? new IliasEventoEventsRepository($DIC->database());
        $this->location_repo      = $location_repo ?? new EventLocationsRepository($DIC->database());
        $this->parent_event_repo  = new ParentEventRepository($DIC->database());
    }

    public function fetchAllEventableObjectsForGivenTitle(string $name)
    {
        $this->event_object_query->fetchAllEventableObjectsForGivenTitle($name);
    }

    public function searchPossibleParentEventForEvent(EventoEvent $evento_event)
    {
        global $DIC;

        $parent_event = $this->parent_event_repo->fetchParentEventForName($evento_event->getGroupName());
        if (!is_null($parent_event)) {
            return new \ilObjCourse($parent_event->getRefId(), true);
        }

        $obj_id = $this->event_object_query->searchPossibleParentEventForEvent($evento_event);
        if (!is_null($obj_id)) {
            return new \ilObjCourse($obj_id, false);
        }

        return null;
    }

    public function iliasEventoEventRepository() : IliasEventoEventsRepository
    {
        return $this->event_repo;
    }

    public function departmentLocationRepository() : EventLocationsRepository
    {
        return $this->location_repo;
    }

    public function addNewSingleEventCourse(EventoEvent $evento_event, \ilObjCourse $crs_object) : IliasEventWrapper
    {
        $ilias_evento_event = new IliasEventoEvent(
            $evento_event->getEventoId(),
            $evento_event->getName(),
            $evento_event->getDescription(),
            $evento_event->getType(),
            $evento_event->hasCreateCourseFlag(),
            $evento_event->getStartDate(),
            $evento_event->getEndDate(),
            $crs_object->getType(),
            $crs_object->getRefId(),
            $crs_object->getId(),
            $crs_object->getDefaultAdminRole(),
            $crs_object->getDefaultMemberRole()
        );

        $this->event_repo->addNewEventoIliasEvent(
            $ilias_evento_event
        );

        return new IliasEventWrapperSingleEvent($ilias_evento_event, $crs_object);
    }

    public function addNewMultiEventCourseAndGroup(EventoEvent $evento_event, \ilObjCourse $crs_object, \ilObjGroup $sub_group) : IliasEventWrapper
    {
        $parent_event = new IliasEventoParentEvent(
            $crs_object->getTitle(),
            $crs_object->getRefId(),
            $crs_object->getDefaultAdminRole(),
            $crs_object->getDefaultMemberRole()
        );

        $ilias_evento_event = new IliasEventoEvent(
            $evento_event->getEventoId(),
            $evento_event->getName(),
            $evento_event->getDescription(),
            $evento_event->getType(),
            $evento_event->hasCreateCourseFlag(),
            $evento_event->getStartDate(),
            $evento_event->getEndDate(),
            $sub_group->getType(),
            $sub_group->getRefId(),
            $sub_group->getId(),
            $sub_group->getDefaultAdminRole(),
            $sub_group->getDefaultMemberRole(),
            $crs_object->getId()
        );

        $this->parent_event_repo->addNewParentEvent($parent_event);
        $this->event_repo->addNewEventoIliasEvent($ilias_evento_event);

        return new IliasEventWrapperEventWithParent($parent_event, $crs_object, $ilias_evento_event, $sub_group);
    }

    public function addNewEventToExistingMultiGroupEvent(EventoEvent $evento_event, \ilObjCourse $crs_object, \ilObjGroup $sub_group) : IliasEventWrapper
    {
        $ilias_parent_event = $this->parent_event_repo->fetchParentEventForRefId($crs_object->getRefId());

        $ilias_evento_event = new IliasEventoEvent(
            $evento_event->getEventoId(),
            $evento_event->getName(),
            $evento_event->getDescription(),
            $evento_event->getType(),
            $evento_event->hasCreateCourseFlag(),
            $evento_event->getStartDate(),
            $evento_event->getEndDate(),
            $sub_group->getType(),
            $sub_group->getRefId(),
            $sub_group->getId(),
            $sub_group->getDefaultAdminRole(),
            $sub_group->getDefaultMemberRole(),
            $crs_object->getId()
        );

        $this->event_repo->addNewEventoIliasEvent(
            $ilias_evento_event
        );

        return new IliasEventWrapperEventWithParent($ilias_parent_event, $crs_object, $ilias_evento_event, $sub_group);
    }
}
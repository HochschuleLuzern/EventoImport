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
        $this->event_repo = $event_repo ?? new IliasEventoEventsRepository($DIC->database());
        $this->location_repo = $location_repo ?? new EventLocationsRepository($DIC->database());
        $this->parent_event_repo = new ParentEventRepository($DIC->database());
    }

    public function fetchAllEventableObjectsForGivenTitle(string $name)
    {
        $this->event_object_query->fetchAllEventableObjectsForGivenTitle($name);
    }

    public function searchPossibleParentEventForEvent(EventoEvent $evento_event) : ?IliasEventoParentEvent
    {
        global $DIC;

        $parent_event = $this->parent_event_repo->fetchParentEventForName($evento_event->getGroupName());
        if (!is_null($parent_event)) {
            return $parent_event;
        }

        $obj_id = $this->event_object_query->searchPossibleParentEventForEvent($evento_event);
        if (!is_null($obj_id)) {
            return $parent_event;
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
            $evento_event->getGroupUniqueKey(),
            $evento_event->getGroupId(),
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
            $crs_object->getRefId()
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

    public function getIliasEventByEventoIdOrReturnNull(int $evento_id) : ?IliasEventWrapper
    {
        $ilias_evento_event = $this->event_repo->getEventByEventoId($evento_id);

        if (is_null($ilias_evento_event)) {
            return null;
        }

        if (!is_null($ilias_evento_event->getParentEventRefId())) {
            $parent_event = $this->parent_event_repo->fetchParentEventForRefId($ilias_evento_event->getParentEventRefId());

            if (is_null($parent_event)) {
                throw new \InvalidArgumentException('Parent Event for ref_id ' . $ilias_evento_event->getParentEventRefId() . ' does not exist.');
            }

            $parent_obj_type = \ilObject::_lookupType($parent_event->getRefId(), true);
            if ($parent_obj_type == 'crs') {
                $parent_event_obj = new \ilObjCourse($parent_event->getRefId());
            } elseif ($parent_obj_type == 'grp') {
                $parent_event_obj = new \ilObjGroup($parent_event->getRefId());
            } else {
                throw new \InvalidArgumentException('Type of parent obj ist not an event type');
            }

            $sub_event = new \ilObjGroup($ilias_evento_event->getRefId(), true);

            return new IliasEventWrapperEventWithParent($parent_event, $parent_event_obj, $ilias_evento_event, $sub_event);
        } else {
            $obj_type = \ilObject::_lookupType($ilias_evento_event->getRefId(), true);
            if ($obj_type == 'crs') {
                $ilias_event_obj = new \ilObjCourse($ilias_evento_event->getRefId());
            } elseif ($obj_type == 'grp') {
                $ilias_event_obj = new \ilObjGroup($ilias_evento_event->getRefId());
            } else {
                throw new \InvalidArgumentException('Type of given ilias obj is not an event type');
            }

            return new IliasEventWrapperSingleEvent($ilias_evento_event, $ilias_event_obj);
        }
    }

    public function searchExactlyOneMatchingCourseByTitle(EventoEvent $evento_event) : ?\ilContainer
    {
        $object_list = $this->event_object_query->fetchAllEventableObjectsForGivenTitle($evento_event->getName());

        if (count($object_list) == 1) {
            return $object_list[0];
        } else {
            return null;
        }
    }
}

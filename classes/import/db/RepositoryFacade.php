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

/**
 * Class RepositoryFacade
 * @package EventoImport\import\db
 */
class RepositoryFacade
{
    /** @var IliasEventObjectQuery */
    private IliasEventObjectQuery $event_object_query;

    /** @var IliasEventoEventsRepository */
    private IliasEventoEventsRepository $event_repo;

    /** @var EventLocationsRepository */
    private EventLocationsRepository $location_repo;

    /** @var ParentEventRepository  */
    private ParentEventRepository $parent_event_repo;

    /**
     * RepositoryFacade constructor.
     * @param null $event_objects_query
     * @param null $event_repo
     * @param null $location_repo
     */
    public function __construct(
        IliasEventObjectQuery $event_objects_query = null,
        IliasEventoEventsRepository $event_repo = null,
        EventLocationsRepository $location_repo = null,
        ParentEventRepository $parent_event_repo = null
    ) {
        global $DIC;

        $this->event_object_query = $event_objects_query ?? new IliasEventObjectQuery($DIC->database());
        $this->event_repo = $event_repo ?? new IliasEventoEventsRepository($DIC->database());
        $this->location_repo = $location_repo ?? new EventLocationsRepository($DIC->database());
        $this->parent_event_repo = $parent_event_repo ?? new ParentEventRepository($DIC->database());
    }

    /**
     * @param string $name
     */
    public function fetchAllEventableObjectsForGivenTitle(string $name)
    {
        $this->event_object_query->fetchAllEventableObjectsForGivenTitle($name);
    }

    /**
     * @param EventoEvent $evento_event
     * @return IliasEventoParentEvent|null
     */
    public function searchPossibleParentEventForEvent(EventoEvent $evento_event) : ?IliasEventoParentEvent
    {
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

    /**
     * @return IliasEventoEventsRepository
     */
    public function iliasEventoEventRepository() : IliasEventoEventsRepository
    {
        return $this->event_repo;
    }

    /**
     * @return EventLocationsRepository
     */
    public function departmentLocationRepository() : EventLocationsRepository
    {
        return $this->location_repo;
    }

    /**
     * @param EventoEvent $evento_event
     * @return \ilContainer|null
     */
    public function searchExactlyOneMatchingCourseByTitle(EventoEvent $evento_event) : ?\ilContainer
    {
        $object_list = $this->event_object_query->fetchAllEventableObjectsForGivenTitle($evento_event->getName());

        if (count($object_list) == 1) {
            return $object_list[0];
        } else {
            return null;
        }
    }

    /**
     * @param EventoEvent  $evento_event
     * @param \ilContainer $ilias_object
     * @return IliasEventoEvent
     */
    public function addNewIliasEvent(EventoEvent $evento_event, \ilContainer $ilias_object) : IliasEventoEvent
    {
        if ($ilias_object instanceof \ilObjCourse || $ilias_object instanceof \ilObjGroup) {
            $ilias_evento_event = new IliasEventoEvent(
                $evento_event->getEventoId(),
                $evento_event->getName(),
                $evento_event->getDescription(),
                $evento_event->getType(),
                $evento_event->hasCreateCourseFlag(),
                $evento_event->getStartDate(),
                $evento_event->getEndDate(),
                $ilias_object->getType(),
                $ilias_object->getRefId(),
                $ilias_object->getId(),
                $ilias_object->getDefaultAdminRole(),
                $ilias_object->getDefaultMemberRole(),
                $evento_event->getGroupUniqueKey()
            );

            $this->event_repo->addNewEventoIliasEvent(
                $ilias_evento_event
            );

            return $ilias_evento_event;
        } else {
            throw new \InvalidArgumentException('Invalid ILIAS Object Type given to register Ilias-Evento-Event');
        }
    }

    /**
     * @param EventoEvent  $evento_event
     * @param \ilContainer $parent_event_ilias_obj
     * @return IliasEventoParentEvent
     */
    public function addNewIliasEventoParentEvent(EventoEvent $evento_event, \ilContainer $parent_event_ilias_obj) : IliasEventoParentEvent
    {
        if ($parent_event_ilias_obj instanceof \ilObjCourse || $parent_event_ilias_obj instanceof \ilObjGroup) {
            $ilias_evento_parent_event = new IliasEventoParentEvent(
                $evento_event->getGroupUniqueKey(),
                $evento_event->getGroupId(),
                $evento_event->getGroupName(),
                $parent_event_ilias_obj->getRefId(),
                $parent_event_ilias_obj->getDefaultAdminRole(),
                $parent_event_ilias_obj->getDefaultMemberRole()
            );
            $this->parent_event_repo->addNewParentEvent($ilias_evento_parent_event);

            return $ilias_evento_parent_event;
        } else {
            throw new \InvalidArgumentException('Invalid ILIAS Object Type given to register Ilias-Parent-Event-Object');
        }
    }

    /**
     * @param EventoEvent      $evento_event
     * @param IliasEventoEvent $old_ilias_event
     * @param \ilContainer     $ilias_object
     * @return IliasEventoEvent
     */
    public function updateIliasEventoEvent(EventoEvent $evento_event, IliasEventoEvent $old_ilias_event, \ilContainer $ilias_object) : IliasEventoEvent
    {
        if ($ilias_object instanceof \ilObjCourse || $ilias_object instanceof \ilObjGroup) {
            $updated_obj = new IliasEventoEvent(
                $evento_event->getEventoId(),
                $evento_event->getName(),
                $evento_event->getDescription(),
                $old_ilias_event->getEventoType(),
                $old_ilias_event->wasAutomaticallyCreated(),
                $evento_event->getStartDate(),
                $evento_event->getEndDate(),
                $ilias_object->getType(),
                $ilias_object->getRefId(),
                $ilias_object->getId(),
                $old_ilias_event->getAdminRoleId(),
                $old_ilias_event->getStudentRoleId(),
                $evento_event->getGroupUniqueKey()
            );

            $this->event_repo->updateIliasEventoEvent($updated_obj);

            return $updated_obj;
        } else {
            throw new \InvalidArgumentException('Invalid ILIAS Object Type given to update Ilias-Event-Object');
        }
    }
}

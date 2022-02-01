<?php declare(strict_types = 1);

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
 * This class is a take on encapsulation all the "Repository" specific functionality from the rest of the import. Thinks
 * like searching a user, building a ilObjUser object from an ID or write user stuff to the DB should go through this
 * class.
 *
 * It started as a take on the facade pattern (hence the name) but quickly became something more. Because of the lack
 * for a better / more matching name, the class was not renamed till now.
 * TODO: Find a more matching name and unify use of method (e.g. replace the object-getters with actual logic)
 * @package EventoImport\import\db
 */
class RepositoryFacade
{
    private IliasEventObjectQuery $event_object_query;
    private IliasEventoEventsRepository $event_repo;
    private EventLocationsRepository $location_repo;
    private ParentEventRepository $parent_event_repo;

    public function __construct(
        IliasEventObjectQuery $event_objects_query,
        IliasEventoEventsRepository $event_repo,
        EventLocationsRepository $location_repo,
        ParentEventRepository $parent_event_repo
    ) {
        $this->event_object_query = $event_objects_query;
        $this->event_repo = $event_repo;
        $this->location_repo = $location_repo;
        $this->parent_event_repo = $parent_event_repo;
    }

    public function fetchAllEventableObjectsForGivenTitle(string $name)
    {
        $this->event_object_query->fetchAllEventableObjectsForGivenTitle($name);
    }

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

    public function iliasEventoEventRepository() : IliasEventoEventsRepository
    {
        return $this->event_repo;
    }

    public function departmentLocationRepository() : EventLocationsRepository
    {
        return $this->location_repo;
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

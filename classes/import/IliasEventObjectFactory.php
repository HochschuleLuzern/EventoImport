<?php

namespace EventoImport\import;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\query\IliasEventObjectQuery;
use EventoImport\import\db\RepositoryFacade;

class IliasEventObjectFactory
{
    private $owner_user_id = 6;
    private $sort_mode;
    private $repository_facade;

    public function __construct(RepositoryFacade $repository_facade) {
        $this->repository_facade = $repository_facade;
    }

    private function buildCourseObject(string $title, string $description, int $owner_id, int $destination_ref_id, $sort_type) : \ilObjCourse
    {
        $course_object = new \ilObjCourse();

        $course_object->setTitle($title);
        $course_object->setDescription($description);
        $course_object->setOwner($owner_id);
        $course_object->create();

        $course_object->createReference();
        $course_object->putInTree($destination_ref_id);
        $course_object->setPermissions($destination_ref_id);

        $settings = new \ilContainerSortingSettings($course_object->getId());
        $settings->setSortMode($sort_type);
        $settings->setSortDirection(\ilContainer::SORT_DIRECTION_ASC);

        $course_object->setOrderType($sort_type);

        $settings->update();
        $course_object->update();

        return $course_object;
    }

    private function buildGroupObject(string $title, string $description, int $owner_id, int $destination_ref_id, $sort_type)
    {
        $group_object = new \ilObjGroup();

        $group_object->setTitle($title);
        $group_object->setDescription($description);
        $group_object->setOwner($owner_id);
        $group_object->create();

        $group_object->createReference();
        $group_object->putInTree($destination_ref_id);
        $group_object->setPermissions($destination_ref_id);

        $settings = new \ilContainerSortingSettings($group_object->getId());
        $settings->setSortMode($sort_type);
        $settings->setSortDirection(\ilContainer::SORT_DIRECTION_ASC);

        $group_object->setOrderType($sort_type);

        $settings->update();
        $group_object->update();

        return $group_object;
    }

    private function createAsSingleGroupEvent(EventoEvent $evento_event, $destiniation) : IliasEventWrapper
    {
        $crs_object = $this->buildCourseObject($evento_event->getName(), $evento_event->getDescription(), $this->owner_user_id, $destiniation, $this->sort_mode);

        return $this->repository_facade->addNewSingleEventCourse($evento_event, $crs_object);
    }

    private function createAsMultiGroupEvent(EventoEvent $evento_event, $destiniation) : IliasEventWrapper
    {
        $parent_event_crs_obj = $this->repository_facade->searchPossibleParentEventForEvent($evento_event);
        $obj_for_parent_already_existed = false;

        if(is_null($parent_event_crs_obj)) {
            $parent_event_crs_obj = $this->buildCourseObject($evento_event->getGroupName(), $evento_event->getDescription(), $this->owner_user_id, $destiniation, $this->sort_mode);
        } else {
            $obj_for_parent_already_existed = true;
        }

        $event_sub_group = $this->buildGroupObject($evento_event->getName(), $evento_event->getDescription(), $this->owner_user_id, $parent_event_crs_obj->getRefId(), $this->sort_mode);

        if($obj_for_parent_already_existed) {
            $event_wrapper = $this->repository_facade->addNewEventToExistingMultiGroupEvent($evento_event, $parent_event_crs_obj, $event_sub_group);
        } else {
            $event_wrapper = $this->repository_facade->addNewMultiEventCourseAndGroup($evento_event, $parent_event_crs_obj, $event_sub_group);
        }

        return $event_wrapper;
    }

    public function buildNewIliasEventObject(EventoEvent $evento_event, $destiniation) : IliasEventWrapper
    {
        if($evento_event->hasGroupMemberFlag()) {
            return $this->createAsMultiGroupEvent($evento_event, $destiniation);
        } else {
            return $this->createAsSingleGroupEvent($evento_event, $destiniation);
        }
    }
}
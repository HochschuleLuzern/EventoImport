<?php

namespace EventoImport\import;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\query\IliasEventObjectQuery;

class IliasEventObjectFactory
{
    private $owner_user_id = 6;
    private $sort_mode;
    private $event_object_query;

    public function __construct(IliasEventObjectQuery $event_object_query) {
        $this->event_object_query = $event_object_query;
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


    private function createAsSingleGroupEvent(EventoEvent $evento_event, $destiniation)
    {
        $crs_object = $this->buildCourseObject($evento_event->getName(), $evento_event->getDescription(), $this->owner_user_id, $destiniation, $this->sort_mode);


    }

    private function createAsMultiGroupEvent(EventoEvent $evento_event, $destiniation)
    {
        if($event_course = $this->event_object_query->getEventCourseOfEvent($evento_event)) {

        } else {
            $crs_object = $this->buildCourseObject($evento_event->getGroupName(), $evento_event->getDescription(), $this->owner_user_id, $destiniation, $this->sort_mode);
        }

        $sub_group = $this->buildGroupObject($evento_event->getName(), $evento_event->getDescription(), $this->owner_user_id, $crs_object->getRefId(), $this->sort_mode);
    }

    public function buildNewIliasEventObject(EventoEvent $evento_event, $destiniation)
    {
        if($evento_event->hasGroupMemberFlag()) {
            $this->createAsMultiGroupEvent($evento_event, $destiniation);
        } else {
            $this->createAsSingleGroupEvent($evento_event, $destiniation);
        }
    }
}
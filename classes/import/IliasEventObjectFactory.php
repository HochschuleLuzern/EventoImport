<?php declare(strict_types = 1);

namespace EventoImport\import;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\RepositoryFacade;
use EventoImport\import\settings\DefaultEventSettings;

class IliasEventObjectFactory
{
    private DefaultEventSettings $default_event_settings;
    private RepositoryFacade $repository_facade;

    public function __construct(RepositoryFacade $repository_facade, DefaultEventSettings $default_event_settings)
    {
        $this->repository_facade = $repository_facade;
        $this->default_event_settings = $default_event_settings;
    }

    private function setValuesToContainerObject(\ilContainer $container_object, string $title, string $description, int $destination_ref_id)
    {
        $container_object->setTitle($title);
        $container_object->setDescription($description);
        $container_object->setOwner($this->default_event_settings->getDefaultObjectOwnerId());
        $container_object->create();

        $container_object->createReference();
        $container_object->putInTree($destination_ref_id);
        $container_object->setPermissions($destination_ref_id);

        $settings = new \ilContainerSortingSettings($container_object->getId());
        $settings->setSortMode($this->default_event_settings->getDefaultSortMode());
        $settings->setSortDirection($this->default_event_settings->getDefaultSortDirection());

        $container_object->setOrderType($this->default_event_settings->getDefaultSortMode());

        $settings->update();
        $container_object->update();
    }

    public function buildNewCourseObject(string $title, string $description, int $destination_ref_id) : \ilObjCourse
    {
        $course_object = new \ilObjCourse();

        $this->setValuesToContainerObject($course_object, $title, $description, $destination_ref_id);

        return $course_object;
    }

    public function buildNewGroupObject(string $title, string $description, int $destination_ref_id)
    {
        $group_object = new \ilObjGroup();

        $this->setValuesToContainerObject($group_object, $title, $description, $destination_ref_id);

        return $group_object;
    }

    private function createAsSingleGroupEvent(EventoEvent $evento_event, $destiniation) : IliasEventWrapper
    {
        $crs_object = $this->buildNewCourseObject($evento_event->getName(), $evento_event->getDescription(), $destiniation);

        return $this->repository_facade->addNewSingleEventCourse($evento_event, $crs_object);
    }

    private function createAsMultiGroupEvent(EventoEvent $evento_event, $destiniation) : IliasEventoEvent
    {
        $parent_event_crs_obj = $this->repository_facade->searchPossibleParentEventForEvent($evento_event);
        $obj_for_parent_already_existed = false;

        if (is_null($parent_event_crs_obj)) {
            $parent_event_crs_obj = $this->buildNewCourseObject($evento_event->getGroupName(), $evento_event->getDescription(), $destiniation);
        } else {
            $obj_for_parent_already_existed = true;
        }

        $event_sub_group = $this->buildNewGroupObject($evento_event->getName(), $evento_event->getDescription(), $parent_event_crs_obj->getRefId());

        if ($obj_for_parent_already_existed) {
            $event_wrapper = $this->repository_facade->addNewIliasEvent($evento_event, $parent_event_crs_obj, $event_sub_group);
        } else {
            $event_wrapper = $this->repository_facade->addNewMultiEventCourseAndGroup($evento_event, $parent_event_crs_obj, $event_sub_group);
        }

        return $event_wrapper;
    }

    public function buildNewIliasEventObject(EventoEvent $evento_event, $destiniation) : IliasEventWrapper
    {
        if ($evento_event->hasGroupMemberFlag()) {
            return $this->createAsMultiGroupEvent($evento_event, $destiniation);
        } else {
            return $this->createAsSingleGroupEvent($evento_event, $destiniation);
        }
    }
}

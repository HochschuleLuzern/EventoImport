<?php declare(strict_types = 1);

namespace EventoImport\import;

use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\db\IliasEventObjectService;
use EventoImport\import\settings\DefaultEventSettings;

class IliasEventObjectFactory
{
    private DefaultEventSettings $default_event_settings;
    private IliasEventObjectService $repository_facade;

    public function __construct(IliasEventObjectService $repository_facade, DefaultEventSettings $default_event_settings)
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
}

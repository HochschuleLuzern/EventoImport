<?php declare(strict_types = 1);

namespace EventoImport\import\service;

use EventoImport\import\db\model\IliasEventoEvent;
use EventoImport\import\settings\DefaultEventSettings;

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
class IliasEventObjectService
{
    private DefaultEventSettings $default_event_settings;
    private \ilDBInterface $db;

    public function __construct(
        DefaultEventSettings $default_event_settings,
        \ilDBInterface $db
    ) {
        $this->default_event_settings = $default_event_settings;
        $this->db = $db;
    }

    public function searchEventableIliasObjectByTitle(string $obj_title, string $filter_for_only_this_type = null) : ?\ilContainer
    {
        $query = 'SELECT obj_id, type FROM object_data WHERE title = ' . $this->db->quote($obj_title, \ilDBConstants::T_TEXT);

        if (!is_null($filter_for_only_this_type) && ($filter_for_only_this_type == 'crs' || $filter_for_only_this_type == 'grp')) {
            $query .= ' AND type = ' . $this->db->quote($filter_for_only_this_type, \ilDBConstants::T_TEXT);
        } else {
            $query .= ' AND type IN ("crs", "grp")';
        }

        $result = $this->db->query($query);

        if ($this->db->numRows($result) == 1) {
            $row = $this->db->fetchAssoc($result);
            switch ($row['type']) {
                case 'crs':
                    return new \ilObjCourse($row['obj_id'], false);
                case 'grp':
                    return new \ilObjGroup($row['obj_id'], false);
                default:
                    throw new \InvalidArgumentException('Invalid object type given for event object');
            }
        }

        return null;
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

    public function getCourseObjectForRefId(int $ref_id) : \ilObjCourse
    {
        return new \ilObjCourse($ref_id, true);
    }

    public function getGroupObjectForRefId(int $ref_id) : \ilObjGroup
    {
        return new \ilObjGroup($ref_id, true);
    }

    public function removeIliasEventObject(IliasEventoEvent $ilias_event_to_remove)
    {
        if ($ilias_event_to_remove->getIliasType() == 'crs') {
            $ilias_obj = $this->getCourseObjectForRefId($ilias_event_to_remove->getRefId());
        } elseif ($ilias_event_to_remove->getIliasType() == 'grp') {
            $ilias_obj = $this->getGroupObjectForRefId($ilias_event_to_remove->getRefId());
        } else {
            throw new \InvalidArgumentException('Invalid type to remove ilias event object given. Type should be "crs" or "grp". Evento ID of DB-Entry = ' . $ilias_event_to_remove->getEventoEventId());
        }

        $ilias_obj->delete();
    }
}

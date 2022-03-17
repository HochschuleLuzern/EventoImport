<?php declare(strict_types = 1);

namespace EventoImport\import\data_management\ilias_core_service;

use EventoImport\import\data_management\repository\model\IliasEventoEvent;
use EventoImport\config\DefaultEventSettings;

/**
 * Class IliasEventObjectService
 * This class is a take on encapsulation all the "Repository Object" specific functionality from the rest of the import.
 * Things like searching a course/group by title, building a course/group object from an ID or write object stuff to the
 * DB should go through this class.
 *
 * @package EventoImport\import\db
 */
class IliasEventObjectService
{
    private DefaultEventSettings $default_event_settings;
    private \ilDBInterface $db;
    private \ilTree $tree;

    public function __construct(
        DefaultEventSettings $default_event_settings,
        \ilDBInterface $db,
        \ilTree $tree
    ) {
        $this->default_event_settings = $default_event_settings;
        $this->db = $db;
        $this->tree = $tree;
    }

    public function searchEventableIliasObjectByTitle(string $obj_title, string $filter_for_only_this_type = null) : ?\ilContainer
    {
        $query = 'SELECT obj.obj_id obj_id, obj.type type, ref.ref_id ref_id FROM object_data AS obj'
              . ' JOIN object_reference AS ref ON obj.obj_id = ref.obj_id'
              . ' WHERE title = ' . $this->db->quote($obj_title, \ilDBConstants::T_TEXT);

        if (!is_null($filter_for_only_this_type) && ($filter_for_only_this_type == 'crs' || $filter_for_only_this_type == 'grp')) {
            $query .= ' AND type = ' . $this->db->quote($filter_for_only_this_type, \ilDBConstants::T_TEXT);
        } else {
            $query .= ' AND type IN ("crs", "grp")';
        }

        $result = $this->db->query($query);
        $found_obj = null;

        if ($this->db->numRows($result) == 1) {
            $row = $this->db->fetchAssoc($result);

            if ($row['type'] == 'crs') {
                $found_obj = $this->getCourseObjectForRefId((int) $row['ref_id']);
            } elseif ($row['type'] == 'grp') {
                $group_obj = $this->getGroupObjectForRefId((int) $row['ref_id']);

                if ($this->isGroupObjPartOfACourse($group_obj)) {
                    $found_obj = $group_obj;
                }
            }
        }

        return $found_obj;
    }

    public function createNewCourseObject(string $title, string $description, int $destination_ref_id) : \ilObjCourse
    {
        $course_object = new \ilObjCourse();

        $this->createContainerObject($course_object, $title, $description, $destination_ref_id);

        return $course_object;
    }

    public function createNewGroupObject(string $title, string $description, int $destination_ref_id)
    {
        $group_object = new \ilObjGroup();

        $this->createContainerObject($group_object, $title, $description, $destination_ref_id);

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

    public function isGroupObjPartOfACourse(\ilObjGroup $group_obj) : bool
    {
        $current_ref_id = $group_obj->getRefId();
        do {
            $current_ref_id = $this->tree->getParentId($current_ref_id);
            $type = $this->getObjTypeForRefId($current_ref_id);

            if ($type == 'crs') {
                return true;
            } elseif ($type == 'cat' || $type == 'root') {
                return false;
            }
        } while ($current_ref_id > 1);

        return false;
    }

    private function getObjTypeForRefId(int $current_ref_id)
    {
        return \ilObject::_lookupType($current_ref_id, true);
    }

    private function createContainerObject(\ilContainer $container_object, string $title, string $description, int $destination_ref_id)
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

    public function renameEventObject(\ilContainer $event_obj, string $new_title) : \ilContainer
    {
        $event_obj->setTitle($new_title);
        $event_obj->update();
        return $event_obj;
    }
}

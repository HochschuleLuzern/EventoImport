<?php declare(strict_types = 1);

namespace EventoImport\import\data_management\ilias_core_service;

use EventoImport\import\data_management\repository\model\IliasEventoEvent;
use EventoImport\config\DefaultEventSettings;
use EventoImport\import\data_management\repository\model\IliasEventoParentEvent;
use ILIAS\DI\RBACServices;

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
    private RBACServices $rbac;

    public function __construct(
        DefaultEventSettings $default_event_settings,
        \ilDBInterface $db,
        \ilTree $tree,
        RBACServices $rbac
    ) {
        $this->default_event_settings = $default_event_settings;
        $this->db = $db;
        $this->tree = $tree;
        $this->rbac = $rbac;
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
        $this->removeDeletePermissionsFromAdminRole($course_object);

        return $course_object;
    }

    public function createNewGroupObject(string $title, string $description, int $destination_ref_id)
    {
        $group_object = new \ilObjGroup();

        $this->createContainerObject($group_object, $title, $description, $destination_ref_id);

        $group_object->updateGroupType(GRP_TYPE_CLOSED);
        $this->removeDeletePermissionsFromAdminRole($group_object);

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

    public function removeIliasEventObjectWithSubObjects(IliasEventoEvent $ilias_event_to_remove)
    {
        $ref_id = $ilias_event_to_remove->getRefId();
        $type = $this->getObjTypeForRefId($ref_id);
        if ($type == 'crs' || $type == 'grp') {
            \ilRepUtil::deleteObjects($this->tree->getParentId($ref_id), [$ref_id]);
        } else {
            throw new \ilException("Failed deleting Parent Event with ref_id = $ref_id. The ILIAS Object had type $type instead of crs");
        }
    }

    public function removeIliasParentEventObject(IliasEventoParentEvent $ilias_evento_parent_event)
    {
        $ref_id = $ilias_evento_parent_event->getRefId();
        $type = $this->getObjTypeForRefId($ref_id);
        if ($type == 'crs') {
            \ilRepUtil::deleteObjects($this->tree->getParentId($ref_id), [$ref_id]);
        } else {
            throw new \ilException("Failed deleting Parent Event with ref_id = $ref_id. The ILIAS Object had type $type instead of crs");
        }
    }

    public function isGroupObjPartOfACourse(\ilObjGroup $group_obj) : bool
    {
        $current_ref_id = (int) $group_obj->getRefId();
        do {
            $current_ref_id = (int) $this->tree->getParentId($current_ref_id);
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

    private function removeDeletePermissionsFromAdminRole(\ilObject $obj)
    {
        if ($obj instanceof \ilObjCourse) {
            $admin_role = $obj->getDefaultAdminRole();
        } else if ($obj instanceof \ilObjGroup) {
            $admin_role = $obj->getDefaultAdminRole();
        } else {
            return;
        }

        $ref_id = $obj->getRefId();
        $rbac_admin = $this->rbac->admin();

        $ops = $this->rbac->review()->getRoleOperationsOnObject(
            $admin_role,
            $ref_id
        );
        if (($key = array_search(6, $ops)) !== false){
            unset($ops[$key]);
        }
        $rbac_admin->revokePermission($ref_id, $admin_role);
        $rbac_admin->grantPermission($admin_role, $ops, $ref_id);

    }
}

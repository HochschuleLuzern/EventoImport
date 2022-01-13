<?php

namespace EventoImport\import\db\query;

class MembershipableObjectsQuery
{
    /** @var \ilTree */
    private \ilTree $tree;

    /** @var array */
    private array $membershipable_co_groups_cache;

    /** @var array */
    private array $participant_object_cache;

    /**
     * MembershipableObjectsQuery constructor.
     * @param \ilTree|null $tree
     */
    public function __construct(\ilTree $tree = null)
    {
        global $DIC;

        $this->tree = $tree ?? $DIC->repositoryTree();
        $this->membershipable_co_groups_cache = [];
        $this->participant_object_cache = [];
    }

    /**
     * @param int $ref_id
     * @return \ilParticipants
     */
    public function getParticipantsObjectForRefId(int $ref_id) : \ilParticipants
    {
        if (!isset($this->participant_object_cache[$ref_id])) {
            $this->participant_object_cache[$ref_id] = \ilParticipants::getInstance($ref_id);
        }

        return $this->participant_object_cache[$ref_id];
    }

    /**
     * @param int   $parent_ref_id
     * @param array $sub_group_list
     * @param bool  $search_below_groups
     * @return array
     */
    public function recursiveSearchSubGroups(int $parent_ref_id, array $sub_group_list, bool $search_below_groups) : array
    {
        foreach ($this->tree->getChilds($parent_ref_id) as $child_ref_id) {
            $type = \ilObject::_lookupType($parent_ref_id, true);
            if ($type == 'grp') {
                $sub_group_list[$child_ref_id] = $child_ref_id;
                if ($search_below_groups) {
                    $sub_group_list = $this->recursiveSearchSubGroups($child_ref_id, $sub_group_list, $search_below_groups);
                }
            } elseif ($type == 'fold') {
                $sub_group_list = $this->recursiveSearchSubGroups($child_ref_id, $sub_group_list, $search_below_groups);
            }
        }

        return $sub_group_list;
    }

    /**
     * @param int $parent_group_ref_id
     * @return array
     */
    public function getMembershipableCoGroups(int $parent_group_ref_id) : array
    {
        if (!isset($this->membershipable_co_groups_cache[$parent_group_ref_id])) {
            $this->membershipable_co_groups_cache[$parent_group_ref_id] = $this->recursiveSearchSubGroups($parent_group_ref_id, [], false);
        }

        return $this->membershipable_co_groups_cache[$parent_group_ref_id];
    }

    /**
     * @param int $parent_ref_id
     * @return array
     */
    public function getAllSubGroups(int $parent_ref_id) : array
    {
        return $this->recursiveSearchSubGroups($parent_ref_id, [], true);
    }

    /**
     * @param int $src_ref_id
     * @return array
     */
    public function getRefIdsOfParentMembershipables(int $src_ref_id) : array
    {
        $current_obj_ref = $src_ref_id;

        // Super parent means the "root"-object which can hold members. Most of the times this is a course
        // But it is also possible, that the object which holds all members is a group (edge case)
        $has_found_super_parent = false;
        $parent_membershipable_objs = [];

        $deadlock_prevention = 0;
        do {
            $current_obj_ref = $this->tree->getParentId($current_obj_ref);
            $type = \ilObject::_lookupType($current_obj_ref, true);
            if ($type == 'crs') {
                $parent_membershipable_objs[] = $current_obj_ref;
                $has_found_super_parent = true;
            } elseif ($type == 'grp') {
                $parent_membershipable_objs[] = $current_obj_ref;
            } elseif ($type == 'cat' || $type == 'root') {
                $has_found_super_parent = true;
            }

            if ($deadlock_prevention++ > 15) {
                throw new \ilException("Event with the ref_id of " . $src_ref_id . " seems to have either over 15 parent objects or their is a circular connection in the Repository-Tree");
            }
        } while (!$has_found_super_parent);

        return $parent_membershipable_objs;
    }

    /**
     * @param int   $parent_ref_id
     * @param array $sub_objects
     * @param int   $current_depth
     * @return array
     */
    public function getRefIdsOfSubMembershipables(int $parent_ref_id, array $sub_objects, int $current_depth)
    {
        global $DIC;

        // Deadlock prevention
        if ($current_depth > 10) {
            return $sub_objects;
        }
        $current_depth++;

        foreach ($DIC->repositoryTree()->getChilds($parent_ref_id) as $child) {
            $type = \ilObject::_lookupType($child, true);
            if ($type == 'grp') {
                $sub_objects[] = $child;
            }

            $sub_objects = $this->getRefIdsOfSubMembershipables($child, $sub_objects, $current_depth);
        }

        return $sub_objects;
    }
}

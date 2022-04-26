<?php declare(strict_types=1);

namespace EventoImport\config\locations;

class RepositoryLocationSeeker
{
    private \ilTree $tree;
    private int $root_ref_id;

    public function __construct(\ilTree $tree, int $root_ref_id)
    {
        $this->tree = $tree;
        $this->root_ref_id = $root_ref_id;
    }

    public function searchRefIdOfKindCategory(string $department, string $kind) : ?int
    {
        $department_ref_id = $this->searchRefIdForObjTitle($this->root_ref_id, $department);

        if (!is_null($department_ref_id)) {
            return $this->searchRefIdForObjTitle($department_ref_id, $kind);
        }

        return null;
    }

    public function searchRefIdOfYearCateogry(string $department, string $kind, int $year) : ?int
    {
        $kind_ref_id = $this->searchRefIdOfKindCategory($department, $kind);

        if (!is_null($kind_ref_id)) {
            return $this->searchRefIdForObjTitle($kind_ref_id, "$year");
        }

        return null;
    }

    private function searchRefIdForObjTitle(int $root_ref_id, string $searched_obj_title) : ?int
    {
        foreach ($this->tree->getChildsByType($root_ref_id, 'cat') as $child_node) {
            $child_ref = $child_node['child'];
            $obj_id = \ilObject::_lookupObjectId($child_ref);
            if (\ilObject::_lookupTitle($obj_id) == $searched_obj_title) {
                return (int) $child_ref;
            }
        }

        return null;
    }
}

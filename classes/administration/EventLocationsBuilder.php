<?php declare(strict_types = 1);

namespace EventoImport\administration;

use EventoImport\import\db\repository\EventLocationsRepository;

class EventLocationsBuilder
{
    /** @var string[] */
    private array $hard_coded_department_mapping;
    private EventLocationsRepository $locations_repository;
    private \ilTree $tree;

    public function __construct(EventLocationsRepository $locations_repository, \ilTree $tree)
    {
        $this->locations_repository = $locations_repository;
        $this->tree = $tree;

        $this->hard_coded_department_mapping = [
            "Hochschule Luzern" => "HSLU",
            "Design & Kunst" => "DK",
            "Informatik" => "I",
            "Musik" => "M",
            "Soziale Arbeit" => "SA",
            "Technik & Architektur" => "TA",
            "Wirtschaft" => "W"
        ];
    }

    public function rebuildRepositoryLocationsTable(array $locations_settings) : int
    {
        $old_locations = $this->locations_repository->fetchAllLocations();
        $this->locations_repository->purgeLocationTable();

        $this->fillRepositoryLocationsTable($locations_settings);
        $new_locations = $this->locations_repository->fetchAllLocations();

        $diff = count($new_locations) - count($old_locations);

        return $diff;
    }

    private function fetchRefIdForObjTitle(int $root_ref_id, string $searched_obj_title) : ?int
    {
        foreach ($this->tree->getChildsByType($root_ref_id, 'cat') as $child_node) {
            $child_ref = $child_node['child'];
            $obj_id = \ilObject::_lookupObjectId($child_ref);
            if (\ilObject::_lookupTitle($obj_id) == $searched_obj_title) {
                return $child_ref;
            }
        }

        return null;
    }

    private function getMappedDepartmentName(string $ilias_department_cat) : string
    {
        if (isset($this->hard_coded_department_mapping[$ilias_department_cat])) {
            return $this->hard_coded_department_mapping[$ilias_department_cat];
        } else {
            return $ilias_department_cat;
        }
    }

    private function fillRepositoryLocationsTable(array $locations_settings) : void
    {
        $repository_root_ref_id = 1;
        foreach ($locations_settings['departments'] as $department) {
            $department_ref_id = $this->fetchRefIdForObjTitle($repository_root_ref_id, $department);
            if ($department_ref_id) {
                foreach ($locations_settings['kinds'] as $kind) {
                    $kind_ref_id = $this->fetchRefIdForObjTitle($department_ref_id, $kind);
                    if ($kind_ref_id) {
                        foreach ($locations_settings['years'] as $year) {
                            $destination_ref_id = $this->fetchRefIdForObjTitle($kind_ref_id, $year);
                            if ($destination_ref_id) {
                                $this->locations_repository->addNewLocation($this->getMappedDepartmentName($department), $kind, $year, $destination_ref_id);
                            }
                        }
                    }
                }
            }
        }
    }
}

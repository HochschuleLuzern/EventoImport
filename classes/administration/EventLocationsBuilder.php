<?php declare(strict_types = 1);

namespace EventoImport\administration;

use EventoImport\config\EventLocationsRepository;

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
        $old_locations = $this->locations_repository->getAllLocationsAsTableRows();
        $this->locations_repository->purgeLocationTable();

        $this->fillRepositoryLocationsTable($locations_settings);
        $new_locations = $this->locations_repository->getAllLocationsAsTableRows();

        $diff = count($new_locations) - count($old_locations);

        return $diff;
    }

    public function buildCategoryObjectsForConfiguredKindAndYears(array $locations_settings) : array
    {
        $repository_root_ref_id = 1;

        $existing_locations = [];
        $newly_created_locations = [];

        foreach ($locations_settings['departments'] as $department) {
            $department_ref_id = $this->fetchRefIdForObjTitle($repository_root_ref_id, $department);
            if ($department_ref_id) {
                foreach ($locations_settings['kinds'] as $kind) {
                    $kind_ref_id = $this->fetchRefIdForObjTitle($department_ref_id, $kind);
                    if (is_null($kind_ref_id)) {
                        $kind_ref_id = $this->createCategoryObject($department_ref_id, $kind);
                        $newly_created_locations[] = strip_tags("$department/$kind");

                        foreach ($locations_settings['years'] as $year) {
                            $this->createCategoryObject($kind_ref_id, $year);
                            $newly_created_locations[] = strip_tags("$department/$kind/$year");
                        }
                    } else {
                        foreach ($locations_settings['years'] as $year) {
                            $destination_ref_id = $this->fetchRefIdForObjTitle($kind_ref_id, $year);
                            if (is_null($destination_ref_id)) {
                                $this->createCategoryObject($kind_ref_id, $year);
                                $newly_created_locations[] = strip_tags("$department/$kind/$year");
                            } else {
                                $existing_locations[] = strip_tags("$department/$kind/$year");
                            }
                        }
                    }
                }
            }
        }

        return ['existing' => $existing_locations, 'new' => $newly_created_locations];
    }

    public function getListOfMissingLocations(array $locations_settings) : array
    {
        $repository_root_ref_id = 1;

        $missing_locations = [];
        foreach ($locations_settings['departments'] as $department) {
            $department_ref_id = $this->fetchRefIdForObjTitle($repository_root_ref_id, $department);
            if (!is_null($department_ref_id)) {
                foreach ($locations_settings['kinds'] as $kind) {
                    $kind_ref_id = $this->fetchRefIdForObjTitle($department_ref_id, $kind);
                    if (!is_null($kind_ref_id)) {
                        foreach ($locations_settings['years'] as $year) {
                            $destination_ref_id = $this->fetchRefIdForObjTitle($kind_ref_id, $year);
                            if (is_null($destination_ref_id)) {
                                $missing_locations[] = strip_tags("/$department/$kind/$year");
                            }
                        }
                    } else {
                        $missing_locations[] = strip_tags("/$department/$kind/*");
                    }
                }
            } else {
                $missing_locations[] = strip_tags("/$department/*");
            }
        }

        return $missing_locations;
    }

    private function createCategoryObject(int $parent_ref_id, string $category_title) : int
    {
        /*
         * The code below are some lines taken from ilObjectGUI and ilObjCategoryGUI which are used to create a new
         * cateogry object. This is because at the writing of this code, there is no ILIAS-Object-Factory class in the
         * ILIAS-Core. Or at least not something that I'm aware of.
         */

        // Create new Category object
        $new_category = new \ilObjCategory();
        $new_category->setType('cat');
        $new_category->setTitle($category_title);
        $new_category->setDescription('');
        $new_category->create();

        // Put in repository tree
        $new_category->createReference();
        $new_category->putInTree($parent_ref_id);
        $new_category->setPermissions($parent_ref_id);

        // default: sort by title
        include_once('Services/Container/classes/class.ilContainerSortingSettings.php');
        $settings = new \ilContainerSortingSettings($new_category->getId());
        $settings->setSortMode(\ilContainer::SORT_TITLE);
        $settings->save();

        try {
            // inherit parents content style, if not individual
            $parent_id = \ilObject::_lookupObjId($parent_ref_id);
            include_once("./Services/Style/Content/classes/class.ilObjStyleSheet.php");
            $style_id = \ilObjStyleSheet::lookupObjectStyle($parent_id);
            if ($style_id > 0) {
                if (\ilObjStyleSheet::_lookupStandard($style_id)) {
                    \ilObjStyleSheet::writeStyleUsage($new_category->getId(), $style_id);
                }
            }
        } catch (\Exception $e) {
        }

        return (int) $new_category->getRefId();
    }

    private function fetchRefIdForObjTitle(int $root_ref_id, string $searched_obj_title) : ?int
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
                                $this->locations_repository->addNewLocation($this->getMappedDepartmentName($department), $kind, (int) $year, $destination_ref_id);
                            }
                        }
                    }
                }
            }
        }
    }
}

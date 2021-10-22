<?php

/**
 * Class ilEventoImportConfigGUI
 */
class ilEventoImportConfigGUI extends ilPluginConfigGUI
{
    private const ALLOWED_PATH_SCHEMA_ELEMENTS = ['department', 'kind', 'year'];

    private $settings;
    private $tree;
    private $tpl;

    public function __construct()
    {
        global $DIC;
        $this->settings = new ilSetting("crevento");
        $this->tree = $DIC->repositoryTree();
        $this->tpl = $DIC->ui()->mainTemplate();
    }

    private function pathSchemaToPathElements(string $path_schema)
    {
        $path_elements = [];
        foreach(explode('/', $path_schema) as $schema_part) {
            $without_braces = trim($schema_part, '{}');
            if(in_array($without_braces, self::ALLOWED_PATH_SCHEMA_ELEMENTS)) {
                $path_schema[] = $without_braces;
            }
        }
    }

    private function fetchRefIdForObjTitle(int $root_ref_id, string $searched_obj_title) : ?int
    {
        foreach($this->tree->getChildsByType($root_ref_id, 'cat') as $child_node) {
            $child_ref = $child_node['child'];
            $obj_id = ilObject::_lookupObjectId($child_ref);
            if(ilObject::_lookupTitle($obj_id) == $searched_obj_title) {
                return $child_ref;
            }
        }

        return null;
    }

    private function reloadRepositoryLocations()
    {
        global $DIC;

        $json_settings = $this->settings->get('crevento_location_settings');
        $locations_settings = json_decode($json_settings, true);

        //$path_schema = $locations_settings['path'];
        //$path_elements = $this->pathSchemaToPathElements($path_schema);

        $location_repository = new \EventoImport\import\db\repository\EventLocationsRepository($DIC->database());
        $location_repository->purgeLocationTable();
        $repository_root_ref_id = 1;
        foreach($locations_settings['departments'] as $department) {

            $department_ref_id = $this->fetchRefIdForObjTitle($repository_root_ref_id, $department);
            if($department_ref_id) {
                foreach ($locations_settings['kinds'] as $kind) {

                    $kind_ref_id = $this->fetchRefIdForObjTitle($department_ref_id, $kind);
                    if($kind_ref_id) {
                        foreach ($locations_settings['years'] as $year) {

                            $destination_ref_id = $this->fetchRefIdForObjTitle($kind_ref_id, $year);
                            if($destination_ref_id) {
                                $location_repository->addNewLocation($department, $kind, $year, $destination_ref_id);
                            }
                        }
                    }
                }
            }
        }
    }

    public function performCommand($cmd)
    {
        switch($cmd) {
            case 'configure':
                global $DIC;
                $link = $DIC->ctrl()->getLinkTarget($this, 'reload_repo_locations');
                $link_btn = $DIC->ui()->factory()->link()->standard('Reload Repository Locations', $link);
                $this->tpl->setContent($DIC->ui()->renderer()->render($link_btn));
                break;

            case 'reload_repo_locations':
                $this->reloadRepositoryLocations();
                break;
        }
    }
}
<?php

use ILIAS\DI\RBACServices;

/**
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */
class ilEventoImportImportUsersConfig
{
    private $imports = [
        'Studierende',
        'Mitarbeitende'
    ];
    
    private $settings_structure = [
        'import' => [
            'operation' => 'Text',
            'selector' => 'Text',
            'additional_roles' => 'Select'
        ],
        'convert' => [
            'operation' => 'Text',
            'deactivate' => 'Bool'
        ]
    ];
    
    private function additional_roles_options (string $operation, string $parameter_name) {
        $global_roles = $this->rbac->review()->getGlobalRoles();
        $global_roles_with_title = [];
        
        foreach ($global_roles as $role_id) {
            $global_roles_with_title[$role_id] = ilObject2::_lookupTitle($role_id);
        }
        
        return $global_roles_with_title;
    }
       
    private $possible_durations = ["", "_max"];
    
    private $settings;
    private $rbac;
    
    private $ilias_auth_mode;
    private $standard_user_role_id;

    public function __construct(ilSetting $settings, RBACServices $rbac) {
        $this->settings = $settings;
        $this->rbac = $rbac;
        $this->setupDurations();
        $this->ilias_auth_mode = $settings->get('crevento_ilias_auth_mode');
        $this->standard_user_role_id = $settings->get('crevento_standard_user_role_id');
    }
    
    /*
     * @return String[]
     */
    public function getImportTypes() : array {
        return $this->imports;
    }
    
    /*
     * @return String[]
     */
    public function getOperations() : array {
        return array_keys($this->settings_structure);
    }
    
    /*
     * @return String[]
     */
    private function getFunctionParameterNamesForOperation(string $operation) : array {
        return $this->settings_structure[$operation];
    }
    
    public function getSettingsName(string $import_type, string $operation, string $parameter_name) : string {
        return 'crevento_'.$operation.'_'.$parameter_name.'_'.$import_type;
    }
    
    public function getFunctionParametersForOperation(string $operation, string $import_type) : array {
        $parameters = [];
        foreach ($this->getFunctionParameterNamesForOperation($operation) as $parameter_name => $parameter_type) {
            $parameters[$parameter_name]['type'] = $parameter_type;
            $parameters[$parameter_name]['value'] = $this->settings->get(
                $this->getSettingsName($import_type, $operation, $parameter_name), '');
            if ($parameter_type == 'Select') {
                $parameters[$parameter_name]['value'] = explode(',', $parameters[$parameter_name]['value']);
                $parameters[$parameter_name]['options'] = $this->{$parameter_name.'_options'}($operation, $parameter_name);
            }
        }
        return $parameters;
    }
    
    public function setupDurations () {
        $durations_array = [];
        foreach ( $this->possible_durations as $duration) {
            if ($this->settings->get('crevento'.$duration.'_account_duration') != 0 ) {
                $value = mktime(date('H'), date('i'), date('s'), date('n') + ($this->settings->get('crevento'.$duration.'_account_duration')% 12), date('j'), date('Y')+ (intdiv($this->settings->get('crevento'.$duration.'_account_duration'), 12)));
                $durations_array['until'.$duration] = $value;
            } else {
                $durations_array['until'.$duration] = 0;
            }
        }
        return $durations_array;
    }
    
    public function getIliasAuthMode() {
        return $this->ilias_auth_mode;
    }
    
    public function getStandardUserRoleId() {
        return $this->standard_user_role_id;
    }
}
?>
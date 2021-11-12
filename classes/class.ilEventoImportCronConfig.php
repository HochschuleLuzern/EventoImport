<?php

class ilEventoImportCronConfig
{
    const LANG_HEADER_API_SETTINGS = 'api_settings';
    const LANG_API_URI = 'api_uri';
    const LANG_API_URI_DESC = 'api_uri_desc';
    const LANG_API_AUTH_TOKEN = 'auth_token';
    const LANG_API_AUTH_TOKEN_DESC = 'auth_token_desc';
    const LANG_API_PAGE_SIZE = 'api_page_size';
    const LANG_API_PAGE_SIZE_DESC = 'api_page_size_desc';
    const LANG_API_MAX_PAGES = 'api_max_pages';
    const LANG_API_MAX_PAGES_DESC = 'api_max_pages_desc';
    const LANG_API_TIMEOUT_AFTER_REQUEST = 'api_timeout_after_request';
    const LANG_API_TIMEOUT_AFTER_REQUEST_DESC = 'api_timeout_after_request_desc';
    const LANG_API_TIMEOUT_FAILED_REQUEST = 'api_timeout_failed_request';
    const LANG_API_TIMEOUT_FAILED_REQUEST_DESC = 'api_timeout_failed_request_desc';
    const LANG_API_MAX_RETRIES = 'api_max_retries';
    const LANG_API_MAX_RETRIES_DESC = 'api_max_retries_desc';
    const LANG_HEADER_USER_SETTINGS = 'user_import_settings';
    const LANG_USER_IMPORT_ACC_DURATION = 'user_import_account_duration';
    const LANG_USER_IMPORT_ACC_DURATION_DESC = 'user_import_account_duration_desc';
    const LANG_USER_MAX_ACC_DURATION = 'user_max_account_duration';
    const LANG_USER_MAX_ACC_DURATION_DESC = 'user_max_account_duration_desc';
    const LANG_DEFAULT_USER_ROLE = 'default_user_role';
    const LANG_DEFAULT_USER_ROLE_DESC = 'default_user_role_desc';
    const LANG_HEADER_USER_ADDITIONAL_ROLE_MAPPING = 'additional_user_roles_mapping';
    const LANG_ROLE_MAPPING_TO = 'maps_to';
    const LANG_HEADER_EVENT_LOCATIONS = 'location_settings';
    const LANG_DEPARTMENTS = 'location_departments';
    const LANG_KINDS = 'location_kinds';
    const LANG_YEARS = 'location_years';
    const LANG_HEADER_EVENT_SETTINGS = 'event_import_settings';
    const LANG_EVENT_OBJECT_OWNER = 'object_owner';
    const LANG_EVENT_OPT_OWNER_ROOT = 'owner_root_user';
    const LANG_EVENT_OPT_OWNER_FIRST_ADMIN = 'owner_first_admin_in_event';
    const LANG_EVENT_OPT_OWNER_CUSTOM_USER = 'owner_custom_user';
    const LANG_EVENT_OPT_OWNER_CUSTOM_ID = 'object_owner_id';

    const FORM_API_URI = 'crevento_api_uri';
    const FORM_API_AUTH_TOKEN = 'crevento_api_auth_token';
    const FORM_API_PAGE_SIZE = 'crevento_api_page_size';
    const FORM_API_MAX_PAGES = 'crevento_api_max_pages';
    const FORM_API_TIMEOUT_AFTER_REQUEST = 'crevento_api_timeout_after_request';
    const FORM_API_TIMEOUT_FAILED_REQUEST = 'crevento_api_timeout_failed_request';
    const FORM_API_MAX_RETRIES = 'crevento_api_max_retries';
    const FORM_USER_IMPORT_ACC_DURATION = 'crevento_user_import_acc_duration';
    const FORM_USER_MAX_ACC_DURATION = 'crevento_user_max_acc_duration';
    const FORM_DEFAULT_USER_ROLE = 'crevento_default_user_role';
    const FORM_USER_GLOBAL_ROLE_ = 'crevento_global_role_';
    const FORM_USER_EVENTO_ROLE_MAPPED_TO_ = 'crevento_map_from_';
    const FORM_DEPARTEMTNS = 'crevento_departments';
    const FORM_KINDS = 'crevento_kinds';
    const FORM_YEARS = 'crevento_years';
    const FORM_EVENT_OBJECT_OWNER = 'crevento_object_owner';
    const FORM_EVENT_OPT_OWNER_ROOT = 'crevento_object_owner_root';
    const FORM_EVENT_OPT_OWNER_FIRST_ADMIN = 'crevento_object_owner_first_admin';
    const FORM_EVENT_OPT_OWNER_CUSTOM_USER = 'crevento_object_owner_custom';
    const FORM_EVENT_OPT_OWNER_CUSTOM_ID = 'crevento_object_owner_custom_id';

    const CONF_API_URI = 'crevento_api_uri';
    const CONF_API_AUTH_TOKEN = 'crevento_api_auth_token';
    const CONF_API_PAGE_SIZE = 'crevento_api_page_size';
    const CONF_API_MAX_PAGES = 'crevento_api_max_pages';
    const CONF_API_TIMEOUT_AFTER_REQUEST = 'crevento_api_timeout_after_request';
    const CONF_API_TIMEOUT_FAILED_REQUEST = 'crevento_api_timeout_failed_request';
    const CONF_API_MAX_RETRIES = 'crevento_api_max_retries';
    const CONF_USER_IMPORT_ACC_DURATION = 'crevento_user_import_acc_duration';
    const CONF_USER_MAX_ACC_DURATION = 'crevento_user_max_acc_duration';
    const CONF_DEFAULT_USER_ROLE = 'crevento_default_user_role';
    const CONF_ROLES_ILIAS_EVENTO_MAPPING = 'crevento_roles_ilias_evento_mapping';
    const CONF_LOCATIONS = 'crevento_location_settings';
    const CONF_KEY_DEPARTMENTS = 'departments';
    const CONF_KEY_KINDS = 'kinds';
    const CONF_KEY_YEARS = 'years';
    const CONF_EVENT_OPT_OWNER_CUSTOM_ID = 'crevento_object_owner_id';
    const CONF_EVENT_OBJECT_OWNER = 'crevento_object_owner';

    private $settings;
    private $cp;
    private $rbac;

    public function __construct(ilSetting $settings, ilPlugin $plugin, \ILIAS\DI\RBACServices $rbac = null)
    {
        global $DIC;

        $this->settings = $settings;
        $this->cp = $plugin;
        $this->rbac = $rbac ?? $DIC->rbac();
    }

    public function fillCronJobSettingsForm(ilPropertyFormGUI $a_form)
    {
        /***************************
         * API Settings
         ***************************/
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->cp->txt(self::LANG_HEADER_API_SETTINGS));
        $a_form->addItem($header);

        $ws_item = new ilUriInputGUI(
            $this->cp->txt(self::LANG_API_URI),
            self::FORM_API_URI //'crevento_api_uri'
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_URI_DESC));
        $ws_item->setRequired(true);
        $ws_item->setValue($this->settings->get(self::CONF_API_URI, ''));
        $a_form->addItem($ws_item);

        $ws_item = new ilTextInputGUI(
            $this->cp->txt(self::LANG_API_AUTH_TOKEN),
            self::FORM_API_AUTH_TOKEN //'crevento_api_auth_toke'
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_AUTH_TOKEN_DESC));
        $ws_item->setRequired(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_AUTH_TOKEN, ''));
        $a_form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_PAGE_SIZE),
            self::FORM_API_PAGE_SIZE //'crevento_api_page_size'
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_PAGE_SIZE_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_PAGE_SIZE, '0'));
        $a_form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_MAX_PAGES),
            self::FORM_API_MAX_PAGES //'crevento_api_max_pages'
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_MAX_PAGES_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_MAX_PAGES, '0'));
        $a_form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_TIMEOUT_AFTER_REQUEST),
            self::FORM_API_TIMEOUT_AFTER_REQUEST //'crevento_api_timeout'
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_TIMEOUT_AFTER_REQUEST_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_TIMEOUT_AFTER_REQUEST, ''));
        $a_form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_TIMEOUT_FAILED_REQUEST),
            self::FORM_API_TIMEOUT_FAILED_REQUEST //'crevento_api_timeout'
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_TIMEOUT_FAILED_REQUEST_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_TIMEOUT_FAILED_REQUEST, ''));
        $a_form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_MAX_RETRIES),
            self::FORM_API_MAX_RETRIES //'crevento_api_max_retries'
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_MAX_RETRIES_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_MAX_RETRIES, ''));
        $a_form->addItem($ws_item);

        /***************************
         * User Import Settings
         ***************************/
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->cp->txt(self::LANG_HEADER_USER_SETTINGS));
        $a_form->addItem($header);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_USER_IMPORT_ACC_DURATION),
            self::FORM_USER_IMPORT_ACC_DURATION
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_USER_IMPORT_ACC_DURATION_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_USER_IMPORT_ACC_DURATION));
        $a_form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_USER_MAX_ACC_DURATION),
            self::FORM_USER_MAX_ACC_DURATION
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_USER_MAX_ACC_DURATION_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_USER_MAX_ACC_DURATION));
        $a_form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_DEFAULT_USER_ROLE),
            self::FORM_DEFAULT_USER_ROLE
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_DEFAULT_USER_ROLE_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_DEFAULT_USER_ROLE));
        $a_form->addItem($ws_item);

        $section = new ilFormSectionHeaderGUI();
        $section->setTitle($this->cp->txt(self::LANG_HEADER_USER_ADDITIONAL_ROLE_MAPPING));
        $a_form->addItem($section);

        $global_roles = $this->rbac->review()->getGlobalRoles();
        $globale_roles_settings = $this->settings->get(self::CONF_ROLES_ILIAS_EVENTO_MAPPING, '');
        $role_mapping = [];
        if ($globale_roles_settings) {
            $role_mapping = unserialize($globale_roles_settings);
        }

        foreach ($global_roles as $role_id) {
            $ws_item = new ilCheckboxInputGUI(
                ilObject::_lookupTitle($role_id),
                self::FORM_USER_GLOBAL_ROLE_ . "$role_id"
            );

            $mapping_input = new ilNumberInputGUI(
                $this->cp->txt(self::LANG_ROLE_MAPPING_TO),
                self::FORM_USER_EVENTO_ROLE_MAPPED_TO_ . $role_id
            );
            $mapping_input->allowDecimals(false);
            $mapping_input->setRequired(true);

            if (isset($role_mapping[$role_id])) {
                $ws_item->setValue('1');
                $mapping_input->setValue($role_mapping[$role_id]);
            }
            $ws_item->addSubItem($mapping_input);
            $a_form->addItem($ws_item);
        }

        /***************************
         * Event Location Settings
         ***************************/
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->cp->txt(self::LANG_HEADER_EVENT_LOCATIONS));
        $a_form->addItem($header);

        $json_settings = $this->settings->get(self::CONF_LOCATIONS); //'crevento_location_settings');
        $locations_settings = json_decode($json_settings, true);

        $departments = new ilTextInputGUI($this->cp->txt(self::LANG_DEPARTMENTS), self::FORM_DEPARTEMTNS);
        $departments->setMulti(true, false, true);
        if (isset($locations_settings[self::CONF_KEY_DEPARTMENTS]) && is_array($locations_settings[self::CONF_KEY_DEPARTMENTS])) {
            $departments->setValue($locations_settings[self::CONF_KEY_DEPARTMENTS]);
        }
        $a_form->addItem($departments);

        $kinds = new ilTextInputGUI($this->cp->txt(self::LANG_KINDS), self::FORM_KINDS);
        $kinds->setMulti(true, false, true);
        if (isset($locations_settings[self::CONF_KEY_KINDS]) && is_array($locations_settings[self::CONF_KEY_KINDS])) {
            $kinds->setValue($locations_settings[self::CONF_KEY_KINDS]);
        }
        $a_form->addItem($kinds);

        $years = new ilTextInputGUI($this->cp->txt(self::LANG_YEARS), self::FORM_YEARS);
        $years->setMulti(true, false, true);
        if (isset($locations_settings[self::CONF_KEY_YEARS]) && is_array($locations_settings[self::CONF_KEY_YEARS])) {
            $years->setValue($locations_settings[self::CONF_KEY_YEARS]);
        }
        $a_form->addItem($years);

        /***************************
         * Event Import Settings
         ***************************/
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->cp->txt(self::LANG_HEADER_EVENT_SETTINGS));
        $a_form->addItem($header);

        $radio = new ilRadioGroupInputGUI(
            $this->cp->txt(self::LANG_EVENT_OBJECT_OWNER),
            self::FORM_EVENT_OBJECT_OWNER //'crevento_object_owner'
        );

        $option = new ilRadioOption(
            $this->cp->txt(self::LANG_EVENT_OPT_OWNER_ROOT),
            self::FORM_EVENT_OPT_OWNER_ROOT
        );
        $radio->addOption($option);

        $option = new ilRadioOption(
            $this->cp->txt(self::LANG_EVENT_OPT_OWNER_FIRST_ADMIN),
            self::FORM_EVENT_OPT_OWNER_FIRST_ADMIN //'first_admin'
        );
        $radio->addOption($option);

        $option = new ilRadioOption(
            $this->cp->txt(self::LANG_EVENT_OPT_OWNER_CUSTOM_USER),
            self::FORM_EVENT_OPT_OWNER_CUSTOM_USER //'custom_user'
        );
        $custom_user_id = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_EVENT_OPT_OWNER_CUSTOM_ID),
            self::FORM_EVENT_OPT_OWNER_CUSTOM_ID// 'crevento_object_owner_id'
        );
        $custom_user_id->allowDecimals(false);
        $custom_user_id->setValue($this->settings->get(self::CONF_EVENT_OPT_OWNER_CUSTOM_ID), '');
        $option->addSubItem($custom_user_id);

        $radio->addOption($option);
        $radio->setValue($this->settings->get(self::CONF_EVENT_OBJECT_OWNER, self::FORM_EVENT_OPT_OWNER_ROOT));

        $a_form->addItem($radio);
    }

    private function getTextInputAndSaveIfNotNull(ilPropertyFormGUI $form, string $input_field, string $conf_key)
    {
        $value = $form->getInput($input_field);
        if (!is_null($value)) {
            $this->settings->set($conf_key, $value);
        }
    }

    private function getIntegerInputAndSaveIfNotNull(ilPropertyFormGUI $form, string $input_field, string $conf_key)
    {
        $value = (int) $form->getInput($input_field);
        if (!is_null($value)) {
            $this->settings->set($conf_key, $value);
        }
    }

    private function purifyLocationSettingsList(array $given_list)
    {
        $list_to_save = [];

        foreach ($given_list as $item_string) {
            if ($item_string != '') {
                $list_to_save[] = trim($item_string);
            }
        }

        return $list_to_save;
    }

    private function locationSettingsToJSON(ilPropertyFormGUI $a_form)
    {
        $settings_list = array(
            self::CONF_KEY_DEPARTMENTS => [],
            self::CONF_KEY_KINDS => [],
            self::CONF_KEY_YEARS => []
        );

        $settings_list[self::CONF_KEY_DEPARTMENTS] = $this->purifyLocationSettingsList($a_form->getInput(self::FORM_DEPARTEMTNS));
        $settings_list[self::CONF_KEY_KINDS] = $this->purifyLocationSettingsList($a_form->getInput(self::FORM_KINDS));
        $settings_list[self::CONF_KEY_YEARS] = $this->purifyLocationSettingsList($a_form->getInput(self::FORM_YEARS));

        return json_encode($settings_list);
    }

    public function saveCustomCronJobSettings(ilPropertyFormGUI $a_form)
    {
        /***************************
         * API Settings
         ***************************/
        $this->getTextInputAndSaveIfNotNull($a_form, self::FORM_API_URI, self::CONF_API_URI);
        $this->getTextInputAndSaveIfNotNull($a_form, self::FORM_API_AUTH_TOKEN, self::CONF_API_AUTH_TOKEN);
        $this->getIntegerInputAndSaveIfNotNull($a_form, self::FORM_API_PAGE_SIZE, self::CONF_API_PAGE_SIZE);
        $this->getIntegerInputAndSaveIfNotNull($a_form, self::FORM_API_MAX_PAGES, self::CONF_API_MAX_PAGES);
        $this->getIntegerInputAndSaveIfNotNull(
            $a_form,
            self::FORM_API_TIMEOUT_AFTER_REQUEST,
            self::CONF_API_TIMEOUT_AFTER_REQUEST
        );
        $this->getIntegerInputAndSaveIfNotNull(
            $a_form,
            self::FORM_API_TIMEOUT_FAILED_REQUEST,
            self::CONF_API_TIMEOUT_FAILED_REQUEST
        );
        $this->getIntegerInputAndSaveIfNotNull($a_form, self::FORM_API_MAX_RETRIES, self::CONF_API_MAX_RETRIES);

        /***************************
         * User Import Settings
         ***************************/
        $this->getIntegerInputAndSaveIfNotNull(
            $a_form,
            self::FORM_USER_IMPORT_ACC_DURATION,
            self::CONF_USER_IMPORT_ACC_DURATION
        );
        $this->getIntegerInputAndSaveIfNotNull(
            $a_form,
            self::FORM_USER_MAX_ACC_DURATION,
            self::CONF_USER_MAX_ACC_DURATION
        );
        $this->getIntegerInputAndSaveIfNotNull($a_form, self::FORM_DEFAULT_USER_ROLE, self::CONF_DEFAULT_USER_ROLE);

        $global_roles = $this->rbac->review()->getGlobalRoles();
        $role_mapping = [];

        foreach ($global_roles as $role_id) {
            $check_box = $a_form->getInput(self::FORM_USER_GLOBAL_ROLE_ . $role_id);
            if ($check_box == '1') {
                $mapped_role_input = (int) $a_form->getInput(self::FORM_USER_EVENTO_ROLE_MAPPED_TO_ . $role_id);
                if (!is_null($mapped_role_input)) {
                    $role_mapping[$role_id] = $mapped_role_input;
                }
            }
        }
        $this->settings->set(self::CONF_ROLES_ILIAS_EVENTO_MAPPING, serialize($role_mapping));

        /***************************
         * Event Location Settings
         ***************************/
        $location_settings = $this->locationSettingsToJSON($a_form);
        $this->settings->set(self::CONF_LOCATIONS, $location_settings);

        /***************************
         * Event Import Settings
         ***************************/
        $input_object_owner = $a_form->getInput(self::FORM_EVENT_OBJECT_OWNER);
        switch ($input_object_owner) {
            case self::FORM_EVENT_OPT_OWNER_ROOT:
                $this->settings->set(self::CONF_EVENT_OBJECT_OWNER, self::FORM_EVENT_OPT_OWNER_CUSTOM_USER);
                $this->settings->set(self::CONF_EVENT_OPT_OWNER_CUSTOM_ID, 6);
                break;
            case self::FORM_EVENT_OPT_OWNER_FIRST_ADMIN:
                $this->settings->set(self::CONF_EVENT_OBJECT_OWNER, self::FORM_EVENT_OPT_OWNER_FIRST_ADMIN);
                break;

            case self::FORM_EVENT_OPT_OWNER_CUSTOM_USER:
                $input_user_id = (int) $a_form->getInput(self::FORM_EVENT_OPT_OWNER_CUSTOM_ID);
                $this->settings->set(self::CONF_EVENT_OBJECT_OWNER, self::FORM_EVENT_OPT_OWNER_CUSTOM_USER);
                $this->settings->set(self::CONF_EVENT_OPT_OWNER_CUSTOM_ID, $input_user_id);
                break;
        }
    }
}

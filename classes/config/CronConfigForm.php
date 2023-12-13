<?php declare(strict_types=1);

namespace EventoImport\config;

use ILIAS\DI\RBACServices;
use ilSelectInputGUI;
use ilNumberInputGUI;
use ilFormSectionHeaderGUI;
use ilPropertyFormGUI;
use ilRadioGroupInputGUI;
use ilUriInputGUI;
use ilCheckboxInputGUI;
use ilTextInputGUI;
use ilRadioOption;
use ilAuthUtils;
use EventoImport\config\locations\RepositoryLocationSeeker;
use EventoImport\config\local_roles\LocalVisitorRoleManager;
use EventoImport\config\locations\BaseLocationConfiguration;

/**
 * Class ilEventoImportCronConfig
 * This class is used to separate the config part for the cron-job from the executing class (ilEventoImportImport)
 */
class CronConfigForm
{
    const LANG_HEADER_API_SETTINGS = 'api_settings';
    const LANG_API_URI = 'api_uri';
    const LANG_API_URI_DESC = 'api_uri_desc';
    const LANG_API_AUTH_KEY = 'auth_key';
    const LANG_API_AUTH_KEY_DESC = 'auth_key_desc';
    const LANG_API_AUTH_SECRET = 'auth_secret';
    const LANG_API_AUTH_SECRET_DESC = 'auth_secret_desc';
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
    const LANG_USER_AUTH_MODE = 'user_auth_mode';
    const LANG_USER_AUTH_MODE_DESC = 'user_auth_mode_desc';
    const LANG_USER_IMPORT_ACC_DURATION = 'user_import_account_duration';
    const LANG_USER_IMPORT_ACC_DURATION_DESC = 'user_import_account_duration_desc';
    const LANG_DEFAULT_USER_ROLE = 'default_user_role';
    const LANG_DEFAULT_USER_ROLE_DESC = 'default_user_role_desc';
    const LANG_HEADER_USER_ADDITIONAL_ROLE_MAPPING = 'additional_user_roles_mapping';
    const LANG_ROLE_DELETE_FROM_ADMIN_ON_REMOVAL = 'delete_from_admins_on_removal';
    const LANG_ROLE_DELETE_FROM_ADMIN_ON_REMOVAL_DESC = 'delete_from_admins_on_removal_desc';
    const LANG_ROLE_TRACK_REMOVAL_CUSTOM_FIELD = 'track_removal_custom_field';
    const LANG_ROLE_TRACK_REMOVAL_CUSTOM_FIELD_DESC = 'track_removal_custom_field_desc';
    const LANG_ROLE_MAPPING_TO = 'maps_to';
    const LANG_ROLE_MAPPING_TO_DESC = 'maps_to_desc';
    const LANG_HEADER_USER_FOLLOW_UP_ROLE_MAPPING = 'follow_up_role_mapping';
    const LANG_HEADER_USER_FOLLOW_UP_ROLE_MAPPING_DESC = 'follow_up_role_mapping_desc';

    const LANG_HEADER_EVENT_LOCATIONS = 'location_settings';
    const LANG_DEPARTMENTS = 'location_departments';
    const LANG_KINDS = 'location_kinds';

    const LANG_HEADER_EVENT_SETTINGS = 'event_import_settings';
    const LANG_EVENT_OBJECT_OWNER = 'object_owner';
    const LANG_EVENT_OBJECT_OWNER_DESC = 'object_owner_desc';
    const LANG_EVENT_OPT_OWNER_ROOT = 'owner_root_user';
    const LANG_EVENT_OPT_OWNER_CUSTOM_USER = 'owner_custom_user';
    const LANG_EVENT_OPT_OWNER_CUSTOM_ID = 'object_owner_id';
    const LANG_HEADER_VISITOR_ROLES = 'visitor_roles_settings';
    const LANG_VISITOR_ROLE_CB = 'visitor_role_cb';
    const LANG_VISITOR_DEP_API_NAME = 'visitor_dep_api_name';
    const LANG_VISITOR_DEP_API_NAME_DESC = 'visitor_dep_api_name_desc';
    const LANG_VISITOR_REF_ID = 'visitor_cat_ref_id';
    const LANG_VISITOR_REF_ID_DESC = 'visitor_cat_ref_id_desc';
    const LANG_VISITOR_ROLE_ID = 'visitor_role_id';
    const LANG_VISITOR_ROLE_ID_DESC = 'visitor_role_id_desc';
    const LANG_VISITOR_NO_ROLE_DESC = 'visitor_no_role_desc';

    const FORM_API_URI = 'crevento_api_uri';
    const FORM_API_AUTH_KEY = 'crevento_api_auth_key';
    const FORM_API_AUTH_SECRET = 'crevento_api_auth_secret';
    const FORM_API_PAGE_SIZE = 'crevento_api_page_size';
    const FORM_API_MAX_PAGES = 'crevento_api_max_pages';
    const FORM_API_TIMEOUT_AFTER_REQUEST = 'crevento_api_timeout_after_request';
    const FORM_API_TIMEOUT_FAILED_REQUEST = 'crevento_api_timeout_failed_request';
    const FORM_API_MAX_RETRIES = 'crevento_api_max_retries';
    const FORM_USER_AUTH_MODE = 'crevento_user_auth_mode';
    const FORM_DEFAULT_USER_ROLE = 'crevento_default_user_role';
    const FORM_USER_GLOBAL_ROLE_ = 'crevento_global_role_';
    const FORM_USER_EVENTO_ROLE_MAPPED_TO_ = 'crevento_map_from_';
    const FORM_USER_EVENTO_ROLE_DELETE_FROM_ADMIN_ON_REMOVAL_ = 'crevento_delete_admin_on_removal_from_';
    const FORM_USER_EVENTO_ROLE_TRACK_REMOVAL_CUSTOM_FIELD_FOR_ = 'crevento_track_removal_custom_field_for_';
    const FORM_USER_FOLLOW_UP_ROLE_FOR_ = 'crevento_follow_up_role_for_';
    const FORM_DEPARTEMTNS = 'crevento_departments';
    const FORM_KINDS = 'crevento_kinds';
    const FORM_EVENT_OBJECT_OWNER = 'crevento_object_owner';
    const FORM_EVENT_OPT_OWNER_ROOT = 'crevento_object_owner_root';
    const FORM_EVENT_OPT_OWNER_CUSTOM_USER = 'crevento_object_owner_custom';
    const FORM_EVENT_OPT_OWNER_CUSTOM_ID = 'crevento_object_owner_custom_id';

    private DefaultUserSettings $default_user_settings;
    private DefaultEventSettings $default_event_settings;
    private ImporterApiSettings $importer_api_settings;
    private BaseLocationConfiguration $event_locations;
    private RepositoryLocationSeeker $location_seeker;
    private LocalVisitorRoleManager $local_visitor_manager;
    private \ilEventoImportPlugin $cp;
    private \ilLanguage $lng;
    private RBACServices $rbac;

    public function __construct(
        DefaultUserSettings $default_user_settings,
        DefaultEventSettings $default_event_settings,
        ImporterApiSettings $importer_api_settings,
        BaseLocationConfiguration $event_locations,
        RepositoryLocationSeeker $location_seeker,
        LocalVisitorRoleManager $local_visitor_manager,
        \ilEventoImportPlugin $plugin,
        \ilLanguage $lng,
        RBACServices $rbac
    ) {
        $this->default_user_settings = $default_user_settings;
        $this->default_event_settings = $default_event_settings;
        $this->importer_api_settings = $importer_api_settings;
        $this->event_locations = $event_locations;
        $this->location_seeker = $location_seeker;
        $this->local_visitor_manager = $local_visitor_manager;
        $this->cp = $plugin;
        $this->lng = $lng;
        $this->rbac = $rbac;
    }

    public function fillFormWithApiConfig(ilPropertyFormGUI $form)
    {
        /***************************
         * API Settings
         ***************************/
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->cp->txt(self::LANG_HEADER_API_SETTINGS));
        $form->addItem($header);

        $ws_item = new ilUriInputGUI(
            $this->cp->txt(self::LANG_API_URI),
            self::FORM_API_URI
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_URI_DESC));
        $ws_item->setRequired(true);
        $ws_item->setValue($this->importer_api_settings->getUrl());
        $form->addItem($ws_item);

        $ws_item = new ilTextInputGUI(
            $this->cp->txt(self::LANG_API_AUTH_KEY),
            self::FORM_API_AUTH_KEY
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_AUTH_KEY_DESC));
        $ws_item->setRequired(false);
        $ws_item->setValue($this->importer_api_settings->getApikey());
        $form->addItem($ws_item);

        $ws_item = new ilTextInputGUI(
            $this->cp->txt(self::LANG_API_AUTH_SECRET),
            self::FORM_API_AUTH_SECRET
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_AUTH_SECRET_DESC));
        $ws_item->setRequired(false);
        $ws_item->setValue($this->importer_api_settings->getApiSecret());
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_PAGE_SIZE),
            self::FORM_API_PAGE_SIZE
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_PAGE_SIZE_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue((string) $this->importer_api_settings->getPageSize());
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_MAX_PAGES),
            self::FORM_API_MAX_PAGES
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_MAX_PAGES_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue((string) $this->importer_api_settings->getMaxPages());
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_TIMEOUT_AFTER_REQUEST),
            self::FORM_API_TIMEOUT_AFTER_REQUEST
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_TIMEOUT_AFTER_REQUEST_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue((string) $this->importer_api_settings->getTimeoutAfterRequest());
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_TIMEOUT_FAILED_REQUEST),
            self::FORM_API_TIMEOUT_FAILED_REQUEST
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_TIMEOUT_FAILED_REQUEST_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue((string) $this->importer_api_settings->getTimeoutFailedRequest());
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_MAX_RETRIES),
            self::FORM_API_MAX_RETRIES
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_MAX_RETRIES_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue((string) $this->importer_api_settings->getMaxRetries());
        $form->addItem($ws_item);
    }

    public function fillFormWithUserImportConfig(ilPropertyFormGUI $form)
    {
        /***************************
         * User Import Settings
         ***************************/
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->cp->txt(self::LANG_HEADER_USER_SETTINGS));
        $form->addItem($header);

        $ws_item = new ilSelectInputGUI(
            $this->cp->txt(self::LANG_USER_AUTH_MODE),
            self::FORM_USER_AUTH_MODE
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_USER_AUTH_MODE_DESC));

        // The following code block to get list of auth-modes is stolen from ilObjUserGUI around line 1096 (from initForm())
        $auth_modes = \ilAuthUtils::_getActiveAuthModes();
        $options = [];
        foreach ($auth_modes as $auth_name => $auth_key) {
            if ($auth_name == 'default') {
                $name = $this->lng->txt('auth_' . $auth_name) . " (" . $this->lng->txt('auth_' . ilAuthUtils::_getAuthModeName($auth_key)) . ")";
            } else {
                $name = ilAuthUtils::getAuthModeTranslation("$auth_key", $auth_name);
            }
            $options[$auth_name] = $name;
        }
        $ws_item->setOptions($options);
        $ws_item->setValue($this->default_user_settings->getAuthMode());
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_DEFAULT_USER_ROLE),
            self::FORM_DEFAULT_USER_ROLE
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_DEFAULT_USER_ROLE_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue((string) $this->default_user_settings->getDefaultUserRoleId());
        $form->addItem($ws_item);

        $section = new ilFormSectionHeaderGUI();
        $section->setTitle($this->cp->txt(self::LANG_HEADER_USER_ADDITIONAL_ROLE_MAPPING));
        $form->addItem($section);

        $global_roles = $this->rbac->review()->getGlobalRoles();
        $role_mapping = array_flip($this->default_user_settings->getEventoCodeToIliasRoleMapping());
        $track_removal_custom_fields_mapping = $this->default_user_settings->getTrackRemovalCustomFieldsMapping();
        $delete_from_admin_when_removed_role_array = $this->default_user_settings->getDeleteFromAdminWhenRemovedFromRoleMapping();

        $custom_fields = \ilUserDefinedFields::_getInstance();
        $available_custom_fields = [0 => '--'];
        foreach ($custom_fields->getDefinitions() as $definition) {
            if ($definition['field_type'] === (string) UDF_TYPE_TEXT) {
                $available_custom_fields[$definition['field_id']] = $definition['field_name'];
            }
        }

        foreach ($global_roles as $role_id) {
            $role_title = \ilObject::_lookupTitle($role_id);
            $ws_item = new ilCheckboxInputGUI(
                $role_title,
                self::FORM_USER_GLOBAL_ROLE_ . "$role_id"
            );
            $ws_item->setValue('1');

            $mapping_input = new ilNumberInputGUI(
                $this->cp->txt(self::LANG_ROLE_MAPPING_TO),
                self::FORM_USER_EVENTO_ROLE_MAPPED_TO_ . $role_id
            );
            $mapping_input->allowDecimals(false);
            $mapping_input->setRequired(true);
            $mapping_desc = sprintf($this->cp->txt(self::LANG_ROLE_MAPPING_TO_DESC), $role_title);
            $mapping_input->setInfo($mapping_desc);

            $track_role_removal_custom_field = new \ilSelectInputGUI(
                $this->cp->txt(self::LANG_ROLE_TRACK_REMOVAL_CUSTOM_FIELD),
                self::FORM_USER_EVENTO_ROLE_TRACK_REMOVAL_CUSTOM_FIELD_FOR_ . $role_id
            );
            $track_role_removal_custom_field->setOptions($available_custom_fields);
            $track_role_removal_custom_field->setInfo($this->cp->txt(self::LANG_ROLE_TRACK_REMOVAL_CUSTOM_FIELD_DESC));
            $track_role_removal_custom_field->setValue($track_removal_custom_fields_mapping[$role_id] ?? 0);

            $delete_from_admin_input = new \ilCheckboxInputGUI(
                $this->cp->txt(self::LANG_ROLE_DELETE_FROM_ADMIN_ON_REMOVAL),
                self::FORM_USER_EVENTO_ROLE_DELETE_FROM_ADMIN_ON_REMOVAL_ . $role_id
            );
            $delete_from_admin_input->setInfo($this->cp->txt(self::LANG_ROLE_DELETE_FROM_ADMIN_ON_REMOVAL_DESC));

            if (isset($role_mapping[$role_id])) {
                $ws_item->setChecked(true);
                $mapping_input->setValue((string) $role_mapping[$role_id]);
                $delete_from_admin_input->setChecked(in_array($role_id, $delete_from_admin_when_removed_role_array));
            } else {
                $ws_item->setChecked(false);
            }

            $ws_item->addSubItem($mapping_input);
            $ws_item->addSubItem($track_role_removal_custom_field);
            $ws_item->addSubItem($delete_from_admin_input);



            $form->addItem($ws_item);
        }

        $section = new ilFormSectionHeaderGUI();
        $section->setTitle($this->cp->txt(self::LANG_HEADER_USER_FOLLOW_UP_ROLE_MAPPING));
        $section->setInfo($this->cp->txt(self::LANG_HEADER_USER_FOLLOW_UP_ROLE_MAPPING_DESC));
        $form->addItem($section);

        $follow_up_role_mapping = $this->default_user_settings->getFollowUpRoleMapping();
        foreach (array_keys($role_mapping) as $role_id) {
            $role_title = \ilObject::_lookupTitle($role_id);
            $options = [
                0 => $this->lng->txt('none')
            ];
            foreach($global_roles as $global_role_id) {
                if ($global_role_id === $role_id) {
                    continue;
                }
                $options[$global_role_id] = \ilObject::_lookupTitle($global_role_id);
            }
            $ws_item = new \ilSelectInputGUI(
                $role_title,
                self::FORM_USER_FOLLOW_UP_ROLE_FOR_ . "$role_id"
            );
            $ws_item->setOptions($options);
            $ws_item->setValue(0);
            if (array_key_exists($role_id, $follow_up_role_mapping)) {
                $ws_item->setValue($follow_up_role_mapping[$role_id]);
            }
            $form->addItem($ws_item);
        }
    }

    public function fillFormWithEventLocationConfig(ilPropertyFormGUI $form)
    {
        /***************************
         * Event Location Settings
         ***************************/
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->cp->txt(self::LANG_HEADER_EVENT_LOCATIONS));
        $form->addItem($header);

        $departments = new ilTextInputGUI($this->cp->txt(self::LANG_DEPARTMENTS), self::FORM_DEPARTEMTNS);
        $departments->setMulti(true, false, true);
        $departments->setValue($this->event_locations->getDepartmentLocationList());
        $form->addItem($departments);

        $kinds = new ilTextInputGUI($this->cp->txt(self::LANG_KINDS), self::FORM_KINDS);
        $kinds->setMulti(true, false, true);
        $kinds->setValue($this->event_locations->getKindLocationList());
        $form->addItem($kinds);
    }

    public function fillFormWithEventConfig(ilPropertyFormGUI $form)
    {
        /***************************
         * Event Import Settings
         ***************************/
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->cp->txt(self::LANG_HEADER_EVENT_SETTINGS));
        $form->addItem($header);

        $radio = new ilRadioGroupInputGUI(
            $this->cp->txt(self::LANG_EVENT_OBJECT_OWNER),
            self::FORM_EVENT_OBJECT_OWNER //'crevento_object_owner'
        );
        $radio->setInfo($this->cp->txt(self::LANG_EVENT_OBJECT_OWNER_DESC));

        $option = new ilRadioOption(
            $this->cp->txt(self::LANG_EVENT_OPT_OWNER_ROOT),
            self::FORM_EVENT_OPT_OWNER_ROOT
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
        $custom_user_id->setValue((string) $this->default_event_settings->getDefaultObjectOwnerId());
        $option->addSubItem($custom_user_id);

        $radio->addOption($option);
        $radio_value = $this->default_event_settings->getDefaultObjectOwnerId() === SYSTEM_USER_ID ?
            self::FORM_EVENT_OPT_OWNER_ROOT : self::FORM_EVENT_OPT_OWNER_CUSTOM_USER;
        $radio->setValue($radio_value);

        $form->addItem($radio);
    }

    public function fillFormWithVisitorConfig(ilPropertyFormGUI $form)
    {
        /***************************
         * Visitors Import Settings
         ***************************/
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle(self::LANG_HEADER_VISITOR_ROLES);
        $form->addItem($header);

        foreach($this->event_locations->getDepartmentLocationList() as $department_name) {
            foreach($this->event_locations->getKindLocationList() as $kind_name) {
                $ref_id = $this->location_seeker->searchRefIdOfKindCategory($department_name, $kind_name);
                if (is_null($ref_id)) {
                    continue;
                }

                $role = $this->local_visitor_manager->getLocalVisitorRoleByDepartmentAndKind($department_name, $kind_name);
                $title = htmlspecialchars("$department_name/$kind_name");
                $title = $this->cp->txt(self::LANG_VISITOR_ROLE_CB) . " \"$title\"";
                $post_var = str_replace(' ', '_',strtolower("visitors-{$department_name}-{$kind_name}"));
                $ws_item = new ilCheckboxInputGUI(
                    $title,
                    $post_var
                );
                $ws_item->setValue('1');
                $ws_item->setChecked(!is_null($role));

                $txt_item = new \ilNonEditableValueGUI($this->cp->txt(self::LANG_VISITOR_REF_ID));
                $txt_item->setValue($ref_id);
                $txt_item->setInfo($this->cp->txt(self::LANG_VISITOR_REF_ID_DESC) . ' ' . \ilLink::_getLink($ref_id, 'cat'));
                $ws_item->addSubItem($txt_item);

                $txt_item = new \ilNonEditableValueGUI($this->cp->txt(self::LANG_VISITOR_ROLE_ID));
                if (is_null($role)) {
                    $txt_item->setValue("-");
                    $txt_item->setInfo($this->cp->txt(self::LANG_VISITOR_NO_ROLE_DESC));
                } else {
                    $txt_item->setValue($role->getRoleId());
                    $txt_item->setInfo($this->cp->txt(self::LANG_VISITOR_ROLE_ID_DESC));
                }

                $ws_item->addSubItem($txt_item);

                $post_var = str_replace(' ', '_',strtolower("shortname_{$department_name}-{$kind_name}"));
                $txt_item = new ilTextInputGUI($this->cp->txt(self::LANG_VISITOR_DEP_API_NAME), $post_var);
                $txt_item->setInfo($this->cp->txt(self::LANG_VISITOR_DEP_API_NAME_DESC));
                if (!is_null($role)) {
                    $txt_item->setValue($role->getDepartmentApiName());
                }
                $ws_item->addSubItem($txt_item);

                $form->addItem($ws_item);
            }
        }
    }

    public function saveApiConfigFromForm(ilPropertyFormGUI $form) : bool
    {
        $this->importer_api_settings->setUrl($form->getInput(self::FORM_API_URI));
        $this->importer_api_settings->setApiKey($form->getInput(self::FORM_API_AUTH_KEY));
        $this->importer_api_settings->setApiSecret($form->getInput(self::FORM_API_AUTH_SECRET));
        $this->importer_api_settings->setPageSize(
            intval($form->getInput(self::FORM_API_PAGE_SIZE))
        );
        $this->importer_api_settings->setMaxPages(
            intval($form->getInput(self::FORM_API_MAX_PAGES))
        );
        $this->importer_api_settings->setTimeoutAfterRequest(
            intval($form->getInput(self::FORM_API_TIMEOUT_AFTER_REQUEST))
        );
        $this->importer_api_settings->setTimeoutFailedRequest(
            intval($form->getInput(self::FORM_API_TIMEOUT_FAILED_REQUEST))
        );
        $this->importer_api_settings->setMaxRetries(
            intval($form->getInput(self::FORM_API_MAX_RETRIES))
        );
        $this->importer_api_settings->saveCurrentConfigurationToSettings();

        return true;
    }

    public function saveUserConfigFromForm(ilPropertyFormGUI $form) : bool
    {
        $this->default_user_settings->setAuthMode($form->getInput(self::FORM_USER_AUTH_MODE));
        $this->default_user_settings->setDefaultUserRoleId(
            intval($form->getInput(self::FORM_DEFAULT_USER_ROLE))
        );
        $this->default_user_settings->saveCurrentConfigurationToSettings();

        $global_roles = $this->rbac->review()->getGlobalRoles();
        $role_mapping = [];
        $delete_admin_on_removal_from_role = [];
        $follow_up_role_mapping = [];

        foreach ($global_roles as $role_id) {
            $check_box = $form->getInput(self::FORM_USER_GLOBAL_ROLE_ . $role_id);
            if ($check_box !== '1') {
                continue;
            }

            $mapped_role_input = $form->getInput(self::FORM_USER_EVENTO_ROLE_MAPPED_TO_ . $role_id);
            $track_removal_custom_field = $form->getInput(self::FORM_USER_EVENTO_ROLE_TRACK_REMOVAL_CUSTOM_FIELD_FOR_ . $role_id);
            $delete_from_admin_on_removal = $form->getInput(self::FORM_USER_EVENTO_ROLE_DELETE_FROM_ADMIN_ON_REMOVAL_ . $role_id);

            if (in_array($mapped_role_input, $role_mapping)) {
                return false;
            }

            $role_mapping[$mapped_role_input] = $role_id;

            $follow_up_role_mapping[$role_id] = intval($form->getInput(self::FORM_USER_FOLLOW_UP_ROLE_FOR_ . $role_id) ?? '0');

            $track_removal_custom_field_mapping[$role_id] = $track_removal_custom_field;

            if ($delete_from_admin_on_removal === '1') {
                $delete_admin_on_removal_from_role[] = $role_id;
            }
        }

        $this->default_user_settings->setEventoCodeToIliasRoleMapping($role_mapping);
        $this->default_user_settings->setDeleteFromAdminWhenRemovedFromRoleMapping($delete_admin_on_removal_from_role);
        $this->default_user_settings->setTrackRemovalCustomFieldsMapping($track_removal_custom_field_mapping);
        $this->default_user_settings->setFollowUpRoleMapping($follow_up_role_mapping);
        $this->default_user_settings->saveCurrentConfigurationToSettings();

        return true;
    }

    public function saveEventLocationConfigFromForm(ilPropertyFormGUI $form) : bool
    {
        $this->event_locations->setDepartmentLocationList($form->getInput(self::FORM_DEPARTEMTNS));
        $this->event_locations->setKindLocationList($form->getInput(self::FORM_KINDS));
        $this->event_locations->saveCurrentConfigurationToSettings();
        return true;
    }

    public function saveEventConfigFromForm(ilPropertyFormGUI $form) : bool
    {
        $input_object_owner = $form->getInput(self::FORM_EVENT_OBJECT_OWNER);
        switch ($input_object_owner) {
            case self::FORM_EVENT_OPT_OWNER_ROOT:
                $this->default_event_settings->setDefaultObjectOwnerId(6);
                break;

            case self::FORM_EVENT_OPT_OWNER_CUSTOM_USER:
                $this->default_event_settings->setDefaultObjectOwnerId(
                    intval($form->getInput(self::FORM_EVENT_OPT_OWNER_CUSTOM_ID))
                );
                break;
        }
        $this->default_event_settings->saveCurrentConfigurationToSettings();
        return true;
    }

    public function saveVisitorRolesConfigForm(ilPropertyFormGUI $form) : bool
    {
        $form_input_correct = true;

        foreach($this->event_locations->getDepartmentLocationList() as $department_name) {
            foreach($this->event_locations->getKindLocationList() as $kind_name) {
                $ref_id = $this->location_seeker->searchRefIdOfKindCategory($department_name, $kind_name);
                if (is_null($ref_id)) {
                    continue;
                }

                $post_var = str_replace(' ', '_',strtolower("visitors-{$department_name}-{$kind_name}"));
                $check_box = $form->getInput($post_var);
                if ($check_box == '1') {
                    $post_var = str_replace(' ', '_',strtolower("shortname_{$department_name}-{$kind_name}"));
                    $dep_api_name = $form->getInput($post_var);
                    if ($dep_api_name == '' || $dep_api_name == null) {
                        $dep_api_name = $department_name;
                    }
                    $this->local_visitor_manager->configLocalRoleByDepartmentAndKind($department_name, $kind_name, $ref_id, $dep_api_name);
                } else {
                    $this->local_visitor_manager->unconfigLocalRoleByDepartmentAndKind($department_name, $kind_name, $ref_id);
                }
            }
        }

        return $form_input_correct;
    }
}

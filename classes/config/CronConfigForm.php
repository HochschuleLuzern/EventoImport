<?php declare(strict_types=1);

namespace EventoImport\config;

use ILIAS\DI\RBACServices;
use ilSelectInputGUI;
use ilNumberInputGUI;
use ilSetting;
use ilFormSectionHeaderGUI;
use ilPropertyFormGUI;
use ilLDAPServer;
use ilRadioGroupInputGUI;
use ilTextAreaInputGUI;
use ilUriInputGUI;
use ilCheckboxInputGUI;
use ilTextInputGUI;
use ilRadioOption;
use ilAuthUtils;
use ilObject;
use EventoImport\config\locations\RepositoryLocationSeeker;
use EventoImport\config\local_roles\LocalVisitorRoleManager;
use EventoImport\config\local_roles\LocalVisitorRoleRepository;
use EventoImport\config\local_roles\LocalVisitorRoleFactory;
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
    const LANG_USER_MAX_ACC_DURATION = 'user_max_account_duration';
    const LANG_USER_MAX_ACC_DURATION_DESC = 'user_max_account_duration_desc';
    const LANG_USER_CHANGED_MAIL_SUBJECT = 'user_changed_mail_subject';
    const LANG_USER_CHANGED_MAIL_SUBJECT_DESC = 'user_changed_mail_subject_desc';
    const LANG_USER_CHANGED_MAIL_BODY = 'user_changed_mail_body';
    const LANG_USER_CHANGED_MAIL_BODY_DESC = 'user_changed_mail_body_desc';
    const LANG_STUDENT_ROLE_ID = 'user_student_role_id';
    const LANG_STUDENT_ROLE_ID_DESC = 'user_student_role_id_desc';
    const LANG_DEFAULT_USER_ROLE = 'default_user_role';
    const LANG_DEFAULT_USER_ROLE_DESC = 'default_user_role_desc';
    const LANG_HEADER_USER_ADDITIONAL_ROLE_MAPPING = 'additional_user_roles_mapping';
    const LANG_ROLE_MAPPING_TO = 'maps_to';
    const LANG_ROLE_MAPPING_TO_DESC = 'maps_to_desc';
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
    const FORM_USER_IMPORT_ACC_DURATION = 'crevento_user_import_acc_duration';
    const FORM_USER_MAX_ACC_DURATION = 'crevento_user_max_acc_duration';
    const FORM_USER_CHANGED_MAIL_SUBJECT = 'crevento_user_changed_mail_subject';
    const FORM_USER_CHANGED_MAIL_BODY = 'crevento_user_changed_mail_body';
    const FORM_USER_STUDENT_ROLE_ID = 'crevento_student_role_id';
    const FORM_DEFAULT_USER_ROLE = 'crevento_default_user_role';
    const FORM_USER_GLOBAL_ROLE_ = 'crevento_global_role_';
    const FORM_USER_EVENTO_ROLE_MAPPED_TO_ = 'crevento_map_from_';
    const FORM_DEPARTEMTNS = 'crevento_departments';
    const FORM_KINDS = 'crevento_kinds';
    const FORM_EVENT_OBJECT_OWNER = 'crevento_object_owner';
    const FORM_EVENT_OPT_OWNER_ROOT = 'crevento_object_owner_root';
    const FORM_EVENT_OPT_OWNER_CUSTOM_USER = 'crevento_object_owner_custom';
    const FORM_EVENT_OPT_OWNER_CUSTOM_ID = 'crevento_object_owner_custom_id';

    const CONF_API_URI = 'crevento_api_uri';
    const CONF_API_AUTH_KEY = 'crevento_api_auth_key';
    const CONF_API_AUTH_SECRET = 'crevento_api_auth_secret';
    const CONF_API_PAGE_SIZE = 'crevento_api_page_size';
    const CONF_API_MAX_PAGES = 'crevento_api_max_pages';
    const CONF_API_TIMEOUT_AFTER_REQUEST = 'crevento_api_timeout_after_request';
    const CONF_API_TIMEOUT_FAILED_REQUEST = 'crevento_api_timeout_failed_request';
    const CONF_API_MAX_RETRIES = 'crevento_api_max_retries';
    const CONF_USER_AUTH_MODE = 'crevento_ilias_auth_mode';
    const CONF_USER_IMPORT_ACC_DURATION = 'crevento_user_import_acc_duration';
    const CONF_USER_MAX_ACC_DURATION = 'crevento_user_max_acc_duration';
    const CONF_USER_CHANGED_MAIL_SUBJECT = 'crevento_email_account_changed_subject';
    const CONF_USER_CHANGED_MAIL_BODY = 'crevento_email_account_changed_body';
    const CONF_USER_STUDENT_ROLE_ID = 'crevento_student_role_id';
    const CONF_DEFAULT_USER_ROLE = 'crevento_default_user_role';
    const CONF_ROLES_ILIAS_EVENTO_MAPPING = 'crevento_roles_ilias_evento_mapping';
    const CONF_EVENT_OWNER_ID = 'crevento_object_owner_id';
    const CONF_EVENT_OBJECT_OWNER = 'crevento_object_owner';

    private \ilSetting $settings;
    private \ilEventoImportPlugin $cp;
    private RBACServices $rbac;

    public function __construct(ilSetting $settings, \ilEventoImportPlugin $plugin, RBACServices $rbac)
    {
        $this->settings = $settings;
        $this->cp = $plugin;
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
        $ws_item->setValue($this->settings->get(self::CONF_API_URI, ''));
        $form->addItem($ws_item);

        $ws_item = new ilTextInputGUI(
            $this->cp->txt(self::LANG_API_AUTH_KEY),
            self::FORM_API_AUTH_KEY
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_AUTH_KEY_DESC));
        $ws_item->setRequired(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_AUTH_KEY, ''));
        $form->addItem($ws_item);

        $ws_item = new ilTextInputGUI(
            $this->cp->txt(self::LANG_API_AUTH_SECRET),
            self::FORM_API_AUTH_SECRET
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_AUTH_SECRET_DESC));
        $ws_item->setRequired(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_AUTH_SECRET, ''));
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_PAGE_SIZE),
            self::FORM_API_PAGE_SIZE
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_PAGE_SIZE_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_PAGE_SIZE, '0'));
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_MAX_PAGES),
            self::FORM_API_MAX_PAGES
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_MAX_PAGES_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_MAX_PAGES, '0'));
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_TIMEOUT_AFTER_REQUEST),
            self::FORM_API_TIMEOUT_AFTER_REQUEST
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_TIMEOUT_AFTER_REQUEST_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_TIMEOUT_AFTER_REQUEST, ''));
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_TIMEOUT_FAILED_REQUEST),
            self::FORM_API_TIMEOUT_FAILED_REQUEST
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_TIMEOUT_FAILED_REQUEST_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_TIMEOUT_FAILED_REQUEST, ''));
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_API_MAX_RETRIES),
            self::FORM_API_MAX_RETRIES
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_API_MAX_RETRIES_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_API_MAX_RETRIES, ''));
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

        global $DIC;
        $lng = $DIC->language();
        // The following code block to get list of auth-modes is stolen from ilObjUserGUI around line 1096 (from initForm())
        $auth_modes = \ilAuthUtils::_getActiveAuthModes();
        $options = [];
        foreach ($auth_modes as $auth_name => $auth_key) {
            if ($auth_name == 'default') {
                $name = $lng->txt('auth_' . $auth_name) . " (" . $lng->txt('auth_' . ilAuthUtils::_getAuthModeName($auth_key)) . ")";

            } else {
                $name = ilAuthUtils::getAuthModeTranslation("$auth_key", $auth_name);
            }
            $options[$auth_name] = $name;
        }
        $ws_item->setOptions($options);
        $ws_item->setValue($this->settings->get(self::CONF_USER_AUTH_MODE));
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_USER_IMPORT_ACC_DURATION),
            self::FORM_USER_IMPORT_ACC_DURATION
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_USER_IMPORT_ACC_DURATION_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_USER_IMPORT_ACC_DURATION));
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_USER_MAX_ACC_DURATION),
            self::FORM_USER_MAX_ACC_DURATION
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_USER_MAX_ACC_DURATION_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_USER_MAX_ACC_DURATION));
        $form->addItem($ws_item);

        $ws_item = new ilTextInputGUI(
            $this->cp->txt(self::LANG_USER_CHANGED_MAIL_SUBJECT),
            self::FORM_USER_CHANGED_MAIL_SUBJECT
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_USER_CHANGED_MAIL_SUBJECT_DESC));
        $ws_item->setRequired(true);
        $ws_item->setValue($this->settings->get(self::CONF_USER_CHANGED_MAIL_SUBJECT, ''));
        $form->addItem($ws_item);

        $ws_item = new ilTextAreaInputGUI(
            $this->cp->txt(self::LANG_USER_CHANGED_MAIL_BODY),
            self::FORM_USER_CHANGED_MAIL_BODY
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_USER_CHANGED_MAIL_BODY_DESC));
        $ws_item->setRequired(true);
        $ws_item->setValue($this->settings->get(self::CONF_USER_CHANGED_MAIL_BODY, ''));
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_STUDENT_ROLE_ID),
            self::FORM_USER_STUDENT_ROLE_ID
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_STUDENT_ROLE_ID_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_USER_STUDENT_ROLE_ID));
        $form->addItem($ws_item);

        $ws_item = new ilNumberInputGUI(
            $this->cp->txt(self::LANG_DEFAULT_USER_ROLE),
            self::FORM_DEFAULT_USER_ROLE
        );
        $ws_item->setInfo($this->cp->txt(self::LANG_DEFAULT_USER_ROLE_DESC));
        $ws_item->setRequired(true);
        $ws_item->allowDecimals(false);
        $ws_item->setValue($this->settings->get(self::CONF_DEFAULT_USER_ROLE));
        $form->addItem($ws_item);

        $section = new ilFormSectionHeaderGUI();
        $section->setTitle($this->cp->txt(self::LANG_HEADER_USER_ADDITIONAL_ROLE_MAPPING));
        $form->addItem($section);

        $global_roles = $this->rbac->review()->getGlobalRoles();
        $globale_roles_settings = $this->settings->get(self::CONF_ROLES_ILIAS_EVENTO_MAPPING, null);
        $role_mapping = !is_null($globale_roles_settings) ? json_decode($globale_roles_settings, true) : null;
        if (is_null($role_mapping) || !is_array($role_mapping)) {
            $role_mapping = [];
        }

        foreach ($global_roles as $role_id) {
            $role_title = ilObject::_lookupTitle($role_id);
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

            if (isset($role_mapping[$role_id])) {
                $ws_item->setChecked(true);
                $mapping_input->setValue((string) $role_mapping[$role_id]);
            } else {
                $ws_item->setChecked(false);
            }

            $ws_item->addSubItem($mapping_input);
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

        $locations = new BaseLocationConfiguration($this->settings);

        $departments = new ilTextInputGUI($this->cp->txt(self::LANG_DEPARTMENTS), self::FORM_DEPARTEMTNS);
        $departments->setMulti(true, false, true);
        $departments->setValue($locations->getDepartmentLocationList());
        $form->addItem($departments);

        $kinds = new ilTextInputGUI($this->cp->txt(self::LANG_KINDS), self::FORM_KINDS);
        $kinds->setMulti(true, false, true);
        $kinds->setValue($locations->getKindLocationList());
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
        $custom_user_id->setValue($this->settings->get(self::CONF_EVENT_OWNER_ID, ''));
        $option->addSubItem($custom_user_id);

        $radio->addOption($option);
        $radio->setValue($this->settings->get(self::CONF_EVENT_OBJECT_OWNER, self::FORM_EVENT_OPT_OWNER_ROOT));

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

        $locations = new BaseLocationConfiguration($this->settings);

        global $DIC;
        $tree = $DIC->repositoryTree();

        $local_role_manager = new LocalVisitorRoleManager(new LocalVisitorRoleRepository($DIC->database()),new LocalVisitorRoleFactory($DIC->rbac()),$DIC->rbac());
        $location_seeker = new RepositoryLocationSeeker($tree, 1);
        foreach($locations->getDepartmentLocationList() as $department_name) {
            foreach($locations->getKindLocationList() as $kind_name) {
                $ref_id = $location_seeker->searchRefIdOfKindCategory($department_name, $kind_name);
                if(!is_null($ref_id)) {
                    $role = $local_role_manager->getLocalVisitorRoleByDepartmentAndKind($department_name, $kind_name);
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
                    if(is_null($role)) {
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
                    if(!is_null($role)) {
                        $txt_item->setValue($role->getDepartmentApiName());
                    }
                    $ws_item->addSubItem($txt_item);

                    $form->addItem($ws_item);
                }
            }
        }
    }

    public function saveApiConfigFromForm(ilPropertyFormGUI $form) : bool
    {
        $form_input_correct = true;

        $this->getTextInputAndSaveIfNotNull($form, self::FORM_API_URI, self::CONF_API_URI);
        $this->getTextInputAndSaveIfNotNull($form, self::FORM_API_AUTH_KEY, self::CONF_API_AUTH_KEY);
        $this->getTextInputAndSaveIfNotNull($form, self::FORM_API_AUTH_SECRET, self::CONF_API_AUTH_SECRET);
        $this->getIntegerInputAndSaveIfNotNull($form, self::FORM_API_PAGE_SIZE, self::CONF_API_PAGE_SIZE);
        $this->getIntegerInputAndSaveIfNotNull($form, self::FORM_API_MAX_PAGES, self::CONF_API_MAX_PAGES);
        $this->getIntegerInputAndSaveIfNotNull(
            $form,
            self::FORM_API_TIMEOUT_AFTER_REQUEST,
            self::CONF_API_TIMEOUT_AFTER_REQUEST
        );
        $this->getIntegerInputAndSaveIfNotNull(
            $form,
            self::FORM_API_TIMEOUT_FAILED_REQUEST,
            self::CONF_API_TIMEOUT_FAILED_REQUEST
        );
        $this->getIntegerInputAndSaveIfNotNull($form, self::FORM_API_MAX_RETRIES, self::CONF_API_MAX_RETRIES);

        return $form_input_correct;
    }

    public function saveUserConfigFromForm(ilPropertyFormGUI $form) : bool
    {
        $form_input_correct = true;

        if ($form->getInput(self::FORM_USER_AUTH_MODE) != null) {
            $this->settings->set(self::CONF_USER_AUTH_MODE, $form->getInput(self::FORM_USER_AUTH_MODE));
        }

        $this->getIntegerInputAndSaveIfNotNull(
            $form,
            self::FORM_USER_IMPORT_ACC_DURATION,
            self::CONF_USER_IMPORT_ACC_DURATION
        );
        $this->getIntegerInputAndSaveIfNotNull(
            $form,
            self::FORM_USER_MAX_ACC_DURATION,
            self::CONF_USER_MAX_ACC_DURATION
        );
        $this->getTextInputAndSaveIfNotNull(
            $form,
            self::FORM_USER_CHANGED_MAIL_SUBJECT,
            self::CONF_USER_CHANGED_MAIL_SUBJECT
        );
        $this->getTextInputAndSaveIfNotNull(
            $form,
            self::FORM_USER_CHANGED_MAIL_BODY,
            self::CONF_USER_CHANGED_MAIL_BODY
        );

        $this->getIntegerInputAndSaveIfNotNull($form, self::FORM_USER_STUDENT_ROLE_ID, self::CONF_USER_STUDENT_ROLE_ID);
        $this->getIntegerInputAndSaveIfNotNull($form, self::FORM_DEFAULT_USER_ROLE, self::CONF_DEFAULT_USER_ROLE);

        $global_roles = $this->rbac->review()->getGlobalRoles();
        $role_mapping = [];
        $save_global_role_mapping = true;

        foreach ($global_roles as $role_id) {
            $check_box = $form->getInput(self::FORM_USER_GLOBAL_ROLE_ . $role_id);
            if ($check_box == '1') {
                $mapped_role_input = (int) $form->getInput(self::FORM_USER_EVENTO_ROLE_MAPPED_TO_ . $role_id);
                if (!is_null($mapped_role_input) && !in_array($mapped_role_input, $role_mapping)) {
                    $role_mapping[$role_id] = $mapped_role_input;
                } elseif (in_array($mapped_role_input, $role_mapping)) {
                    $form_input_correct = false;
                    $save_global_role_mapping = false;
                }
            }
        }
        if ($save_global_role_mapping) {
            $this->settings->set(self::CONF_ROLES_ILIAS_EVENTO_MAPPING, json_encode($role_mapping));
        }

        return $form_input_correct;
    }

    public function saveEventLocationConfigFromForm(ilPropertyFormGUI $form) : bool
    {
        $form_input_correct = true;

        $location_settings = new BaseLocationConfiguration($this->settings);

        $location_settings->setDepartmentLocationList($form->getInput(self::FORM_DEPARTEMTNS));
        $location_settings->setKindLocationList($form->getInput(self::FORM_KINDS));

        $location_settings->saveCurrentConfigurationToSettings();

        return $form_input_correct;
    }

    public function saveEventConfigFromForm(ilPropertyFormGUI $form) : bool
    {
        $form_input_correct = true;

        $input_object_owner = $form->getInput(self::FORM_EVENT_OBJECT_OWNER);
        switch ($input_object_owner) {
            case self::FORM_EVENT_OPT_OWNER_ROOT:
                $this->settings->set(self::CONF_EVENT_OBJECT_OWNER, self::FORM_EVENT_OPT_OWNER_ROOT);
                $this->settings->set(self::CONF_EVENT_OWNER_ID, "6");
                break;

            case self::FORM_EVENT_OPT_OWNER_CUSTOM_USER:
                $input_user_id = (int) $form->getInput(self::FORM_EVENT_OPT_OWNER_CUSTOM_ID);
                $this->settings->set(self::CONF_EVENT_OBJECT_OWNER, self::FORM_EVENT_OPT_OWNER_CUSTOM_USER);
                $this->settings->set(self::CONF_EVENT_OWNER_ID, "$input_user_id");
                break;
        }

        return $form_input_correct;
    }

    public function saveVisitorRolesConfigForm(ilPropertyFormGUI $form) : bool
    {
        $form_input_correct = true;

        $locations = new BaseLocationConfiguration($this->settings);

        global $DIC;
        $tree = $DIC->repositoryTree();

        $location_seeker = new RepositoryLocationSeeker($tree, 1);
        $local_role_manager = new LocalVisitorRoleManager(new LocalVisitorRoleRepository($DIC->database()),new LocalVisitorRoleFactory($DIC->rbac()),$DIC->rbac());
        foreach($locations->getDepartmentLocationList() as $department_name) {
            foreach($locations->getKindLocationList() as $kind_name) {
                $ref_id = $location_seeker->searchRefIdOfKindCategory($department_name, $kind_name);
                if(!is_null($ref_id)) {

                    $post_var = str_replace(' ', '_',strtolower("visitors-{$department_name}-{$kind_name}"));
                    $check_box = $form->getInput($post_var);
                    if($check_box == '1') {
                        $post_var = str_replace(' ', '_',strtolower("shortname_{$department_name}-{$kind_name}"));
                        $dep_api_name = $form->getInput($post_var);
                        if($dep_api_name == '' || $dep_api_name == null) {
                            $dep_api_name = $department_name;
                        }
                        $local_role_manager->configLocalRoleByDepartmentAndKind($department_name, $kind_name, $ref_id, $dep_api_name);
                    } else {
                        $local_role_manager->unconfigLocalRoleByDepartmentAndKind($department_name, $kind_name, $ref_id);
                    }
                }
            }
        }

        return $form_input_correct;
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
            $this->settings->set($conf_key, "$value");
        }
    }
}

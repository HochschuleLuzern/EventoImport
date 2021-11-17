<?php

namespace EventoImport\import\settings;

use ILIAS\UI\Implementation\Component\Input\Field\DateTime;

class DefaultUserSettings
{
    private $settings;

    private $now;
    private $auth_mode;
    private $is_profile_public;
    private $is_profile_picture_public;
    private $is_mail_public;
    private $default_user_role_id;
    private $acc_duration_after_import;
    private $max_acc_duration;
    private $default_hits_per_page;
    private $default_show_users_online;
    private $mail_incoming_type;
    private $evento_to_ilias_role_mapping;
    private $assignable_roles;
    private $ilias_to_evento_role_mapping;

    public function __construct(\ilSetting $settings)
    {
        $this->settings = $settings;

        $this->assignable_roles = array();

        $this->default_user_role_id = $settings->get(\ilEventoImportCronConfig::CONF_DEFAULT_USER_ROLE);
        $this->assignable_roles[] = $this->default_user_role_id;
        $this->default_hits_per_page = 100;
        $this->default_show_users_online = 'associated';

        $this->now = new \DateTime();
        $import_acc_duration_in_months = (int) $settings->get(\ilEventoImportCronConfig::CONF_USER_IMPORT_ACC_DURATION);
        $this->acc_duration_after_import = $this->addMonthsToCurrent($import_acc_duration_in_months);
        $max_acc_duration_in_months = (int) $settings->get(\ilEventoImportCronConfig::CONF_USER_MAX_ACC_DURATION);
        $this->max_acc_duration = $this->addMonthsToCurrent($max_acc_duration_in_months);

        $this->auth_mode = $settings->get(\ilEventoImportCronConfig::CONF_USER_AUTH_MODE);
        $this->is_profile_public = true;
        $this->is_profile_picture_public = true;
        $this->is_mail_public = true;
        $this->mail_incoming_type = 2;
        $role_mapping = $settings->get(\ilEventoImportCronConfig::CONF_ROLES_ILIAS_EVENTO_MAPPING);
        if (!is_null($role_mapping)) {
            $role_mapping = unserialize($role_mapping);
            $this->evento_to_ilias_role_mapping = [];
            foreach ($role_mapping as $ilias_role_id => $evento_role) {
                $this->evento_to_ilias_role_mapping[$evento_role] = $ilias_role_id;
                $this->ilias_to_evento_role_mapping[$ilias_role_id] = $evento_role;
                $this->assignable_roles[] = $ilias_role_id;
            }
        }
    }

    private function addMonthsToCurrent(int $import_acc_duration_in_months) : \DateTime
    {
        return $this->now->add(new \DateInterval('P' . $import_acc_duration_in_months . 'M'));
    }

    public function getNow() : \DateTime
    {
        return $this->now;
    }

    public function getAccDurationAfterImport() : \DateTime
    {
        return $this->acc_duration_after_import;
    }

    public function getMaxDurationOfAccounts() : \DateTime
    {
        return $this->max_acc_duration;
    }

    public function getAuthMode()
    {
        return $this->auth_mode;
    }

    public function isProfilePublic() : bool
    {
        return $this->is_profile_public;
    }

    public function isProfilePicturePublic() : bool
    {
        return $this->is_profile_picture_public;
    }

    public function isMailPublic() : bool
    {
        return $this->is_mail_public;
    }

    public function getMailIncomingType() : int
    {
        return $this->mail_incoming_type;
    }

    public function getDefaultUserRoleId() : int
    {
        return $this->default_user_role_id;
    }

    public function getDefaultHitsPerPage() : int
    {
        return $this->default_hits_per_page;
    }

    public function getDefaultShowUsersOnline() : string
    {
        return $this->default_show_users_online;
    }

    public function getEventoCodeToIliasRoleMapping()
    {
        return $this->evento_to_ilias_role_mapping;
    }
}

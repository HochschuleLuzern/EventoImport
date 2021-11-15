<?php

namespace EventoImport\import\settings;

use ILIAS\UI\Implementation\Component\Input\Field\DateTime;

class DefaultUserSettings
{
    private $settings;

    private $now_timestamp;
    private $valid_until_timestamp;
    private $valid_until_max_timestamp;
    private $auth_mode;
    private $is_profile_public;
    private $is_profile_picture_public;
    private $is_mail_public;
    private $default_user_role_id;
    private $acc_duration_after_import;
    private $max_acc_duration;
    private $default_hits_per_page;
    private $default_show_users_online;

    public function __construct(\ilSetting $settings)
    {
        $this->settings = $settings;

        $this->now_timestamp = time();
        $this->now = new \DateTime();

        $this->default_user_role_id = $settings->get(\ilEventoImportCronConfig::CONF_DEFAULT_USER_ROLE);
        $this->default_hits_per_page = 100;
        $this->default_show_users_online = 'associated';

        $import_acc_duration_in_months = (int) $settings->get(\ilEventoImportCronConfig::CONF_USER_IMPORT_ACC_DURATION);
        $this->acc_duration_after_import = $this->addMonthsToCurrent($import_acc_duration_in_months);
        //$this->acc_duration_after_import = new \DateTime(strtotime("+$import_acc_duration_in_months months", $this->now->getTimestamp())); //  $this->now->add(new \DateInterval($import_acc_duration_in_months . 'm'));
        $max_acc_duration_in_months = (int) $settings->get(\ilEventoImportCronConfig::CONF_USER_MAX_ACC_DURATION);
        $this->max_acc_duration = $this->addMonthsToCurrent($import_acc_duration_in_months); //new \DateTime(strtotime("+$max_acc_duration_in_months months", $this->now->getTimestamp()));//$this->now->add(new \DateInterval($max_acc_duration_in_months . 'm'));

        $this->auth_mode = $settings->get(\ilEventoImportCronConfig::CONF_USER_AUTH_MODE);
        $this->is_profile_public = true;
        $this->is_profile_picture_public = true;
        $this->is_mail_public = true;
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

    public function getValidUntilTimestamp()
    {
        return $this->valid_until_timestamp;
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
}

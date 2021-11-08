<?php

namespace EventoImport\import\settings;

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

    public function __construct(\ilSetting $settings)
    {
        $this->settings = $settings;

        $this->now_timestamp = time();
        $this->valid_until_timestamp = $this->convertDateTimeSetting('crevento_account_duration');
        ;
        $this->valid_until_max_timestamp = $this->convertDateTimeSetting('crevento_account_max_duration');
        $this->auth_mode = $settings->get('crevento_ilias_auth_mode');
        $this->is_profile_public = true;
        $this->is_profile_picture_public = true;
        $this->is_mail_public = true;
        $this->default_user_role_id = $settings->get('crevento_standard_user_role_id');
    }

    private function convertDateTimeSetting(string $key) : int
    {
        $setting = $this->settings->get($key);
        if ($setting != 0) {
            $value = mktime(
                date('H'),
                date('i'),
                date('s'),
                date('n') + ($setting % 12),
                date('j'),
                date('Y') + intdiv($setting, 12)
            );
        } else {
            $value = 0;
        }

        return $value;
    }

    public function getNowTimestamp()
    {
        return $this->now_timestamp;
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
}

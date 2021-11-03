<?php

namespace EventoImport\import\settings;

class DefaultUserSettings
{
    private $now_timestamp;
    private $valid_until_timestamp;
    private $auth_mode;
    private $is_profile_public;
    private $is_profile_picture_public;
    private $is_mail_public;
    private $default_user_role_id;

    public function __construct()
    {
        $this->now_timestamp;
        $this->valid_until_timestamp;
        $this->auth_mode;
        $this->is_profile_public;
        $this->is_profile_picture_public;
        $this->is_mail_public;
        $this->default_user_role_id;
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
<?php declare(strict_types = 1);

namespace EventoImport\config;

class DefaultUserSettings
{
    private \ilSetting $settings;

    private \DateTimeImmutable $now;
    private $auth_mode;
    private bool $is_auth_mode_ldap;
    private bool $is_profile_public;
    private bool $is_profile_picture_public;
    private bool $is_mail_public;
    private int $default_user_role_id;
    private \DateTimeImmutable $acc_duration_after_import;
    private \DateTimeImmutable $max_acc_duration;
    private int $default_hits_per_page;
    private string $default_show_users_online;
    private int $mail_incoming_type;
    private array $evento_to_ilias_role_mapping;
    private array $assignable_roles;
    private int $student_role_id;

    public function __construct(\ilSetting $settings)
    {
        $this->settings = $settings;

        $this->assignable_roles = [];

        $this->default_user_role_id = (int) $settings->get(CronConfigForm::CONF_DEFAULT_USER_ROLE); // TODO: Use default user role from constants
        $this->assignable_roles[] = $this->default_user_role_id;
        $this->default_hits_per_page = 100;
        $this->default_show_users_online = 'associated';
        $this->student_role_id = (int) $settings->get(CronConfigForm::CONF_USER_STUDENT_ROLE_ID);

        $this->now = new \DateTimeImmutable();
        $import_acc_duration_in_months = (int) $settings->get(CronConfigForm::CONF_USER_IMPORT_ACC_DURATION, "12");
        $this->acc_duration_after_import = $this->addMonthsToCurrent($import_acc_duration_in_months);
        $max_acc_duration_in_months = (int) $settings->get(CronConfigForm::CONF_USER_MAX_ACC_DURATION, "24");
        $this->max_acc_duration = $this->addMonthsToCurrent($max_acc_duration_in_months);

        $this->auth_mode = $settings->get(CronConfigForm::CONF_USER_AUTH_MODE, 'local');
        $this->is_auth_mode_ldap = \ilLDAPServer::isAuthModeLDAP($this->auth_mode);
        $this->is_profile_public = true;
        $this->is_profile_picture_public = true;
        $this->is_mail_public = true;
        $this->mail_incoming_type = 2;

        $role_mapping = $settings->get(CronConfigForm::CONF_ROLES_ILIAS_EVENTO_MAPPING, null);
        $role_mapping = !is_null($role_mapping) ? json_decode($role_mapping, true) : null;
        $this->evento_to_ilias_role_mapping = [];
        if (!is_null($role_mapping)) {
            foreach ($role_mapping as $ilias_role_id => $evento_role) {
                $this->evento_to_ilias_role_mapping[$evento_role] = $ilias_role_id;
                $this->assignable_roles[] = $ilias_role_id;
            }
        }
    }

    private function addMonthsToCurrent(int $import_acc_duration_in_months) : \DateTimeImmutable
    {
        return $this->getNow()->add(new \DateInterval('P' . $import_acc_duration_in_months . 'M'));
    }

    public function getNow() : \DateTimeImmutable
    {
        return $this->now;
    }

    public function getAccDurationAfterImport() : \DateTimeImmutable
    {
        return $this->acc_duration_after_import;
    }

    public function getMaxDurationOfAccounts() : \DateTimeImmutable
    {
        return $this->max_acc_duration;
    }

    public function getAuthMode()
    {
        return $this->auth_mode;
    }

    public function isAuthModeLDAP() : bool
    {
        return $this->is_auth_mode_ldap;
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

    public function getEventoCodeToIliasRoleMapping() : array
    {
        return $this->evento_to_ilias_role_mapping;
    }

    public function getStudentRoleId() : int
    {
        return $this->student_role_id;
    }
}

<?php declare(strict_types = 1);

namespace EventoImport\config;

class DefaultUserSettings
{
    private const CONF_USER_AUTH_MODE = 'crevento_ilias_auth_mode';
    private const CONF_DEFAULT_USER_ROLE = 'crevento_default_user_role';
    private const CONF_ROLES_ILIAS_EVENTO_MAPPING = 'crevento_roles_ilias_evento_mapping';
    private const CONF_ROLES_DELETE_FROM_ADMIN_ON_REMOVAL = 'crevento_roles_delete_from_admin_on_removal';
    private const CONF_ROLES_FOLLOW_UP_ROLE_MAPPING = 'crevento_roles_follow_up_role_mapping';

    private \ilSetting $settings;

    private $auth_mode;
    private bool $is_profile_public;
    private bool $is_profile_picture_public;
    private bool $is_mail_public;
    private int $default_user_role_id;
    private int $default_hits_per_page;
    private string $default_show_users_online;
    private int $mail_incoming_type;
    private array $evento_to_ilias_role_mapping;
    private array $delete_when_removed_mapping;
    private array $follow_up_role_mapping;

    public function __construct(\ilSetting $settings)
    {
        $this->settings = $settings;

        $this->auth_mode = $this->settings->get(self::CONF_USER_AUTH_MODE, 'local');

        $this->default_user_role_id = (int) $this->settings->get(self::CONF_DEFAULT_USER_ROLE); // TODO: Use default user role from constants

        $this->default_hits_per_page = 100;
        $this->default_show_users_online = 'associated';
        $this->is_profile_public = true;
        $this->is_profile_picture_public = true;
        $this->is_mail_public = true;
        $this->mail_incoming_type = 2;

        $this->evento_to_ilias_role_mapping = array_flip(
            json_decode($this->settings->get(self::CONF_ROLES_ILIAS_EVENTO_MAPPING, '[]'), true) ?? []
        );
        $this->delete_when_removed_mapping = json_decode($this->settings->get(self::CONF_ROLES_DELETE_FROM_ADMIN_ON_REMOVAL, '[]'), true) ?? [];

        $this->follow_up_role_mapping = json_decode($this->settings->get(self::CONF_ROLES_FOLLOW_UP_ROLE_MAPPING, '[]'), true) ?? [];
    }

    public function getAuthMode(): string
    {
        return $this->auth_mode;
    }

    public function setAuthMode(string $auth_mode): void
    {
        $this->auth_mode = $auth_mode;
    }

    public function isProfilePublic(): bool
    {
        return $this->is_profile_public;
    }

    public function isProfilePicturePublic(): bool
    {
        return $this->is_profile_picture_public;
    }

    public function isMailPublic(): bool
    {
        return $this->is_mail_public;
    }

    public function getMailIncomingType(): int
    {
        return $this->mail_incoming_type;
    }

    public function getDefaultUserRoleId(): int
    {
        return $this->default_user_role_id;
    }

    public function setDefaultUserRoleId(int $default_user_role_id): void
    {
        $this->default_user_role_id = $default_user_role_id;
    }

    public function getDefaultHitsPerPage(): int
    {
        return $this->default_hits_per_page;
    }

    public function getDefaultShowUsersOnline(): string
    {
        return $this->default_show_users_online;
    }

    public function getEventoCodeToIliasRoleMapping(): array
    {
        return $this->evento_to_ilias_role_mapping;
    }

    public function setEventoCodeToIliasRoleMapping(array $evento_to_ilias_role_mapping): void
    {
        $this->evento_to_ilias_role_mapping = $evento_to_ilias_role_mapping;
    }

    public function getDeleteFromAdminWhenRemovedFromRoleMapping(): array
    {
        return $this->delete_when_removed_mapping;
    }

    public function setDeleteFromAdminWhenRemovedFromRoleMapping(array $delete_when_removed_mapping): void
    {
        $this->delete_when_removed_mapping = $delete_when_removed_mapping;
    }

    public function getFollowUpRoleMapping(): array
    {
        return $this->follow_up_role_mapping;
    }

    public function setFollowUpRoleMapping(array $follow_up_role_mapping): void
    {
        $this->follow_up_role_mapping = $follow_up_role_mapping;
    }

    public function saveCurrentConfigurationToSettings(): void
    {
        $this->settings->set(self::CONF_USER_AUTH_MODE, $this->getAuthMode());
        $this->settings->set(self::CONF_DEFAULT_USER_ROLE, (string) $this->getDefaultUserRoleId());
        $this->settings->set(
            self::CONF_ROLES_ILIAS_EVENTO_MAPPING,
            json_encode(
                array_flip($this->getEventoCodeToIliasRoleMapping())
            )
        );
        $this->settings->set(
            self::CONF_ROLES_DELETE_FROM_ADMIN_ON_REMOVAL,
            json_encode($this->getDeleteFromAdminWhenRemovedFromRoleMapping())
        );
        $this->settings->set(
            self::CONF_ROLES_FOLLOW_UP_ROLE_MAPPING,
            json_encode($this->getFollowUpRoleMapping())
        );
    }
}

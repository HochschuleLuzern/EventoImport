<?php declare(strict_types = 1);

namespace EventoImport\config;

class DefaultUserSettings
{
    private const DEFAULT_USER_ROLE = 109;
    private const DEFAULT_GUEST_ROLE = 5;
    private const DEFAULT_HITS_PER_PAGE = 100;
    private const DEFAULT_SHOW_USERS_ONLINE = 'associated';
    private const DEFAULT_IS_PROFILE_PUBLIC = true;
    private const DEFAULT_IS_PROFILE_PICTURE_PUBLIC = true;
    private const DEFAULT_IS_MAIL_PUBLIC = true;
    private const DEFAULT_MAIL_INCOMING_TYPE = 2;

    private const CONF_USER_AUTH_MODE = 'crevento_ilias_auth_mode';
    private const CONF_ROLES_ILIAS_EVENTO_MAPPING = 'crevento_roles_ilias_evento_mapping';
    private const CONF_ROLES_TRACK_REMOVAL_CUSTOM_FIELD = 'crevento_roles_track_removal_custom_field';
    private const CONF_ROLES_DELETE_FROM_ADMIN_ON_REMOVAL = 'crevento_roles_delete_from_admin_on_removal';
    private const CONF_ROLES_FOLLOW_UP_ROLE_MAPPING = 'crevento_roles_follow_up_role_mapping';

    private \ilSetting $settings;

    private $auth_mode;
    private bool $is_profile_picture_public;
    private array $evento_to_ilias_role_mapping;
    private array $delete_when_removed_mapping;
    private array $track_removal_custom_fields_mapping;
    private array $follow_up_role_mapping;

    public function __construct(\ilSetting $settings)
    {
        $this->settings = $settings;

        $this->auth_mode = $this->settings->get(self::CONF_USER_AUTH_MODE, 'local');

        $this->evento_to_ilias_role_mapping = array_flip(
            json_decode($this->settings->get(self::CONF_ROLES_ILIAS_EVENTO_MAPPING, '[]'), true) ?? []
        );
        $this->delete_when_removed_mapping = json_decode($this->settings->get(self::CONF_ROLES_DELETE_FROM_ADMIN_ON_REMOVAL, '[]'), true) ?? [];
        $this->track_removal_custom_fields_mapping = json_decode($this->settings->get(self::CONF_ROLES_TRACK_REMOVAL_CUSTOM_FIELD, '[]'), true) ?? [];
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
        return self::DEFAULT_IS_PROFILE_PUBLIC;
    }

    public function isProfilePicturePublic(): bool
    {
        return self::DEFAULT_IS_PROFILE_PICTURE_PUBLIC;
    }

    public function isMailPublic(): bool
    {
        return self::DEFAULT_IS_MAIL_PUBLIC;
    }

    public function getMailIncomingType(): int
    {
        return self::DEFAULT_MAIL_INCOMING_TYPE;
    }

    public function getDefaultUserRoleId(): int
    {
        return self::DEFAULT_USER_ROLE;
    }

    public function getDefaultGuestRoleId(): int
    {
        return self::DEFAULT_GUEST_ROLE;
    }

    public function getDefaultHitsPerPage(): int
    {
        return self::DEFAULT_HITS_PER_PAGE;
    }

    public function getDefaultShowUsersOnline(): string
    {
        return self::DEFAULT_SHOW_USERS_ONLINE;
    }

    public function getEventoCodeToIliasRoleMapping(): array
    {
        return $this->evento_to_ilias_role_mapping;
    }

    public function setEventoCodeToIliasRoleMapping(array $evento_to_ilias_role_mapping): void
    {
        $this->evento_to_ilias_role_mapping = $evento_to_ilias_role_mapping;
    }

    public function getTrackRemovalCustomFieldsMapping(): array
    {
        return $this->track_removal_custom_fields_mapping;
    }

    public function setTrackRemovalCustomFieldsMapping(array $track_removal_custom_fields_mapping): void
    {
        $this->track_removal_custom_fields_mapping = $track_removal_custom_fields_mapping;
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
            self::CONF_ROLES_TRACK_REMOVAL_CUSTOM_FIELD,
            json_encode($this->getTrackRemovalCustomFieldsMapping())
        );
        $this->settings->set(
            self::CONF_ROLES_FOLLOW_UP_ROLE_MAPPING,
            json_encode($this->getFollowUpRoleMapping())
        );
    }
}

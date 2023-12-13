<?php declare(strict_types=1);

namespace EventoImport\import\data_management;

use EventoImport\communication\api_models\EventoUser;
use EventoImport\import\data_management\ilias_core_service\IliasUserServices;
use EventoImport\import\data_management\repository\IliasEventoUserRepository;
use EventoImport\communication\api_models\EventoUserShort;
use EventoImport\config\DefaultUserSettings;
use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\import\data_management\repository\model\IliasEventoUser;
use EventoImport\import\Logger;

class UserManager
{
    private IliasUserServices $ilias_user_service;
    private IliasEventoUserRepository $evento_user_repo;
    private DefaultUserSettings $default_user_settings;
    private Logger $logger;

    public function __construct(IliasUserServices $ilias_user_service, IliasEventoUserRepository $evento_user_repo, DefaultUserSettings $default_user_settings, Logger $logger)
    {
        $this->ilias_user_service = $ilias_user_service;
        $this->evento_user_repo = $evento_user_repo;
        $this->default_user_settings = $default_user_settings;
        $this->logger = $logger;
    }

    public function createAndSetupNewIliasUser(EventoUser $evento_user): \ilObjUser
    {
        $ilias_user_object = $this->setUserValuesFromEventoUserObject(
            $this->ilias_user_service->createNewIliasUserObject(),
            $evento_user
        );

        $ilias_user_object->create();
        $ilias_user_object->saveAsNew(false);

        $this->setUserDefaultSettings($ilias_user_object, $this->default_user_settings);
        $this->setForcedUserSettings($ilias_user_object, $this->default_user_settings);

        $this->evento_user_repo->addNewEventoIliasUser(
            $evento_user->getEventoId(),
            $ilias_user_object->getId(),
            IliasEventoUserRepository::TYPE_HSLU_AD
        );

        return $ilias_user_object;
    }

    public function updateSettingsForExistingUser(\ilObjUser $ilias_user)
    {
        $this->setForcedUserSettings($ilias_user, $this->default_user_settings);
    }

    public function importAndSetUserPhoto(\ilObjUser $ilias_user, int $evento_id, EventoUserPhotoImporter $photo_importer): void
    {
        if ($this->ilias_user_service->userHasPersonalPicture($ilias_user->getId())) {
            return;
        }

        try {
            $photo_import = $photo_importer->fetchUserPhotoDataById($evento_id);

            if (!is_null($photo_import)
                && $photo_import->getHasPhoto()
                && $photo_import->getImgData()
                && strlen($photo_import->getImgData()) > 10
            ) {
                $this->ilias_user_service->saveEncodedPersonalPictureToUserProfile($ilias_user->getId(), $photo_import->getImgData());
            }
        } catch (\Exception $e) {
            $this->logger->logException('Photo Import', 'Exception on importing User Photo. EventoID = ' . $evento_id
                . ', IliasUserId = ' . $ilias_user->getId() . ', Exception Message = ' . $e->getMessage());
        }
    }

    public function synchronizeIliasUserWithEventoRoles(\ilObjUser $user, array $imported_evento_roles): void
    {
        $this->ilias_user_service->assignUserToRole($user->getId(), $this->default_user_settings->getDefaultUserRoleId());

        // Set ilias roles according to given evento roles
        foreach ($this->default_user_settings->getEventoCodeToIliasRoleMapping() as $evento_role_code => $ilias_role_id) {
            if ($this->ilias_user_service->isUserAssignedToRole($user->getId(), $ilias_role_id)
                && in_array($evento_role_code, $imported_evento_roles)) {
                continue;
            }

            if (in_array($evento_role_code, $imported_evento_roles)) {
                $this->performAddToUserRoleSubTasks($user, $imported_evento_roles, $evento_role_code, $ilias_role_id);
                continue;
            }

            $this->removeUserAccessAfterRemovalOfEventoRole($user, $ilias_role_id);
        }
    }

    private function performAddToUserRoleSubTasks(
        \ilObjUser $user,
        array $imported_evento_roles,
        int $evento_role_code,
        int $ilias_role_id
    ): void {
        $track_removal_custom_fields_mapping = $this->default_user_settings->getTrackRemovalCustomFieldsMapping();
        $follow_up_roles_mapping = $this->default_user_settings->getFollowUpRoleMapping();

        if (in_array($evento_role_code, $imported_evento_roles)
            && array_key_exists($ilias_role_id, $follow_up_roles_mapping)
            && $this->ilias_user_service->isUserAssignedToRole(
                $user->getId(),
                $follow_up_roles_mapping[$ilias_role_id])
            ) {
            $this->ilias_user_service->deassignUserFromRole($user->getId(), $follow_up_roles_mapping[$ilias_role_id]);
        }

        if (in_array($evento_role_code, $imported_evento_roles)
            && array_key_exists($ilias_role_id, $track_removal_custom_fields_mapping)
            && $track_removal_custom_fields_mapping[$ilias_role_id] !== 0) {
            $this->saveUserRoleRemovalDateToCustomField($user, $track_removal_custom_fields_mapping[$ilias_role_id], date('Y-m-d H:i:s'));
        }

        $this->ilias_user_service->assignUserToRole($user->getId(), $ilias_role_id);
    }

    public function removeUserAccessesAfterLeavingInstitution(\ilObjUser $user): void
    {
        $this->ilias_user_service->deassignUserFromRole($user->getId(), $this->default_user_settings->getDefaultUserRoleId());

        $assigned_global_roles = $this->ilias_user_service->getGlobalRolesOfUser($user->getId());
        $roles_mapped_to_evento = $this->default_user_settings->getEventoCodeToIliasRoleMapping();
        foreach ($assigned_global_roles as $global_role_id) {
            if (!in_array($global_role_id, $roles_mapped_to_evento)) {
                continue;
            }
            $this->removeUserAccessAfterRemovalOfEventoRole($user, $global_role_id);
        }
    }

    private function removeUserAccessAfterRemovalOfEventoRole(\ilObjUser $user, int $role_id): void
    {
        $follow_up_roles_mapping = $this->default_user_settings->getFollowUpRoleMapping();
        $track_removal_custom_fields_mapping = $this->default_user_settings->getTrackRemovalCustomFieldsMapping();
        $roles_needing_admin_removal = $this->default_user_settings->getDeleteFromAdminWhenRemovedFromRoleMapping();

        $this->ilias_user_service->deassignUserFromRole($user->getId(), $role_id);

        if (array_key_exists($role_id, $track_removal_custom_fields_mapping)
            && $track_removal_custom_fields_mapping[$role_id] !== 0) {
            $this->saveUserRoleRemovalDateToCustomField($user, $track_removal_custom_fields_mapping[$role_id], date('Y-m-d H:i:s'));
        }

        if (array_key_exists($role_id, $follow_up_roles_mapping)) {
            $this->ilias_user_service->assignUserToRole($user->getId(), $follow_up_roles_mapping[$role_id]);
        }

        if (in_array($role_id, $roles_needing_admin_removal)) {
            $admin_roles = $this->ilias_user_service->getCrsAdminButNotOwnerRolesOfUser();
            foreach ($admin_roles as $admin_role_id) {
                $this->ilias_user_service->deassignUserFromRole($user->getId(), $admin_role_id);
            }
        }
    }

    public function saveUserRoleRemovalDateToCustomField(\ilObjUser $user, int $custom_field_id, string $date): void
    {
        $user->setUserDefinedData([$custom_field_id => $date]);
        $user->update();
    }

    public function renameAndDeactivateIliasUser(\ilObjUser $old_user_to_rename, string $new_login)
    {
        $old_user_to_rename->setActive(false);
        $old_user_to_rename->update();
        $old_user_to_rename->setLogin($new_login);
        $old_user_to_rename->updateLogin($old_user_to_rename->getLogin());
    }

    public function registerEventoUserAsDelivered(EventoUser $evento_user)
    {
        $this->evento_user_repo->registerUserAsDelivered($evento_user->getEventoId());
    }

    public function updateIliasUserFromEventoUser(\ilObjUser $ilias_user, EventoUser $evento_user)
    {
        $changed_user_data = [];
        $first_name = $this->shortenStringIfTooLong($evento_user->getFirstName(), 32);
        if ($ilias_user->getFirstname() != $first_name) {
            $changed_user_data['first_name'] = [
                'old' => $ilias_user->getFirstname(),
                'new' => $first_name
            ];
            $ilias_user->setFirstname($first_name);
        }

        $last_name = $this->shortenStringIfTooLong($evento_user->getLastName(), 32);
        if ($ilias_user->getlastname() != $last_name) {
            $changed_user_data['last_name'] = [
                'old' => $ilias_user->getFirstname(),
                'new' => $last_name
            ];
            $ilias_user->setLastname($last_name);
        }

        $received_gender_char = $this->convertEventoToIliasGenderChar($evento_user->getGender());
        if ($ilias_user->getGender() !== $received_gender_char) {
            $changed_user_data['gender'] = [
                'old' => $ilias_user->getGender(),
                'new' => $received_gender_char
            ];
            $ilias_user->setGender($received_gender_char);
        }

        $mail_list = $evento_user->getEmailList();
        if (isset($mail_list[0]) && ($ilias_user->getSecondEmail() !== $mail_list[0])) {
            $changed_user_data['second_mail'] = [
                'old' => $ilias_user->getSecondEmail(),
                'new' => $mail_list[0]
            ];
            $ilias_user->setSecondEmail($mail_list[0]);
        }

        if ($ilias_user->getMatriculation() !== ('Evento:' . $evento_user->getEventoId())) {
            $changed_user_data['matriculation'] = [
                'old' => $ilias_user->getMatriculation(),
                'new' => 'Evento:' . $evento_user->getEventoId()
            ];
            $ilias_user->setMatriculation('Evento:' . $evento_user->getEventoId());
        }

        if ($ilias_user->getAuthMode() !== $this->default_user_settings->getAuthMode()) {
            $changed_user_data['auth_mode'] = [
                'old' => $ilias_user->getAuthMode(),
                'new' => $this->default_user_settings->getAuthMode()
            ];
            $ilias_user->setAuthMode($this->default_user_settings->getAuthMode());
        }

        if (!$ilias_user->getActive()) {
            $changed_user_data['active'] = [
                'old' => false,
                'new' => true
            ];
            $ilias_user->setActive(true);
        }

        if ($changed_user_data !== []) {
            $ilias_user->update();
        }

        return $changed_user_data;
    }

    private function setUserValuesFromEventoUserObject(\ilObjUser $ilias_user, EventoUser $evento_user): \ilObjUser
    {
        $ilias_user->setLogin($evento_user->getLoginName());
        $ilias_user->setFirstname($this->shortenStringIfTooLong($evento_user->getFirstName(), 32));
        $ilias_user->setLastname($this->shortenStringIfTooLong($evento_user->getLastName(), 32));
        $ilias_user->setGender($this->convertEventoToIliasGenderChar($evento_user->getGender()));
        $ilias_user->setEmail($evento_user->getEmailList()[0]);
        $ilias_user->setSecondEmail($evento_user->getEmailList()[0]);
        $ilias_user->setTitle($ilias_user->getFullname());
        $ilias_user->setDescription($ilias_user->getEmail());
        $ilias_user->setMatriculation('Evento:' . $evento_user->getEventoId());
        $ilias_user->setExternalAccount($evento_user->getEventoId() . '@hslu.ch');
        $ilias_user->setAuthMode($this->default_user_settings->getAuthMode());

        return $ilias_user;
    }

    private function setForcedUserSettings(\ilObjUser $ilias_user, DefaultUserSettings $user_settings)
    {
        $ilias_user->setActive(true);

        // Reset login attempts over night -> needed since login attempts are limited to 8
        $ilias_user->setLoginAttempts(0);
        $ilias_user->setTimeLimitUnlimited(true);

        $ilias_user->setPasswd('');

        // profil is always public for registered users
        $ilias_user->setPref(
            'public_profile',
            $user_settings->isProfilePublic()  ? 'y' : 'n'
        );

        // profil picture is always public for registered users, but it can be changed by the profile owner
        $ilias_user->setPref(
            'public_upload',
            $user_settings->isProfilePicturePublic()  ? 'y' : 'n'
        );

        // email is always public for registered users, but it can be changed by the profile owner
        $ilias_user->setPref('public_email', $user_settings->isMailPublic() ? 'y' : 'n');

        $ilias_user->update();
        $ilias_user->writePrefs();
    }

    private function setUserDefaultSettings(\ilObjUser $ilias_user_object, DefaultUserSettings $user_settings)
    {
        $ilias_user_object->setActive(true);
        $ilias_user_object->setTimeLimitUnlimited(true);

        $ilias_user_object->setAuthMode($user_settings->getAuthMode());

        // Set default prefs
        $ilias_user_object->setPref(
            'hits_per_page',
            (string) $user_settings->getDefaultHitsPerPage()
        ); //100 hits per page
        $ilias_user_object->setPref(
            'show_users_online',
            $user_settings->getDefaultShowUsersOnline()
        ); //nur Leute aus meinen Kursen zeigen

        // update mail preferences
        $this->ilias_user_service->setMailPreferences(
            $ilias_user_object->getId(),
            $user_settings->getMailIncomingType()
        );
    }

    private function convertEventoToIliasGenderChar(string $evento_gender_char): string
    {
        switch (strtolower($evento_gender_char)) {
            case 'f':
                return 'f';
            case 'm':
                return 'm';
            case 'x':
            default:
                return 'n';
        }
    }

    public function deleteEventoUserToIliasUserConnection(int $evento_id)
    {
        $this->evento_user_repo->deleteEventoIliasUserConnectionByEventoId($evento_id);
    }

    public function getExistingIliasUserObjectById(int $ilias_user_id): \ilObjUser
    {
        return $this->ilias_user_service->getExistingIliasUserObjectById($ilias_user_id);
    }

    public function getIliasEventoUserByEventoId(int $evento_id): IliasEventoUser
    {
        return $this->evento_user_repo->getIliasEventoUserByEventoId($evento_id);
    }

    public function getIliasUserIdByEventoId(int $evento_id): ?int
    {
        return $this->evento_user_repo->getIliasUserIdByEventoId($evento_id);
    }

    public function getIliasUserIdByEventoUserShort(EventoUserShort $evento_user): ?int
    {
        $ilias_user_id = $this->evento_user_repo->getIliasUserIdByEventoId($evento_user->getEventoId());
        if (is_null($ilias_user_id)) {
            $ilias_user_id = $this->ilias_user_service->getUserIdByExternalAccount($evento_user->getEduId());
            if ($ilias_user_id !== 0) {
                $this->evento_user_repo->addNewEventoIliasUser(
                    $evento_user->getEventoId(),
                    $ilias_user_id,
                    IliasEventoUserRepository::TYPE_EDU_ID
                );
            }
        }

        return $ilias_user_id;
    }

    public function getIliasEventoUserForEventoUser(EventoUserShort $evento_user): ?IliasEventoUser
    {
        return $this->evento_user_repo->getIliasEventoUserByEventoId($evento_user->getEventoId());
    }

    private function shortenStringIfTooLong(string $string, int $max_length)
    {
        if (mb_strlen($string) > $max_length) {
            return mb_substr($string, 0, $max_length);
        }

        return $string;
    }
}

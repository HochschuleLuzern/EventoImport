<?php declare(strict_types = 1);

namespace EventoImport\import\data_management\ilias_core_service;

use ILIAS\DI\RBACServices;
use EventoImport\config\DefaultUserSettings;

/**
 * Class IliasUserServices
 *
 * This class is a take on encapsulation all the "User" specific functionality from the rest of the import. Things like
 * searching an ILIAS-Object by title or saving something event-specific to the DB should go through this class.
 *
 * @package EventoImport\import\db
 */
class IliasUserServices
{
    private DefaultUserSettings $default_user_settings;
    private \ilDBInterface $db;
    private \ilRbacReview $rbac_review;
    private \ilRbacAdmin $rbac_admin;
    private ?int $student_role_id;

    public function __construct(
        DefaultUserSettings $default_user_settings,
        \ilDBInterface $db,
        \ilRbacReview $rbac_review,
        \ilRbacAdmin $rbac_admin
    ) {
        $this->default_user_settings = $default_user_settings;
        $this->db = $db;
        $this->rbac_review = $rbac_review;
        $this->rbac_admin = $rbac_admin;

        $this->student_role_id = null;
    }

    /*
     * Get / Create ILIAS User objects
     */

    public function createNewIliasUserObject(): \ilObjUser
    {
        return new \ilObjUser();
    }

    public function getExistingIliasUserObjectById(int $user_id): \ilObjUser
    {
        return new \ilObjUser($user_id);
    }

    /*
     * Search for ILIAS User IDs by criteria
     */

    public function getUserIdsByEmailAddresses(array $email_adresses)
    {
        $user_lists = [];

        // For each mail given in the adress array...
        foreach ($email_adresses as $email_adress) {

            // ... get all user ids in which a user has this email
            foreach ($this->getUserIdsByEmailAddress($email_adress) as $ilias_id_by_mail) {
                if (!in_array($ilias_id_by_mail, $user_lists)) {
                    $user_lists[] = (int) $ilias_id_by_mail;
                }
            }
        }

        return $user_lists;
    }

    public function getUserIdsByEmailAddress(string $mail_address): array
    {
        /* The user ids from ilObjUser::getUserIdsByEmail() are returned as string instead of int. Since we use strict_type
        int his plugin, this throws a TypeError when ever an id from this array is passed to a method which expects an argument
        with the type of int. */
        $ids = [];
        foreach (\ilObjUser::getUserIdsByEmail($mail_address) as $user_id) {
            $ids[] = (int) $user_id;
        }

        return $ids;
    }

    public function getUserIdByExternalAccount(string $external_account): int
    {
        $login_name = \ilObjUser::_checkExternalAuthAccount($this->default_user_settings->getAuthMode(), $external_account);

        if ($login_name === null) {
            return 0;
        }

        return \ilObjUser::getUserIdByLogin($login_name);
    }

    public function getUserIdByLogin(string $login_name): int
    {
        return \ilObjUser::getUserIdByLogin($login_name);
    }

    public function getLoginByUserId(int $user_id): ?string
    {
        return \ilObjUser::_lookupLogin($user_id);
    }

    public function getUserIdsByEventoId(int $evento_id): array
    {
        $list = [];

        $query = "SELECT usr_id FROM usr_data WHERE matriculation = " . $this->db->quote("Evento:$evento_id", \ilDBConstants::T_TEXT);
        $result = $this->db->query($query);
        while ($user_record = $this->db->fetchAssoc($result)) {
            $list[] = (int) $user_record["usr_id"];
        }

        return $list;
    }

    public function getGlobalRolesOfUser(int $user_id): array
    {
        return $this->rbac_review->assignedGlobalRoles($user_id);
    }

    public function getCrsAdminButNotOwnerRolesOfUser(int $user_id): array
    {
        $roles = $this->rbac_review->assignedRoles($user_id);
        $admin_roles = [];
        foreach ($roles as $role_id) {
            $title = \ilObject::_lookupTitle($role_id);
            $object_id = $this->rbac_review->getObjectOfRole($role_id);

            if (substr($title, 0, 12) === 'il_crs_admin'
                && \ilObject::_lookupOwner($object_id) !== $user_id) {
                $admin_roles[] = $role_id;
            }
        }
        return $admin_roles;
    }

    public function isUserAssignedToRole(int $user_id, int $role_id): bool
    {
        return $this->rbac_review->isAssigned($user_id, $role_id);
    }

    /*
     * User and role specific methods
     */
    public function assignUserToRole(int $user_id, int $role_id): void
    {
        if (!$this->rbac_review->isAssigned($user_id, $role_id)) {
            $this->rbac_admin->assignUser($role_id, $user_id);
        }
    }

    public function deassignUserFromRole(int $user_id, int $role_id)
    {
        if ($this->rbac_review->isAssigned($user_id, $role_id)) {
            $this->rbac_admin->deassignUser($role_id, $user_id);
        }
    }

    public function userWasStudent(\ilObjUser $ilias_user_object): bool
    {
        // TODO: Implement config for this
        if (is_null($this->student_role_id)) {
            $this->student_role_id = $this->default_user_settings->getStudentRoleId();
            if (is_null($this->student_role_id)) {
                return false;
            }
        }

        return $this->rbac_review->isAssigned($ilias_user_object->getId(), $this->student_role_id);
    }

    /*
     *
     */

    public function userHasPersonalPicture(int $ilias_user_id): bool
    {
        $personal_picturpath = \ilObjUser::_getPersonalPicturePath($ilias_user_id, "small", false);

        return strpos($personal_picturpath, 'data:image/svg+xml') === false;
    }

    public function saveEncodedPersonalPictureToUserProfile(int $ilias_user_id, string $encoded_image_string): void
    {
        try {
            return;

            // Deactivated for the moment since the method does not exist anymore
            $tmp_file = \ilUtil::ilTempnam();
            imagepng(
                imagecreatefromstring(
                    base64_decode(
                        $encoded_image_string
                    )
                ),
                $tmp_file,
                0
            );
            \ilObjUser::_uploadPersonalPicture($tmp_file, $ilias_user_id);
        } catch (\Exception $e) {
            global $DIC;
            $DIC->logger()->root()->log('Evento Import: Exception on Photo Upload: ' . print_r($e, true), \ilLogLevel::ERROR);
        } finally {
            if (isset($tmp_file)) {
                unlink($tmp_file);
            }
        }
    }

    public function setMailPreferences(int $user_id, int $incoming_type)
    {
        $mail_options = new \ilMailOptions($user_id);
        $mail_options->setIncomingType($incoming_type);
        $mail_options->updateOptions();
    }
}

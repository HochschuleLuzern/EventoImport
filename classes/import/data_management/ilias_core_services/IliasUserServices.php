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
    private DefaultUserSettings $user_settings;
    private \ilDBInterface $db;
    private RBACServices $rbac_services;
    private \ilRbacReview $rbac_review;
    private \ilRbacAdmin $rbac_admin;
    private ?int $student_role_id;

    public function __construct(DefaultUserSettings $user_settings, \ilDBInterface $db, RBACServices $rbac_services)
    {
        $this->user_settings = $user_settings;
        $this->db = $db;
        $this->rbac_services = $rbac_services;
        $this->rbac_review = $rbac_services->review();
        $this->rbac_admin = $rbac_services->admin();

        $this->student_role_id = null;
    }

    /*
     * Get / Create ILIAS User objects
     */

    public function createNewIliasUserObject() : \ilObjUser
    {
        return new \ilObjUser();
    }

    public function getExistingIliasUserObjectById(int $user_id) : \ilObjUser
    {
        return new \ilObjUser($user_id);
    }

    /*
     * Search for ILIAS User IDs by criteria
     */

    public function getUserIdsByEmailAddresses(array $email_adresses)
    {
        $user_lists = array();

        // For each mail given in the adress array...
        foreach ($email_adresses as $email_adress) {

            // ... get all user ids in which a user has this email
            foreach ($this->getUserIdsByEmailAddress($email_adress) as $ilias_id_by_mail) {
                if (!in_array($ilias_id_by_mail, $user_lists)) {
                    $user_lists[] = $ilias_id_by_mail;
                }
            }
        }

        return $user_lists;
    }

    public function getUserIdsByEmailAddress(string $mail_address) : array
    {
        return \ilObjUser::getUserIdsByEmail($mail_address);
    }

    public function getUserIdByLogin(string $login_name)
    {
        return \ilObjUser::getUserIdByLogin($login_name);
    }

    public function getUserIdsByEventoId(int $evento_id) : array
    {
        $list = array();

        $query = "SELECT usr_id FROM usr_data WHERE matriculation = " . $this->db->quote("Evento:$evento_id", \ilDBConstants::T_TEXT);
        $result = $this->db->query($query);
        while ($user_record = $this->db->fetchAssoc($result)) {
            $list[] = (int) $user_record["usr_id"];
        }

        return $list;
    }

    public function searchEduUserByEmail(string $mail_address) : ?\ilObjUser
    {
        $user_ids = $this->getUserIdsByEmailAddress($mail_address);

        $found_user_obj = null;
        foreach ($user_ids as $user_id) {
            $user_obj = $this->getExistingIliasUserObjectById($user_id);
            if (stristr($user_obj->getExternalAccount(), '@eduid.ch')) {
                $found_user_obj = $user_id;
            }
        }

        return $found_user_obj;
    }

    /*
     * User and role specific methods
     */

    public function assignUserToRole(int $user_id, int $role_id)
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

    public function userWasStudent(\ilObjUser $ilias_user_object) : bool
    {
        // TODO: Implement config for this
        if (is_null($this->student_role_id)) {
            $this->student_role_id = \ilObjRole::_lookupTitle('Studierende');
            if (is_null($this->student_role_id)) {
                return false;
            }
        }

        return $this->rbac_services->review()->isAssigned($ilias_user_object->getId(), $this->student_role_id);
    }

    /*
     *
     */

    public function userHasPersonalPicture(int $ilias_user_id) : bool
    {
        $personal_picturpath = \ilObjUser::_getPersonalPicturePath($ilias_user_id, "small", false);

        return strpos(
            $personal_picturpath,
            'data:image/svg+xml'
        ) !== false;
    }

    public function saveEncodedPersonalPictureToUserProfile(int $ilias_user_id, string $encoded_image_string) : void
    {
        try {
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
            $DIC->logger()->root()->log('Evento Import: Exception on Photo Upload: ' . print_r($e, true));
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

    public function sendLoginChangedMail(\ilObjUser $ilias_user, string $old_login)
    {
        $mail = new ImportMailNotification();
        $mail->setType(ImportMailNotification::MAIL_TYPE_USER_NAME_CHANGED);
        $mail->setUserInformation(
            $ilias_user->getId(),
            $old_login,
            $ilias_user->getLogin(),
            $ilias_user->getEmail()
        );
        $mail->send();
    }

    /*
     * Set values for multiple users
     */

    public function setUserTimeLimits()
    {
        $until_max = $this->user_settings->getMaxDurationOfAccounts()->getTimestamp();

        $this->setTimeLimitForUnlimitedUsersExceptSpecialUsers($until_max);
        $this->setUserTimeLimitsToAMaxValue($until_max);
        $this->setUserTimeLimitsBelowThresholdToGivenValue(90, 7889229);
    }

    private function setTimeLimitForUnlimitedUsersExceptSpecialUsers(int $new_time_limit_ts) : void
    {
        if ($new_time_limit_ts == 0) {
            throw new \InvalidArgumentException("Error in setting time limit for unlimited users: new time limit cannot be 0");
        }

        //no unlimited users
        $q = "UPDATE usr_data set time_limit_unlimited=0, time_limit_until='" . $this->db->quote($new_time_limit_ts, \ilDBConstants::T_INTEGER) . "' WHERE time_limit_unlimited=1 AND login NOT IN ('root','anonymous')";
        $this->db->manipulate($q);
    }

    private function setUserTimeLimitsToAMaxValue(int $until_max_ts) : void
    {
        if ($until_max_ts === 0) {
            throw new \InvalidArgumentException("Error in setting user time limits to max value: Until Max cannot be 0");
        }

        //all users are constraint to a value defined in the configuration
        $q = "UPDATE usr_data set time_limit_until='" . $this->db->quote($until_max_ts, \ilDBConstants::T_INTEGER) . "'"
            . " WHERE time_limit_until>'" . $this->db->quote($until_max_ts, \ilDBConstants::T_INTEGER) . "'";
        $this->db->manipulate($q);
    }

    private function setUserTimeLimitsBelowThresholdToGivenValue(int $min_threshold_in_days, int $new_time_limit_ts) : void
    {
        if ($new_time_limit_ts === 0) {
            throw new \InvalidArgumentException("Error in setting user time limits to new value: New time limit cannot be 0");
        }

        //all users have at least 90 days of access (needed for Shibboleth)
        $q = "UPDATE `usr_data` SET time_limit_until=time_limit_until+" . $this->db->quote($new_time_limit_ts, \ilDBConstants::T_INTEGER)
            . " WHERE DATEDIFF(FROM_UNIXTIME(time_limit_until),create_date)< " . $this->db->quote($min_threshold_in_days, \ilDBConstants::T_INTEGER);
        $this->db->manipulate($q);
    }
}

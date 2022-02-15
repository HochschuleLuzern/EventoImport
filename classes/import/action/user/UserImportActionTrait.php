<?php declare(strict_types = 1);

namespace EventoImport\import\action\user;

use EventoImport\import\settings\DefaultUserSettings;
use EventoImport\import\db\IliasUserServices;
use ILIAS\DI\RBACServices;

trait UserImportActionTrait
{
    protected function convertEventoToIliasGenderChar(string $evento_gender_char) : string
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

    protected function setForcedUserSettings(\ilObjUser $ilias_user, DefaultUserSettings $user_settings)
    {
        /*
            The old import had the $user->setPasswd method two times called. One time within an if-statement and another time without
            Code snipped of if-statement below:
                if ($user_settings->isAuthModeLDAP()) {
                    $user->setPasswd('');
                }

            Since the second call without an if-statement makes this block useless, it is not in the code anymore
        */
        $ilias_user->setPasswd('');

        $ilias_user->setActive(true);

        // Reset login attempts over night -> needed since login attempts are limited to 8
        $ilias_user->setLoginAttempts(0);

        // Set user time limits
        if ($user_settings->getAccDurationAfterImport()->getTimestamp() == 0) {
            $ilias_user->setTimeLimitUnlimited(true);
        } else {
            $ilias_user->setTimeLimitUnlimited(false);

            if ($ilias_user->getTimeLimitFrom() == 0 ||
                $ilias_user->getTimeLimitFrom() > $user_settings->getNow()->getTimestamp()) {
                $ilias_user->setTimeLimitFrom($user_settings->getNow()->getTimestamp());
            }

            $ilias_user->setTimeLimitUntil($user_settings->getAccDurationAfterImport()->getTimestamp());
        }

        // profil is always public for registered users
        $ilias_user->setPref(
            'public_profile',
            $user_settings->isProfilePublic()
        );

        // profil picture is always public for registered users, but it can be changed by the profile owner
        $ilias_user->setPref(
            'public_upload',
            $user_settings->isProfilePicturePublic()
        );

        // email is always public for registered users, but it can be changed by the profile owner
        $ilias_user->setPref('public_email', $user_settings->isMailPublic());

        $ilias_user->update();
        $ilias_user->writePrefs();
    }

    protected function synchronizeUserWithGlobalRoles(int $user_id, array $imported_evento_roles, DefaultUserSettings $user_settings, IliasUserServices $ilias_user_service) : void
    {
        $ilias_user_service->assignUserToRole($user_id, $user_settings->getDefaultUserRoleId());

        // Set ilias roles according to given evento roles
        foreach ($user_settings->getEventoCodeToIliasRoleMapping() as $evento_role_code => $ilias_role_id) {
            if (in_array($evento_role_code, $imported_evento_roles)) {
                $ilias_user_service->assignUserToRole($user_id, $ilias_role_id);
            } else {
                $ilias_user_service->deassignUserFromRole($user_id, $ilias_role_id);
            }
        }
    }
}

<?php declare(strict_type=1);

use ILIAS\DI\RBACServices;
use ILIAS\Refinery\Factory;

class ilEventoImportDailyImportCronJob extends ilCronJob
{
    public const ID = "crevento_daily_import";

    private ilEventoImportPlugin $cp;
    private RBACServices $rbac;
    private ilDBInterface $db;
    private Factory $refinery;
    private ilSetting $settings;

    public function __construct(
        \ilEventoImportPlugin $cp,
        RBACServices $rbac_services,
        ilDBInterface $db,
        Factory $refinery,
        ilSetting $settings
    ) {
        global $DIC;

        $this->cp = $cp;
        $this->rbac = $rbac_services;
        $this->db = $db;
        $this->refinery = $refinery;
        $this->settings = $settings;
    }

    public function getId()
    {
        return self::ID;
    }

    public function hasAutoActivation()
    {
        return false;
    }

    public function hasFlexibleSchedule()
    {
        return true;
    }

    public function getDefaultScheduleType()
    {
        return self::SCHEDULE_TYPE_DAILY;
    }

    public function getDefaultScheduleValue()
    {
        return;
    }

    public function run()
    {
        // TODO: Implement run() method.
    }

    public function getTitle()
    {
        return $this->cp->txt('full_import_cj_title');
    }

    public function getDescription()
    {
        return $this->cp->txt('full_import_cj_desc');
    }

    public function isManuallyExecutable() : bool
    {
        return true;
    }

    public function hasCustomSettings() : bool
    {
        return true;
    }

    public function addCustomSettingsToForm(ilPropertyFormGUI $a_form) : void
    {
        $conf = new ilEventoImportCronConfig($this->settings, $this->cp, $this->rbac);
        $conf->fillCronJobSettingsForm($a_form);
    }

    public function saveCustomSettings(ilPropertyFormGUI $a_form) : bool
    {
        $conf = new ilEventoImportCronConfig($this->settings, $this->cp, $this->rbac);

        return $conf->saveCustomCronJobSettings($a_form);
    }
}
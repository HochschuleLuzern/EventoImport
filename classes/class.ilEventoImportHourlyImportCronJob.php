<?php declare(strict_type=1);

use ILIAS\DI\RBACServices;
use ILIAS\Refinery\Factory;
use EventoImport\import\ImportTaskFactory;
use EventoImport\communication\EventoAdminImporter;
use EventoImport\import\Logger;
use EventoImport\config\ImporterApiSettings;

class ilEventoImportHourlyImportCronJob extends ilCronJob
{
    public const ID = "crevento_hourly_import";

    private ilEventoImportPlugin $cp;
    private RBACServices $rbac;
    private ilDBInterface $db;
    private Factory $refinery;
    private ilSetting $settings;
    private ImportTaskFactory $import_factory;

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

        $this->import_factory = new ImportTaskFactory($db, $DIC->repositoryTree(), $rbac_services, $settings);
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
        return self::SCHEDULE_TYPE_IN_MINUTES;
    }

    public function getDefaultScheduleValue()
    {
        return 30;
    }

    public function run()
    {
        try {
            $logger = new Logger($this->db);

            $api_settings = new ImporterApiSettings($this->settings);

            $data_source = new RestClientService(
                $api_settings->getUrl(),
                $api_settings->getTimeoutAfterRequest(),
                $api_settings->getApikey(),
                $api_settings->getApiSecret()
            );

            $import_admins = $this->import_factory->buildAdminImport(
                new EventoAdminImporter(
                    $data_source,
                    $logger,
                    $api_settings->getTimeoutFailedRequest(),
                    $api_settings->getMaxRetries()
                )
            );
            $import_admins->run();

            return new ilEventoImportResult(ilEventoImportResult::STATUS_OK, 'Cron job terminated successfully.');
        } catch (Exception $e) {
            return new ilEventoImportResult(ilEventoImportResult::STATUS_CRASHED, 'Cron job crashed: ' . $e->getMessage());
        }
    }

    public function getTitle()
    {
        return $this->cp->txt('admin_import_cj_title');
    }

    public function getDescription()
    {
        return $this->cp->txt('admin_import_cj_desc');
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

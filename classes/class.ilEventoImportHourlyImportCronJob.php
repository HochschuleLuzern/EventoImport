<?php declare(strict_type=1);

use EventoImport\import\ImportTaskFactory;
use EventoImport\communication\EventoAdminImporter;
use EventoImport\import\Logger;
use EventoImport\communication\request_services\RestClientService;
use EventoImport\config\ConfigurationManager;

class ilEventoImportHourlyImportCronJob extends ilCronJob
{
    public const ID = "crevento_hourly_import";

    private ilEventoImportPlugin $cp;
    private ImportTaskFactory $import_factory;
    private ConfigurationManager $config_manager;
    private Logger $logger;

    public function __construct(
        \ilEventoImportPlugin $cp,
        ImportTaskFactory $import_factory,
        ConfigurationManager $config_manager,
        Logger $logger
    ) {
        $this->cp = $cp;
        $this->import_factory = $import_factory;
        $this->config_manager = $config_manager;
        $this->logger = $logger;
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
            $api_settings = $this->config_manager->getApiConfiguration();

            $data_source = new RestClientService(
                $api_settings->getUrl(),
                $api_settings->getTimeoutAfterRequest(),
                $api_settings->getApikey(),
                $api_settings->getApiSecret()
            );

            $import_admins = $this->import_factory->buildAdminImport(
                new EventoAdminImporter(
                    $data_source,
                    $this->logger,
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
        return $this->cp->txt('hourly_import_cj_title');
    }

    public function getDescription()
    {
        return $this->cp->txt('hourly_import_cj_desc');
    }

    public function isManuallyExecutable() : bool
    {
        return true;
    }

    public function hasCustomSettings() : bool
    {
        return true;
    }

    public function addCustomSettingsToForm(ilPropertyFormGUI $form) : void
    {
        $this->config_manager->form()->fillFormWithApiConfig($form);
    }

    public function saveCustomSettings(ilPropertyFormGUI $form) : bool
    {
        return $this->config_manager->form()->saveApiConfigFromForm($form);
    }
}

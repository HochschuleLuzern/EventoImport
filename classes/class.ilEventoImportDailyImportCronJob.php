<?php declare(strict_type=1);

use ILIAS\DI\RBACServices;
use ILIAS\Refinery\Factory;
use EventoImport\communication\EventoUserImporter;
use EventoImport\communication\ImporterIterator;
use EventoImport\config\ImporterApiSettings;
use EventoImport\communication\request_services\RestClientService;
use EventoImport\communication\EventoEventImporter;
use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\import\Logger;
use EventoImport\import\ImportTaskFactory;
use EventoImport\config\ConfigurationManager;
use EventoImport\config\CronConfigForm;

class ilEventoImportDailyImportCronJob extends ilCronJob
{
    public const ID = "crevento_daily_import";

    private ilEventoImportPlugin $cp;
    private ImportTaskFactory $import_factory;
    private ConfigurationManager $config_manager;
    private CronConfigForm $cron_config;
    private Logger $logger;

    public function __construct(
        \ilEventoImportPlugin $cp,
        ImportTaskFactory $import_factory,
        ConfigurationManager $config_manager,
        CronConfigForm $cron_config,
        Logger $logger
    ) {
        $this->cp = $cp;
        $this->import_factory = $import_factory;
        $this->config_manager = $config_manager;
        $this->cron_config = $cron_config;
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
        return self::SCHEDULE_TYPE_DAILY;
    }

    public function getDefaultScheduleValue()
    {
        return;
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

            $import_users = $this->import_factory->buildUserImport(
                new EventoUserImporter(
                    $data_source,
                    new ImporterIterator($api_settings->getPageSize()),
                    $this->logger,
                    $api_settings->getTimeoutFailedRequest(),
                    $api_settings->getMaxRetries()
                ),
                new EventoUserPhotoImporter(
                    $data_source,
                    $api_settings->getTimeoutFailedRequest(),
                    $api_settings->getMaxRetries(),
                    $this->logger
                )
            );

            $import_events = $this->import_factory->buildEventImport(
                new EventoEventImporter(
                    $data_source,
                    new ImporterIterator($api_settings->getPageSize()),
                    $this->logger,
                    $api_settings->getTimeoutFailedRequest(),
                    $api_settings->getMaxRetries()
                )
            );

            $import_users->run();
            $import_events->run();

            return new ilEventoImportResult(ilEventoImportResult::STATUS_OK, 'Cron job terminated successfully.');
        } catch (Exception $e) {
            return new ilEventoImportResult(ilEventoImportResult::STATUS_CRASHED, 'Cron job crashed: ' . $e->getMessage());
        }
    }

    public function getTitle()
    {
        return $this->cp->txt('daily_import_cj_title');
    }

    public function getDescription()
    {
        return $this->cp->txt('daily_import_cj_desc');
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
        $this->cron_config->fillFormWithApiConfig($form);
        $this->cron_config->fillFormWithUserImportConfig($form);
        $this->cron_config->fillFormWithEventLocationConfig($form);
        $this->cron_config->fillFormWithEventConfig($form);
    }

    public function saveCustomSettings(ilPropertyFormGUI $form) : bool
    {
        return $this->cron_config->saveApiConfigFromForm($form)
            && $this->cron_config->saveUserConfigFromForm($form)
            && $this->cron_config->saveEventLocationConfigFromForm($form)
            && $this->cron_config->saveEventConfigFromForm($form);
    }
}

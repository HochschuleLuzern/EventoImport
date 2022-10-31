<?php declare(strict_types=1);

use EventoImport\communication\EventoUserImporter;
use EventoImport\communication\ImporterIterator;
use EventoImport\communication\request_services\RestClientService;
use EventoImport\communication\EventoEventImporter;
use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\import\Logger;
use EventoImport\import\ImportTaskFactory;
use EventoImport\config\ConfigurationManager;
use EventoImport\communication\EventoEmployeeImporter;

class ilEventoImportDailyImportCronJob extends ilCronJob
{
    public const ID = "crevento_daily_import";

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

            $import_local_visitors = $this->import_factory->buildLocalVisitorImport(
                new EventoEmployeeImporter(
                    $data_source,
                    $this->logger,
                    $api_settings->getTimeoutAfterRequest(),
                    $api_settings->getMaxRetries()
                )
            );

            $import_users->run();
            $import_events->run();
            $import_local_visitors->run();

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
        $this->config_manager->form()->fillFormWithApiConfig($form);
        $this->config_manager->form()->fillFormWithUserImportConfig($form);
        $this->config_manager->form()->fillFormWithEventLocationConfig($form);
        $this->config_manager->form()->fillFormWithEventConfig($form);
        $this->config_manager->form()->fillFormWithVisitorConfig($form);
    }

    public function saveCustomSettings(ilPropertyFormGUI $form) : bool
    {
        return $this->config_manager->form()->saveApiConfigFromForm($form)
            && $this->config_manager->form()->saveUserConfigFromForm($form)
            && $this->config_manager->form()->saveEventLocationConfigFromForm($form)
            && $this->config_manager->form()->saveEventConfigFromForm($form)
            && $this->config_manager->form()->saveVisitorRolesConfigForm($form);
    }
}

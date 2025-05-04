<?php declare(strict_types = 1);

namespace EventoImport\administration;

use EventoImport\communication\api_models\ApiDataModelBase;
use EventoImport\communication\request_services\RestClientService;
use EventoImport\communication\EventoUserImporter;
use EventoImport\communication\EventoEventImporter;
use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\communication\EventoAdminImporter;
use EventoImport\communication\request_services\RequestClientService;
use EventoImport\config\ImporterApiSettings;

class EventoImportApiTester
{
    private \ilSetting $settings;
    private \ilDBInterface $db;

    public function __construct(\ilSetting $settings, \ilDBInterface $db)
    {
        $this->settings = $settings;
        $this->db = $db;
    }

    public function fetchDataRecord(string $cmd, int $id) : ?ApiDataModelBase
    {
        $api_importer_settings = new ImporterApiSettings($this->settings);
        $iterator = new \EventoImport\communication\ImporterIterator($api_importer_settings->getPageSize());
        $logger = new \EventoImport\import\Logger($this->db);

        $request_client = $this->buildDataSource($api_importer_settings);

        if ($cmd == 'user') {
            $importer = new EventoUserImporter(
                $request_client,
                $iterator,
                $logger,
                $api_importer_settings->getTimeoutAfterRequest(),
                $api_importer_settings->getMaxRetries()
            );
            return $importer->fetchUserDataRecordById($id);
        }

        if ($cmd == 'event') {
            $importer = new EventoEventImporter(
                $request_client,
                $iterator,
                $logger,
                $api_importer_settings->getTimeoutAfterRequest(),
                $api_importer_settings->getMaxRetries()
            );
            return $importer->fetchEventDataRecordById($id);
        }

        if ($cmd == 'photo') {
            $importer = new EventoUserPhotoImporter(
                $request_client,
                $api_importer_settings->getTimeoutAfterRequest(),
                $api_importer_settings->getMaxRetries(),
                $logger
            );
            return $importer->fetchUserPhotoDataById($id);
        }

        if ($cmd == 'admin') {
            $importer = new EventoAdminImporter(
                $request_client,
                $logger,
                $api_importer_settings->getTimeoutAfterRequest(),
                $api_importer_settings->getMaxRetries()
            );
            return $importer->fetchEventAdminDataRecordById($id);
        }

        return null;
    }

    public function fetchDataSet(string $cmd, int $skip, int $take) : array
    {
        $api_importer_settings = new ImporterApiSettings($this->settings);
        $iterator = new \EventoImport\communication\ImporterIterator($api_importer_settings->getPageSize());
        $logger = new \EventoImport\import\Logger($this->db);

        $request_client = $this->buildDataSource($api_importer_settings);

        if ($cmd == 'user') {
            $importer = new EventoUserImporter(
                $request_client,
                $iterator,
                $logger,
                $api_importer_settings->getTimeoutAfterRequest(),
                $api_importer_settings->getMaxRetries()
            );
            return $importer->fetchSpecificUserDataSet($skip, $take);
        }

        if ($cmd == 'event') {
            $importer = new EventoEventImporter(
                $request_client,
                $iterator,
                $logger,
                $api_importer_settings->getTimeoutAfterRequest(),
                $api_importer_settings->getMaxRetries()
            );
            return $importer->fetchSpecificEventDataSet($skip, $take);
        }

        return [];
    }

    public function fetchParameterlessDataset() : array
    {
        $api_importer_settings = new ImporterApiSettings($this->settings);
        $logger = new \EventoImport\import\Logger($this->db);

        $data_source = $this->buildDataSource($api_importer_settings);

        $importer = new EventoAdminImporter(
            $data_source,
            $logger,
            $api_importer_settings->getTimeoutAfterRequest(),
            $api_importer_settings->getMaxRetries()
        );
        return $importer->fetchAllIliasAdmins();
    }

    private function buildDataSource(ImporterApiSettings $api_importer_settings) : RequestClientService
    {
        return new RestClientService(
            $api_importer_settings->getUrl(),
            $api_importer_settings->getTimeoutAfterRequest(),
            $api_importer_settings->getApiKey(),
            $api_importer_settings->getApiSecret()
        );
    }
}

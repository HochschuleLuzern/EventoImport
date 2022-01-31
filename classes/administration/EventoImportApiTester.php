<?php declare(strict_types = 1);

namespace EventoImport\administration;

use EventoImport\communication\api_models\ApiDataModelBase;
use EventoImport\communication\request_services\RestClientService;
use EventoImport\communication\EventoUserImporter;
use EventoImport\communication\EventoEventImporter;
use EventoImport\communication\EventoUserPhotoImporter;
use EventoImport\communication\EventoAdminImporter;
use EventoImport\communication\request_services\RequestClientService;
use EventoImport\communication\ImporterApiSettings;

class EventoImportApiTester
{
    private \ilSetting $settings;

    public function __construct(\ilSetting $settings, \ilDBInterface $db = null)
    {
        global $DIC;

        $this->settings = $settings;
        $this->db = $db ?? $DIC->database();
    }

    public function fetchDataRecord(string $cmd, int $id) : ?ApiDataModelBase
    {
        $api_importer_settings = new ImporterApiSettings($this->settings);
        $iterator = new \ilEventoImporterIterator($api_importer_settings->getPageSize());
        $logger = new \ilEventoImportLogger($this->db);

        $request_client = $this->buildDataSource($api_importer_settings);

        if ($cmd == 'fetch_record_user') {
            $importer = new EventoUserImporter(
                $request_client,
                $iterator,
                $logger,
                $api_importer_settings->getTimeoutAfterRequest(),
                $api_importer_settings->getMaxRetries()
            );
            return $importer->fetchUserDataRecordById($id);
        } else {
            if ($cmd == 'fetch_record_event') {
                $importer = new EventoEventImporter(
                    $request_client,
                    $iterator,
                    $logger,
                    $api_importer_settings->getTimeoutAfterRequest(),
                    $api_importer_settings->getMaxRetries()
                );
                return $importer->fetchEventDataRecordById($id);
            } else {
                if ($cmd == 'fetch_user_photo') {
                    $importer = new EventoUserPhotoImporter(
                        $request_client,
                        $api_importer_settings->getTimeoutAfterRequest(),
                        $api_importer_settings->getMaxRetries(),
                        $logger
                    );
                    return $importer->fetchUserPhotoDataById($id);
                } else {
                    if ($cmd == 'fetch_ilias_admins_for_event') {
                        $importer = new EventoAdminImporter(
                            $request_client,
                            $logger,
                            $api_importer_settings->getTimeoutAfterRequest(),
                            $api_importer_settings->getMaxRetries()
                        );
                        return $importer->fetchEventAdminDataRecordById($id);
                    }
                }
            }
        }
    }

    public function fetchDataSet(string $cmd, int $skip, int $take) : array
    {
        $api_importer_settings = new ImporterApiSettings($this->settings);
        $iterator = new \ilEventoImporterIterator($api_importer_settings->getPageSize());
        $logger = new \ilEventoImportLogger($this->db);

        $request_client = $this->buildDataSource($api_importer_settings);

        if ($cmd == 'fetch_data_set_users') {
            $importer = new EventoUserImporter(
                $request_client,
                $iterator,
                $logger,
                $api_importer_settings->getTimeoutAfterRequest(),
                $api_importer_settings->getMaxRetries()
            );
            return $importer->fetchSpecificUserDataSet($skip, $take);
        } else {
            if ($cmd == 'fetch_data_set_events') {
                $importer = new EventoEventImporter(
                    $request_client,
                    $iterator,
                    $logger,
                    $api_importer_settings->getTimeoutAfterRequest(),
                    $api_importer_settings->getMaxRetries()
                );
                return $importer->fetchSpecificEventDataSet($skip, $take);
            }
        }
    }

    public function fetchParameterlessDataset(string $cmd) : array
    {
        $api_importer_settings = new ImporterApiSettings($this->settings);
        $logger = new \ilEventoImportLogger($this->db);

        $data_source = $this->buildDataSource($api_importer_settings);

        if ($cmd == 'fetch_all_ilias_admins') {
            $importer = new EventoAdminImporter(
                $data_source,
                $logger,
                $api_importer_settings->getTimeoutAfterRequest(),
                $api_importer_settings->getMaxRetries()
            );
            return $importer->fetchAllIliasAdmins();
        }
    }

    private function buildDataSource(ImporterApiSettings $api_importer_settings) : RequestClientService
    {
        return new RestClientService(
            $api_importer_settings->getUrl(),
            $api_importer_settings->getTimeoutAfterRequest(),
            $api_importer_settings->getApikey(),
            $api_importer_settings->getApiSecret()
        );
    }
}

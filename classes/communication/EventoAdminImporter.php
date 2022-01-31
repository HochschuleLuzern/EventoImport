<?php declare(strict_types = 1);

namespace EventoImport\communication;

use EventoImport\communication\generic_importers\DataSetImport;
use EventoImport\communication\generic_importers\SingleDataRecordImport;
use EventoImport\communication\request_services\RequestClientService;
use EventoImport\communication\api_models\EventoEventIliasAdmins;

/**
 * Class EventoAdminImporter
 * @package EventoImport\communication
 */
class EventoAdminImporter extends \ilEventoImporter
{
    use SingleDataRecordImport;
    use DataSetImport;

    /** @var string */
    private $fetch_single_record;

    /** @var string */
    private $fetch_all_admins;

    /**
     * EventoAdminImporter constructor.
     * @param RequestClientService  $data_source
     * @param \ilEventoImportLogger $logger
     * @param int                   $seconds_before_retry
     * @param int                   $max_retries
     */
    public function __construct(
        RequestClientService $data_source,
        \ilEventoImportLogger $logger,
        int $seconds_before_retry,
        int $max_retries
    ) {
        parent::__construct($data_source, $seconds_before_retry, $max_retries, $logger);

        $this->fetch_single_record = "GetIliasAdminsByIdEvent";
        $this->fetch_all_admins = "GetIliasAdmins";
    }

    public function getDataRecordMethodName() : string
    {
        return $this->fetch_single_record;
    }

    public function fetchAllIliasAdmins() : array
    {
        $response = $this->fetchDataSet(
            $this->data_source,
            $this->fetch_all_admins,
            [],
            $this->seconds_before_retry,
            $this->max_retries
        );

        return $response->getData();
    }

    public function fetchEventAdminDataRecordById(int $evento_event_id) : ?EventoEventIliasAdmins
    {
        $api_data = $this->fetchDataRecordById(
            $this->data_source,
            $this->fetch_single_record,
            $evento_event_id,
            $this->seconds_before_retry,
            $this->max_retries
        );

        return !is_null($api_data) ? new EventoEventIliasAdmins($api_data) : null;
    }
}

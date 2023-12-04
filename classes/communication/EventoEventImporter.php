<?php declare(strict_types = 1);

namespace EventoImport\communication;

use EventoImport\communication\generic_importers\DataSetImport;
use EventoImport\communication\generic_importers\SingleDataRecordImport;
use EventoImport\communication\request_services\RequestClientService;
use EventoImport\communication\api_models\EventoEvent;
use EventoImport\import\Logger;

class EventoEventImporter extends EventoImporterBase
{
    use SingleDataRecordImport;
    use DataSetImport;

    private ImporterIterator $iterator;
    protected string $fetch_data_set_method;
    protected string $fetch_data_record_method;


    public function __construct(
        RequestClientService $data_source,
        ImporterIterator $iterator,
        Logger $logger,
        int $seconds_before_retry,
        int $max_retries
    ) {
        parent::__construct($data_source, $seconds_before_retry, $max_retries, $logger);

        $this->iterator = $iterator;
        $this->fetch_data_set_method = 'GetEvents';
        $this->fetch_data_record_method = 'GetEventById';
    }

    public function fetchNextEventDataSet() : array
    {
        $skip = ($this->iterator->getPage() - 1) * $this->iterator->getPageSize();
        $take = $this->iterator->getPageSize();

        $response = $this->fetchDataSet(
            $this->data_source,
            $this->fetch_data_set_method,
            [
                "skip" => $skip,
                "take" => $take
            ],
            $this->seconds_before_retry,
            $this->max_retries
        );
        $this->iterator->nextPage();

        if (count($response->getData()) < 1) {
            $this->has_more_data = false;
            return [];
        }

        $this->has_more_data = $response->getHasMoreData();
        return $response->getData();
    }

    public function fetchSpecificEventDataSet(int $skip, int $take) : array
    {
        $response = $this->fetchDataSet(
            $this->data_source,
            $this->fetch_data_set_method,
            [
                "skip" => $skip,
                "take" => $take
            ],
            $this->seconds_before_retry,
            $this->max_retries
        );

        return $response->getData();
    }

    public function fetchEventDataRecordById(int $evento_event_id) : ?EventoEvent
    {
        $api_data = $this->fetchDataRecordById(
            $this->data_source,
            $this->fetch_data_record_method,
            $evento_event_id,
            $this->seconds_before_retry,
            $this->max_retries
        );

        return !is_null($api_data) ? new EventoEvent($api_data) : null;
    }
}

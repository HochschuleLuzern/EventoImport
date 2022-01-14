<?php

namespace EventoImport\communication;

use EventoImport\communication\generic_importers\DataSetImport;
use EventoImport\communication\generic_importers\SingleDataRecordImport;
use EventoImport\communication\request_services\RequestClientService;

/**
 * Class EventoEventImporter
 * @package EventoImport\communication
 */
class EventoEventImporter extends \ilEventoImporter
{
    use SingleDataRecordImport;
    use DataSetImport;

    /** @var \ilEventoImporterIterator */
    private \ilEventoImporterIterator $iterator;

    /** @var string */
    protected string $fetch_data_set_method;

    /** @var string */
    protected string $fetch_data_record_method;

    /**
     * EventoEventImporter constructor.
     * @param RequestClientService      $data_source
     * @param \ilEventoImporterIterator $iterator
     * @param \ilEventoImportLogger     $logger
     * @param int                       $seconds_before_retry
     * @param int                       $max_retries
     */
    public function __construct(
        RequestClientService $data_source,
        \ilEventoImporterIterator $iterator,
        \ilEventoImportLogger $logger,
        int $seconds_before_retry,
        int $max_retries
    ) {
        parent::__construct($data_source, $seconds_before_retry, $max_retries, $logger);

        $this->iterator = $iterator;
        $this->fetch_data_set_method = 'GetEvents';
        $this->fetch_data_record_method = 'GetEventById';
    }

    /**
     * @return array
     * @throws \Exception
     */
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
        } else {
            $this->has_more_data = $response->getHasMoreData();
            return $response->getData();
        }
    }

    /**
     * @param int $skip
     * @param int $take
     * @return array
     * @throws \Exception
     */
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

    /**
     * @param int $evento_event_id
     * @return array
     * @throws \Exception
     */
    public function fetchEventDataRecordById(int $evento_event_id) : array
    {
        return $this->fetchDataRecordById(
            $this->data_source,
            $this->fetch_data_record_method,
            $evento_event_id,
            $this->seconds_before_retry,
            $this->max_retries
        );
    }
}

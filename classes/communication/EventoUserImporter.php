<?php

namespace EventoImport\communication;

use EventoImport\communication\request_services\RequestClientService;
use EventoImport\communication\generic_importers\SingleDataRecordImport;
use EventoImport\communication\generic_importers\DataSetImport;

/**
 * Class EventoUserImporter
 * @package EventoImport\communication
 */
class EventoUserImporter extends \ilEventoImporter
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
     * EventoUserImporter constructor.
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

        $this->fetch_data_set_method = 'GetAccounts';
        $this->fetch_data_record_method = 'GetAccountById';
    }

    /**
     * @return string
     */
    public function getDataSetMethodName() : string
    {
        return $this->fetch_data_set_method;
    }

    /**
     * @return string
     */
    public function getDataRecordMethodName() : string
    {
        return $this->fetch_data_record_method;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function fetchNextUserDataSet() : array
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
    public function fetchSpecificUserDataSet(int $skip, int $take) : array
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
     * @param int $evento_user_id
     * @return array
     * @throws \Exception
     */
    public function fetchUserDataRecordById(int $evento_user_id) : array
    {
        return $this->fetchDataRecordById(
            $this->data_source,
            $this->fetch_data_record_method,
            $evento_user_id,
            $this->seconds_before_retry,
            $this->max_retries
        );
    }
}

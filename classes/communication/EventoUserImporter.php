<?php declare(strict_types = 1);

namespace EventoImport\communication;

use EventoImport\communication\request_services\RequestClientService;
use EventoImport\communication\generic_importers\SingleDataRecordImport;
use EventoImport\communication\generic_importers\DataSetImport;
use EventoImport\communication\api_models\EventoUser;

class EventoUserImporter extends \ilEventoImporter
{
    use SingleDataRecordImport;
    use DataSetImport;

    private \EventoImport\communication\ImporterIterator $iterator;
    protected string $fetch_data_set_method;
    protected string $fetch_data_record_method;

    public function __construct(
        RequestClientService $data_source,
        \EventoImport\communication\ImporterIterator $iterator,
        \EventoImport\import\Logger $logger,
        int $seconds_before_retry,
        int $max_retries
    ) {
        parent::__construct($data_source, $seconds_before_retry, $max_retries, $logger);

        $this->iterator = $iterator;

        $this->fetch_data_set_method = 'GetAccounts';
        $this->fetch_data_record_method = 'GetAccountById';
    }

    public function getDataSetMethodName() : string
    {
        return $this->fetch_data_set_method;
    }

    public function getDataRecordMethodName() : string
    {
        return $this->fetch_data_record_method;
    }

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

    public function fetchUserDataRecordById(int $evento_user_id) : ?EventoUser
    {
        $api_data = $this->fetchDataRecordById(
            $this->data_source,
            $this->fetch_data_record_method,
            $evento_user_id,
            $this->seconds_before_retry,
            $this->max_retries
        );

        return !is_null($api_data) ? new EventoUser($api_data) : null;
    }
}

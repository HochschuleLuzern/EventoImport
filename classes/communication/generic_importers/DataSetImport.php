<?php

namespace EventoImport\communication\generic_importers;

use EventoImport\communication\request_services\RequestClientService;
use EventoImport\communication\api_models\EventoImportDataSetResponse;

class DataSetImport
{
    private $data_source;
    private $max_retries;
    private $seconds_before_retry;

    public function __construct(RequestClientService $data_source, int $max_retries, int $seconds_before_retry)
    {
        $this->data_source = $data_source;
        $this->max_retries = $max_retries;
        $this->seconds_before_retry = $seconds_before_retry;
    }

    private function fetchDataSet(string $method_name, array $params) : EventoImportDataSetResponse
    {
        $nr_of_tries = 0;
        do {
            try {
                $json_response = $this->data_source->sendRequest($method_name, $params);
                $json_response_decoded = json_decode($json_response, true);

                $response = new EventoImportDataSetResponse($json_response_decoded);
                $request_was_successful = $response->getSuccess();
            } catch (\Exception $e) {
                $request_was_successful = false;
            } finally {
                $nr_of_tries++;
            }

            if (!$request_was_successful) {
                if ($nr_of_tries < $this->max_retries) {
                    sleep($this->seconds_before_retry);
                } else {
                    throw new \Exception("After $nr_of_tries there was still no successful call to API");
                }
            }
        } while (!$request_was_successful);

        return $response;
    }

    public function fetchPagedDataSet(string $method_name, int $skip, int $take) : EventoImportDataSetResponse
    {
        return $this->fetchDataSet(
            $method_name,
            [
                "skip" => $skip,
                "take" => $take
            ]
        );
    }

    public function fetchDataSetParameterless($method_name) : EventoImportDataSetResponse
    {
        return $this->fetchDataSet(
            $method_name,
            [ ]
        );
    }
}

<?php

namespace EventoImport\communication\generic_importers;

use EventoImport\communication\request_services\RequestClientService;

class SingleDataRecordImport
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

    public function fetchDataRecordById(string $method_name, $id)
    {
        $params = array(
            "id" => (int) $id
        );

        $nr_of_tries = 0;
        do {
            try {
                $json_response = $this->data_source->sendRequest($method_name, $params);

                $request_was_successful = $this->requestWasSuccessful($json_response);
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

        if (!is_null($json_response)) {
            return json_decode($json_response, true);
        } else {
            return null;
        }
    }

    private function requestWasSuccessful(string $json_response) : bool
    {
        return !(is_null($json_response) || $json_response == '');
    }
}
<?php

namespace EventoImport\communication\generic_importers;

use EventoImport\communication\request_services\RequestClientService;

/**
 * Class SingleDataRecordImport
 * @package EventoImport\communication\generic_importers
 */
trait SingleDataRecordImport
{
    /**
     * @param string $method_name
     * @param        $id
     * @return mixed|null
     * @throws \Exception
     */
    protected function fetchDataRecordById(RequestClientService $data_source, string $method_name, int $id, int $seconds_before_retry, int $max_retries)
    {
        $params = array(
            "id" => (int) $id
        );

        $nr_of_tries = 0;
        do {
            try {
                $plain_response = $data_source->sendRequest($method_name, $params);

                $request_was_successful = $this->requestWasSuccessful($plain_response);
            } catch (\Exception $e) {
                $request_was_successful = false;
            } finally {
                $nr_of_tries++;
            }

            if (!$request_was_successful) {
                if ($nr_of_tries < $max_retries) {
                    sleep($seconds_before_retry);
                } else {
                    throw new \Exception("After $nr_of_tries there was still no successful call to API");
                }
            }
        } while (!$request_was_successful);

        if (!is_null($plain_response)) {
            return json_decode($plain_response, true);
        } else {
            return null;
        }
    }

    /**
     * @param string $json_response
     * @return bool
     */
    private function requestWasSuccessful(string $json_response) : bool
    {
        return !(is_null($json_response) || $json_response == '');
    }
}

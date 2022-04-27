<?php declare(strict_types = 1);

namespace EventoImport\communication\generic_importers;

use EventoImport\communication\request_services\RequestClientService;

trait SingleDataRecordImport
{
    protected function fetchDataRecordById(RequestClientService $data_source, string $method_name, int $id, int $seconds_before_retry, int $max_retries) : ?array
    {
        $params = array(
            "id" => (int) $id
        );

        $nr_of_tries = 0;
        $plain_response = null;
        do {
            try {
                $plain_response = $data_source->sendRequest($method_name, $params);

                // If request for a specific data record was not successful, an exception will be thrown
                $request_was_successful = true;
            } catch (\Exception $e) {
                $request_was_successful = false;
            } finally {
                $nr_of_tries++;
            }

            if (!$request_was_successful) {
                if ($nr_of_tries < $max_retries) {
                    sleep($seconds_before_retry);
                } else {
                    throw new \ilEventoImportCommunicationException(
                        self::class,
                        [
                            'method_name' => $method_name,
                            'request_params' => ['id' => $id]
                        ],
                        "After $nr_of_tries tries, there was still no successful call to the API"
                    );
                }
            }
        } while (!$request_was_successful);

        if (!is_null($plain_response) && $plain_response != '') {
            return json_decode($plain_response, true, 10, JSON_THROW_ON_ERROR);
        } else {
            return null;
        }
    }
}

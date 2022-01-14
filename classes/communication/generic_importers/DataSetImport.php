<?php

namespace EventoImport\communication\generic_importers;

use EventoImport\communication\request_services\RequestClientService;
use EventoImport\communication\api_models\EventoImportDataSetResponse;

/**
 * Class DataSetImport
 * @package EventoImport\communication\generic_importers
 */
trait DataSetImport
{
    /**
     * @param string $method_name
     * @param array  $request_params
     * @return EventoImportDataSetResponse
     * @throws \Exception
     */
    protected function fetchDataSet(RequestClientService $data_source, string $method_name, array $request_params, int $seconds_before_retry, int $max_retries) : EventoImportDataSetResponse
    {
        $nr_of_tries = 0;
        do {
            try {
                $json_response = $data_source->sendRequest($method_name, $request_params);
                $json_response_decoded = json_decode($json_response, true);

                $response = new EventoImportDataSetResponse($json_response_decoded);
                $request_was_successful = $response->getSuccess();
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

        return $response;
    }
}

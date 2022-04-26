<?php declare(strict_types = 1);

namespace EventoImport\communication\generic_importers;

use EventoImport\communication\request_services\RequestClientService;
use EventoImport\communication\api_models\EventoImportDataSetResponse;

trait DataSetImport
{
    protected function fetchDataSet(RequestClientService $data_source, string $method_name, array $request_params, int $seconds_before_retry, int $max_retries) : EventoImportDataSetResponse
    {
        $nr_of_tries = 0;
        do {
            try {
                $json_response = $data_source->sendRequest($method_name, $request_params);
                $json_response_decoded = json_decode($json_response, true, 10, JSON_THROW_ON_ERROR);

                $response = new EventoImportDataSetResponse($json_response_decoded);
                $request_was_successful = $response->getSuccess();
            } catch (\ilEventoImportApiDataException $e) {
                global $DIC;
                $DIC->logger()->root()->log('Error in API-Response for requested data set: ' . $e->getMessage());
            } catch (\JsonException $e) {
                if (!isset($json_response)) {
                    $json_response = '';
                }

                throw new \ilEventoImportApiDataException(
                    'API-Data-String to JSON',
                    'Conversion of API response from JSON-String to JSON-Array failed',
                    [
                        'api_string' => $json_response,
                        'json_error' => $e->getMessage()
                    ]
                );
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
                            'request_params' => $request_params
                        ],
                        "After $nr_of_tries tries, there was still no successful call to the API"
                    );
                }
            }
        } while (!$request_was_successful);

        return $response;
    }
}

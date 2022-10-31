<?php declare(strict_types=1);

namespace EventoImport\communication;

use EventoImport\communication\request_services\RequestClientService;
use EventoImport\import\Logger;
use EventoImport\communication\generic_importers\DataSetImport;
use EventoImport\communication\api_models\EventoDepartmentKindDataSet;

class EventoEmployeeImporter extends EventoImporterBase
{
    use DataSetImport;

    private string $fetch_all_employees;

    public function __construct(
        RequestClientService $data_source,
        Logger $logger,
        int $seconds_before_retry,
        int $max_retries
    ) {
        parent::__construct($data_source, $seconds_before_retry, $max_retries, $logger);

        $this->fetch_all_employees = "GetEmployees";
    }

    public function fetchEmployees(string $department_name, string $kind) : array
    {
        $request_params = [
            "dep" => $department_name,
            "kind" => $kind
        ];

        $nr_of_tries = 0;
        do {
            try {
                $json_response = $this->data_source->sendRequest($this->fetch_all_employees, $request_params);
                $json_response_decoded = json_decode($json_response, true, 10, JSON_THROW_ON_ERROR);

                $response = new EventoDepartmentKindDataSet($json_response_decoded);
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
                if ($nr_of_tries < $this->max_retries) {
                    sleep($this->seconds_before_retry);
                } else {
                    throw new \ilEventoImportCommunicationException(
                        self::class,
                        [
                            'method_name' => $this->fetch_all_employees,
                            'request_params' => $request_params
                        ],
                        "After $nr_of_tries tries, there was still no successful call to the API"
                    );
                }
            }
        } while (!$request_was_successful);

        return $response->getData();
    }
}
<?php

namespace EventoImport\communication\importer_traits;

use EventoImport\communication\request_services\RequestClientService;

trait DataSetImport
{
    abstract public function getDataSource() : RequestClientService;
    abstract public function getDataSetMethodName() : string;

    public function fetchSpecificDataSet(int $skip, int $take)
    {
        $json_response_decoded = $this->fetchDataSet($skip, $take);

        if (count($json_response_decoded['data']) < 1) {
            $this->has_more_data = false;
            return [];
        } elseif (!$json_response_decoded['hasMoreData']) {
            $this->has_more_data = false;
        }

        return $json_response_decoded['data'];
    }

    protected function fetchDataSet(int $skip, int $take)
    {
        $params = array(
            "skip" => $skip,
            "take" => $take
        );

        $json_response = $this->getDataSource()->sendRequest($this->getDataSetMethodName(), $params);

        return $this->validateResponseAndGetAsJsonStructure($json_response);
    }

    private function validateResponseAndGetAsJsonStructure(string $json_response)
    {
        $json_response_decoded = json_decode($json_response, true);

        $missing_fields = array();

        if (!isset($json_response_decoded['success'])) {
            $missing_fields[] = '"success"';
        }

        if (!isset($json_response_decoded['hasMoreData'])) {
            $missing_fields[] = '"hasMoreData"';
        }

        if (!isset($json_response_decoded['message'])) {
            $missing_fields[] = '"message"';
        }

        // Data must be set an be an array. If the evento import does not have any data left, the array MUST be empty
        if (!isset($json_response_decoded['data']) || !is_array($json_response_decoded['data'])) {
            $missing_fields[] = '"data"';
        }

        if (count($missing_fields) > 0) {
            throw new \Exception('Following fields are missing a correct value: ' . implode(', ', $missing_fields));
        }

        return $json_response_decoded;
    }
}

<?php

namespace EventoImport\communication\importer_traits;

use EventoImport\communication\request_services\RequestClientService;

trait SingleDataRecordImport
{
    abstract public function getDataSource() : RequestClientService;
    abstract public function getDataRecordMethodName() : string;

    public function fetchDataRecordById($id)
    {
        $params = array(
            "id" => (int) $id
        );

        $json_response = $this->data_source->sendRequest($this->getDataRecordMethodName(), $params);

        if (!is_null($json_response)) {
            return json_decode($json_response, true);
        } else {
            return null;
        }
    }
}

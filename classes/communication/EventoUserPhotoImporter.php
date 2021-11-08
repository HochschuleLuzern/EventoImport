<?php

namespace EventoImport\communication;

class EventoUserPhotoImporter implements DataRecordImporter
{
    public function __construct(\EventoImport\communication\request_services\RequestClientService $data_source)
    {
        $this->data_source = $data_source;
    }

    public function fetchDataRecord($id)
    {
    }
}

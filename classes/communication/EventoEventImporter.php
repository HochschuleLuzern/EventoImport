<?php

namespace EventoImport\communication;

use EventoImport\communication\request_services\RequestClientService;

class EventoEventImporter extends \ilEventoImporter implements DataRecordImporter, DataSetImporter
{
    public function __construct(\ilEventoImporterIterator $iterator, \ilSetting $settings, \ilEventoImportLogger $logger, \EventoImport\communication\request_services\RequestClientService $data_source)
    {
        $this->fetch_data_set_method = 'GetEvents';
        $this->fetch_data_record_method = 'GetEventById';

        parent::__construct($iterator, $settings, $logger, $data_source);
    }
}
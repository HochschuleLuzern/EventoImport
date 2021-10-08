<?php

namespace EventoImport\communication;

use EventoImport\communication\request_services\RequestClientService;

class EventoEventImporter extends \ilEventoImporter implements DataRecordImporter, DataSetImporter
{
    public function __construct(\ilEventoImporterIterator $iterator, \ilSetting $settings, \ilEventoImportLogger $logger, \EventoImport\communication\request_services\RestClientService $data_source)
    {
        $this->fetch_data_set_method = '';
        $this->fetch_data_record_method = '';

        parent::__construct($iterator, $settings, $logger, $data_source);
    }
}
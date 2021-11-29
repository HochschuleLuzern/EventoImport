<?php

namespace EventoImport\communication;

use EventoImport\communication\importer_traits\DataSetImport;
use EventoImport\communication\importer_traits\SingleDataRecordImport;

class EventoEventImporter extends \ilEventoImporter
{
    use DataSetImport;
    use SingleDataRecordImport;

    protected $iterator;
    protected $fetch_data_set_method;
    protected $fetch_data_record_method;

    public function __construct(\ilEventoImporterIterator $iterator, ApiImporterSettings $settings, \ilEventoImportLogger $logger, \EventoImport\communication\request_services\RequestClientService $data_source)
    {
        parent::__construct($data_source, $settings, $logger);

        $this->iterator = $iterator;
        $this->fetch_data_set_method = 'GetEvents';
        $this->fetch_data_record_method = 'GetEventById';
    }

    public function fetchNextDataSet()
    {
        $skip = ($this->iterator->getPage() - 1) * $this->iterator->getPageSize();
        $take = $this->iterator->getPageSize();

        $json_response_decoded = $this->fetchDataSet($skip, $take);

        if (count($json_response_decoded['data']) < 1) {
            $this->has_more_data = false;
            return [];
        } elseif (!$json_response_decoded['hasMoreData']) {
            $this->has_more_data = false;
        }

        return $json_response_decoded['data'];
    }

    public function getDataSetMethodName() : string
    {
        return $this->fetch_data_set_method;
    }

    public function getDataRecordMethodName() : string
    {
        return $this->fetch_data_record_method;
    }
}

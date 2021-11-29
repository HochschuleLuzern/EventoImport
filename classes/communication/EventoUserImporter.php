<?php

namespace EventoImport\communication;

use EventoImport\communication\importer_traits\DataSetImport;
use EventoImport\communication\importer_traits\SingleDataRecordImport;
use EventoImport\communication\importer_traits\DataSetWithIteratorImport;

class EventoUserImporter extends \ilEventoImporter
{
    use DataSetImport;
    use DataSetWithIteratorImport;
    use SingleDataRecordImport;

    protected $iterator;
    protected $fetch_data_set_method;
    protected $fetch_data_record_method;

    public function __construct(\ilEventoImporterIterator $iterator, ApiImporterSettings $settings, \ilEventoImportLogger $logger, \EventoImport\communication\request_services\RequestClientService $data_source)
    {
        parent::__construct($data_source, $settings, $logger);

        $this->iterator = $iterator;
        $this->fetch_data_set_method = 'GetAccounts';
        $this->fetch_data_record_method = 'GetAccountById';
    }

    public function getDataSetMethodName() : string
    {
        return $this->fetch_data_set_method;
    }

    public function getDataRecordMethodName() : string
    {
        return $this->fetch_data_record_method;
    }

    protected function getIterator() : \ilEventoImporterIterator
    {
        return $this->iterator;
    }
}

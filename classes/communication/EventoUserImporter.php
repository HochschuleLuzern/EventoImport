<?php

namespace EventoImport\communication;

use EventoImport\communication\importer_traits\DataSetImport;
use EventoImport\communication\importer_traits\SingleDataRecordImport;
use EventoImport\communication\importer_traits\DataSetWithIteratorImport;

class EventoUserImporter extends \ilEventoImporter implements EventoSingleDataRecordImporter, EventoDataSetImporter
{
    private $iterator;
    private $data_set_import;
    private $data_record_import;

    protected $fetch_data_set_method;
    protected $fetch_data_record_method;

    public function __construct(
        generic_importers\DataSetImport $data_set_import,
        generic_importers\SingleDataRecordImport $data_record_import,
        \ilEventoImporterIterator $iterator,
        \ilEventoImportLogger $logger
    ) {
        parent::__construct($logger);

        $this->iterator = $iterator;
        $this->data_set_import = $data_set_import;
        $this->data_record_import = $data_record_import;

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

    public function fetchNextDataSet() : array
    {
        $skip = ($this->iterator->getPage() - 1) * $this->iterator->getPageSize();
        $take = $this->iterator->getPageSize();

        $response = $this->data_set_import->fetchPagedDataSet($this->fetch_data_set_method, $skip, $take);

        if (count($response->getData()) < 1) {
            $this->has_more_data = false;
            return [];
        } else {
            $this->has_more_data = $response->getHasMoreData();
            return $response->getData();
        }
    }

    public function fetchSpecificDataSet(int $skip, int $take) : array
    {
        $response = $this->data_set_import->fetchPagedDataSet($this->fetch_data_set_method, $skip, $take);
        return $response->getData();
    }

    public function fetchDataRecordById(int $evento_id) : array
    {
        return $this->data_record_import->fetchDataRecordById($this->fetch_data_record_method, $evento_id);
    }
}

<?php

namespace EventoImport\communication\importer_traits;

trait DataSetWithIteratorImport
{
    use DataSetImport;

    protected $has_more_data = true;
    protected $iterator;

    public function fetchNextDataSet()
    {
        $iterator = $this->getIterator();
        $skip = ($iterator->getPage() - 1) * $iterator->getPageSize();
        $take = $iterator->getPageSize();

        $json_response_decoded = $this->fetchDataSet($skip, $take);
        $iterator->nextPage();

        if (count($json_response_decoded['data']) < 1) {
            $this->has_more_data = false;
            return [];
        } elseif (!$json_response_decoded['hasMoreData']) {
            $this->has_more_data = false;
        }

        return $json_response_decoded['data'];
    }

    public function hasMoreData() : bool
    {
        return $this->has_more_data;
    }

    abstract protected function getIterator() : \ilEventoImporterIterator;
}

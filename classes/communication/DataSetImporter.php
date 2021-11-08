<?php

namespace EventoImport\communication;

interface DataSetImporter
{
    public function fetchNextDataSet();
    public function hasMoreData() : bool;
}

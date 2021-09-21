<?php

namespace EventoImport\communication;

interface DataRecordImporter
{
    public function fetchNextDataRecord($id);
}
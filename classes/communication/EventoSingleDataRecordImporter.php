<?php

namespace EventoImport\communication;

interface EventoSingleDataRecordImporter
{
    public function fetchDataRecordById(int $id) : array;
}

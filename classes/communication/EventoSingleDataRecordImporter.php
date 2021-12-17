<?php

namespace EventoImport\communication;

/**
 * Interface EventoSingleDataRecordImporter
 * @package EventoImport\communication
 */
interface EventoSingleDataRecordImporter
{
    /**
     * Fetches a single data record by its id
     *
     * @param int $id
     * @return array
     */
    public function fetchDataRecordById(int $id) : array;
}

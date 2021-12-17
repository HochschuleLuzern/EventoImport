<?php

namespace EventoImport\communication;

/**
 * Interface EventoDataSetImporter
 * @package EventoImport\communication
 */
interface EventoDataSetImporter
{
    /**
     * Use a given iterator object to fetch data sets
     * @return array
     */
    public function fetchNextDataSet() : array;

    /**
     * Fetch a specific data set
     *
     * @param int $skip
     * @param int $take
     * @return array
     */
    public function fetchSpecificDataSet(int $skip, int $take) : array;
}

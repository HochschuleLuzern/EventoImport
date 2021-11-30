<?php

namespace EventoImport\communication;

interface EventoDataSetImporter
{
    public function fetchNextDataSet() : array;
    public function fetchSpecificDataSet(int $skip, int $take) : array;
}

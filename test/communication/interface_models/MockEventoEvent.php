<?php

class MockEventoEvent extends \EventoImport\communication\api_models\EventoEvent
{
    public function __construct(array $data_set)
    {
        try {
            parent::__construct($data_set);
        } catch (ilEventoImportApiDataException $e) {
        }
    }
}

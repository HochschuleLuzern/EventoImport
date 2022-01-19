<?php

class MockEventoUser extends \EventoImport\communication\api_models\EventoUser
{
    public function __construct(array $data_set)
    {
        try {
            parent::__construct($data_set);
        } catch (ilEventoImportApiDataException $e) {
        }
    }
}

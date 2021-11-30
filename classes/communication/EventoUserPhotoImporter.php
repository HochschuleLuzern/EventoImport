<?php

namespace EventoImport\communication;

use EventoImport\communication\generic_importers\SingleDataRecordImport;

class EventoUserPhotoImporter extends \ilEventoImporter implements EventoSingleDataRecordImporter
{
    private $data_record_importer;
    private $fetch_data_record_method;

    public function __construct(
        generic_importers\SingleDataRecordImport $data_record_importer,
        \ilEventoImportLogger $logger
    ) {
        parent::__construct($logger);

        $this->data_record_importer = $data_record_importer;

        $this->fetch_data_record_method = 'GetPhotoById';
    }

    public function fetchDataRecordById(int $evento_id) : array
    {
        return $this->data_record_importer->fetchDataRecordById($this->fetch_data_record_method, $evento_id);
    }
}

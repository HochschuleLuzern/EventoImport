<?php

namespace EventoImport\communication;

use EventoImport\communication\importer_traits\SingleDataRecordImport;

class EventoUserPhotoImporter extends \ilEventoImporter
{
    use SingleDataRecordImport;

    /**
     * @var string
     */
    private $fetch_data_record_method;

    public function __construct(
        \EventoImport\communication\request_services\RequestClientService $data_source,
        \EventoImport\communication\ApiImporterSettings $settings,
        \ilEventoImportLogger $logger
    ) {
        parent::__construct($data_source, $settings, $logger);

        $this->fetch_data_record_method = 'GetPhotoById';
    }

    public function getDataRecordMethodName() : string
    {
        return $this->fetch_data_record_method;
    }
}

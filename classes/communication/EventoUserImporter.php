<?php

namespace EventoImport\communication;

use EventoImport\communication\request_services\RequestClientService;
use ilEventoImporterIterator;
use ilEventoImportLogger;
use ilSetting;

class EventoUserImporter extends \ilEventoImporter implements DataRecordImporter, DataSetImporter
{
    public function __construct(ilEventoImporterIterator $iterator, ApiImporterSettings $settings, ilEventoImportLogger $logger, \EventoImport\communication\request_services\RequestClientService $data_source)
    {
        $this->fetch_data_set_method = 'GetAccounts';
        $this->fetch_data_record_method = 'GetAccountById';

        parent::__construct($iterator, $settings, $logger, $data_source);
    }
}

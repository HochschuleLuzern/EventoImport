<?php

namespace EventoImport\communication;

class EventoAdminImporter extends \ilEventoImporter implements EventoSingleDataRecordImporter
{
    private $fetch_single_record;
    private $fetch_all_admins;

    private $data_set_importer;
    private $data_record_importer;

    public function __construct(
        generic_importers\DataSetImport $data_set_importer,
        generic_importers\SingleDataRecordImport $data_record_importer,
        \ilEventoImportLogger $logger
    ) {
        parent::__construct($logger);

        $this->data_set_importer = $data_set_importer;
        $this->data_record_importer = $data_record_importer;

        $this->fetch_single_record = "GetIliasAdminsByIdEvent";
        $this->fetch_all_admins = "GetIliasAdmins";
    }

    public function getDataRecordMethodName() : string
    {
        return $this->fetch_single_record;
    }

    public function fetchAllIliasAdmins()
    {
        $response = $this->data_set_importer->fetchDataSetParameterless($this->fetch_all_admins);

        return $response->getData();
    }

    public function fetchDataRecordById(int $EventoEventid) : array
    {
        return $this->data_record_importer->fetchDataRecordById($this->fetch_single_record, $EventoEventid);
    }
}

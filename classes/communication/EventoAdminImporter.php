<?php

namespace EventoImport\communication;

/**
 * Class EventoAdminImporter
 * @package EventoImport\communication
 */
class EventoAdminImporter extends \ilEventoImporter implements EventoSingleDataRecordImporter
{
    /** @var string */
    private $fetch_single_record;

    /** @var string */
    private $fetch_all_admins;

    /** @var generic_importers\DataSetImport */
    private $data_set_importer;

    /** @var generic_importers\SingleDataRecordImport */
    private $data_record_importer;

    /**
     * EventoAdminImporter constructor.
     * @param generic_importers\DataSetImport          $data_set_importer
     * @param generic_importers\SingleDataRecordImport $data_record_importer
     * @param \ilEventoImportLogger                    $logger
     */
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

    public function fetchAllIliasAdmins() : array
    {
        $response = $this->data_set_importer->fetchDataSetParameterless($this->fetch_all_admins);

        return $response->getData();
    }

    public function fetchDataRecordById(int $EventoEventid) : array
    {
        return $this->data_record_importer->fetchDataRecordById($this->fetch_single_record, $EventoEventid);
    }
}

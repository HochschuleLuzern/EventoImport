<?php

namespace EventoImport\communication;

/**
 * Class EventoEventImporter
 * @package EventoImport\communication
 */
class EventoEventImporter extends \ilEventoImporter implements EventoSingleDataRecordImporter, EventoDataSetImporter
{
    /** @var \ilEventoImporterIterator */
    private \ilEventoImporterIterator $iterator;

    /** @var generic_importers\DataSetImport */
    private generic_importers\DataSetImport $data_set_import;

    /** @var generic_importers\SingleDataRecordImport */
    private generic_importers\SingleDataRecordImport $data_record_import;

    /** @var string */
    protected string $fetch_data_set_method;

    /** @var string */
    protected string $fetch_data_record_method;

    /**
     * EventoEventImporter constructor.
     * @param generic_importers\DataSetImport          $data_set_import
     * @param generic_importers\SingleDataRecordImport $data_record_import
     * @param \ilEventoImporterIterator                $iterator
     * @param \ilEventoImportLogger                    $logger
     */
    public function __construct(
        generic_importers\DataSetImport $data_set_import,
        generic_importers\SingleDataRecordImport $data_record_import,
        \ilEventoImporterIterator $iterator,
        \ilEventoImportLogger $logger
    ) {
        parent::__construct($logger);

        $this->iterator = $iterator;
        $this->data_set_import = $data_set_import;
        $this->data_record_import = $data_record_import;

        $this->fetch_data_set_method = 'GetEvents';
        $this->fetch_data_record_method = 'GetEventById';
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function fetchNextDataSet() : array
    {
        $skip = ($this->iterator->getPage() - 1) * $this->iterator->getPageSize();
        $take = $this->iterator->getPageSize();

        $response = $this->data_set_import->fetchPagedDataSet($this->fetch_data_set_method, $skip, $take);
        $this->iterator->nextPage();

        if (count($response->getData()) < 1) {
            $this->has_more_data = false;
            return [];
        } else {
            $this->has_more_data = $response->getHasMoreData();
            return $response->getData();
        }
    }

    /**
     * @param int $skip
     * @param int $take
     * @return array
     * @throws \Exception
     */
    public function fetchSpecificDataSet(int $skip, int $take) : array
    {
        $response = $this->data_set_import->fetchPagedDataSet($this->fetch_data_set_method, $skip, $take);
        return $response->getData();
    }

    /**
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function fetchDataRecordById(int $id) : array
    {
        return $this->data_record_import->fetchDataRecordById($this->fetch_data_record_method, $id);
    }
}

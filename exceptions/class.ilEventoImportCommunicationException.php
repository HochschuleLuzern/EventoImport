<?php declare(strict_types = 1);

class ilEventoImportCommunicationException extends ilException
{
    private string $importer_class_name;
    private array $request_params;

    public function __construct(string $importer_class_name, array $request_params, $a_message, $a_code = 0)
    {
        parent::__construct($a_message, $a_code);

        $this->importer_class_name = $importer_class_name;
        $this->request_params = $request_params;
    }

    public function getImporterClassName() : string
    {
        return $this->importer_class_name;
    }

    public function getRequestPrams() : array
    {
        return $this->request_uri;
    }
}

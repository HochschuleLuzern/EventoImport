<?php

namespace EventoImport\communication\api_models;

/**
 * Class ApiDataModelBase
 * @package EventoImport\communication\api_models
 */
abstract class ApiDataModelBase
{
    use JSONDataValidator;

    /** @var array */
    protected array $decoded_api_data;

    protected function checkErrorsAndMaybeThrowException()
    {
        if (count($this->key_errors) > 0) {
            $error_message = 'One or more fields in the given array were invalid or missing: ';
            foreach ($this->key_errors as $field => $error) {
                $error_message .= "Field $field - $error; ";
            }

            throw new \ilEventoImportApiDataException('Create obj: ' . self::class, $error_message, $this->decoded_api_data);
        }
    }

    /**
     * @return array
     */
    abstract public function getDecodedApiData() : array;
}

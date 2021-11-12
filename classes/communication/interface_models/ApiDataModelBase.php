<?php

namespace EventoImport\communication\api_models;

abstract class ApiDataModelBase
{
    use JSONDataValidator;

    protected function checkErrorsAndMaybeThrowException()
    {
        if (count($this->key_errors) > 0) {
            $error_message = 'One or more fields in the given array were invalid: ';
            foreach ($this->key_errors as $field => $error) {
                $error_message .= "Field $field - $error; ";
            }

            throw new \InvalidArgumentException($error_message);
        }
    }
}

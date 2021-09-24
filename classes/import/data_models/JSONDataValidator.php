<?php

namespace EventoImport\import\data_models;

trait JSONDataValidator
{
    protected $key_errors = array();

    protected function validateAndReturnNumber(array $data_array, string $key) : ?int
    {
        if(!isset($data_array[$key])) {
            $this->key_errors[$key] = 'Value not set';
            return null;
        }

        return (int)$data_array[$key];
    }

    protected function validateAndReturnString(array $data_array, string $key) : ?string
    {
        if(!isset($data_array[$key])) {
            $this->key_errors[$key] = 'Value not set';
            return null;
        }

        return $data_array[$key];
    }

    protected function validateAndReturnArray(array $data_array, string $key) : ?array
    {
        if(!isset($data_array[$key])) {
            $this->key_errors[$key] = 'Value not set';
            return null;
        } else if(!is_array($data_array)) {
            $this->key_errors[$key] = 'Value MUST be an array';
            return null;
        }

        return $data_array[$key];
    }

    protected function validateCombineAndReturnListOfValues(array $data_array, array $key_list, bool $is_empty_list_allowed = false) : ?array
    {
        $list_of_values = array();

        foreach($key_list as $key) {
            if(isset($data_array[$key]) && !in_array($data_array[$key], $list_of_values)) {
                $list_of_values[] = $data_array[$key];
            }
        }

        if($is_empty_list_allowed && count($list_of_values) == 0) {
            $this->key_errors[implode(', ', $key_list)] = 'None of the keys are set in the array';
            return null;
        }

        return $list_of_values;
    }
}
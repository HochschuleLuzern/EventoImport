<?php declare(strict_types = 1);

namespace EventoImport\communication\api_models;

/**
 * Trait JSONDataValidator
 * @package EventoImport\communication\api_models
 */
trait JSONDataValidator
{
    /** @var array */
    protected array $key_errors = array();

    /**
     * @param array  $data_array
     * @param string $key
     * @return int|null
     */
    protected function validateAndReturnNumber(array $data_array, string $key) : ?int
    {
        if (!isset($data_array[$key])) {
            $this->key_errors[$key] = 'Value not set';
            return null;
        }

        return (int) $data_array[$key];
    }

    /**
     * @param array  $data_array
     * @param string $key
     * @return string|null
     */
    protected function validateAndReturnString(array $data_array, string $key) : ?string
    {
        if (!isset($data_array[$key])) {
            $this->key_errors[$key] = 'Value not set';
            return null;
        }

        return $data_array[$key];
    }

    protected function validateAndReturnBoolean(array $data_array, string $key, bool $as_string_possible = false, bool $is_mandatory = true) : ?bool
    {
        if (!isset($data_array[$key])) {
            if ($is_mandatory) {
                $this->key_errors[$key] = 'Value not set';
            }
            return null;
        }

        if (is_bool($data_array[$key])) {
            return $data_array[$key];
        } elseif ($as_string_possible && is_string($data_array[$key])) {
            if ($data_array[$key] == 'true') {
                return true;
            } elseif ($data_array[$key] == 'false') {
                return false;
            } else {
                $this->key_errors[$key] = 'Invalid string given as boolean';
                return null;
            }
        } else {
            $this->key_errors[$key] = 'Value ist not a boolean';
            return null;
        }
    }

    /**
     * @param array  $data_array
     * @param string $key
     * @return array|null
     */
    protected function validateAndReturnArray(array $data_array, string $key) : ?array
    {
        if (!isset($data_array[$key])) {
            $this->key_errors[$key] = 'Value not set';
            return null;
        } elseif (!is_array($data_array)) {
            $this->key_errors[$key] = 'Value MUST be an array';
            return null;
        }

        return $data_array[$key];
    }

    /**
     * @param array $data_array
     * @param array $key_list
     * @param bool  $is_empty_list_allowed
     * @return array|null
     */
    protected function validateCombineAndReturnListOfValues(array $data_array, array $key_list, bool $is_empty_list_allowed = false) : ?array
    {
        $list_of_values = array();

        foreach ($key_list as $key) {
            if (isset($data_array[$key]) && !in_array($data_array[$key], $list_of_values)) {
                $list_of_values[] = $data_array[$key];
            }
        }

        if ($is_empty_list_allowed && count($list_of_values) == 0) {
            $this->key_errors[implode(', ', $key_list)] = 'None of the keys are set in the array';
            return null;
        }

        return $list_of_values;
    }

    /**
     * @param array $data_array
     * @param array $key_list
     * @param bool  $is_empty_list_allowed
     * @return array|null
     */
    protected function validateCombineAndReturnListOfNonEmptyStrings(array $data_array, array $key_list, bool $is_empty_list_allowed = false) : ?array
    {
        $list_of_values = array();

        foreach ($key_list as $key) {
            if (
                isset($data_array[$key])
                && is_string($data_array[$key])
                && strlen($data_array[$key]) > 0
                && !in_array($data_array[$key], $list_of_values)
            ) {
                $list_of_values[] = $data_array[$key];
            }
        }

        if ($is_empty_list_allowed && count($list_of_values) == 0) {
            $this->key_errors[implode(', ', $key_list)] = 'None of the keys are set in the array';
            return null;
        }

        return $list_of_values;
    }

    /**
     * @param array  $data_array
     * @param string $key
     * @return \DateTime|null
     * @throws \Exception
     */
    protected function validateAndReturnDateTime(array $data_array, string $key) : ?\DateTime
    {
        if (!isset($data_array[$key])) {
            $this->key_errors[$key] = 'Value not set';
            return null;
        }

        return new \DateTime($data_array[$key]);
    }
}

<?php

namespace EventoImport\communication\api_models;

/**
 * Class EventoImportDataSetResponse
 * @package EventoImport\communication\api_models
 */
class EventoImportDataSetResponse
{
    use JSONDataValidator;

    public const JSON_SUCCESS = 'success';
    public const JSON_HAS_MORE_DATA = 'hasMoreData';
    public const JSON_MESSAGE = 'message';
    public const JSON_DATA = 'data';

    /** @var bool */
    private ?bool $success;

    /** @var bool  */
    private ?bool $has_more_data;

    /** @var string */
    private ?string $message;

    /** @var array */
    private ?array $data;

    /**
     * EventoImportDataSetResponse constructor.
     *
     * @param array $json_response
     */
    public function __construct(array $json_response)
    {
        $this->success = $this->validateAndReturnBoolean($json_response, self::JSON_SUCCESS);
        $this->has_more_data = $this->validateAndReturnBoolean($json_response, self::JSON_HAS_MORE_DATA);
        $this->success = $this->validateAndReturnString($json_response, self::JSON_MESSAGE);
        $this->data = $this->validateAndReturnArray($json_response, self::JSON_DATA);

        if (count($this->key_errors) > 0) {
            $error_message = 'Following fields are missing a correct value: ';
            foreach ($this->key_errors as $field => $error) {
                $error_message .= "Field $field - $error; ";
            }

            throw new \InvalidArgumentException($error_message);
        }
    }

    /**
     * @return bool
     */
    public function getSuccess() : bool
    {
        return $this->success;
    }

    /**
     * @return bool
     */
    public function getHasMoreData() : bool
    {
        return $this->has_more_data;
    }

    /**
     * @return string
     */
    public function getMessage() : string
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }
}

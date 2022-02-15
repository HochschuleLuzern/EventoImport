<?php declare(strict_types = 1);

namespace EventoImport\communication\api_models;

class EventoImportDataSetResponse
{
    use JSONDataValidator;

    public const JSON_SUCCESS = 'success';
    public const JSON_HAS_MORE_DATA = 'hasMoreData';
    public const JSON_MESSAGE = 'message';
    public const JSON_DATA = 'data';

    private ?bool $success;
    private ?bool $has_more_data;
    private ?string $message;
    private ?array $data;

    public function __construct(array $json_response)
    {
        $this->success = $this->validateAndReturnBoolean($json_response, self::JSON_SUCCESS);
        $this->has_more_data = $this->validateAndReturnBoolean($json_response, self::JSON_HAS_MORE_DATA);
        $this->message = $this->validateAndReturnString($json_response, self::JSON_MESSAGE);
        $this->data = $this->validateAndReturnArray($json_response, self::JSON_DATA);

        if (count($this->key_errors) > 0) {
            $error_message = 'Following fields are missing a correct value: ';
            foreach ($this->key_errors as $field => $error) {
                $error_message .= "Field $field - $error; ";
            }

            throw new \ilEventoImportApiDataException(self::class, $error_message, $json_response);
        }
    }

    public function getSuccess() : bool
    {
        return $this->success;
    }

    public function getHasMoreData() : bool
    {
        return $this->has_more_data;
    }

    public function getMessage() : string
    {
        return $this->message;
    }

    public function getData() : array
    {
        return $this->data;
    }
}

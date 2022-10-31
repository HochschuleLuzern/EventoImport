<?php declare(strict_types=1);

namespace EventoImport\communication\api_models;

class EventoDepartmentKindDataSet
{
    use JSONDataValidator;

    public const JSON_SUCCESS = 'success';
    public const JSON_MESSAGE = 'message';
    public const JSON_DEPARTMENT = 'department';
    public const JSON_EVENT_KIND = 'eventKind';
    public const JSON_DATA = 'data';

    private ?bool $success;
    private ?string $message;
    private ?string $department;
    private ?string $event_kind;
    private ?array $data;

    public function __construct(array $json_response)
    {
        $this->success = $this->validateAndReturnBoolean($json_response, self::JSON_SUCCESS);
        $this->message = $this->validateAndReturnString($json_response, self::JSON_MESSAGE);
        $this->department = $this->validateAndReturnString($json_response, self::JSON_DEPARTMENT);
        $this->event_kind = $this->validateAndReturnString($json_response, self::JSON_EVENT_KIND);
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

    public function getMessage() : string
    {
        return $this->message;
    }

    public function getDepartment() : string
    {
        return $this->department;
    }

    public function getEventKind() : string
    {
        return $this->event_kind;
    }

    public function getData() : array
    {
        return $this->data;
    }
}
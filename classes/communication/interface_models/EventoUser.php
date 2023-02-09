<?php declare(strict_types = 1);

namespace EventoImport\communication\api_models;

class EventoUser extends ApiDataModelBase
{
    const JSON_ID = 'idAccount';
    const JSON_LAST_NAME = 'lastName';
    const JSON_FIRST_NAME = 'firstName';
    const JSON_GENDER = 'gender';
    const JSON_LOGIN_NAME = 'loginName';
    const JSON_EMAIL = 'email';
    const JSON_EMAIL_2 = 'email2';
    const JSON_EMAIL_3 = 'email3';
    const JSON_ROLES = 'roles';
    const JSON_IS_LOCKDOWN_ACCOUNT = 'isLockdownAccount';

    private ?int $evento_id;
    private ?string $last_name;
    private ?string $first_name;
    private ?string $gender;
    private ?string $login_name;
    private ?array $email_list;
    private ?array $roles;
    private ?bool $is_lockdown_account;

    public function __construct(array $data_set)
    {
        $this->evento_id = $this->validateAndReturnNumber($data_set, self::JSON_ID);
        $this->last_name = $this->validateAndReturnString($data_set, self::JSON_LAST_NAME);
        $this->first_name = $this->validateAndReturnString($data_set, self::JSON_FIRST_NAME);
        $this->gender = $this->validateAndReturnString($data_set, self::JSON_GENDER);
        $this->login_name = $this->validateAndReturnString($data_set, self::JSON_LOGIN_NAME);
        $this->email_list = $this->validateCombineAndReturnListOfNonEmptyStrings($data_set, [self::JSON_EMAIL, self::JSON_EMAIL_2, self::JSON_EMAIL_3], false);
        $this->roles = $this->validateAndReturnArray($data_set, self::JSON_ROLES);
        $this->is_lockdown_account = $this->validateAndReturnBoolean($data_set, self::JSON_IS_LOCKDOWN_ACCOUNT, false, false) ?? false;

        $this->decoded_api_data = $data_set;
        $this->checkErrorsAndMaybeThrowException();
    }

    public function getEventoId() : int
    {
        return $this->evento_id;
    }

    public function getLastName() : string
    {
        return $this->last_name;
    }

    public function getFirstName() : string
    {
        return $this->first_name;
    }

    public function getGender() : string
    {
        return $this->gender;
    }

    public function getLoginName() : string
    {
        return $this->login_name;
    }

    public function getEmailList() : array
    {
        return $this->email_list;
    }

    public function getRoles() : array
    {
        return $this->roles;
    }

    public function isLockdownAccount() : bool
    {
        return $this->is_lockdown_account;
    }

    public function getDecodedApiData() : array
    {
        return $this->decoded_api_data;
    }
}

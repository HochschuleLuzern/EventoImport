<?php

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

    private $evento_id;
    private $last_name;
    private $first_name;
    private $gender;
    private $login_name;
    private $email_list;
    private $roles;

    public function __construct(array $data_set)
    {
        $this->evento_id = $this->validateAndReturnNumber($data_set, self::JSON_ID);
        $this->last_name = $this->validateAndReturnString($data_set, self::JSON_LAST_NAME);
        $this->first_name = $this->validateAndReturnString($data_set, self::JSON_FIRST_NAME);
        $this->gender = $this->validateAndReturnString($data_set, self::JSON_GENDER);
        $this->login_name = $this->validateAndReturnString($data_set, self::JSON_LOGIN_NAME);
        $this->email_list = $this->validateCombineAndReturnListOfValues($data_set, [self::JSON_EMAIL, self::JSON_EMAIL_2, self::JSON_EMAIL_3], false);
        $this->roles = $this->validateAndReturnArray($data_set, self::JSON_ROLES);

        $this->checkErrorsAndMaybeThrowException();
        $this->decoded_api_data = $data_set;
    }

    /**
     * @return int
     */
    public function getEventoId() : int
    {
        return $this->evento_id;
    }

    /**
     * @return string
     */
    public function getLastName() : string
    {
        return $this->last_name;
    }

    /**
     * @return string
     */
    public function getFirstName() : string
    {
        return $this->first_name;
    }

    /**
     * @return string
     */
    public function getGender() : string
    {
        return $this->gender;
    }

    /**
     * @return string
     */
    public function getLoginName() : string
    {
        return $this->login_name;
    }

    /**
     * @return array
     */
    public function getEmailList() : array
    {
        return $this->email_list;
    }

    /**
     * @return array
     */
    public function getRoles() : array
    {
        return $this->roles;
    }

    /**
     * @return array
     */
    public function getDecodedApiData() : array
    {
        return $this->decoded_api_data;
    }
}

<?php

namespace EventoImport\import\data_models;

class EventoUser
{
    use JSONDataValidator;

    const JSON_ID = 'Id';
    const JSON_LAST_NAME = 'LastName';
    const JSON_FIRST_NAME = 'FirstName';
    const JSON_GENDER = 'Gender';
    const JSON_LOGIN_NAME = 'LoginName';
    const JSON_EMAIL = 'Email';
    const JSON_EMAIL_2 = 'Email2';
    const JSON_EMAIL_3 = 'Email3';
    const JSON_ROLES = 'Roles';

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

        if(count($this->key_errors) > 0) {
            $error_message = 'One or more fields in the given array were invalid: ';
            foreach($this->key_errors as $field => $error) {
                $error_message .= "Field $field - $error; ";
            }

            throw new \InvalidArgumentException($error_message);
        }
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
}
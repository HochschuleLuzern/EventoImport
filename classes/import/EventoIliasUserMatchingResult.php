<?php

class EventoIliasUserMatchingResult
{
    public const RESULT_NO_MATCHING_USER = 1;
    public const RESULT_EXACTLY_ONE_MATCHING_USER = 2;
    public const RESULT_CONVERT_EXISTING_CREATE_NEW = 3;
    public const RESULT_CONFLICT_OF_ACCOUNTS = 4;
    public const RESULT_ERROR = 5;

    private $matched_user_id;

    private $match_by_login;
    private $match_by_evento_id;
    private $match_by_email;

    private $should_create_new_user;

    public function __construct(int $result_code, ?int $matched_user_id = null, array $additional_params = array()) {
        $this->matched_user_id = $matched_user_id;
        $this->match_type = $result_code;

        /*
        $this->match_by_login = $match_by_login;
        $this->match_by_evento_id = $match_by_evento_id;
        $this->match_by_email = $match_by_email;
        */
    }

    public function getMatchedUserId() : ?int
    {
        return $this->matched_user_id;
    }
}
<?php

class EventoIliasUserMatchingResult
{
    public const RESULT_NO_MATCHING_USER = 1;
    public const RESULT_EXACTLY_ONE_MATCHING_USER = 2;
    public const RESULT_ONE_CONFLICTING_USER = 3;
    public const RESULT_CONFLICT_OF_ACCOUNTS = 4;
    public const RESULT_ERROR = 5;

    private $matched_user_id;
    private $result_code;
    private $additional_params;

    private $match_by_login;
    private $match_by_evento_id;
    private $match_by_email;

    private $should_create_new_user;

    public function __construct(int $result_code, ?int $matched_user_id = null, array $additional_params = array()) {
        $this->matched_user_id = $matched_user_id;
        $this->result_code = $result_code;
        $this->additional_params = $additional_params;
    }

    public function getResultType() : int
    {
        return $this->result_code;
    }

    public function getMatchedUserId() : ?int
    {
        return $this->matched_user_id;
    }

    public function getAdditionalParams() : array
    {
        return $this->additional_params;
    }

    public static function NoMatchingUserResult() : EventoIliasUserMatchingResult
    {
        return new EventoIliasUserMatchingResult(self::RESULT_NO_MATCHING_USER);
    }

    public static function ExactlyOneMatchingUserResult(int $ilias_user_id) : EventoIliasUserMatchingResult
    {
        return new EventoIliasUserMatchingResult(self::RESULT_EXACTLY_ONE_MATCHING_USER, $ilias_user_id);
    }

    public static function ConflictingUserToConvertResult(array $conflicting_user_data) : EventoIliasUserMatchingResult
    {
        return new EventoIliasUserMatchingResult(self::RESULT_ONE_CONFLICTING_USER, 0, $conflicting_user_data);
    }

    public static function MultiUserConflict() : EventoIliasUserMatchingResult
    {
        return new EventoIliasUserMatchingResult(self::RESULT_CONFLICT_OF_ACCOUNTS);
    }

    public static function Error() : EventoIliasUserMatchingResult
    {
        return new EventoIliasUserMatchingResult(self::RESULT_ERROR);
    }

    /*
     *     public const RESULT_NO_MATCHING_USER = 1;
    public const RESULT_EXACTLY_ONE_MATCHING_USER = 2;
    public const RESULT_CONVERT_EXISTING_CREATE_NEW = 3;
    public const RESULT_CONFLICT_OF_ACCOUNTS = 4;
    public const RESULT_ERROR = 5;
     */
}
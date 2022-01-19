<?php

namespace EventoImport\communication\api_models;

/**
 * Class EventoEvent
 * @package EventoImport\communication\api_models
 */
class EventoEvent extends ApiDataModelBase
{
    public const JSON_ID = 'idEvent';
    public const JSON_NAME = 'name';
    public const JSON_DESCRIPTION = 'description';
    public const JSON_TYPE = 'type';
    public const JSON_KIND = 'kind';
    public const JSON_DEPARTMENT = 'department';
    public const JSON_START_DATE = 'startDate';
    public const JSON_END_DATE = 'endDate';
    public const JSON_IS_CREATE_COURSE_FLAG = 'isCreateCourse';

    public const JSON_IS_GROUP_MEMBER_FLAG = 'isGroupMember';
    public const JSON_GROUP_UNIQUE_KEY = 'groupUniqueKey';
    public const JSON_GROUP_ID = 'groupId';
    public const JSON_GROUP_NAME = 'groupName';
    public const JSON_GROUP_MEMBER_COUNT = 'groupMemberCount';

    public const JSON_EMPLOYEES = 'employeeAccounts';
    public const JSON_STUDENTS = 'studentAccounts';

    public const EVENTO_TYPE_MODULANLASS = 'Modulanlass';
    public const EVENTO_TYPE_KURS = 'Kurs';

    /** @var int */
    private ?int $evento_id;

    /** @var string */
    private ?string $name;

    /** @var string*/
    private ?string $description;

    /** @var string */
    private ?string $type;

    /** @var string */
    private ?string $kind;

    /** @var string */
    private ?string $department;

    /** @var \DateTime */
    private ?\DateTime $start_date;

    /** @var \DateTime */
    private ?\DateTime $end_date;

    /** @var bool */
    private ?bool $is_create_course_flag;

    /** @var bool */
    private ?bool $is_group_member_flag;

    /** @var string */
    private ?string $group_unique_key;

    /** @var int */
    private ?int $group_id;

    /** @var string */
    private ?string $group_name;

    /** @var int */
    private ?int $group_member_count;

    /** @var array */
    private array $employees;

    /** @var array */
    private array $students;

    private static $CREATE_COURSES = 0;
    private static $MAX_CREATE_COURSE = 4000;

    /**
     * EventoEvent constructor.
     * @param array $data_set
     * @throws \Exception
     */
    public function __construct(array $data_set)
    {
        $this->evento_id = $this->validateAndReturnNumber($data_set, self::JSON_ID);
        $this->name = $this->validateAndReturnString($data_set, self::JSON_NAME);
        $this->description = $this->validateAndReturnString($data_set, self::JSON_DESCRIPTION);
        $this->type = $this->validateAndReturnString($data_set, self::JSON_TYPE);
        $this->kind = $this->validateAndReturnString($data_set, self::JSON_KIND);
        $this->department = $this->validateAndReturnString($data_set, self::JSON_DEPARTMENT);
        $this->start_date = $this->validateAndReturnDateTime($data_set, self::JSON_START_DATE);
        $this->end_date = $this->validateAndReturnDateTime($data_set, self::JSON_END_DATE);
        $this->is_create_course_flag = $this->validateAndReturnBoolean($data_set, self::JSON_IS_CREATE_COURSE_FLAG);

        $this->is_group_member_flag = $this->validateAndReturnBoolean($data_set, self::JSON_IS_GROUP_MEMBER_FLAG);
        $this->group_id = $this->validateAndReturnNumber($data_set, self::JSON_GROUP_ID);
        $this->group_unique_key = $this->validateAndReturnString($data_set, self::JSON_GROUP_UNIQUE_KEY);
        $this->group_name = $this->validateAndReturnString($data_set, self::JSON_GROUP_NAME);
        $this->group_member_count = $this->validateAndReturnNumber($data_set, self::JSON_GROUP_MEMBER_COUNT);

        $list_employees = $this->validateAndReturnArray($data_set, self::JSON_EMPLOYEES);
        $list_students = $this->validateAndReturnArray($data_set, self::JSON_STUDENTS);

        if (!is_null($list_employees) && !is_null($list_students)) {
            $this->employees = $this->buildMembershipList($list_employees);
            $this->students = $this->buildMembershipList($list_students);
        }

        $this->decoded_api_data = $data_set;
        $this->checkErrorsAndMaybeThrowException();
    }

    /**
     * @param array $account_list
     * @return array
     */
    private function buildMembershipList(array $account_list) : array
    {
        $typed_list = [];
        foreach ($account_list as $account_data) {
            $typed_list[] = new EventoUserShort($account_data);
        }
        return $typed_list;
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
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getKind() : string
    {
        return $this->kind;
    }

    /**
     * @return string
     */
    public function getDepartment() : string
    {
        return $this->department;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate() : \DateTime
    {
        return $this->start_date;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate() : \DateTime
    {
        return $this->end_date;
    }

    /**
     * @return bool
     */
    public function hasCreateCourseFlag() : bool
    {
        return $this->is_create_course_flag;
    }

    /**
     * @return bool
     */
    public function hasGroupMemberFlag() : bool
    {
        return $this->is_group_member_flag;
    }

    /**
     * @return string
     */
    public function getGroupUniqueKey() : string
    {
        return $this->group_unique_key;
    }

    /**
     * @return int
     */
    public function getGroupId() : int
    {
        return $this->group_id;
    }

    /**
     * @return string
     */
    public function getGroupName() : string
    {
        return $this->group_name;
    }

    /**
     * @return int
     */
    public function getGroupMemberCount() : int
    {
        return $this->group_member_count;
    }

    /**
     * @return array
     */
    public function getEmployees() : array
    {
        return $this->employees;
    }

    /**
     * @return array
     */
    public function getStudents() : array
    {
        return $this->students;
    }

    /**
     * @return array
     */
    public function getDecodedApiData() : array
    {
        return $this->decoded_api_data;
    }
}

<?php

namespace EventoImport\communication\api_models;

class EventoEvent
{
    use JSONDataValidator;

    public const JSON_ID = 'Id';
    public const JSON_NAME = 'Name';
    public const JSON_DESCRIPTION = 'Description';
    public const JSON_TYPE = 'Type';
    public const JSON_KIND = 'Kind';
    public const JSON_DEPARTMENT = 'Department';
    public const JSON_START_DATE = 'StartDate';
    public const JSON_END_DATE = 'EndDate';
    public const JSON_IS_CREATE_COURSE_FLAG = 'IsCreateCourse';
    public const JSON_IS_GROUP_MEMBER_FLAG = 'IsGroupMember';
    public const JSON_GROUP_NAME = 'GroupName';
    public const JSON_GROUP_MEMBER_COUNT = 'GroupMemberCount';
    public const JSON_EMPLOYEES = 'Employees';
    public const JSON_STUDENTS = 'Students';

    public const EVENTO_TYPE_MODULANLASS = 'Modulanlass';
    public const EVENTO_TYPE_KURS = 'Kurs';

    private $evento_id;
    private $name;
    private $description;
    private $type;
    private $kind;
    private $department;
    private $start_date;
    private $end_date;
    private $is_create_course_flag;
    private $is_group_member_flag;
    private $group_name;
    private $group_member_count;
    private $employees;
    private $students;

    public function __construct(array $data_set)
    {
        $this->evento_id             = $this->validateAndReturnNumber($data_set, self::JSON_ID);
        $this->name                  = $this->validateAndReturnString($data_set, self::JSON_NAME);
        $this->description           = $this->validateAndReturnString($data_set, self::JSON_DESCRIPTION);
        $this->type                  = $this->validateAndReturnString($data_set, self::JSON_TYPE);
        $this->kind                  = $this->validateAndReturnString($data_set, self::JSON_KIND);
        $this->department            = $this->validateAndReturnString($data_set, self::JSON_DEPARTMENT);
        $this->start_date            = $this->validateAndReturnDateTime($data_set, self::JSON_START_DATE);
        $this->end_date              = $this->validateAndReturnDateTime($data_set, self::JSON_END_DATE);
        $this->is_create_course_flag = $this->validateAndReturnBoolean($data_set, self::JSON_IS_CREATE_COURSE_FLAG);
        $this->is_group_member_flag  = $this->validateAndReturnBoolean($data_set, self::JSON_IS_GROUP_MEMBER_FLAG);
        $this->group_name            = $this->validateAndReturnString($data_set, self::JSON_GROUP_NAME);
        $this->group_member_count    = $this->validateAndReturnNumber($data_set, self::JSON_GROUP_MEMBER_COUNT);
        $this->employees             = $this->validateAndReturnArray($data_set, self::JSON_EMPLOYEES);
        $this->students              = $this->validateAndReturnArray($data_set, self::JSON_STUDENTS);

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
}
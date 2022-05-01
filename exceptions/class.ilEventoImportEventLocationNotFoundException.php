<?php declare(strict_types = 1);

class ilEventoImportEventLocationNotFoundException extends ilException
{
    public const MISSING_DEPARTMENT = 1;
    public const MISSING_KIND = 2;
    public const MISSING_YEAR = 3;

    private bool $department_exists;
    private bool $kind_exists;
    private bool $year_exists;

    public function __construct(
        string $a_message,
        int $a_code
    ) {
        switch ($a_code) {
            case self::MISSING_YEAR:
                $this->department_exists = true;
                $this->kind_exists = true;
                $this->year_exists = false;
                break;
            case self::MISSING_KIND:
                $this->department_exists = true;
                $this->kind_exists = false;
                $this->year_exists = false;
                break;
            case self::MISSING_DEPARTMENT:
                $this->department_exists = false;
                $this->kind_exists = false;
                $this->year_exists = false;
                break;
            default:
                throw new Exception("Unknown exception code given for ilEventoImportEventLocationNotFoundException");
        }

        parent::__construct($a_message, $a_code);
    }

    public function departmentExists() : bool
    {
        return $this->department_exists;
    }

    public function kindExists() : bool
    {
        return $this->kind_exists;
    }

    public function yearExists() : bool
    {
        return $this->year_exists;
    }
}

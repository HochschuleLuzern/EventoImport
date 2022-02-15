<?php

class ilEventoImportCronStateChecker
{
    private ilDBInterface $db;

    public function __construct(ilDBInterface $db)
    {
        $this->db = $db;
    }

    public function wasFullImportAlreadyRunToday() : bool
    {
        // Try to get the date from the last run
        $sql = "SELECT * FROM cron_job WHERE job_id = " . $this->db->quote(ilEventoImportImport::ID, \ilDBConstants::T_TEXT);
        $res = $this->db->query($sql);
        $cron = $this->db->fetchAssoc($res);

        $last_run = $cron['job_result_ts'];
        if (is_null($last_run)) {
            return false;
        }

        return date('d.m.Y H:i') == date('d.m.Y H:i', strtotime($cron['job_result_ts']));
    }
}

<?php

/**
 * Class JobsShell
 */
class PeriodicJobsShell
{
    /**
     * @var Job
     */
    public $jobsModel;

    /**
     * @var PeriodicJob
     */
    public $periodicJobsModel;


    /**
     * JobsShell constructor.
     */
    public function __construct()
    {
        set_time_limit(0);
        $this->jobsModel = new Job();
        $this->periodicJobsModel = new PeriodicJob();
    }

    /**
     * 主程
     * Author : Gabriel
     */
    public function main()
    {
        $executeTime = $this->_executeTime();
        $executeTimeStr = $this->_executeTimeStr();

        $periodicJobs = $this->periodicJobsModel->where([
            'status' => PeriodicJob::STATUS_EXECUTABLE
        ])->getAll('*');

        if (!empty($periodicJobs)) {
            foreach ($periodicJobs as $pj) {
                $periodTime = PeriodicJob::PeriodTime($pj);
                if ($periodTime == $executeTimeStr) {
                    $this->_buildJob($pj, $executeTime);
                }
            }
        }
    }

    private function _executeTimeStr($t = null)
    {
        $t = empty($t) ? $this->_executeTime() : $t;
        return date('YmdH', $t);
    }

    private function _executeTime()
    {
        return mktime(date('H') + 1, 0, 0);
    }

    private function _buildJob($pj, $executeTime)
    {
        $job = [
            'job' => $pj['pjob'],
            'priority' => $pj['priority'],
            'execute_after' => date('Y-m-d H:i:s', $executeTime),
            'params' => $pj['params'],
            'status' => Job::STATUS_UNEXECUTED,
            'created_at' => date('Y-m-d H:i:s'),
            'pjid' => $pj['pjid']
        ];
        $jid = $this->jobsModel->insert($job);

        if (!empty($jid)) {
            $msg = '[Success]BuildPeriodicJobs('.$pj['pjid'].'): '.$pj['pjob'].' periodic jobs will execute at '.date('Y-m-d H:i:s', $executeTime).'.';
            $this->jobsModel->saveLog($msg, ['pjid' => $pj['pjid']]);
            echo $msg . PHP_EOL;
            return true;
        } else {
            $msg = '[Fail]BuildPeriodicJobs('.$pj['pjid'].'): '.$pj['pjob'].' periodic jobs will execute at '.date('Y-m-d H:i:s', $executeTime).'.';
            $this->jobsModel->saveLog($msg, ['pjid' => $pj['pjid']]);
            echo $msg . PHP_EOL;
            return false;
        }
    }
}

<?php
/**
 * Class JobsShell
 */
class JobsShell
{
    /**
     * @var Jobs
     */
    public $jobsModel;

    /**
     * @var
     */
    public $exceptionMessage;

    const MAX_TASK_ATTEMPTS = 3;
    const TASKS_PER_POLLING = 10;

    /**
     * JobsShell constructor.
     */
    public function __construct()
    {
        set_time_limit(0);
        $this->jobsModel = new Job();
    }

    /**
     * 主程
     * Author : Gabriel
     * @return int
     */
    public function main()
    {
        //检查执行中任务数
        $runningJobsCnt = $this->_isTooManyJobsExecuting();
        if ($runningJobsCnt) {
            $msg = "Jobs: {$runningJobsCnt} jobs are executing, waiting for next loop.";
            $this->jobsModel->saveLog($msg, ['running_jobs_cnt' => $runningJobsCnt]);
            echo $msg . PHP_EOL;
            return -1;
        }

        //获取要执行的任务
        $jobs = $this->_fetchJobs();
        echo 'Jobs: ' . count($jobs) . ' jobs found' . PHP_EOL;

        $jobCnt = 0;
        if (!empty($jobs)) {
            //更新job状态执行中
            $this->_updateJobsAsExecuting($jobs);

            foreach ($jobs as $job) {
                //判断尝试次数
                $attempts = $job['attempts'];
                if ($attempts >= self::MAX_TASK_ATTEMPTS) {
                    $this->jobsModel->update([
                        'status' => Job::STATUS_TOO_MANY_ATTAMPTS
                    ], ['jid' => $job['jid']]);
                    continue;
                }

                //判断过期

                //记录任务开始
                $this->_logJobStart($job);

                //执行任务
                $rt = $this->_executeTask($job);

                //记录结果
                $this->_logJobResult($job, $rt);

                $jobCnt++;
            }
        }
        echo "Jobs: finished {$jobCnt} jobs" . PHP_EOL;
        return -1;
    }

    /**
     * 记录结果
     * Author : Gabriel
     * @param $job
     * @param $rt
     */
    private function _logJobResult($job, $rt)
    {
        if ($rt == Job::RESULT_OK) {
            $this->jobsModel->update([
                'attempts' => ++$job['attempts'],
                'status' => Job::STATUS_EXECUTED,
                'last_attempt_time' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ], ['jid' => $job['jid']]);
            $msg = "[Success]job-{$job['jid']}: {$job['job']}";
            $logdata = [
                'jid' => $job['jid'],
                'job' => $job['job']
            ];
            $this->jobsModel->saveLog($msg, $logdata);
            echo  $msg . PHP_EOL;
        } elseif ($rt == Job::RESULT_NOT_IN_TIME) {
            echo "[Failed]job-{$job['jid']}: {$job['job']} not in execute time.".PHP_EOL;
            $this->jobsModel->update(['status' => Job::STATUS_UNEXECUTED], ['jid' => $job['jid']]);
        } else {
            if ($rt == Job::RESULT_EXCEPTION) {
                $msg = "[Exception]job-{$job['jid']}:{$job['job']}:(" . $this->exceptionMessage . ")";
                $this->jobsModel->update([
                    'status' => Job::STATUS_EXCEPTION,
                    'msg' => $this->exceptionMessage
                ], ['jid' => $job['jid']]);
            } else {
                $msg = "[Failed]job-{$job['jid']}:{$job['job']}";
            }
            $logdata = [
                'jid' => $job['jid'],
                'job' => $job['job']
            ];
            $this->jobsModel->saveLog($msg, $logdata);
            CommonFunction::logRecords($logdata);
            echo $msg . PHP_EOL;
        }
    }

    /**
     * 执行任务
     * Author : Gabriel
     * @param $job
     * @return bool|int
     */
    private function _executeTask($job)
    {
        //检查执行时间
        if (!empty($job['execute_after'])
            && time() < strtotime($job['execute_after'])) {
            return Job::RESULT_NOT_IN_TIME;
        }

        $className = $job['job'];
        $dept = Wave::app()->config['dept'];
        $dirname = ROOT_PATH . '/crontab/' . $dept . '/';
        $basename = $className . '.php';
        include_once $dirname . $basename;
        if (!class_exists($className)) {
            return false;
        }

        $params = empty($job['params']) ? null : $job['params'];

        try {
            $j = new $className();
            $rt = $j->handle($job['jid'], $params);
            return $rt ? Job::RESULT_OK : Job::RESULT_FAILED;
        } catch (Exception $e) {
            $this->exceptionMessage = $e->getMessage();
            return Job::RESULT_EXCEPTION;
        }
    }

    /**
     * 更新job状态执行中
     * Author : Gabriel
     * @param $jobs
     */
    private function _updateJobsAsExecuting($jobs)
    {
        if (empty($jobs)) {
            return;
        }

        $jobIds = array_map(function ($v) {
            return $v['jid'];
        }, $jobs);

        return $this->jobsModel->in(['jid' => implode($jobIds)])
                        ->update([
                            'status' => Job::STATUS_EXECUTING
                        ], ['status' => Job::STATUS_UNEXECUTED]);
    }

    /**
     * 检查执行中任务数
     * Author : Gabriel
     * @return bool
     */
    private function _isTooManyJobsExecuting()
    {
        $runningJobsCnt = $this->jobsModel->getCount('*', [
            'status' => Job::STATUS_EXECUTING
        ]);

        if ($runningJobsCnt > self::TASKS_PER_POLLING) {
            return $runningJobsCnt;
        }
        return false;
    }

    /**
     * 获取要执行的任务
     * Author : Gabriel
     * @return mixed
     */
    private function _fetchJobs()
    {
        $where = [
            'status' => Job::STATUS_UNEXECUTED,
            'execute_after >' => time()
        ];
        return $this->jobsModel->where($where)
                                    ->order('priority')
                                    ->limit(0, self::TASKS_PER_POLLING)
                                    ->getAll('*');
    }

    /**
     * 记录任务开始
     * Author : Gabriel
     * @param $job
     */
    private function _logJobStart($job)
    {
        $msg = "Jobs: {$job['job']}:{$job['jid']} starting.";
        $logdata = [
            'jid' => $job['jid'],
            'job' => $job['job']
        ];
        $this->jobsModel->saveLog($msg, $logdata);
        echo $msg . PHP_EOL;
    }
}

<?php

/**
 * Class PeriodicJob
 */
class PeriodicJob extends Model
{
    const STATUS_EXECUTABLE = 1;
    const STATUS_PENDING = 10;

    /**
     * 初始化表
     * Author : Gabriel
     */
    protected function init()
    {
        $this->_tableName = 'k_periodic_jobs';
    }

    /**
     * 所有状态
     * Author : Gabriel
     * @return array
     */
    static public function AllStatus()
    {
        return [
            self::STATUS_EXECUTABLE => self::Status(self::STATUS_EXECUTABLE),
            self::STATUS_PENDING    => self::GetStatus(self::STATUS_PENDING)
        ];
    }

    /**
     * 状态
     * Author : Gabriel
     * @param $s
     * @return string
     */
    static public function Status($s)
    {
        switch ($s) {
            case self::STATUS_EXECUTABLE :
                return '可执行';
            case self::STATUS_PENDING :
                return '暂停';
            default:
                return '未知';
        }
    }

    /**
     * 预备周期任务
     * Author : Gabriel
     * @param $data
     * @return array
     */
    public function preparePeriodicJob($data)
    {
        $op = '<a href="editperiodicjob/' . $data['pjid'] . '" class="btn btn-xs btn-info">编辑</a>';
        if ($data['status'] == PeriodicJob::STATUS_EXECUTABLE) {
            $op .= ' | <a href="pendperiodicjob/' . $data['pjid'] . '" class="btn btn-xs btn-default">暂停</a>';
        } else {
            $op .= ' | <a href="executeperiodicjob/' . $data['pjid'] . '" class="btn btn-xs btn-success">执行</a>';
        }

        return [
            'pjid'             => $data['pjid'],
            'pjob'             => $data['pjob'],
            'priority'         => Job::Priority($data['priority']),
            'params'           => $data['params'],
            'status'           => self::Status($data['status']),
            'period'           => $data['period'],
            'period_parameter' => $data['period_parameter'],
            'op'               => $op
        ];
    }

    /**
     * 分页
     * Author : Gabriel
     * @param $where
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function paginate($where, $skip = 0, $limit = 50)
    {
        return $this->where($where)
            ->limit($skip, $limit)
            ->order('pjid')
            ->getAll('*');
    }

    public function addPeriodJob(
        $pjob,
        $type,
        $period,
        $priority = self::PRIORITY_NORMAL,
        $params = '',
        $periodParameter = ''
    )
    {
        return $this->insert([
            'pjob'             => $pjob,
            'type'             => $type,
            'priority'         => $priority,
            'period'           => $period,
            'params'           => $params,
            'period_parameter' => $periodParameter,
            'created_at'       => date('Y-m-d H:i:s')
        ]);
    }

    public function modifyPeriodJob(
        $pjid,
        $pjob,
        $type,
        $period,
        $priority = self::PRIORITY_NORMAL,
        $params = '',
        $periodParameter = '',
        $status = self::STATUS_PENDING
    )
    {
        return $this->update([
            'pjob'             => $pjob,
            'type'             => $type,
            'priority'         => $priority,
            'period'           => $period,
            'params'           => $params,
            'period_parameter' => $periodParameter,
            'status'           => $status,
            'created_at'       => date('Y-m-d H:i:s')
        ], ['pjid' => $pjid]);
    }

    public function executePeriodicjob($pjid)
    {
        return $this->update([
            'status' => self::STATUS_EXECUTABLE
        ], ['pjid' => $pjid]);
    }

    public function pendPeriodicjob($pjid)
    {
        return $this->update([
            'status' => self::STATUS_PENDING
        ], ['pjid' => $pjid]);
    }

    public static function PeriodTime($job)
    {
        switch ($job['period']) {
            case 'monthly' :
                return self::_monthlyPeriodTime($job);
            case 'daily' :
                return self::_dailyPeriodTime($job);
            case 'weekly' :
                return self::_weeklyPeriodTime($job);
            case 'hourly' :
                return self::_hourlyPeriodTime($job);
            case 'minutely':
                return self::_minutelyPeriodTime();
            default:
                return null;
        }
    }

    private static function _monthlyPeriodTime($job)
    {
        if (empty($job['period_parameter'])) {
            return null;
        }

        $params = json_decode($job['period_parameter'], true);
        if (empty($params['day'])) {
            return null;
        }

        $day = $params['day'];
        $hour = empty($params['hour']) ? 0 : $params['hour'];
        $minute = empty($params['minute']) ? 0 : $params['minute'];

        $monthTime = mktime($hour, $minute, 0, date('n'), $day);
        if ($monthTime < time()) {
            $monthTime = mktime($hour, $minute, 0, date('n') + 1, $day);
        }
        return date('YmdHi', $monthTime);
    }

    private static function _weeklyPeriodTime($job)
    {
        if (empty($job['period_parameter'])) {
            return null;
        }

        $params = json_decode($job['period_parameter'], true);
        if (empty($params['week_day'])) {
            return null;
        }

        $w = $params['week_day'];
        $hour = empty($params['hour']) ? 0 : $params['hour'];
        $minute = empty($params['minute']) ? 0 : $params['minute'];
        $cw = date('w');
        $cw = ($cw == 0 ? 7 : $cw);

        if ($w >= $cw) {
            $weekTime = mktime($hour, $minute, 0, date('n'), date('j') + $w - $cw);
        } else {
            $weekTime = 0;
        }

        if ($weekTime < time()) {
            $weekTime = mktime($hour, $minute, 0, date('n'), date('j') + 7 + $w - $cw);
        }
        return date('YmdHi', $weekTime);
    }

    private static function _dailyPeriodTime($job)
    {
        if (empty($job['period_parameter'])) {
            return null;
        }

        $params = json_decode($job['period_parameter'], true);
        if (!isset($params['hour'])) {
            return null;
        }

        $hour = $params['hour'];
        $minute = empty($params['minute']) ? 0 : $params['minute'];

        $dayTime = mktime($hour, $minute, 0);
        if ($dayTime < time()) {
            $dayTime = mktime($hour, $minute, 0, date('n'), date('j') + 1);
        }

        return date('YmdHi', $dayTime);
    }

    private static function _hourlyPeriodTime($job)
    {
        if (empty($job['period_parameter'])) {
            return null;
        }

        $params = json_decode($job['period_parameter'], true);
        if (!isset($params['minute'])) {
            return null;
        }

        $minute = $params['minute'];

        $dayTime = mktime(date('H'), $minute, 0);
        if ($dayTime < time()) {
            $dayTime = mktime(date('H') + 1, $minute, 0);
        }

        return date('YmdHi', $dayTime);
    }

    private static function _minutelyPeriodTime()
    {
        $t = mktime(date('H'), date('i') + 1, 0);
        return date('YmdHi', $t);
    }
}
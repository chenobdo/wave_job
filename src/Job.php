<?php

/**
 * Class Job
 */
class Job extends Model
{
    const STATUS_UNEXECUTED = 1;
    const STATUS_EXECUTING = 2;
    const STATUS_EXECUTED = 10;
    const STATUS_EXPIRES = 100;
    const STATUS_TOO_MANY_ATTAMPTS = 101;
    const STATUS_EXCEPTION = 102;

    const PRIORITY_HIGHEST = 100;
    const PRIORITY_HIGH = 50;
    const PRIORITY_NORMAL = 10;
    const PRIORITY_BEST_EFFORT = 0;

    const RESULT_OK = 1;
    const RESULT_FAILED = 0;
    const RESULT_NOT_IN_TIME = 100;
    const RESULT_EXCEPTION = 200;

    const MANUALLY = 1;
    const NOT_MANUALLY = 0;

    const EXECUTE_AFTER = 1;
    const EXECUTE_NOW = 0;

    const TYPE_MASTER = 1;
    const TYPE_TEST = 2;

    /**
     * 初始化表
     * Author : Gabriel
     */
    protected function init()
    {
        $this->_tableName = 'k_jobs';
        // $this->cache = Wave::app()->redis;
    }

    /**
     * 所有状态
     * Author : Gabriel
     * @return array
     */
    static public function AllStatus()
    {
        return [
            self::STATUS_UNEXECUTED        => self::Status(self::STATUS_UNEXECUTED),
            self::STATUS_EXECUTING         => self::Status(self::STATUS_EXECUTING),
            self::STATUS_EXECUTED          => self::Status(self::STATUS_EXECUTED),
            self::STATUS_EXPIRES           => self::Status(self::STATUS_EXPIRES),
            self::STATUS_TOO_MANY_ATTAMPTS => self::Status(self::STATUS_TOO_MANY_ATTAMPTS),
            self::STATUS_EXCEPTION         => self::Status(self::STATUS_EXCEPTION)
        ];
    }

    static public function Type($t)
    {
        switch ($t) {
            case self::TYPE_MASTER :
                return '正式';
            case self::TYPE_TEST :
                return '测试';
            default:
                return '未知';
        }
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
            case self::STATUS_UNEXECUTED :
                return '未执行';
            case self::STATUS_EXECUTING :
                return '执行中';
            case self::STATUS_EXECUTED :
                return '已执行';
            case self::STATUS_EXPIRES :
                return '已过期';
            case self::STATUS_TOO_MANY_ATTAMPTS :
                return '尝试多次';
            case self::STATUS_EXCEPTION :
                return '执行异常';
            default:
                return '未知';
        }
    }

    static public function Priority($p)
    {
        switch ($p) {
            case self::PRIORITY_BEST_EFFORT :
                return '低';
            case self::PRIORITY_NORMAL :
                return '普通';
            case self::PRIORITY_HIGH :
                return '高';
            case self::PRIORITY_HIGHEST :
                return '最高';
            default:
                return '未知';
        }
    }

    /**
     * 记录日志
     * Author : Gabriel
     * @param $msg
     * @param $data
     */
    public function saveLog($msg, $data)
    {
        $data['msg'] = $msg;
        $data['op'] = 'jobs';
        $data['time'] = date('Y-m-d H:i:s');
        CommonFunction::logRecords($data);
    }

    /**
     * 预备任务
     * Author : Gabriel
     * @param $data
     * @return array
     */
    public function prepareJob($data)
    {
        $op = '<div class="btn-group"><button type="button" data-toggle="modal" data-target="#myModal" data-jid="' . $data['jid'] . '" class="btn btn-xs btn-view btn-default">详情</button>';
        $op .= '<button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>';
        $op .= '<ul class="dropdown-menu" role="menu">';
        if ($data['status'] != Job::STATUS_EXECUTED) {
            $op .= '<li><a href="/jobs/destory/' . $data['jid'] . '" class="btn-confirm">删除</a></li>';
        } else {
            $op .= '<li><a href="/jobs/executeagain/' . $data['jid'] . '" class="btn-confirm">再次执行</a></li>';
        }
        if ($data['status'] == Job::STATUS_UNEXECUTED) {
            $op .= '<li><a href="/jobs/edit/' . $data['jid'] . '">编辑</a></li>';
            $op .= '<li><a href="/jobs/execute/' . $data['jid'] . '" class="btn-confirm">执行</a></li>';
        }
        $op .= '</ul></div>';

        return [
            'jid'               => $data['jid'],
            'job'               => $data['job'],
            'type'              => self::Type($data['type']),
            'priority'          => self::Priority($data['priority']),
            'params'            => $data['params'],
            'status'            => self::Status($data['status']),
            'attempts'          => $data['attempts'],
            'execute_after'     => $data['execute_after'],
            'last_attempt_time' => $data['last_attempt_time'],
            'op'                => $op
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
            ->order('jid')
            ->getAll('*');
    }

    public function addJob(
        $job,
        $type,
        $executeAfter,
        $priority = self::PRIORITY_NORMAL,
        $params = '',
        $mark = ''
    )
    {
        return $this->insert([
            'job'           => $job,
            'type'          => $type,
            'priority'      => $priority,
            'execute_after' => $executeAfter,
            'params'        => trim($params),
            'status'        => Job::STATUS_UNEXECUTED,
            'created_at'    => date('Y-m-d H:i:s'),
            'mark'          => $mark
        ]);
    }

    public function modifyJob(
        $jid,
        $job,
        $type,
        $executeAfter,
        $priority = self::PRIORITY_NORMAL,
        $params = '',
        $mark = ''
    )
    {
        return $this->update([
            'job'           => $job,
            'type'          => $type,
            'priority'      => $priority,
            'execute_after' => $executeAfter,
            'params'        => trim($params),
            'status'        => Job::STATUS_UNEXECUTED,
            'created_at'    => date('Y-m-d H:i:s'),
            'mark'          => $mark
        ], ['jid' => $jid]);
    }

    public function executeJob($jid)
    {
        return $this->update([
            'execute_after' => date('Y-m-d H:i:s')
        ], ['jid' => $jid]);
    }
}
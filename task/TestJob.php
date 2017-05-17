<?php

class TestJob
{
    /**
     * 主业务
     * Author : Gabriel
     * @param $jid
     * @param null $params
     * @return bool
     */
    public function handle($jid, $params = null)
    {
        exit("14");
//        $msg = [
//            'jid' => $jid,
//            'params' => $params,
//            'timezone' => ini_get('date.timezone')
//        ];
//        $data['msg'] = $msg;
//        $data['op'] = 'test_job';
//        $data['time'] = date('Y-m-d H:i:s');
//        CommonFunction::logRecords($data);
        return true;
    }
}

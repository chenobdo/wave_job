<?php

class GenarateServicebackJsonJob
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
        //問題列表
        $questionTypeModel = new QuestionType();
        $typeData = $questionTypeModel->getList();
        $type = [];
        $typep = [];
        foreach ($typeData as $data) {
            if (empty($data['pid'])) {
                $typep[$data['tid']] = $data['type'];
            } else {
                $type[$data['tid']] = $data['type'];
            }
        }
        $content  = '';
        $content .= "// 问题父类型\r\n";
        $content .= 'var questionTypep = ' . json_encode($typep, JSON_UNESCAPED_UNICODE) . ";\r\n\r\n";
        $content .= "// 问题类型\r\n";
        $content .= 'var questionType = ' . json_encode($type, JSON_UNESCAPED_UNICODE) . ";\r\n\r\n";
        $content .= "// 全部问题类型\r\n";
        $content .= 'var questionTypeList = ' . json_encode($typeData, JSON_UNESCAPED_UNICODE) . ";\r\n\r\n";

        $groupModel = new Group();
        $groupData = $groupModel->getList();
        $group = [];
        foreach ($groupData as $data) {
            $group[$data['group_id']] = $data['group_name'];
        }
        $content .= "// 客服分组类型\r\n";
        $content .= 'var group = ' . json_encode($group, JSON_UNESCAPED_UNICODE) . ";\r\n\r\n";

        $content = str_replace('\\', '', $content);
        $filedir = ROOT_PATH . '/web/kugou/js/';
        WaveCommon::mkDir($filedir);
        $filepath = $filedir . 'serviceback_json.js';
        Wave::writeCache($filepath, $content);

        return true;
    }
}

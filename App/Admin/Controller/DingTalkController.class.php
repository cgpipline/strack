<?php

namespace Admin\Controller;

// +----------------------------------------------------------------------
// | 钉钉设置数据控制层
// +----------------------------------------------------------------------

use Common\Service\OptionsService;

class DingTalkController extends AdminController
{
    /**
     * 显示页面
     */
    public function index()
    {
        return $this->display();
    }

    /**
     * 获取钉钉配置
     * @return \Think\Response
     */
    public function getDingTalkConfig()
    {
        $optionsService = new OptionsService();
        $resData = $optionsService->getOptionsData("dingtalk_settings");
        return json($resData);
    }

    /**
     * 更新默认设置
     * @return \Think\Response
     */
    public function updateDingTalkConfig()
    {
        $param = $this->request->param();
        $optionsService = new OptionsService();
        $resData = $optionsService->updateOptionsData("dingtalk_settings", $param, L("Ding_Talk_Disk_SC"));
        return json($resData);
    }
}

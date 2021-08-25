<?php

namespace Admin\Controller;

// +----------------------------------------------------------------------
// | 项目模块设置数据控制层
// +----------------------------------------------------------------------

use Common\Service\OptionsService;

class ProjectModuleController extends AdminController
{
    /**
     * 显示页面
     */
    public function index()
    {
        return $this->display();
    }

    /**
     * 获取项目模块配置
     */
    public function getProjectModuleConfig()
    {
        $optionsService = new OptionsService();
        $resData = $optionsService->getOptionsData("project_module_settings");
        return json($resData);
    }

    /**
     * 更新默认设置
     */
    public function updateProjectModuleConfig()
    {
        $param = $this->request->param();
        $optionsService = new OptionsService();
        $resData = $optionsService->updateOptionsData("project_module_settings", $param, L("Save_Project_Module_SC"));
        return json($resData);
    }
}

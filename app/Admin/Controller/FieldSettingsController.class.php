<?php

namespace Admin\Controller;

// +----------------------------------------------------------------------
// |  字段配置设置数据控制层
// +----------------------------------------------------------------------

use Common\Service\OptionsService;

class FieldSettingsController extends AdminController
{
    /**
     * 显示页面
     */
    public function index()
    {
        return $this->display();
    }

    /**
     * 加载字段设置
     */
    public function getFieldSettings()
    {
        $optionsService = new OptionsService();
        $resData = $optionsService->getOptionsData("field_settings");
        return json($resData);
    }


    /**
     * 更新字段设置
     */
    public function updateFieldSettings()
    {
        $param = $this->request->param();
        $optionsService = new OptionsService();
        $resData = $optionsService->updateOptionsData("field_settings", $param, L("Field_Setting_Save_SC"));
        S('formula_config_cache', null);
        return json($resData);
    }
}

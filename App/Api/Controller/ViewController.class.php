<?php

namespace Api\Controller;


use Common\Model\ModuleModel;
use Common\Service\ViewService;

class ViewController extends BaseController
{

    // 验证器
    protected $commonVerify = 'View';

    // 验证场景 （key名小写）
    protected $commonVerifyScene = [
        'createDefaultView' => 'CreateDefaultView',
        'deleteDefaultView' => 'DeleteDefaultView',
        'findDefaultView' => 'FindDefaultView',
    ];

    /**
     * 创建默认视图
     * @return \Think\Response
     */
    public function createDefaultView()
    {
        $viewService = new ViewService();
        $resData = $viewService->saveViewDefault($this->param);
        return json($resData);
    }

    /**
     * 删除默认视图
     * @return \Think\Response
     */
    public function deleteDefaultView()
    {
        $viewService = new ViewService();
        $resData = $viewService->deleteViewDefault($this->param);
        return json($resData);
    }

    /**
     * 查找默认视图
     * @return \Think\Response
     */
    public function findDefaultView()
    {
        $viewService = new ViewService();
        $resData = $viewService->getDefaultView($this->param);
        return json($resData);
    }

    /**
     * 获取看板可用视图
     * @return \Think\Response
     */
    public function getKanbanViewList()
    {
        $viewService = new ViewService();
        $moduleModel = new ModuleModel();
        $moduleData = $moduleModel->where(['code' => $this->param['module_code']])->find();

        $page = '';
        $schemaPage = '';
        $viewType = 'grid';
        if ($this->param['module_code'] === 'base') {
            $page = 'project_base';
            $schemaPage = 'project_base';
        }

        $getParam = [
            'page' => $page,
            'schema_page' => $schemaPage,
            'project_id' => $this->param['project_id'],
            'module_id' => $moduleData['id'],
            'view_type' => $viewType
        ];

        $resData = $viewService->getKanbanViewList($getParam);
        return json(success_response('', $resData));
    }


    /**
     * 获取表格面板数据
     * @return \Think\Response
     */
    public function getGridPanelData()
    {
        $viewService = new ViewService();
        $moduleModel = new ModuleModel();
        $moduleData = $moduleModel->where(['code' => $this->param['module_code']])->find();

        $page = '';
        $schemaPage = '';
        $viewType = 'grid';
        if ($this->param['module_code'] === 'base') {
            $page = 'project_base';
            $schemaPage = 'project_base';
        }

        $getParam = [
            'page' => $page,
            'schema_page' => $schemaPage,
            'project_id' => $this->param['project_id'],
            'module_id' => $moduleData['id'],
            'view_type' => $viewType
        ];

        $resData = $viewService->getGridPanelData($getParam);

        return json(success_response('', $resData));
    }

    /**
     * 获取看板分组数据配置
     * @return \Think\Response
     */
    public function getGridCollaborators()
    {
        $viewService = new ViewService();
        $resData = $viewService->getGridCollaborators($this->param);
        return json(success_response('', $resData));
    }
}

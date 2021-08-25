<?php

namespace Api\Controller;

use Common\Controller\VerifyController;
use Common\Model\ModuleModel;
use Common\Service\CommonService;
use Common\Service\SchemaService;

class BaseController extends VerifyController
{
    /**
     * @var \Common\Service\CommonService
     */
    protected $commonService = null;

    // 项目ID
    protected $projectId = 0;

    // 是否存在project_id字段
    protected $exitsProjectId = false;

    // 验证器
    protected $commonVerify = null;

    // 场景验证配置
    protected $commonVerifyScene = [];

    // 通用方法
    protected $commonActionList = ["find", "select", "getrelation", "delete", "update", "create", "fields"];

    // 模块映射字典
    protected $moduleMapData = null;

    // 排除验证方法
    protected $excludeVerifyAction = [];

    // 实例化模型名
    protected $commonModelName = '';

    /**
     * 404页面
     */
    protected function _empty()
    {
        throw_strack_exception('Resources do not exist.', -404);
    }

    /**
     * 403页面
     */
    protected function _noPermission()
    {
        throw_strack_exception('Access without permission.', -403);
    }

    /**
     * 处理请求参数
     * @param string $field
     */
    protected function getModuleMapData($field = "code")
    {
        //需要module参数的方法
        if (empty($this->moduleMapData)) {
            $schemaService = new SchemaService();
            $this->moduleMapData = $schemaService->getModuleMapData($field);
        }
    }

    /**
     * 验证API权限
     */
    protected function checkRouteAccess()
    {
        // 当前用户id
        $this->userId = (int)session("user_id");
        $this->fullController = strtolower($this->request->module() . DS . $this->request->controller() . DS . $this->request->action());
        //$this->_noPermission();
    }

    /**
     * 参数场景验证
     * @param $fileName
     * @param $sceneName
     */
    protected function validateSceneCheck($fileName, $sceneName)
    {
        // 存在应用场景就验证否则跳过
        $class = "\\Common\\Validate\\{$fileName}";

        $validateClass = new $class();

        // 设置验证场景
        $validateClass->scene($sceneName);

        // 验证请求数据
        if (!$validateClass->check($this->param)) {
            throw_strack_exception($validateClass->getError(), 400001);
        }
    }

    /**
     * 验证请求参数
     */
    protected function checkRequestParam()
    {
        // 需要module参数的方法
        if (in_array($this->currentAction, $this->commonActionList)) {

            // 初始化通用方法
            if (!empty($this->commonModelName)) {
                $this->commonService = new CommonService($this->commonModelName);
            } else {
                $this->commonService = new CommonService($this->currentSourceController);
            }

            // 当前控制器
            $actionName = ucfirst(ACTION_NAME);

            // 通用方法验证
            if (array_key_exists($this->currentAction, $this->commonVerifyScene)) {
                $this->validateSceneCheck($this->commonVerify, $this->commonVerifyScene[$this->currentAction]);
            } else {
                $this->validateSceneCheck('Communal', $actionName);
            }

            // 通用数据处理
            $filterFunction = "generate" . ucfirst($actionName) . "Param";
            if (method_exists($this, $filterFunction)) {
                $this->param = call_user_func([$this, $filterFunction], $this->param);
            }

        } elseif (array_key_exists($this->currentAction, $this->commonVerifyScene)) {
            $this->validateSceneCheck($this->commonVerify, $this->commonVerifyScene[$this->currentAction]);
        }
    }


    /**
     * 字段基础方法
     * @return \think\Response
     */
    public function fields()
    {
        // 获取模块映射数据
        $this->getModuleMapData('id');

        $schemaService = new SchemaService();

        $moduleModel = new ModuleModel();

        $this->param["module"]['module_id'] = $moduleModel->where(['code' => $this->param["module"]['code']])->getField('id');


        $fieldsData = $schemaService->getTableFields($this->param["module"], $this->projectId, $this->moduleMapData);
        return json(success_response('', $fieldsData));
    }

    /**
     * find,select方法参考getRelation方法，仅仅支持本身固定字段和自定义字段
     * @param null $method
     * @return array
     */
    private function selectByRelation($method = null)
    {
        $resData = $this->commonService->getRelation($this->param, $method);
        return $resData;
    }

    /**
     * 添加实体模块必须过滤条件
     * @param $filter
     * @param $moduleData
     */
    private function appendEntityModuleMustFilter()
    {
        $moduleData = $this->param['module'];
        if ($moduleData['type'] === 'entity') {

            $this->getModuleMapData('code');

            if (array_key_exists('param', $this->param) && array_key_exists('filter', $this->param['param'])) {
                $this->param['param']['filter']['module_id'] = $this->moduleMapData[$moduleData['code']]['id'];
            } else {
                $this->param['param']['filter'] = [
                    'module_id' => $this->moduleMapData[$moduleData['code']]['id']
                ];
            }
        }
    }

    /**
     * 单条数据查找基础方法
     * @return \Think\Response
     */
    public function find()
    {
        // 判断查询模式
        if (!empty($this->param['mode']) && $this->param['mode'] === 'relation') {
            // 关联查询，限制查询一条
            $this->param['param']['page'] = [1, 1];
            $resData = $this->selectByRelation('find');
        } else {
            $this->appendEntityModuleMustFilter();
            $resData = $this->commonService->find($this->param['param']);
        }
        return json($resData);
    }

    /**
     * 多条数据查询基础方法
     */
    public function select()
    {
        // 判断查询模式
        if (!empty($this->param['mode']) && $this->param['mode'] === 'relation') {
            // 关联查询，查询多条
            $resData = $this->selectByRelation('select');
        } else {
            $this->appendEntityModuleMustFilter();
            // entity 查询自动加上module_id默认条件
            $resData = $this->commonService->select($this->param['param']);
        }
        return json($resData);
    }

    /**
     * 多条数据复杂过滤关联查询基础方法
     */
    public function getRelation()
    {
        $resData = $this->commonService->getRelation($this->param);
        return json($resData);
    }

    /**
     * 创建基础方法
     * @return \Think\Response
     */
    public function create()
    {
        $resData = $this->commonService->apiAddItemDialog($this->param);
        return json($resData);
    }

    /**
     * 创建基础方法
     * @return \think\Response
     */
    public function update()
    {
        $resData = $this->commonService->apiModifyItemDialog($this->param);
        return json($resData);
    }

    /**
     * 删除基础方法
     * @return \think\Response
     */
    public function delete()
    {
        $resData = $this->commonService->delete($this->param['param']['filter']);
        $resData['data'] = $this->param['param']['filter'];
        return json($resData);
    }

    /**
     * 检测用户登录状态
     */
    protected function _initialize()
    {
        parent::_initialize();

        // 初始化项目ID
        $this->projectId = array_key_exists('project_id', $this->param) ? $this->param['project_id'] : 0;

        // 验证请求数据
        $this->checkRequestParam();
    }
}

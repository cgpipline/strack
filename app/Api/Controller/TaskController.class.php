<?php

namespace Api\Controller;

use Common\Model\ModuleModel;
use Common\Service\BaseService;
use Common\Service\CommonService;
use Common\Service\HorizontalService;
use Common\Service\OptionsService;
use Common\Service\StatusService;
use Common\Service\UserService;

class TaskController extends BaseController
{
    // 当前模块
    public $commonModelName = 'base';

    // 验证器
    protected $commonVerify = 'Base';

    // 验证场景 （key名小写）
    protected $commonVerifyScene = [
        'gettaskstatuslist' => 'GetTaskStatusList'
    ];

    /**
     * 创建基础方法
     * @return \Think\Response
     */
    public function syncCreate()
    {
        if (isset($this->commonService)) {
            $extraData = $this->param["extra_data"];
            $queryParam = $this->param["query_param"];
            $user = new UserService();

            //用户uc_id转uid
            foreach ($queryParam['relation_data']['base'] as $k => $v) {
                if ($v['fields'] == 'fenpairen' || $v['fields'] == 'zhixingren') {
                    $users = explode(',', $v['value']);
                    if (count($users) > 1) {
                        $id = [];
                        foreach ($users as $u) {
                            //如果找不到用户会报错
                            if (in_array($u, [100, 4386])) {
                                $userInfo = $user->getUserFindField(['uc_id' => $u], 'id');
                                $id[] = $userInfo['id'];
                            }

                        }
                        $id = implode(',', $id);
                    } else {
                        if (in_array($v['value'], [100, 4386])) {
                            $id = ($user->getUserFindField(['uc_id' => $v['value']], 'id'))['id'];
                        } else {
                            $id = 0;
                        }
                    }

                    $queryParam['relation_data']['base'][$k]['value'] = $id;
                }
            }
            $resData = $this->commonService->addItemDialog($queryParam, $extraData);
            return json($resData);
        } else {
            $this->_empty();
        }
    }

    /**
     * 获取任务状态列表
     * @return \Think\Response
     */
    public function getTaskStatusList()
    {
        $statusService = new StatusService();
        $param = [
            'project_id' => $this->param['project_id'],
            'frozen_module' => $this->param['module_code']
        ];

        $resData = $statusService->getTemplateStatusList($param);

        return json(success_response('', $resData));
    }


    /**
     * 获取任务表单字段
     * @return \Think\Response
     */
    public function getTaskFormFieldsConfig()
    {
        $baseService = new BaseService();
        $resData = $baseService->getTaskFormFieldsConfig();
        return json(success_response('', $resData));
    }

    /**
     * 获取用户任务列表
     * @return \Think\Response
     */
    public function getUserTaskList(){
        $param = $this->request->param();
        $base = new BaseService();
        $resData = $base->getTaskListbyUserid(session('user_id'),$param);
        return json(success_response('获取成功', $resData));
    }

    /**
     * 添加子任务
     * @return \Think\Response
     */
    public function addSubTask()
    {
        $commonService = new CommonService('Base');
        $resData = $commonService->apiAddItemDialog($this->param);

        if($resData['status'] === 200){
            $moduleModel = new ModuleModel();
            $moduleData = $moduleModel->where(['code' => $this->param['module']['code']])->find();

            $formulaConfigData = (new OptionsService())->getFormulaConfigData();

            // 自动关联
            $horizontalService = new HorizontalService();
            $relatedParam = [
                'param' => [
                    'dst_module_id' => $moduleData['id'],
                    'from' => "toolbar",
                    'grid_id' => "",
                    'horizontal_type' => $this->param['param']['horizontal_type'],
                    'link_data' => [],
                    'project_id' => $this->param['param']['project_id'],
                    'src_link_id' => $this->param['param']['from_item_id'],
                    'src_module_id' => $moduleData['id'],
                    'variable_id' => $formulaConfigData['sub_task']
                ],
                'up_data' => [
                    'add' => [$resData['data']['id']],
                    'delete' => []
                ]
            ];
            $resData['data']['horizontal'] = $horizontalService->modifyHRelationDestData($relatedParam);
        }

        return json($resData);
    }
}

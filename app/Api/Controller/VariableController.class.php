<?php

namespace Api\Controller;


use Common\Service\VariableService;

class VariableController extends BaseController
{

    // 验证器
    protected $commonVerify = 'Variable';

    // 验证场景 （key名小写）
    protected $commonVerifyScene = [
        'create' => 'Create' ,
    ];

    /**
     * 创建自定义字段
     * @return \Think\Response
     */
    public function create()
    {
        if (!isset($this->commonService)) {
            $this->_empty();
        }

        $extraData = $this->param["extra_data"];
        $queryParam = $this->param["query_param"];
        $authData = $this->check($this->param, "createVariable");
        //添加字段
        $resData = $this->commonService->addItemDialog($queryParam, $extraData);
        if ($resData["status"] == 200) {
            $variableService = new VariableService();
            $authData["variable_id"] = $resData["data"]["id"];
            $variableService->changeAuthFieldConfig($authData, "add");
        }
        return json($resData);
    }
}

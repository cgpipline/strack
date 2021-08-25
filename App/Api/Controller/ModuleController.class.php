<?php

namespace Api\Controller;


use Common\Service\SchemaService;

class ModuleController extends BaseController
{

    // 验证器
    protected $commonVerify = 'Module';

    // 验证场景 （key名小写）
    protected $commonVerifyScene = [
        'getrelationmoduledata' => 'GetRelationModuleData',
    ];

    /**
     * 获得所有module信息
     * @return \Think\Response
     */
    public function getModuleData()
    {
        $SchemaService = new SchemaService();
        $result = $SchemaService->getModuleData();
        return json(success_response("", $result));
    }

    /**
     * 获取指定module关联模块配置数据
     * @return \Think\Response
     */
    public function getRelationModuleData()
    {
        $SchemaService = new SchemaService();
        $result = $SchemaService->getRelationModuleData($this->param);
        return json(success_response("", $result));
    }
}

<?php

namespace Api\Controller;


use Common\Service\HorizontalService;

class HorizontalController extends BaseController
{

    // 验证器
    protected $commonVerify = 'Horizontal';

    // 验证场景 （key名小写）
    protected $commonVerifyScene = [
        'createHorizontal' => 'CreateHorizontal' ,
    ];

    /**
     * 创建一条水平关联数据
     * @return \Think\Response
     */
    public function createHorizontal()
    {
        $schemaService = new HorizontalService();
        $resData = $schemaService->addHorizontalData($this->param);
        return json($resData);
    }
}

<?php

namespace Api\Controller;

use Common\Service\CommonService;
use Common\Service\MessageService;

class EventController extends BaseController
{

    // 验证器
    protected $commonVerify = 'Event';

    // 验证场景 （key名小写）
    protected $commonVerifyScene = [

    ];

    /**
     * 获取消息
     * @return \Think\Response
     */
    public function getSideInboxData()
    {
        $messageService = new MessageService();
        $resData = $messageService->getSideInboxData($this->param);
        return json(success_response('', $resData));
    }

    /**
     * 更新任务审核数据
     * @return \Think\Response
     */
    public function updateBaseConfirmationData()
    {
        $commonService = new CommonService();
        $resData = $commonService->updateBaseConfirmationData($this->param);
        return json($resData);
    }
}

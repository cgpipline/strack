<?php

namespace Common\Behaviors;

use Think\Request;
use Common\Service\EventService;

class EndControllerBehavior extends \Think\Behavior
{
    /**
     * 行为执行入口
     * @param mixed $param
     * @throws \Ws\Http\Exception
     */
    public function run(&$param)
    {
        $batchNumber = Request::$batchNumber;
        $batchCache = S($batchNumber);
        if (!empty($batchCache)) {
            // 只有增删改有操作记录
            $eventService = new EventService();
            $requestParam = Request::instance()->param();
            $eventService->generateMessageData([
                'controller' => CONTROLLER_NAME,
                'action' => ACTION_NAME,
                'config' => $param,
                'request_param' => $requestParam,
                'operation_data' => $batchCache
            ]);
        };
    }
}

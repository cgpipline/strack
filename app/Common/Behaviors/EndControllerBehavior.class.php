<?php

namespace Common\Behaviors;

use Common\Service\EventService;
use Think\QueueClient;
use Think\Request;

class EndControllerBehavior extends \Think\Behavior
{
    /**
     * 行为执行入口
     * @param mixed $param
     */
    public function run(&$param)
    {
        $batchNumber = Request::$batchNumber;

        $batchCache = S($batchNumber);
        if (!empty($batchCache)) {
            // 只有增删改有操作记录
            $requestParam = Request::instance()->param();

            $eventService = new EventService();
            $messageData = $eventService->generateMessageData( [
                'batch_number' => $batchNumber,
                'controller' => CONTROLLER_NAME,
                'action' => ACTION_NAME,
                'config' => $param,
                'request_param' => $requestParam,
                'operation_data' => $batchCache
            ]);

            // 异步处理
            QueueClient::send('message', $messageData);
        };
    }
}

<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Behavior;

use Common\Service\EventLogService;
use Think\Request;

/**
 * 行为扩展：处理event
 */
class EventBehavior
{

    /**
     * 处理记录event log
     * @param $params
     * @throws \Exception
     */
    public function run(&$params)
    {

        $params['batch_number'] = Request::$batchNumber;

        $eventData = [
            "event_from" => session("event_from"),   // 增加从哪里来参数
            "user_info" => S('user_data_' . fill_created_by()),
            "params" => $params
        ];

        // 异步处理
        $eventLogService = new EventLogService();
        $eventLogService->addInsideEventLog($eventData["event_from"], $eventData["params"], $eventData["user_info"]);

        //QueueClient::send('eventlog', ['some', time()]);
    }
}

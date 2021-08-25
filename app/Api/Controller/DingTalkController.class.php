<?php

namespace Api\Controller;

use Common\Service\TimelogService;
use Think\Controller;
use Common\Service\DingTalkService;

class DingTalkController extends Controller
{
    /**
     * 同步钉钉数据
     * @throws \Think\Exception
     */
    public function sync()
    {
        $timelogService = new TimelogService();
        $timelogService->reviseUnCompleteTimeLogRecords();
        echo '成功同步钉钉打卡记录';
    }

    /**
     * 同步钉钉打卡记录
     */
    public function synchronousPunchRecord(){
        $dingTalkService = new DingTalkService();
        $result = $dingTalkService->synchronousPunchRecord();
        echo '同步钉钉打卡记录成功';
    }

}

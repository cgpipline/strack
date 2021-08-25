<?php

namespace Api\Controller;


use Common\Model\ModuleModel;
use Common\Service\TimelogService;

class TimelogController extends BaseController
{
    protected $timelogServer;

    // 验证器
    protected $commonVerify = 'Timelog';

    // 验证场景 （key名小写）
    protected $commonVerifyScene = [
        'stopTimer' => 'StopTimer',
        'startTimer' => 'StartTimer',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->timelogServer = new TimelogService();
    }

    /**
     * 停止定时器
     * @return \Think\Response
     */
    public function stopTimer()
    {
        $resData = $this->timelogServer->stopTimelogTimer($this->param["id"]);
        return json($resData);
    }

    /**
     * 创建一个定时器
     * @return \Think\Response
     */
    public function startTimer()
    {
        // user
        $moduleModel = new ModuleModel();
        $moduleData = $moduleModel->where(['code' => $this->param['module_code']])->find();

        $param = [
            "id" => $this->param['id'],
            "module_id" => $moduleData['id'],
            "user_id" => $this->param['user_id']
        ];
        $resData = $this->timelogServer->addTimelogTimer($param);
        return json($resData);
    }
}

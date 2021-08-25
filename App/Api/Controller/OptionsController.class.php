<?php

namespace Api\Controller;

use Common\Service\EventLogService;
use Common\Service\OptionsService;

class OptionsController extends BaseController
{
    protected $optionsService;

    // 验证器
    protected $commonVerify = 'Options';

    // 验证场景 （key名小写）
    protected $commonVerifyScene = [
        'getOptions' => 'GetOptions',
        'updateOptions' => 'UpdateOptions',
        'addOptions' => 'AddOptions',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->optionsService = new OptionsService();
    }

    /**
     * 获得指定名称的配置
     * @return \Think\Response
     */
    public function getOptions()
    {
        $resData = $this->optionsService->getOptionsData($this->param["options_name"]);
        return json(success_response('', $resData));
    }

    /**
     * 修改options
     * @return \Think\Response
     */
    public function updateOptions()
    {
        $resData = $this->optionsService->updateOptionsData($this->param["options_name"], $this->param["config"]);
        return json($resData);
    }


    /**
     *  添加配置，用户添加的默认都是custom
     * @return \Think\Response
     */
    public function addOptions()
    {
        $resData = $this->optionsService->addOptionsData($this->param["options_name"], $this->param["config"], 'custom');
        return json($resData);
    }

    /**
     * 获取webSocket服务器地址
     * @return \Think\Response
     */
    public function getWebSocketServer()
    {
        $data = $this->optionsService->getLogServerStatus();
        if (!empty($data)) {
            $resData["status"] = $data["status"];
            $resData["connect_time"] = $data["connect_time"];
            $resData["websocket_url"] = $data["websocket_url"];
        } else {
            $resData = [];
        }
        return json(success_response('', $resData));
    }

    /**
     * 获取邮件服务器地址
     * @return \Think\Response
     */
    public function getEmailServer()
    {
        $eventLogServer = new EventLogService();
        $data = $eventLogServer->getEventServer();
        if ($data["status"] == 200) {
            $resData["status"] = $data["status"];
            $resData["connect_time"] = $data["connect_time"];
            $resData["send_url"] = $data["request_url"] . "/email/send?sign={$data['token']}";
            $resData["template_url"] = $data["request_url"] . "/email/template?sign={$data['token']}";
        } else {
            $resData = [];
        }
        return json(success_response('', $resData));
    }

    /**
     * 获取log服务器地址
     * @return \Think\Response
     */
    public function getEventLogServer()
    {
        $eventService = new EventLogService();
        $resData = $eventService->getEventServer();
        return json(success_response('', $resData));
    }
}

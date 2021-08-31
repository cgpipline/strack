<?php

namespace Admin\Controller;

use Common\Service\EmailService;
use Common\Service\OptionsService;

// +----------------------------------------------------------------------
// | 后台邮件设置数据控制层
// +----------------------------------------------------------------------

class EmailController extends AdminController
{
    /**
     * 显示页面
     */
    public function index()
    {
        return $this->display();
    }

    /**
     * 获取Email设置
     */
    public function getEmailSetting()
    {
        $optionsService = new OptionsService();
        $resData = $optionsService->getOptionsData("email_settings");
        return json($resData);
    }


    /**
     * 更新邮件设置
     * @return \Think\Response
     */
    public function saveEmailSetting()
    {
        $param = $this->request->param();
        $optionsService = new OptionsService();
        $resData = $optionsService->updateOptionsData("email_settings", $param, L("Email_Settings_Save_SC"));
        return json($resData);
    }


    /**
     * 测试邮件发送
     * @return \Think\Response
     * @throws \Throwable
     */
    public function testSendEmail()
    {
        $param = $this->request->param();
        $param = [
            "param" => [
                "addressee" => $param["email_account"],
                "subject" => "测试邮件"
            ],
            "data" => [
                "template" => "text",
                "content" => $param["email_content"]
            ]

        ];
        $emailService = new EmailService();
        $resData = $emailService->testSendEmail($param);
        return json($resData);
    }
}

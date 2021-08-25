<?php

namespace Api\Controller;

use Common\Service\LoginService;
use Common\Service\OptionsService;
use Common\Service\UserService;

class LoginController extends BaseController
{

    // 验证器
    protected $commonVerify = 'Login';

    // 验证场景 （key名小写）
    protected $commonVerifyScene = [
        'in' => 'In' ,
        'refresh' => 'Refresh',
    ];

    // 需要排除验证的方法（小写）
    protected $excludeVerifyAction = [
        'getthirdserverlist'
    ];

    /**
     * API 用户登录
     * @throws \Exception
     */
    public function in()
    {
        $loginService = new LoginService();
        $resData = $loginService->login($this->param);
        return json($resData);
    }

    /**
     * 刷新 token
     * @return \Think\Response
     */
    public function refresh()
    {
        $userService = new UserService();
        $resData = $userService->renewToken($this->param);
        return json($resData);
    }

    /**
     * 注销 token
     * @return \Think\Response
     * @throws \Think\Exception
     */
    public function cancel()
    {
        $userService = new UserService();
        $userService->cancelToken();
        return json(success_response(L('Token_Refresh_Success')));
    }

    /**
     * 获取第三方登录列表
     * @return \Think\Response
     */
    public function getThirdServerList()
    {
        $optionsService = new OptionsService();
        $resData = $optionsService->getThirdServerList();
        return json($resData);
    }

}

<?php

namespace Home\Controller;

use Think\Controller;
use Common\Service\LoginService;
use Common\Service\UserService;
use Common\Service\OptionsService;

class CallbackController extends Controller
{

    /**
     * 显示绑定用户界面
     * @param $type
     * @param $handleData
     * @return mixed
     */
    public function index($type, $handleData)
    {
        $systemConfig = [
            "is_dev" => APP_DEBUG,
            "user_id" => session("user_id"),
            'new_login' => 'no'
        ];

        $optionsService = new OptionsService();
        $defaultSettings = $optionsService->getOptionsData("default_settings");
        $beianNumber = "";
        if (!empty($defaultSettings) && array_key_exists("default_beian_number", $defaultSettings)) {
            $beianNumber = $defaultSettings["default_beian_number"];
        }

        $this->assign("beian_number", $beianNumber);
        $this->assign("open_id", $handleData['openid']);
        $this->assign("login_type", $type);
        $this->assign($systemConfig);
    }

    /**
     * QQ回调操作
     * @return mixed
     * @throws \Exception
     */
    public function qq()
    {
        $loginService = new LoginService();
        $handleData = $loginService->handleOauthCallBack('qq');

        if (!empty($handleData)) {
            if ($handleData['status'] === 'binded') {
                // 已绑定，直接进入
                if (!empty($handleData['param']['url'])) {
                    $this->redirect($handleData['param']['url']);
                } else {
                    // 跳转到首页
                    $this->redirect('/schedule/index');
                }
            } else {
                // 未绑定
                $userService = new UserService();
                if (!empty(session('user_id'))) {
                    //已登录状态直接绑定账号
                    $userService->loginBind(session('user_id'), $handleData['id_field'], $handleData['openid']);
                    $this->redirect('/account/preferences');
                } else {
                    // 跳转到绑定用户账户页面
                    $this->index('qq', $handleData);
                    return $this->display('Callback:index');
                }
            }
        }
    }


    /**
     * strack union 账户回调
     * @return mixed
     * @throws \Exception
     */
    public function strack()
    {
        $loginService = new LoginService();
        $handleData = $loginService->handleOauthCallBack('strack');

        if (!empty($handleData)) {
            if ($handleData['status'] === 'binded') {
                // 已绑定，直接进入
                if(!empty($_GET['page_url'])){
                    $handleData['param']['url'] = $_GET['page_url'];
                }

                if (!empty($handleData['param']['url'])) {
                    $this->redirect($handleData['param']['url']);
                } else {
                    // 跳转到首页
                    $this->redirect('/schedule/index');
                }
            } else {
                // 未绑定
                $userService = new UserService();
                if (!empty(session('user_id'))) {
                    //已登录状态直接绑定账号
                    $userService->loginBind(session('user_id'), $handleData['id_field'], $handleData['openid']);
                    $this->redirect('/account/preferences');
                } else {
                    // 跳转到绑定用户账户页面
                    $this->index('strack', $handleData);
                    return $this->display('Callback:index');
                }
            }
        }
    }
}

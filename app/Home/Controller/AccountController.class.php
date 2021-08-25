<?php

namespace Home\Controller;

use Common\Controller\VerifyController;
use Common\Service\LoginService;
use Common\Service\OptionsService;
use Common\Service\UserService;

class AccountController extends VerifyController
{
    /*
   * 显示My Account页面
   */
    public function index()
    {
        $param = [
            'module_id' => C("MODULE_ID")["user"],
            "user_id" => session("user_id"),
            "page" => "my_account"
        ];

        // 生成页面唯一信息
        $this->generatePageIdentityID("my_account");

        //$this->getPageAuthRules("my_account", "home");
        $this->assign($param);
        return $this->display();
    }

    /**
     * 显示Account Preferences页面
     * @return mixed
     */
    public function preferences()
    {
        $param = [
            'module_id' => C("MODULE_ID")["user"],
            "page" => "my_account_preference"
        ];

        // 生成页面唯一信息
        $this->generatePageIdentityID("my_account_preference");

        //$this->getPageAuthRules("my_account_preference", "home");

        $assignData = [
            'qq_bind_open' => 'no',
            'qq_bind_status' => 'no',
            'uc_bind_open' => 'no',
            'uc_bind_status' => 'no',
            'strack_union_bind_open' => 'no',
            'strack_union_bind_status' => 'no'
        ];

        //判断第三方登录信息
        $optionsService = new  OptionsService();
        $loginMethodSetting = $optionsService->getOptionsData("login_method_setting");

        $userServer = new UserService();
        $userInfo = $userServer->getUserFindField(['id' => session("user_id")], 'qq_openid,strack_union_id');

        if (!empty($loginMethodSetting)) {

            $loginService = new LoginService();

            if (array_key_exists('qq_login_open', $loginMethodSetting) && $loginMethodSetting['qq_login_open']) {
                // 判断是否绑定QQ第三方信息
                $assignData['qq_bind_open'] = 'yes';

                if (empty($userInfo['qq_openid'])) {
                    //未绑定qq
                    //获取QQ登录链接
                    $this->assign('qqurl', $loginService->getThirdAuthUrl('qq'));
                } else {
                    $assignData['qq_bind_status'] = 'yes';
                }
            }

            if (array_key_exists('strack_union_open', $loginMethodSetting) && $loginMethodSetting['strack_union_open']) {
                // 判断是否绑定Strack Union 第三方信息
                $assignData['strack_union_bind_open'] = 'yes';

                if (empty($userInfo['strack_union_id'])) {
                    //未绑定
                    //获取登录链接
                    $this->assign('strack_union_url', $loginService->getThirdAuthUrl('strack'));
                } else {
                    $assignData['strack_union_bind_status'] = 'yes';
                }
            }
        }

        $this->assign('userInfo', $assignData);
        $this->assign($param);
        return $this->display();
    }

    /**
     * 显示Account Security页面
     */
    public function security()
    {
        if (session("user_id") === 1) {
            $this->_noPermission();
        }

        $param = [
            "page" => "my_account_security"
        ];

        // 生成页面唯一信息
        $this->generatePageIdentityID("my_account_security");

        $this->assign($param);

        return $this->display();
    }
}

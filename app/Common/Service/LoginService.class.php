<?php
// +----------------------------------------------------------------------
// | 登录服务服务层
// +----------------------------------------------------------------------
// | 主要服务于登录数据处理
// +----------------------------------------------------------------------
// | 错误编码头 209xxx
// +----------------------------------------------------------------------
namespace Common\Service;

use Common\Model\UserModel;

class LoginService
{

    // 错误信息
    protected $errorMessage = "";

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->errorMessage;
    }

    /**
     * 验证登录数据 login_name, password, from
     * @param $param
     * @return bool
     */
    private function checkLoginParam($param)
    {
        $requestKeys = ['login_name', 'password', 'from'];
        foreach ($requestKeys as $key) {
            if (!array_key_exists($key, $param)) {
                $this->errorMessage = L("Login_Param_Error") . ' : ' . $key;
                return false;
                break;
            }
        }
        return true;
    }

    /**
     * 用户登录（method：登录方式，）
     * @param $param
     * @return array
     * @throws \Exception
     */
    public function login($param)
    {
        if ($this->checkLoginParam($param)) {

            // 验证数据参数
            $method = array_key_exists("method", $param) ? $param["method"] : '';

            //第三方登录
            $method = empty($param['login_type']) ? $method : $param['login_type'];
            // 判断登录方式
            switch ($method) {
                case 'ldap':
                    // 域用户登录方式
                    $resData = $this->ldapLogin($param);
                    break;
                case 'qq':
                    // QQ 登录方式
                    $resData = $this->qqLogin($param);
                    break;
                case 'strack':
                    // Strack 登录方式
                    $resData = $this->strackLogin($param);
                    break;
                case 'strack_app_inside':
                    // Strack APP 内部登录方式
                    $resData = $this->strackLoginAppInside($param);
                    break;
                case 'weChat':
                    // 微信登录方式
                    $resData = $this->wechatLogin($param);
                    break;
                default:
                    // 默认系统用户验证登录
                    $resData = $this->defaultLogin($param);
                    break;
            }

            // 判断返回数据
            if (!$resData) {
                // 登录存在错误
                throw_strack_exception($this->getError(), 209001);
            } else {
                // 登录成功
                return success_response(L("Login_SC"), $resData);
            }
        } else {
            // 登录参数有问题
            throw_strack_exception($this->getError(), 209002);
        }
    }

    /**
     * 系统登录方式（前端验证，后端验证）
     * @param $param
     * @return array|bool
     * @throws \Exception
     */
    private function defaultLogin($param)
    {
        // 查找当前匹配用户
        $userModel = new UserModel();

        $userData = $userModel->findData([
            "filter" => [
                "login_name" => $param["login_name"]
            ]
        ]);

        if (!empty($userData)) {
            // 判断是否是管理员
            $administratorPassword = C("Administrator_Password");

            if ($userData["id"] == 1) {
                // 超级管理员不通过数据库验证密码
                if ($param["password"] === $administratorPassword) {
                    // 登录成功
                    return $this->afterLoginSuccess($param, $userData);
                }
            } else {
                // 判断密码
                if (check_pass($param["password"], $userData["password"])) {
                    // 登录成功
                    return $this->afterLoginSuccess($param, $userData);
                }
            }

            // 密码错误
            $this->errorMessage = L("Login_Name_Or_Password_Error");
            return false;

        } else {
            // 用户不存在
            $this->errorMessage = L("Login_Name_Or_Password_Error");
            return false;
        }
    }

    /**
     * ldap登录方式
     * @param $param
     * @return array|bool
     * @throws \Exception
     */
    private function ldapLogin($param)
    {
        $ldapService = new LdapService();
        $ldapService->initConfig($param["server_id"]);
        $ldapService->ldapVerify($param);
        $userData = $ldapService->updateUserData($param);
        return $this->afterLoginSuccess($param, $userData);
    }

    /**
     * 第三方登录统一处理
     * @param $param
     * @param $idField
     * @param $errorMsg
     * @return array|bool
     * @throws \Exception
     */
    private function thirdPartyLoginCommon($param, $idField, $errorMsg)
    {
        $userModel = new UserModel();
        $newOpenid = $param['openid'];

        // 判断是api还是web登陆
        if (strtolower($param["from"]) === 'api') {
            // 通过openid获取用户信息
            $userData = $userModel->findData([
                "filter" => [
                    $idField => $newOpenid
                ]
            ]);

            if (!empty($userData)) {
                // 登录成功
                return $this->afterLoginSuccess($param, $userData);
            } else {
                // 用户不存在
                $this->errorMessage = L("Login_Name_Or_Password_Error");
                return false;
            }
        } else {
            // 用户绑定
            $userData = $userModel->findData([
                "filter" => [
                    "login_name" => $param["login_name"]
                ]
            ]);

            if (!empty($userData)) {

                //判断账号是否已绑定过QQ号了
                if (!empty($userData[$idField])) {
                    $this->errorMessage = $errorMsg;
                    return false;
                }

                // 判断是否是管理员
                $administratorPassword = C("Administrator_Password");
                if ($userData["id"] == 1) {
                    // 超级管理员不通过数据库验证密码
                    if ($param["password"] !== $administratorPassword) {
                        //密码错误
                        $this->errorMessage = L("Login_Password_Error");
                        return false;
                    }
                } else {
                    // 判断密码
                    if (!check_pass($param["password"], $userData["password"])) {
                        //密码错误
                        $this->errorMessage = L("Login_Password_Error");
                        return false;
                    }
                }

                //更新openid
                $updateData = ['id' => $userData['id'], $idField => $newOpenid];
                $resData = $userModel->modifyItem($updateData);
                if (!$resData) {
                    $this->errorMessage = L("Login_Password_Error");
                    return false;
                }

                // 登录成功
                return $this->afterLoginSuccess($param, $userData);

            } else {
                // 用户不存在
                $this->errorMessage = L("Login_Name_Or_Password_Error");
                return false;
            }
        }
    }

    /**
     * QQ登录入口 TODO
     * 第三方登录用户创建
     * 第三方登录用户注册
     * @param $param
     * @return array|bool
     * @throws \Exception
     */
    private function qqLogin($param)
    {
        // 查找当前匹配用户
        if (empty($param['openid'])) {
            $this->errorMessage = L("QQ_Openid_Error");
            return false;
        }

        return $this->thirdPartyLoginCommon($param, 'qq_openid', L("Account_has_been_bound_to_QQ"));
    }

    /**
     * strack登录入口
     * @param $param
     * @return array|bool
     * @throws \Exception
     */
    private function strackLogin($param)
    {
        // 查找当前匹配用户
        if (empty($param['openid'])) {
            $this->errorMessage = L("Strack_Openid_Error");
            return false;
        }

        return $this->thirdPartyLoginCommon($param, 'strack_union_id', L("Account_has_been_bound_to_Strack_Union"));
    }

    /**
     * strack登录APP内部免登录
     * @param $param
     * @return array|bool
     * @throws \Exception
     */
    private function strackLoginAppInside($param)
    {
        // 查找当前匹配用户
        if (empty($param['code'])) {
            $this->errorMessage = L("Strack_App_Inside_Code_Error");
            return false;
        }

        $oauthObject = $this->initOauthObject('strack');
        if (!empty($oauthObject['stateName'])) {

            $oAuth = $oauthObject['oAuth'];
            $strackUserData = $oAuth->getUserInfoByTempCode($param['code']);

            $userService = new UserService();
            // 判断是否有存在当前手机号的用户
            $userService->syncCreateUserByTheThirdLogin($strackUserData['data'], $oauthObject['idField']);

            // 3、判断此openid是否已绑定过账号
            $param['openid'] = $oAuth->openid;
        }

        return $this->thirdPartyLoginCommon($param, 'strack_union_id', L("Account_has_been_bound_to_Strack_Union"));
    }

    /**
     * wechat登录入口 TODO
     * 第三方登录用户创建
     * 第三方登录用户注册
     * @param $param
     */
    private function weChatLogin($param)
    {

    }

    /**
     * 处理登录成功后操作
     * @param $param
     * @param $userData
     * @param string $method
     * @return array
     * @throws \Exception
     */
    public function afterLoginSuccess($param, $userData, $method = 'default')
    {

        // 获取当前用户配置
        $userService = new UserService();
        $userLangSetting = $userService->getUserDefaultLang($userData["id"]);

        $token = md5(string_random(8) . '_' . $userData["id"] . '_' . time());

        // 当前用户
        if (!(session("?user_id") && session("user_id") == $userData["id"])) {
            session("user_id", $userData["id"]);
        }

        // 获取当前用户所在时差
        get_user_timezone_inter($userData["id"]);

        $resData = [];
        $sessionPrefix = strtolower($param["from"]);
        switch (strtolower($param["from"])) {
            case "api":
                // api登录请求，保存当前用户token
                $token = $userService->checkTokenExpireTime($userData, true);
                break;
            case "web_admin":
                // 后台登录方式

                break;
            case "default":
            default:
                //web 浏览器方式

                $resData["url"] = session("redirect_url"); // 返回跳转地址
                session('redirect_url', null);

                // 把当前用户使用语言设置写入cookie
                cookie('think_language', $userLangSetting);

                // 当前用户信息
                S('user_data_' . $userData['id'], $userData);
                break;
        }


        // Token放入session
        session($sessionPrefix . "_login_session", $token);

        $resData["token"] = $token;
        $resData["user_id"] = $userData["id"];


        return $resData;
    }


    /**
     * 统一处理登出操作
     * @param $from
     * @param int $userId
     * @return array
     */
    public function loginOut($from, $userId = 0)
    {
        if (in_array($from, ["api", "web", "web_admin"])) {

            // 销毁session
            session($from . "_login_session", null);

            switch ($from) {
                case "api":
                    // 处理api登出，销毁token

                    break;
                case "web_admin":
                    // 处理后台登出

                    break;
                default:
                    // 默认web登出

                    // 销毁语言包cookie设置
                    cookie('think_language', null);

                    break;
            }

            return success_response(L("Login_Out_SC"));
        } else {
            //  非法操作
            throw_strack_exception(L("Illegal_Operation"), 209005);
        }
    }

    /**
     * 获取第三方登录重链接
     * @param $type
     * @return string
     */
    public function getThirdAuthUrl($type)
    {
        $url = '';
        switch ($type) {
            case 'qq':
                $oAuth = new \Yurun\OAuthLogin\QQ\OAuth2(C("QQ_CONFIG")["app_id"], C("QQ_CONFIG")["app_key"], C("QQ_CONFIG")["callback_url"]);
                $url = $oAuth->getAuthUrl();
                session('YURUN_QQ_STATE', $oAuth->state);
                break;
            case 'strack':
                $oAuth = new \Yurun\OAuthLogin\Strack\OAuth2(C("STRACK_UNION_CONFIG")["app_key"], C("STRACK_UNION_CONFIG")["app_secret"], C("STRACK_UNION_CONFIG")["callback_url"]);
                $oAuth->setBaseUser(C("STRACK_UNION_CONFIG")["base_url"]);
                $url = $oAuth->getAuthUrl();
                session('YURUN_STRACK_STATE', $oAuth->state);
                break;
        }
        return $url;
    }

    /**
     * 初始化Oauth2.0权限对象
     * @param $type
     * @return array
     */
    public function initOauthObject($type)
    {
        $oAuth = [];
        $stateName = '';
        $idField = '';

        switch ($type) {
            case 'qq':
                $oAuth = new \Yurun\OAuthLogin\QQ\OAuth2(C("QQ_CONFIG")["app_id"], C("QQ_CONFIG")["app_key"], C("QQ_CONFIG")["callback_url"]);
                $stateName = 'YURUN_QQ_STATE';
                $idField = 'qq_openid';
                break;
            case 'strack':
                $oAuth = new \Yurun\OAuthLogin\Strack\OAuth2(C("STRACK_UNION_CONFIG")["app_key"], C("STRACK_UNION_CONFIG")["app_secret"], C("STRACK_UNION_CONFIG")["callback_url"]);
                $oAuth->setBaseUser(C("STRACK_UNION_CONFIG")["base_url"]);
                $stateName = 'YURUN_STRACK_STATE';
                $idField = 'strack_union_id';
                break;
        }

        return [
            'oAuth' => $oAuth,
            'stateName' => $stateName,
            'idField' => $idField,
        ];
    }

    /**
     * 处理第三方登录回调
     * @param $type
     * @return array
     * @throws \Exception
     */
    public function handleOauthCallBack($type)
    {

        $resParam = [];
        $oauthObject = $this->initOauthObject($type);

        if (!empty($oauthObject['stateName'])) {

            $oAuth = $oauthObject['oAuth'];
            $stateName = $oauthObject['stateName'];
            $idField = $oauthObject['idField'];

            $scope = !empty($_GET['scope']) ? $_GET['scope'] : '';

            if (!empty($scope) && $scope === 'app_inside_auth') {
                // 1、用户资料
                $strackUserData = $oAuth->getUserInfoByTempCode();

                // 自动创建账户绑定账户（仅限strack模式）
                if ($type === 'strack') {
                    $userService = new UserService();

                    // 判断是否有存在当前手机号的用户
                    $userService->syncCreateUserByTheThirdLogin($strackUserData['data'], $idField);
                }
            } else {
                // 1、验证state并获取accesstoken
                $oAuth->getAccessToken(session($stateName));

                // 2、用户资料
                $oAuth->getUserInfo();
            }

            // 3、判断此openid是否已绑定过账号
            $openid = $oAuth->openid;

            $userService = new UserService();
            $userDetail = $userService->getUserFindField([$idField => $openid], '*');

            if (!empty($userDetail)) {
                // 已经绑定账户
                $returnArr = $this->afterLoginSuccess(['from' => 'web', 'method' => ''], $userDetail);
                $resParam = [
                    'status' => 'binded',
                    'param' => $returnArr
                ];
            } else {
                // 未绑定账户
                $resParam = [
                    'status' => 'unbind',
                    'id_field' => $idField,
                    'openid' => $openid
                ];
            }
        }

        return $resParam;
    }
}

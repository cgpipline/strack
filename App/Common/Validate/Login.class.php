<?php

namespace Common\Validate;

use Think\Validate;

class Login extends Validate
{
    // 验证规则
    protected $rule = [];

    // GetToken 验证场景定义
    public function sceneIn()
    {
        return $this->append('login_name', 'require')
            ->append('password', 'require')
            ->append('from', 'require|eq:api')
            ->append('method', 'in:ldap,qq,weChat,strack,strack_app_inside')
            ->append('server_id', 'number');
    }

    // Refresh 验证场景定义
    public function sceneRefresh()
    {
        return $this->append('token', 'require');
    }
}

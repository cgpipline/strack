<?php

namespace Common\Validate;

use Think\Validate;

class User extends Validate
{
    // 验证规则
    protected $rule = [

    ];

    // Create 验证场景定义
    public function sceneCreate()
    {
        return $this;
    }

    // Update 验证场景定义
    public function sceneUpdate()
    {
        return $this->remove('app_uuid', 'require')
            ->remove('name', 'require')
            ->remove('code', 'require');
    }

    // Update 验证场景定义
    public function sceneRegister()
    {
        return $this->append('data', 'require')
            ->append('data.user', 'require')
            ->append('data.user.login_name', 'require')
            ->append('data.user.phone', 'require')
            ->append('data.user.name', 'require')
            ->append('data.user.password', 'require');
    }
}

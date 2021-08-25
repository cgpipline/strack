<?php

namespace Common\Validate;

use Think\Validate;

class Variable extends Validate
{
    // 验证规则
    protected $rule = [];

    // 创建自定义字段
    public function sceneCreate()
    {
        return $this->append('module', 'require')
            ->append('data', 'require');
    }
}

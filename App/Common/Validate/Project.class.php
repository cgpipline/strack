<?php

namespace Common\Validate;

use Think\Validate;

class Project extends Validate
{
    // 验证规则
    protected $rule = [];

    // Create 验证场景定义
    public function sceneCreate()
    {
        return $this->append('data.project', 'require|max:128')
            ->append('module.id', 'require|integer')
            ->append('module.code', 'require|max:128')
            ->append('module.type', 'require|max:128');
    }

    // Update 验证场景定义
    public function sceneUpdate()
    {
        return $this->append('data.project', 'require|max:128')
            ->append('module.id', 'require|integer')
            ->append('module.code', 'require|max:128')
            ->append('module.type', 'require|max:128');
    }
}

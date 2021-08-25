<?php

namespace Common\Validate;

use Think\Validate;

class Communal extends Validate
{
    // 验证规则
    protected $rule = [];

    // Fields 验证场景定义
    public function sceneFields()
    {
        return $this->append('module', 'require')
            ->append('module.code', 'require')
            ->append('module.type', 'require')
            ->append('project_id', 'integer');
    }

    // Find 验证场景定义
    public function sceneFind()
    {
        return $this->append('param.filter', 'array');
    }

    // Select 验证场景定义
    public function sceneSelect()
    {
        return $this->append('param.filter', 'array')
            ->append('param.page', 'array');
    }

    // GetRelationData 验证场景定义
    public function sceneGetRelationData()
    {
        return $this->append('param.filter', 'array')
            ->append('param.page', 'array');
    }

    // Create 验证场景定义
    public function sceneCreate()
    {
        return $this->append('data', 'require|array')
            ->append('module', 'require|array')
            ->append('module.code', 'require')
            ->append('module.type', 'require');
    }

    // Update 验证场景定义
    public function sceneUpdate()
    {
        return $this->append('param.filter', 'require|array')
            ->append('data', 'require|array')
            ->append('module', 'require|array')
            ->append('module.code', 'require')
            ->append('module.type', 'require');
    }

    // Delete 验证场景定义
    public function sceneDelete()
    {
        return $this->append('param.filter', 'require|array');
    }
}

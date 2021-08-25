<?php
/**
 * Created by PhpStorm.
 * User: kjb-02
 * Date: 2019/7/31
 * Time: 11:37
 */

namespace Common\Validate;

use Think\Validate;

class Base extends Validate
{
    // 验证规则
    protected $rule = [];

    // Create 验证场景定义
    public function sceneCreate()
    {
        return $this->append('data.base','require|max:128')
            ->append('module.id','require|integer')
            ->append('module.code','require|max:128')
            ->append('module.type','require|max:128');
    }

    // Update 验证场景定义
    public function sceneUpdate()
    {
        return $this->append('data.base','require|max:128')
            ->append('module.id','require|integer')
            ->append('module.code','require|max:128')
            ->append('module.type','require|max:128');
    }

    // find 验证场景定义
    public function sceneFind()
    {
        return $this->append('param.filter','array')
            ->append('param.fields','array');
    }

    // select 验证场景定义
    public function sceneSelect()
    {
        return $this->append('param.filter','array')
            ->append('param.fields','array')
            ->append('param.page','require|array')
            ->append('param.order','array');
    }

    public function sceneDelete()
    {
        return $this->append('param.filter', 'require|array');
    }

    // GetTaskStatusList 验证场景定义
    public function sceneGetTaskStatusList()
    {
        return $this->append('project_id', 'require|integer')
            ->append('module_code', 'require');
    }
}

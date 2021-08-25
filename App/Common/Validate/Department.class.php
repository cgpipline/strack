<?php
/**
 * Created by PhpStorm.
 * User: kjb-02
 * Date: 2019/8/8
 * Time: 9:22
 */

namespace Common\Validate;

use Think\Validate;

class Department extends Validate
{
    // 验证规则
    protected $rule = [];

    // Create 验证场景定义
    public function sceneCreate()
    {
        return $this->append('data.department','require|max:128')
            ->append('module.id','require|integer')
            ->append('module.code','require|max:128')
            ->append('module.type','require|max:128');
    }

    // Update 验证场景定义
    public function sceneUpdate()
    {
        return $this->append('data.department','require|max:128')
            ->append('module.id','require|integer')
            ->append('module.code','require|max:128')
            ->append('module.type','require|max:128');
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
        return $this->append('param.filter', 'require|array')
            ->append('module.id','require|integer')
            ->append('module.code','require|max:128')
            ->append('module.type','require|max:128');
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: kjb-02
 * Date: 2019/8/7
 * Time: 8:40
 */

namespace Common\Validate;

use Think\Validate;

class Tag extends Validate
{
    // 验证规则
    protected $rule = [];

    // Create 验证场景定义
    public function sceneCreate()
    {
        return $this->append('data.tag','require|max:128')
            ->append('module.name','require|max:128')
            ->append('module.code','require|max:128')
            ->append('module.type','require|in:system,review,approve,publish,custom,liber');
    }

    // Update 验证场景定义
    public function sceneUpdate()
    {
        return $this->append('data.tag','require|max:128')
            ->append('module.id','require|integer')
            ->append('module.name','max:128')
            ->append('module.code','max:128')
            ->append('module.type','in:system,review,approve,publish,custom,liber');
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
}

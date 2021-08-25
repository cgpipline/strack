<?php

namespace Common\Validate;

use Think\Validate;

class DirTemplate extends Validate
{
    // 验证规则
    protected $rule = [];

    //获得模板路径
    public function sceneGetTemplatePath()
    {
        return $this->append('module_id', 'require|number')
            ->append('link_id', 'require|number');
    }

    //获得模板路径
    public function sceneGetItemPath()
    {
        return $this->append('module_id', 'require|number')
            ->append('link_id', 'require|number');
    }

    //获得模板路径
    public function sceneFindTemplatePath()
    {
        return $this->append('filter.code', 'require');
    }
}

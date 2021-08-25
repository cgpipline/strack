<?php

namespace Common\Validate;

use Think\Validate;

class Horizontal extends Validate
{
    // 验证规则
    protected $rule = [];

    //创建一条水平关联数据
    public function sceneCreateHorizontal()
    {
        return $this->append('src_module_id', 'require|number')
            ->append('src_link_id', 'require|number')
            ->append('dst_module_id', 'require|number')
            ->append('dst_link_id', 'require|number')
            ->append('variable_id', 'require|number');
    }
}

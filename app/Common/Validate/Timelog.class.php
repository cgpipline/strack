<?php

namespace Common\Validate;

use Think\Validate;

class Timelog extends Validate
{
    // 验证规则
    protected $rule = [];

    // 创建一个定时器
    public function sceneStartTimer()
    {
        return $this->append('user_id', 'require|number')
            ->append('module_id', 'require|number')
            ->append('link_id', 'require|number');
    }

    // 停止定时器
    public function sceneStopTimer()
    {
        return $this->append('id', 'require|number');
    }
}

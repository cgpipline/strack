<?php

namespace Common\Validate;

use Think\Validate;

class Event extends Validate
{
    // 验证规则
    protected $rule = [];

    // 获取指定服务器信息
    public function sceneGetEventLogServer()
    {
        return $this->append('server_id', 'require|number');
    }
}

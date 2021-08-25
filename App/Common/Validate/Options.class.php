<?php

namespace Common\Validate;

use Think\Validate;

class Options extends Validate
{
    // 验证规则
    protected $rule = [];

    // 获得指定名称的配置
    public function sceneGetOptions()
    {
        return $this->append('options_name', 'require|max:50');
    }

    // 修改options
    public function sceneUpdateOptions()
    {
        return $this->append('options_name', 'require|max:50')
            ->append('config', 'require');
    }

    // 添加配置
    public function sceneAddOptions()
    {
        return $this->append('options_name', 'require|max:50')
            ->append('config', 'require');
    }
}

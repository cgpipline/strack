<?php
/**
 * Created by PhpStorm.
 * User: kjb-02
 * Date: 2019/8/7
 * Time: 8:40
 */

namespace Common\Validate;

use Think\Validate;

class Module extends Validate
{
    // 验证规则
    protected $rule = [];

    // GetRelationModuleData 验证场景定义
    public function sceneGetRelationModuleData()
    {
        return $this->append('module_code', 'require');
    }
}

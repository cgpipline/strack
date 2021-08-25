<?php
/**
 * Created by PhpStorm.
 * User: kjb-02
 * Date: 2019/7/31
 * Time: 14:13
 */

namespace Common\Validate;

use Think\Validate;

class View extends  Validate
{
    // 验证规则
    protected $rule = [];

    // 创建默认视图
    public function sceneCreateDefaultView()
    {
        return $this->append('page','require')
            ->append('code','require')
            ->append('name','require')
            ->append('config','require');
    }

    // 删除默认视图
    public function sceneDeleteDefaultView()
    {
        return $this->append('page','require')
            ->append('project_id','require|number');
    }

    // 查找默认视图
    public function sceneFindDefaultView()
    {
        return $this->append('filter.page','require')
            ->append('filter.project_id','require|number');
    }

}

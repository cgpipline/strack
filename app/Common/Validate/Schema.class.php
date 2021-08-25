<?php

namespace Common\Validate;

use Think\Validate;

class Schema extends Validate
{
    // 验证规则
    protected $rule = [];

    // 创建Schema关联结构
    public function sceneCreateSchemaStructure()
    {
        return $this->append('name', 'require|max:25')
            ->append('code', 'require')
            ->append('type', 'in:system,project')
            ->append('relation_data', 'require');
    }

    // 修改Schema结构
    public function sceneUpdateSchemaStructure()
    {
        return $this->append('schema_id', 'require|number')
            ->append('module_id', 'require|number')
            ->append('relation_module', 'require');
    }

    // 获得Schema
    public function sceneGetSchemaStructure()
    {
        return $this->append('schema_id', 'require|number');
    }

    // 删除Schema
    public function sceneDeleteSchemaStructure()
    {
        return $this->append('schema_id', 'require|number');
    }

    // 创建Entity模块
    public function sceneCreateEntityModule()
    {
        return $this->append('name', 'require|max:25')
            ->append('code', 'require');
    }

    // 获取单个表配置
    public function sceneGetTableConfig()
    {
        return $this->append('table', 'require');
    }

    // 更新字段表配置
    public function sceneUpdateTableConfig()
    {
        return $this->append('table', 'require')
            ->append('config', 'require');
    }
}

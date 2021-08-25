<?php

namespace Common\Model;

use Think\Model\RelationModel;

class ProjectDiskModel extends RelationModel
{

    //自动验证
    protected $_validate = [
        ['project_id', '', '', self::MUST_VALIDATE, 'require', self::MODEL_INSERT],//必须字段
        ['project_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['config', '', '', self::EXISTS_VALIDATE, 'array']
    ];

    //自动完成
    protected $_auto = [
        ['config', 'json_encode', self::EXISTS_VALIDATE, 'function'],
        ['uuid', 'create_uuid', self::MODEL_INSERT, 'function']
    ];

    /**
     * 获取器：配置
     * @param $value
     * @return array|mixed
     */
    public function getConfigAttr($value)
    {
        if (!empty($value)) {
            return json_decode($value, true);
        }
        return [];
    }
}

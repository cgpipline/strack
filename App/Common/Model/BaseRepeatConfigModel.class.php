<?php

namespace Common\Model;

use Think\Model\RelationModel;

class BaseRepeatConfigModel extends RelationModel
{
    //自动验证
    protected $_validate = [
        ['base_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['mode', ['daily','weekly','monthly','annually','working_days','custom'], '', self::EXISTS_VALIDATE, 'in'],
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

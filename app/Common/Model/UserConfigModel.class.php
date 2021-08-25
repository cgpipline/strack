<?php

// +----------------------------------------------------------------------
// | 用户配置数据表
// +----------------------------------------------------------------------

namespace Common\Model;

use Think\Model\RelationModel;

class UserConfigModel extends RelationModel
{

    //自动验证
    protected $_validate = [
        ['user_id', '', '', self::MUST_VALIDATE, 'require', self::MODEL_INSERT],//必须字段
        ['user_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['type', ['system', 'reminder', 'filter_stick', 'add_panel', 'update_panel', 'top_field', 'main_field', 'fields_show_mode'], '', self::EXISTS_VALIDATE, 'in'],
        ['config', '', '', self::EXISTS_VALIDATE, 'array'],
        ['template_id', '', '', self::EXISTS_VALIDATE, 'integer'],
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

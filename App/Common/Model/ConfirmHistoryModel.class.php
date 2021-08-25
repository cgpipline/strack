<?php

namespace Common\Model;

use Think\Model\RelationModel;

class ConfirmHistoryModel extends RelationModel
{

    //自动验证
    protected $_validate = [
        ['link_id', '', '', self::MUST_VALIDATE, 'require', self::MODEL_INSERT],//必须字段
        ['link_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['module_id', '', '', self::MUST_VALIDATE, 'require', self::MODEL_INSERT],//必须字段
        ['module_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['user_id', '', '', self::MUST_VALIDATE, 'require', self::MODEL_INSERT],//必须字段
        ['variable_id', '', '', self::EXISTS_VALIDATE, 'integer']
    ];

    //自动完成
    protected $_auto = [
        ['created', 'time', self::MODEL_INSERT, 'function'],
        ['uuid', 'create_uuid', self::MODEL_INSERT, 'function']
    ];
}

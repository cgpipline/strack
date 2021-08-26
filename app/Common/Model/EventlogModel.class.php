<?php

namespace Common\Model;

use Think\Model\RelationModel;

class EventlogModel extends RelationModel
{
    // 自动验证
    protected $_validate = [
        ['operate', '', '', self::MUST_VALIDATE, 'require', self::MODEL_INSERT],//必须字段
        ['operate', '1,128', '', self::EXISTS_VALIDATE, 'length'],
        ['type', ['built_in', 'custom'], '', self::EXISTS_VALIDATE, 'in'],
        ['table', '', '', self::MUST_VALIDATE, 'require', self::MODEL_INSERT],//必须字段
        ['table', '1,32', '', self::EXISTS_VALIDATE, 'length'],
        ['project_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['project_name', '0,128', '', self::EXISTS_VALIDATE, 'length'],
        ['link_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['link_name', '0,128', '', self::EXISTS_VALIDATE, 'length'],
        ['module_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['module_code', '0,128', '', self::EXISTS_VALIDATE, 'length'],
        ['module_name', '0,128', '', self::EXISTS_VALIDATE, 'length'],
        ['record', '', '', self::EXISTS_VALIDATE, 'array'],
        ['belong_system', '0,128', '', self::EXISTS_VALIDATE, 'length'],
        ['batch_number', '0,45', '', self::EXISTS_VALIDATE, 'length'],
        ['from', '0,64', '', self::EXISTS_VALIDATE, 'length'],
        ['user_uuid', '0,32', '', self::EXISTS_VALIDATE, 'length'],
        ['created_by', '', '', self::EXISTS_VALIDATE, 'integer'],
    ];

    // 自动完成
    public $_auto = [
        ['record', 'json_encode', self::EXISTS_VALIDATE, 'function'],
        ['created', 'time', self::MODEL_INSERT, 'function'],
        ['uuid', 'create_uuid', self::MODEL_INSERT, 'function']
    ];
}

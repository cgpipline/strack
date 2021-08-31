<?php

namespace Common\Model;

use Think\Model\RelationModel;

class MessageModel extends RelationModel
{

    //自动验证
    protected $_validate = [
        ['operate', '', '', self::MUST_VALIDATE, 'require', self::MODEL_INSERT],//必须字段
        ['operate', '1,128', '', self::EXISTS_VALIDATE, 'length'],
        ['type', '', '', self::MUST_VALIDATE, 'require', self::MODEL_INSERT],//必须字段
        ['type', '1,128', '', self::EXISTS_VALIDATE, 'length'],
        ['module_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['project_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['primary_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['content', '', '', self::EXISTS_VALIDATE, 'array'],
        ['emergent', ['normal', 'emergent'], '', self::EXISTS_VALIDATE, 'in'],
        ['sender', '', '', self::EXISTS_VALIDATE, 'array'],
        ['email_template', '0,128', '', self::EXISTS_VALIDATE, 'length'],
        ['from', '0,64', '', self::EXISTS_VALIDATE, 'length'],
        ['identity_id', '0,64', '', self::EXISTS_VALIDATE, 'length'],
        ['created_by', '', '', self::EXISTS_VALIDATE, 'integer'],
    ];

    // 自动完成
    protected $_auto = [
        ['content', 'json_encode', self::EXISTS_VALIDATE, 'function'],
        ['sender', 'json_encode', self::EXISTS_VALIDATE, 'function'],
        ['uuid', 'create_uuid', self::MODEL_INSERT, 'function']
    ];
}

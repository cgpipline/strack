<?php

namespace Common\Model;

use Think\Model\RelationModel;

class MessageMemberModel extends RelationModel
{

    //自动验证
    protected $_validate = [
        ['status', ['read', 'unread'], '', self::EXISTS_VALIDATE, 'in'],
        ['message_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['user_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['name', '0,255', '', self::EXISTS_VALIDATE, 'length'],
        ['email', '0,128', '', self::EXISTS_VALIDATE, 'length'],
        ['belong_type', '0,36', '', self::EXISTS_VALIDATE, 'length'],
        ['created_by', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['json', '', '', self::EXISTS_VALIDATE, 'array'],
    ];

    // 自动完成
    protected $_auto = [
        ['json', 'json_encode', self::EXISTS_VALIDATE, 'function'],
        ['created', 'time', self::MODEL_INSERT, 'function'],
        ['uuid', 'create_uuid', self::MODEL_INSERT, 'function']
    ];
}

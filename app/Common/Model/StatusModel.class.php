<?php

// +----------------------------------------------------------------------
// | 状态数据表
// +----------------------------------------------------------------------

namespace Common\Model;

use Think\Model\RelationModel;

class StatusModel extends RelationModel
{

    //自动验证
    protected $_validate = [
        ['name', '', '', self::MUST_VALIDATE, 'require', self::MODEL_INSERT],//必须字段
        ['name', '1,128', '', self::EXISTS_VALIDATE, 'length'],
        ['name', '', '', self::EXISTS_VALIDATE, 'unique'],
        ['code', '', '', self::MUST_VALIDATE, 'require', self::MODEL_INSERT],//必须字段
        ['code', '1,128', '', self::EXISTS_VALIDATE, 'length'],
        ['code', '', '', self::EXISTS_VALIDATE, 'unique'],
        ['code', '', '', self::EXISTS_VALIDATE, 'alphaDash'],
        ['color', '6', '', self::EXISTS_VALIDATE, 'length'],
        ['icon', '0,24', '', self::EXISTS_VALIDATE, 'length'],
        ['correspond', ['un_evaluated', 'blocked', 'not_started', 'in_progress', 'daily', 'done', 'hide'], '', self::EXISTS_VALIDATE, 'in']
    ];

    // 自动完成
    protected $_auto = [
        ['uuid', 'create_uuid', self::MODEL_INSERT, 'function']
    ];
}

<?php

namespace Common\Model;

use Think\Model\RelationModel;

class PlanLockModel extends RelationModel
{
    //自动验证
    protected $_validate = [
        ['date', '', '', self::EXISTS_VALIDATE, 'date']
    ];

    //自动完成
    protected $_auto = [
        ['date', 'strtotime', self::EXISTS_VALIDATE, 'function'],
        ['updated_by', 'fill_created_by', self::MODEL_BOTH, 'function'],
        ['uuid', 'create_uuid', self::MODEL_INSERT, 'function']
    ];
}

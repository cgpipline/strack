<?php

namespace Common\Model;

use Think\Model\RelationModel;

class PlanModel extends RelationModel
{
    //自动验证
    protected $_validate = [
        ['lock', ['yes', 'no'], '', self::EXISTS_VALIDATE, 'in'],
        ['module_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['link_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['user_id', '', '', self::EXISTS_VALIDATE, 'integer'],
        ['start_time', '', '', self::EXISTS_VALIDATE, 'date'],
        ['end_time', '', '', self::EXISTS_VALIDATE, 'date']
    ];

    //自动完成
    protected $_auto = [
        ['created_by', 'fill_created_by', self::MODEL_INSERT, 'function'],
        ['start_time', 'strtotime', self::EXISTS_VALIDATE, 'function'],
        ['end_time', 'strtotime', self::EXISTS_VALIDATE, 'function'],
        ['created', 'time', self::MODEL_INSERT, 'function'],
        ['uuid', 'create_uuid', self::MODEL_INSERT, 'function']
    ];
}

<?php

// +----------------------------------------------------------------------
// | 视图数据表
// +----------------------------------------------------------------------

namespace Common\Model;

use Think\Model\RelationModel;

class DingtalkPunchCardRecordModel extends RelationModel
{

    //自动验证
    protected $_validate = [
    ];

    //自动完成
    protected $_auto = [
        ['created', 'time', self::MODEL_INSERT, 'function']
    ];

}

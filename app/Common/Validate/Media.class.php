<?php

namespace Common\Validate;

use Think\Validate;

class Media extends Validate
{
    // 验证规则
    protected $rule = [];

    // 添加media
    public function sceneCreateMedia()
    {
        return $this->append('media_data', 'require')
            ->append('link_id', 'require|number')
            ->append('media_server', 'require')
            ->append('type', 'require')
            ->append('module_code', 'require|number');
    }

    // 获取指定服务器信息
    public function sceneGetMediaServerItem()
    {
        return $this->append('param.filter', 'require|array');
    }

    // 获取指定尺寸的媒体缩略图路径
    public function sceneGetSpecifySizeThumbPath()
    {
        return $this->append('param.filter', 'require|array')
            ->append('size', 'require');
    }

    // 获取指定媒体信息
    public function sceneGetMediaData()
    {
        return $this->append('param.filter', 'require|array');
    }

    // 获取多个媒体信息
    public function sceneSelectMediaData()
    {
        return $this->append('param.filter', 'require|array');
    }

    // 更新media
    public function sceneUpdateMedia()
    {
        return $this->append('media_data', 'require')
            ->append('link_id', 'require|number')
            ->append('module_code', 'require')
            ->append('media_server', 'require');
    }
}

<?php

namespace Common\Service;

class RedisService
{

    /**
     * 保存缓存
     * @param $name
     * @param $data
     */
    public function saveCache($name, $data)
    {
        S($name, $data);
    }

    /**
     * 获取缓存
     * @param $name
     * @return string
     */
    public function getCache($name)
    {
        $cacheData = S($name);
        if (!empty($cacheData) && $cacheData['expire'] > time()) {
            return $cacheData['data'];
        }

        return '';
    }

}

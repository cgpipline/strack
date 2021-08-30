<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;

class QueueClient
{
    /**
     * Send.
     * @param $queue
     * @param $data
     * @param int $delay
     * @param null $cb
     */
    public static function send($queue, $data, $delay = 0, $cb = null)
    {
        Cache::init();

        static $_id = 0;
        $id = \microtime(true) . '.' . (++$_id);
        $now = time();
        $package_str = \json_encode([
            'id' => $id,
            'time' => $now,
            'delay' => $delay,
            'attempts' => 0,
            'queue' => $queue,
            'data' => $data
        ]);
        if (\is_callable($delay)) {
            $cb = $delay;
            $delay = 0;
        }
        if ($cb) {
            $cb = function ($ret) use ($cb) {
                $cb((bool)$ret);
            };
            if ($delay == 0) {
                Cache::lPush(\Workerman\RedisQueue\Client::QUEUE_WAITING . $queue, $package_str, $cb);
            } else {
                Cache::zAdd(\Workerman\RedisQueue\Client::QUEUE_DELAYED, $now + $delay, $package_str, $cb);
            }
            return;
        }
        if ($delay == 0) {
            Cache::lPush(\Workerman\RedisQueue\Client::QUEUE_WAITING . $queue, $package_str);
        } else {
            Cache::zAdd(\Workerman\RedisQueue\Client::QUEUE_DELAYED, $now + $delay, $package_str);
        }
    }
}

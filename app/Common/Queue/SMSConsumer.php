<?php

namespace Common\Queue;


use Think\Console;

class SMSConsumer{

    // 要消费的队列名
    public $queue = 'sms';

    /**
     * 消费
     * @param $data
     */
    public function consume($data)
    {
        try {
            // 执行应用
            Console::call($this->queue, ['param' => json_encode($data)]);
        } catch (\Throwable $e) {

        }
    }
}

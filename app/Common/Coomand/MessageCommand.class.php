<?php

namespace Common\Command;

use Common\Service\CentrifugalService;
use Common\Service\MessageService;
use Think\Console\Command;
use Think\Console\Input;
use Think\Console\Output;

class MessageCommand extends Command
{
    protected function configure()
    {
        $this->setName('message')
            ->setDescription('Implementation of message')
            ->addArgument('param');
    }

    /**
     * 执行添加
     * @param Input $input
     * @param Output $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(Input $input, Output $output)
    {
        $arg = $input->getArguments();

        $param = json_decode($arg['param'], true);

        $messageService = new MessageService();

        if (!empty($param)) {
            $CentrifugalService = new CentrifugalService();
            foreach ($param as $item) {
                // 把消息写入数据库
                if (!empty($item["message_data"]["member"])) {
                    $messageService->addMessage($item);
                }
                // 推送消息
                $CentrifugalService->pushMassage('strack_browser_channel', $item['response_data']);
            }
        }
    }
}

<?php

namespace Common\Command;

use Common\Service\MessageService;
use Think\Console\Command;
use Think\Console\Input;
use Think\Console\Output;

class SMSCommand extends Command
{
    protected function configure()
    {
        $this->setName('sms')
            ->setDescription('Implementation of sms')
            ->addArgument('param');
    }

    /**
     * 执行添加
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        $arg = $input->getArguments();

        $param = json_decode($arg['param'], true);

        if(!empty($param)){
            $messageService = new MessageService();
            $messageService->sendSMS($param['data'], $param['gateway']);
        }
    }
}

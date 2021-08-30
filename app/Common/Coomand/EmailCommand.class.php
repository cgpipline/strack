<?php

namespace Common\Command;

use Common\Service\EmailService;
use Think\Console\Command;
use Think\Console\Input;
use Think\Console\Output;

class EmailCommand extends Command
{
    protected function configure()
    {
        $this->setName('email')
            ->setDescription('Implementation of message')
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
            $emailService = new EmailService();
            $emailService->queueSendEmail($param);
        }
    }
}

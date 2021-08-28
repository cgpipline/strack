<?php

namespace Common\Command;

use Common\Service\EventLogService;
use Think\Console\Command;
use Think\Console\Input;
use Think\Console\Output;

class EventlogCommand extends Command
{
    protected function configure()
    {
        $this->setName('eventlog')
            ->setDescription('Implementation of eventlog')
            ->addArgument('param');
    }

    /**
     * 执行添加
     * @param Input $input
     * @param Output $output
     * @return int|void|null
     * @throws \Think\Exception
     */
    protected function execute(Input $input, Output $output)
    {
        $arg = $input->getArguments();

        $param = json_decode($arg['param'], true);

        if(!empty($param)){
            $eventLogService = new EventLogService();
            $eventLogService->addInsideEventLog($param["event_from"], $param["params"], $param["user_info"]);
        }
    }
}

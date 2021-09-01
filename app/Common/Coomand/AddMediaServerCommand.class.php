<?php

namespace Common\Command;

use Common\Service\MediaService;
use Think\Console\Command;
use Think\Console\Input;
use Think\Console\Output;

class AddMediaServerCommand extends Command
{
    protected function configure()
    {
        $this->setName('add_media_server')
            ->setDescription('Implementation of add media server')
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
            try {
                $mediaService = new MediaService();
                $mediaService->addMediaServer($param);
            }catch (\Exception $e){

            }
        }
    }
}

<?php

namespace Common\Command;

use Think\Console\Command;
use Think\Console\Input;
use Think\Console\Output;
use Think\QueueClient;

class TestMsgCommand extends Command
{
    protected function configure()
    {
        $this->setName('msg')
            ->setDescription('Implementation of message');
    }

    /**
     * 执行添加
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {

        $test = [
            "event_from"=>"strack_web",
            "user_info"=>[
                "id"=>2,
                "login_name"=>"strack",
                "email"=>"client@strack.com",
                "name"=>"strack",
                "nickname"=>"strack",
                "phone"=>"88888888888",
                "department_id"=>0,
                "status"=>"in_service",
                "login_count"=>0,
                "token_time"=>1630132920,
                "forget_count"=>0,
                "forget_token"=>"",
                "last_forget"=>0,
                "failed_login_count"=>0,
                "last_login"=>1630132920,
                "created"=>"",
                "uuid"=>"e10247c0-0710-11ec-ae55-fdd185538804",
                "login_secret_key"=>"",
                "qq_openid"=>"",
                "strack_union_id"=>""
            ],
            "params"=>[
                "operate"=>"update",
                "primary_id"=>1,
                "primary_field"=>"id",
                "data"=>[
                    "old"=>[
                        "name"=>"\u6d4b\u8bd5"
                    ],
                    "new"=>[
                        "name"=>"\u6d4b\u8bd52222"
                    ]
                ],
                "param"=>[
                    "table"=>"strack_project",
                    "model"=>"Project",
                    "where"=>[
                        "id"=>"1"
                    ]
                ],
                "table"=>"strack_project",
                "batch_number"=>"task_fdc01fcc-c19dcd90-07e7-11ec-9987-27bfd783dc47"
            ]
        ];

        QueueClient::send('eventlog', $test);
    }

}

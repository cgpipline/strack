<?php


use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class fillScheduleIndexRules extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */

    /**
     * 保存权限组
     * @param $data
     * @param int $parentId
     */
    protected function savePageAuth($data, $parentId = 0)
    {
        $pageAuthTable = $this->table('strack_page_auth');
        $pageLinkAuthTable = $this->table('strack_page_link_auth');

        $data["page"]["parent_id"] = $parentId;

        $pageAuthTable->insert($data["page"])->save();
        $query = $this->fetchRow('SELECT max(`id`) as id FROM strack_page_auth');

        if (!empty($data["auth_group"])) {
            foreach ($data["auth_group"] as $authGroup) {
                $authGroup["page_auth_id"] = $query["id"];
                $pageLinkAuthTable->insert($authGroup)->save();
            }
        }

        if (!empty($data["list"])) {
            foreach ($data["list"] as $children) {
                $this->savePageAuth($children, $query["id"]);
            }
        }
    }

    /**
     * 绑定权限组
     * @param $id
     * @throws Exception
     */
    public function bindAuthGroup($id)
    {

        $authGroupNode = [
            "auth_group_id" => $id,
            "auth_node_id" => 730,
            "uuid" => Webpatser\Uuid\Uuid::generate()->string
        ];

        $this->table('strack_auth_group_node')->insert($authGroupNode)->save();
    }


    /**
     * 保存权限组
     * @param $data
     */
    protected function saveAuthGroup($data)
    {
        // 初始化table
        $authGroupTable = $this->table('strack_auth_group');
        $authGroupNodeTable = $this->table('strack_auth_group_node');

        $authGroupTable->insert($data["group"])->save();
        $query = $this->fetchRow('SELECT max(`id`) as id FROM strack_auth_group');

        foreach ($data["rules"] as $authGroupNode) {
            $authGroupNode["auth_group_id"] = $query["id"];
            $authGroupNodeTable->insert($authGroupNode)->save();
        }
    }


    /**
     * @throws Exception
     */
    public function up()
    {
        // 添加权限节点
        $authNodes = [
            [
                'name' => '访问我的日程页面',
                'code' => 'index',
                'lang' => 'index',
                'type' => 'view',
                'module' => 'page',
                'project_id' => 0,
                'module_id' => 0,
                'rules' => 'Home/Schedule/index',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '获取我的日程',
                'code' => 'get_my_schedule_data',
                'lang' => 'Get_My_Schedule_Data',
                'type' => 'route',
                'module' => 'page',
                'project_id' => 0,
                'module_id' => 0,
                'rules' => 'Home/Widget/getMyScheduleData',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '获取日历过滤配置',
                'code' => 'get_calendar_filter_config',
                'lang' => 'Get_Calendar_Filter_Config',
                'type' => 'route',
                'module' => 'page',
                'project_id' => 0,
                'module_id' => 0,
                'rules' => 'Home/Widget/getCalendarFilterConfig',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '添加任务计划',
                'code' => 'add_task_plan',
                'lang' => 'Add_Task_Plan',
                'type' => 'route',
                'module' => 'page',
                'project_id' => 0,
                'module_id' => 0,
                'rules' => 'Home/Schedule/addTaskPlan',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '锁定任务计划按钮',
                'code' => 'lock_task_plan_bnt',
                'lang' => 'Lock_Task_Plan',
                'type' => 'view',
                'module' => 'page',
                'project_id' => 0,
                'module_id' => 0,
                'rules' => 'lock_task_plan',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '锁定任务计划路由',
                'code' => 'lock_task_plan',
                'lang' => 'Lock_Task_Plan',
                'type' => 'route',
                'module' => 'page',
                'project_id' => 0,
                'module_id' => 0,
                'rules' => 'Home/Schedule/lockTaskPlan',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '删除任务计划按钮',
                'code' => 'delete_task_plan_bnt',
                'lang' => 'Delete_Task_Plan',
                'type' => 'view',
                'module' => 'page',
                'project_id' => 0,
                'module_id' => 0,
                'rules' => 'delete_task_plan',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '删除任务计划路由',
                'code' => 'delete_task_plan',
                'lang' => 'Delete_Task_Plan',
                'type' => 'route',
                'module' => 'page',
                'project_id' => 0,
                'module_id' => 0,
                'rules' => 'Home/Schedule/deleteTaskPlan',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ]
        ];

        foreach ($authNodes as $authNode) {
            $this->table('strack_auth_node')->insert($authNode)->save();
        }

        // 绑定权限分组
        /**
         * 我的日程
         */
        $scheduleButtonRows = [
            [
                'group' => [
                    'name' => '我的日程',
                    'code' => 'my_schedule_index',
                    'lang' => 'My_Schedule',
                    'type' => 'view',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ],
                'rules' => [
                    [ // 我的日程页面
                        'auth_group_id' => 0,
                        'auth_node_id' => 736,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 获取我的日程数据路由
                        'auth_group_id' => 0,
                        'auth_node_id' => 732,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 修改单个组件数据
                        'auth_group_id' => 0,
                        'auth_node_id' => 268,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 获取日历过滤配置
                        'auth_group_id' => 0,
                        'auth_node_id' => 737,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 添加任务计划
                        'auth_group_id' => 0,
                        'auth_node_id' => 738,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 获取当前模块面包屑导航路由
                        'auth_group_id' => 0,
                        'auth_node_id' => 239,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 获取详情页面顶部缩略图路由
                        'auth_group_id' => 0,
                        'auth_node_id' => 183,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 获取当前模块面包屑导航路由
                        'auth_group_id' => 0,
                        'auth_node_id' => 237,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 获取数据表格边侧栏历史数据路由
                        'auth_group_id' => 0,
                        'auth_node_id' => 272,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 网盘权限
                        'auth_group_id' => 0,
                        'auth_node_id' => 704,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ]
                ]
            ],
            [
                'group' => [
                    'name' => '更新我的日程',
                    'code' => 'update_item_dialog',
                    'lang' => 'update_Item_Dialog',
                    'type' => 'view',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ],
                'rules' => [
                    [ // 修改单个组件数据
                        'auth_group_id' => 0,
                        'auth_node_id' => 269,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ]
                ]
            ],
            [
                'group' => [
                    'name' => '创建任务',
                    'code' => 'create_schedule_task',
                    'lang' => 'Create_Schedule_Task',
                    'type' => 'view',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ],
                'rules' => [
                    [ // 创建任务
                        'auth_group_id' => 0,
                        'auth_node_id' => 225,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 获取控件数据
                        'auth_group_id' => 0,
                        'auth_node_id' => 267,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 保存创建配置
                        'auth_group_id' => 0,
                        'auth_node_id' => 164,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ]
                ]
            ],
            [
                'group' => [
                    'name' => '锁定任务计划',
                    'code' => 'lock_task_plan',
                    'lang' => 'Lock_Task_Plan',
                    'type' => 'view',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ],
                'rules' => [
                    [ // 锁定任务计划按钮
                        'auth_group_id' => 0,
                        'auth_node_id' => 739,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 锁定任务计划路由
                        'auth_group_id' => 0,
                        'auth_node_id' => 740,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ]
                ]
            ],
            [
                'group' => [
                    'name' => '删除任务计划',
                    'code' => 'delete_task_plan',
                    'lang' => 'Delete_Task_Plan',
                    'type' => 'view',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ],
                'rules' => [
                    [ // 删除任务计划按钮
                        'auth_group_id' => 0,
                        'auth_node_id' => 741,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    [ // 删除任务计划路由
                        'auth_group_id' => 0,
                        'auth_node_id' => 742,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ]
                ]
            ]
        ];

        foreach ($scheduleButtonRows as $scheduleButtonRow){
            $this->saveAuthGroup($scheduleButtonRow);
        }

        /**
         * 消息盒子
         */
        $messageBoxRows = [
            'page' => [
                'name' => '我的日程',
                'code' => 'my_schedule_index',
                'lang' => 'My_Schedule',
                'page' => 'home_schedule_index',
                'menu' => 'top_main_menu',
                'category' => 'Top_Main_Menu',
                'param' => '',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 484,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ],
            'list' => [
                [
                    'page' => [
                        'name' => '我的日程访问',
                        'code' => 'my_schedule_index',
                        'lang' => 'My_Schedule',
                        'page' => 'home_schedule_index',
                        'param' => '',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 484,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '更新任务',
                        'code' => 'update_widget',
                        'lang' => 'Update_Widget',
                        'page' => 'home_schedule_index',
                        'param' => '',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 485,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '创建任务',
                        'code' => 'create',
                        'lang' => 'Create',
                        'page' => 'home_schedule_index',
                        'param' => '',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 486,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '锁定任务计划',
                        'code' => 'lock_task_plan',
                        'lang' => 'Lock_Task_Plan',
                        'page' => 'home_schedule_index',
                        'param' => '',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 487,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '删除任务计划',
                        'code' => 'delete_task_plan',
                        'lang' => 'Delete_Task_Plan',
                        'page' => 'home_schedule_index',
                        'param' => '',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 488,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '边侧栏',
                        'code' => 'side_bar',
                        'lang' => 'Side_Bar',
                        'page' => 'home_schedule_index',
                        'param' => '',
                        'type' => 'children',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'list' => [
                        [
                            'page' => [
                                'name' => '顶部面板',
                                'code' => 'top_panel',
                                'lang' => 'Top_Panel',
                                'page' => 'home_schedule_index',
                                'param' => '',
                                'type' => 'children',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                            ],
                            'list' => [
                                [
                                    'page' => [
                                        'name' => '字段配置',
                                        'code' => 'fields_rules',
                                        'lang' => 'Fields_rules',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 168,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '上一个/下一个',
                                        'code' => 'prev_next_one',
                                        'lang' => 'Prev_Next_One',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 169,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '动作',
                                        'code' => 'action',
                                        'lang' => 'Action',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 3,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '记录Timelog',
                                        'code' => 'timelog',
                                        'lang' => 'Timelog',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 193,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '修改缩略图',
                                        'code' => 'modify_thumb',
                                        'lang' => 'Modify_Thumb',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 6,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '清除缩略图',
                                        'code' => 'clear_thumb',
                                        'lang' => 'Clear_Thumb',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 7,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'page' => [
                                'name' => '标签栏',
                                'code' => 'tab_bar',
                                'lang' => 'Tab_Bar',
                                'page' => 'home_schedule_index',
                                'param' => '',
                                'type' => 'children',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'list' => [
                                [
                                    'page' => [
                                        'name' => '反馈',
                                        'code' => 'note',
                                        'lang' => 'Note',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 124,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    'list' => [
                                        [
                                            'page' => [
                                                'name' => '反馈',
                                                'code' => 'note',
                                                'lang' => 'note',
                                                'page' => 'home_schedule_index',
                                                'param' => '',
                                                'type' => 'belong',
                                                'parent_id' => 0,
                                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                            ],
                                            [
                                                'page' => [
                                                    'name' => '反馈提交',
                                                    'code' => 'submit',
                                                    'lang' => 'Submit',
                                                    'page' => 'home_schedule_index',
                                                    'param' => '',
                                                    'type' => 'belong',
                                                    'parent_id' => 0,
                                                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                                ],
                                                'auth_group' => [
                                                    [
                                                        'page_auth_id' => 0,
                                                        'auth_group_id' => 125,
                                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ], [
                                    'page' => [
                                        'name' => '信息',
                                        'code' => 'info',
                                        'lang' => 'Info',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 170,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    'list' => [
                                        [
                                            'page' => [
                                                'name' => '修改单个组件信息',
                                                'code' => 'modify',
                                                'lang' => 'Modify',
                                                'page' => 'home_schedule_index',
                                                'param' => '',
                                                'type' => 'belong',
                                                'parent_id' => 0,
                                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                            ],
                                            'auth_group' => [
                                                [
                                                    'page_auth_id' => 0,
                                                    'auth_group_id' => 171,
                                                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '现场数据',
                                        'code' => 'onset',
                                        'lang' => 'Onset',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 172,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '历史记录',
                                        'code' => 'history',
                                        'lang' => 'History',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 176,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '设置标签栏',
                                        'code' => 'template_fixed_tab',
                                        'lang' => 'Template_Fixed_Tab',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 177,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '任务',
                                        'code' => 'base',
                                        'lang' => 'Base',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 457,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    "list" => []
                                ],
                                [
                                    'page' => [
                                        'name' => '文件',
                                        'code' => 'file',
                                        'lang' => 'File',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 173,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    'list' => []
                                ],
                                [
                                    'page' => [
                                        'name' => '文件提交批次',
                                        'code' => 'commit',
                                        'lang' => 'File_Commit',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 148,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    'list' => []
                                ],
                                [
                                    'page' => [
                                        'name' => '相关任务',
                                        'code' => 'correlation_task',
                                        'lang' => 'Correlation_Task',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 174,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    'list' => []
                                ],
                                [
                                    'page' => [
                                        'name' => '水平关联表格',
                                        'code' => 'horizontal_relationship',
                                        'lang' => 'Horizontal_Relationship',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 175,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    'list' => []
                                ],
                                [
                                    'page' => [
                                        'name' => '云盘',
                                        'code' => 'cloud_disk',
                                        'lang' => 'Cloud_Disk',
                                        'page' => 'home_schedule_index',
                                        'param' => '',
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 467,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    "list" => []
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->savePageAuth($messageBoxRows);
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM strack_auth_group_node');
        $this->execute('DELETE FROM strack_auth_node');
        $this->execute('DELETE FROM strack_auth_group');
    }
}

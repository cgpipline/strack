<?php


use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class FillApiScheduleRules extends AbstractMigration
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
     * @throws Exception
     */
    public function up()
    {
        $authNodeRows = [
            [
                'name' => '日程单条查找',
                'code' => 'schedule_find',
                'lang' => 'Schedule_Find',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/find',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '日程多条查找',
                'code' => 'schedule_select',
                'lang' => 'Schedule_Select',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/select',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '日程修改',
                'code' => 'schedule_update',
                'lang' => 'Schedule_Update',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/update',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '日程创建',
                'code' => 'schedule_create',
                'lang' => 'Schedule_Create',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/create',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '日程删除',
                'code' => 'schedule_delete',
                'lang' => 'Schedule_Delete',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/delete',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '日程字段',
                'code' => 'schedule_fields',
                'lang' => 'Schedule_Fields',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/fields',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '获取我的日程',
                'code' => 'schedule_getmyscheduledata',
                'lang' => 'Schedule_GetMyScheduleData',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/getMyScheduleData',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '获取日历过滤配置',
                'code' => 'schedule_getcalendarfilterconfig',
                'lang' => 'Schedule_GetCalendarFilterConfig',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/getCalendarFilterConfig',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '添加任务计划',
                'code' => 'schedule_addtaskplan',
                'lang' => 'Schedule_AddTaskPlan',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/addTaskPlan',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '修改任务计划',
                'code' => 'schedule_modifytaskplan',
                'lang' => 'Schedule_ModifyTaskPlan',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/modifyTaskPlan',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '锁定任务计划',
                'code' => 'schedule_locktaskplan',
                'lang' => 'Schedule_LockTaskPlan',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/lockTaskPlan',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '删除任务计划',
                'code' => 'schedule_deletetaskplan',
                'lang' => 'Schedule_DeleteTaskPlan',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Schedule/deleteTaskPlan',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ]
        ];


        $this->table('strack_auth_node')->insert($authNodeRows)->save();



        /**
         * 日程单条查找
         */
        $scheduleFindRouteRows = [
            'group' => [
                'name' => '动作单条查找',
                'code' => 'schedule_find',
                'lang' => 'Schedule_Find',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [ // 日程单条查找
                    'auth_group_id' => 0,
                    'auth_node_id' => 770,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($scheduleFindRouteRows);


        /**
         * 日程多条查找
         */
        $scheduleSelectRouteRows = [
            'group' => [
                'name' => '日程多条查找',
                'code' => 'schedule_select',
                'lang' => 'Schedule_Select',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 771,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($scheduleSelectRouteRows);


        /**
         * 日程修改
         */
        $scheduleUpdateRouteRows = [
            'group' => [
                'name' => '日程修改',
                'code' => 'schedule_update',
                'lang' => 'Schedule_Update',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 772,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($scheduleUpdateRouteRows);

        /**
         * 日程创建
         */
        $scheduleCreateRouteRows = [
            'group' => [
                'name' => '日程创建',
                'code' => 'schedule_create',
                'lang' => 'Schedule_Create',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 773,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($scheduleCreateRouteRows);

        /**
         * 日程删除
         */
        $scheduleDeleteRouteRows = [
            'group' => [
                'name' => '日程删除',
                'code' => 'schedule_delete',
                'lang' => 'Schedule_Delete',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 774,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($scheduleDeleteRouteRows);


        /**
         * 日程字段
         */
        $scheduleFieldsRouteRows = [
            'group' => [
                'name' => '日程字段',
                'code' => 'schedule_fields',
                'lang' => 'Schedule_Fields',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 775,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($scheduleFieldsRouteRows);


        /**
         * 获取我的日程
         */
        $scheduleGetMyScheduleDataRouteRows = [
            'group' => [
                'name' => '获取我的日程',
                'code' => 'schedule_getmyscheduledata',
                'lang' => 'Schedule_GetMyScheduleData',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 776,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($scheduleGetMyScheduleDataRouteRows);


        /**
         * 获取日历过滤配置
         */
        $scheduleGetCalendarFilterConfigRouteRows = [
            'group' => [
                'name' => '获取日历过滤配置',
                'code' => 'schedule_getcalendarfilterconfig',
                'lang' => 'Schedule_GetCalendarFilterConfig',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 777,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($scheduleGetCalendarFilterConfigRouteRows);


        /**
         * 添加任务计划
         */
        $scheduleAddTaskPlanRouteRows = [
            'group' => [
                'name' => '添加任务计划',
                'code' => 'schedule_addtaskplan',
                'lang' => 'Schedule_AddTaskPlan',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 778,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($scheduleAddTaskPlanRouteRows);

        /**
         * 修改任务计划
         */
        $scheduleModifyTaskPlanRouteRows = [
            'group' => [
                'name' => '修改任务计划',
                'code' => 'schedule_modifytaskplan',
                'lang' => 'Schedule_ModifyTaskPlan',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 779,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($scheduleModifyTaskPlanRouteRows);


        /**
         * 锁定任务计划
         */
        $scheduleLockTaskPlanRouteRows = [
            'group' => [
                'name' => '锁定任务计划',
                'code' => 'schedule_locktaskplan',
                'lang' => 'Schedule_LockTaskPlan',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 780,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($scheduleLockTaskPlanRouteRows);



        /**
         * 删除任务计划
         */
        $ScheduleDeleteTaskPlanRouteRows = [
            'group' => [
                'name' => '删除任务计划',
                'code' => 'schedule_deletetaskplan',
                'lang' => 'Schedule_DeleteTaskPlan',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 781,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($ScheduleDeleteTaskPlanRouteRows);


        /**
         * 日程模块
         */
        $actionModuleRows = [
            'page' => [
                'name' => '日程模块',
                'code' => 'schedule',
                'lang' => 'Schedule',
                'page' => 'api_schedule',
                'menu' => 'api',
                'category' => 'API_Module',
                'param' => '',
                'type' => 'children',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'list' => [
                [
                    'page' => [
                        'name' => '动作单条查找',
                        'code' => 'schedule_find',
                        'lang' => 'Find',
                        'page' => 'api_schedule',
                        'param' => 'find',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 506,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '日程多条查找',
                        'code' => 'schedule_select',
                        'lang' => 'Select',
                        'page' => 'api_schedule',
                        'param' => 'select',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 507,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '日程修改',
                        'code' => 'schedule_update',
                        'lang' => 'Update',
                        'page' => 'api_schedule',
                        'param' => 'update',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 508,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '日程创建',
                        'code' => 'schedule_create',
                        'lang' => 'Create',
                        'page' => 'api_schedule',
                        'param' => 'create',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 509,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '日程删除',
                        'code' => 'schedule_delete',
                        'lang' => 'Delete',
                        'page' => 'api_schedule',
                        'param' => 'delete',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 510,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '日程字段',
                        'code' => 'schedule_fields',
                        'lang' => 'Get_Fields',
                        'page' => 'api_schedule',
                        'param' => 'fields',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 511,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '获取我的日程',
                        'code' => 'schedule_getmyscheduledata',
                        'lang' => 'Schedule_GetMyScheduleData',
                        'page' => 'api_schedule',
                        'param' => 'getmyscheduledata',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 512,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '获取日历过滤配置',
                        'code' => 'schedule_getcalendarfilterconfig',
                        'lang' => 'Schedule_GetCalendarFilterConfig',
                        'page' => 'api_schedule',
                        'param' => 'getcalendarfilterconfig',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 513,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '添加任务计划',
                        'code' => 'schedule_addtaskplan',
                        'lang' => 'Schedule_AddTaskPlan',
                        'page' => 'api_schedule',
                        'param' => 'addtaskplan',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 514,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '修改任务计划',
                        'code' => 'schedule_modifytaskplan',
                        'lang' => 'Schedule_ModifyTaskPlan',
                        'page' => 'api_schedule',
                        'param' => 'modifytaskplan',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 515,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '锁定任务计划',
                        'code' => 'schedule_locktaskplan',
                        'lang' => 'Schedule_LockTaskPlan',
                        'page' => 'api_schedule',
                        'param' => 'locktaskplan',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 516,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '删除任务计划',
                        'code' => 'schedule_deletetaskplan',
                        'lang' => 'Schedule_DeleteTaskPlan',
                        'page' => 'api_schedule',
                        'param' => 'deletetaskplan',
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [
                            'page_auth_id' => 0,
                            'auth_group_id' => 517,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
            ]
        ];

        $this->savePageAuth($actionModuleRows);
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

<?php


use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class FillApiGetrelationRules extends AbstractMigration
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
                'name' => '动作关联查询',
                'code' => 'action_get_relation',
                'lang' => 'Action_Get_Relation',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Action/getRelation',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '实体关联查询',
                'code' => 'entity_get_relation',
                'lang' => 'Entity_Get_Relation',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Entity/getRelation',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '文件关联查询',
                'code' => 'file_get_relation',
                'lang' => 'File_Get_Relation',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/File/getRelation',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '反馈关联查询',
                'code' => 'note_get_relation',
                'lang' => 'Note_Get_Relation',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Note/getRelation',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '现场数据关联查询',
                'code' => 'onset_get_relation',
                'lang' => 'Onset_Get_Relation',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Onset/getRelation',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '项目关联查询',
                'code' => 'project_get_relation',
                'lang' => 'Project_Get_Relation',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Project/getRelation',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '用户关联查询',
                'code' => 'user_get_relation',
                'lang' => 'User_Get_Relation',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/User/getRelation',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '提交文件关联查询',
                'code' => 'filecommit_get_relation',
                'lang' => 'FileCommit_Get_Relation',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/FileCommit/getRelation',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '任务关联查询',
                'code' => 'task_get_relation',
                'lang' => 'Task_Get_Relation',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Task/getRelation',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ]
        ];


        $this->table('strack_auth_node')->insert($authNodeRows)->save();


        /**
         * 动作关联查询
         */
        $actionGetRelationRouteRows = [
            'group' => [
                'name' => '动作关联查询',
                'code' => 'action_get_relation',
                'lang' => 'Action_Get_Relation',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [ // 动作关联查询路由
                    'auth_group_id' => 0,
                    'auth_node_id' => 761,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($actionGetRelationRouteRows);

        /**
         * 动作关联查询页面权限规则注册
         */
        $actionGetRelationPageRows = [
            'page' => [
                'name' => '动作关联查询',
                'code' => 'action_get_relation',
                'lang' => 'Get_Relation',
                'page' => 'api_action',
                'param' => 'getrelation',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 497,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($actionGetRelationPageRows, 689);


        /**
         * 实体关联查询
         */
        $entityGetRelationRouteRows = [
            'group' => [
                'name' => '实体关联查询',
                'code' => 'entity_get_relation',
                'lang' => 'Entity_Get_Relation',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [ // 实体关联查询路由
                    'auth_group_id' => 0,
                    'auth_node_id' => 762,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($entityGetRelationRouteRows);

        /**
         * 实体关联查询页面权限规则注册
         */
        $entityGetRelationPageRows = [
            'page' => [
                'name' => '实体关联查询',
                'code' => 'entity_get_relation',
                'lang' => 'Get_Relation',
                'page' => 'api_entity',
                'param' => 'getrelation',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 498,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($entityGetRelationPageRows, 733);

        /**
         * 文件关联查询
         */
        $fileGetRelationRouteRows = [
            'group' => [
                'name' => '文件关联查询',
                'code' => 'file_get_relation',
                'lang' => 'File_Get_Relation',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [ // 文件关联查询路由
                    'auth_group_id' => 0,
                    'auth_node_id' => 763,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($fileGetRelationRouteRows);

        /**
         * 文件关联查询页面权限规则注册
         */
        $fileGetRelationPageRows = [
            'page' => [
                'name' => '文件关联查询',
                'code' => 'file_get_relation',
                'lang' => 'Get_Relation',
                'page' => 'api_file',
                'param' => 'getrelation',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 499,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($fileGetRelationPageRows, 740);

        /**
         * 反馈关联查询
         */
        $noteGetRelationRouteRows = [
            'group' => [
                'name' => '反馈关联查询',
                'code' => 'note_get_relation',
                'lang' => 'Note_Get_Relation',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [ // 反馈单条关联查询
                    'auth_group_id' => 0,
                    'auth_node_id' => 764,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($noteGetRelationRouteRows);

        /**
         * 反馈查询页面权限规则注册
         */
        $noteGetRelationPageRows = [
            'page' => [
                'name' => '反馈关联查询',
                'code' => 'note_get_relation',
                'lang' => 'Get_Relation',
                'page' => 'api_note',
                'param' => 'getrelation',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 500,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($noteGetRelationPageRows, 791);

        /**
         * 现场数据关联查询
         */
        $onsetGetRelationRouteRows = [
            'group' => [
                'name' => '现场数据关联查询',
                'code' => 'onset_get_relation',
                'lang' => 'Onset_Get_Relation',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [ // 现场数据关联查询路由
                    'auth_group_id' => 0,
                    'auth_node_id' => 765,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($onsetGetRelationRouteRows);

        /**
         * 现场数据查询页面权限规则注册
         */
        $onsetGetRelationPageRows = [
            'page' => [
                'name' => '现场数据关联查询',
                'code' => 'onset_get_relation',
                'lang' => 'Get_Relation',
                'page' => 'api_onset',
                'param' => 'getrelation',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 501,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($onsetGetRelationPageRows, 798);


        /**
         * 项目关联查询
         */
        $projectGetRelationRouteRows = [
            'group' => [
                'name' => '项目关联查询',
                'code' => 'project_get_relation',
                'lang' => 'Project_Get_Relation',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [ // 项目关联查询路由
                    'auth_group_id' => 0,
                    'auth_node_id' => 766,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($projectGetRelationRouteRows);

        /**
         * 项目查询页面权限规则注册
         */
        $projectGetRelationPageRows = [
            'page' => [
                'name' => '项目关联查询',
                'code' => 'project_get_relation',
                'lang' => 'Get_Relation',
                'page' => 'api_project',
                'param' => 'getrelation',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 502,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($projectGetRelationPageRows, 819);

        /**
         * 用户关联查询
         */
        $userGetRelationRouteRows = [
            'group' => [
                'name' => '用户关联查询',
                'code' => 'user_get_relation',
                'lang' => 'User_Get_Relation',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [ // 用户关联查询路由
                    'auth_group_id' => 0,
                    'auth_node_id' => 767,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($userGetRelationRouteRows);

        /**
         * 用户关联查询页面权限规则注册
         */
        $userGetRelationPageRows = [
            'page' => [
                'name' => '用户关联查询',
                'code' => 'user_get_relation',
                'lang' => 'Get_Relation',
                'page' => 'api_user',
                'param' => 'getrelation',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 503,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($userGetRelationPageRows, 891);

        /**
         * 提交文件关联查询
         */
        $fileCommitGetRelationRouteRows = [
            'group' => [
                'name' => '提交文件关联查询',
                'code' => 'file_commit_get_relation',
                'lang' => 'file_Commit_Get_Relation',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [ // 提交文件关联查询路由
                    'auth_group_id' => 0,
                    'auth_node_id' => 768,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($fileCommitGetRelationRouteRows);

        /**
         * 提交文件关联查询页面权限规则注册
         */
        $fileCommitGetRelationPageRows = [
            'page' => [
                'name' => '提交文件关联查询',
                'code' => 'file_commit_get_relation',
                'lang' => 'Get_Relation',
                'page' => 'api_filecommit',
                'param' => 'getrelation',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 504,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($fileCommitGetRelationPageRows, 899);


        /**
         * 任务关联查询
         */
        $taskGetRelationRouteRows = [
            'group' => [
                'name' => '任务关联查询',
                'code' => 'task_get_relation',
                'lang' => 'Task_Get_Relation',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [ // 任务关联查询路由
                    'auth_group_id' => 0,
                    'auth_node_id' => 769,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($taskGetRelationRouteRows);


        /**
         * 任务关联查询页面权限规则注册
         */
        $taskGetRelationPageRows = [
            'page' => [
                'name' => '任务关联查询',
                'code' => 'task_get_relation',
                'lang' => 'Get_Relation',
                'page' => 'api_task',
                'param' => 'getrelation',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 505,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($taskGetRelationPageRows, 913);
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

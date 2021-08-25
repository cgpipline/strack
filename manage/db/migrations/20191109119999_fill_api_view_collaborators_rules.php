<?php


use Phinx\Migration\AbstractMigration;

class FillApiViewCollaboratorsRules extends AbstractMigration
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
        $getTaskStatusListNodeRows = [
            [
                'name' => '获取看板可用视图',
                'code' => 'get_kanban_view_list',
                'lang' => 'Get_Kanban_View_List',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/View/getKanbanViewList',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '获取看板分组数据配置',
                'code' => 'get_grid_collaborators',
                'lang' => 'Get_Grid_Collaborators',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/View/getGridCollaborators',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ]
        ];


        $this->table('strack_auth_node')->insert($getTaskStatusListNodeRows)->save();


        /**
         * 获取看板可用视图
         */
        $getTaskStatusListGroupRows = [
            'group' => [
                'name' => '获取看板可用视图',
                'code' => 'get_kanban_view_list',
                'lang' => 'Get_Kanban_View_List',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 790,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($getTaskStatusListGroupRows);


        /**
         * 获取看板分组数据配置
         */
        $getTaskStatusListGroupRows = [
            'group' => [
                'name' => '获取看板分组数据配置',
                'code' => 'get_grid_collaborators',
                'lang' => 'Get_Grid_Collaborators',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 791,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($getTaskStatusListGroupRows);


        /**
         * 获取看板可用视图页面权限规则注册
         */
        $getTaskStatusListPageAuthRows = [
            'page' => [
                'name' => '获取看板可用视图',
                'code' => 'get_kanban_view_list',
                'lang' => 'Get_Kanban_View_List',
                'page' => 'api_view',
                'param' => 'getkanbanviewlist',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 523,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($getTaskStatusListPageAuthRows, 944);


        /**
         * 获取看板分组数据配置页面权限规则注册
         */
        $getTaskStatusListPageAuthRows = [
            'page' => [
                'name' => '获取看板分组数据配置',
                'code' => 'get_grid_collaborators',
                'lang' => 'Get_Grid_Collaborators',
                'page' => 'api_view',
                'param' => 'getgridcollaborators',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 524,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($getTaskStatusListPageAuthRows, 944);
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

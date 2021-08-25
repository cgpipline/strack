<?php


use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class FillApiGetModuleRelationDataRules extends AbstractMigration
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
        $getRelationModuleDataNodeRows = [
            [
                'name' => '获取指定模块关联模块数据',
                'code' => 'get_relation_module_data',
                'lang' => 'Get_Relation_Module_Data',
                'type' => 'route',
                'module' => 'api',
                'project_id' => '0',
                'module_id' => '0',
                'rules' => 'api/Module/getRelationModuleData',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ]
        ];


        $this->table('strack_auth_node')->insert($getRelationModuleDataNodeRows)->save();


        /**
         * 获取指定module关联模块配置数据关联模块数据
         */
        $getRelationModuleDataGroupRows = [
            'group' => [
                'name' => '获取指定模块关联模块数据',
                'code' => 'get_relation_module_data',
                'lang' => 'Get_Relation_Module_Data',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [
                    'auth_group_id' => 0,
                    'auth_node_id' => 797,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($getRelationModuleDataGroupRows);


        /**
         * 获取指定module关联模块配置数据页面权限规则注册
         */
        $getRelationModuleDataPageAuthRows = [
            'page' => [
                'name' => '获取指定模块关联模块数据',
                'code' => 'get_relation_module_data',
                'lang' => 'Get_Relation_Module_Data',
                'page' => 'api_module',
                'param' => 'getrelationmoduledata',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 529,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($getRelationModuleDataPageAuthRows, 946);
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

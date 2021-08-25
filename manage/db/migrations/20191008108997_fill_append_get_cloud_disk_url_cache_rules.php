<?php


use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class FillAppendGetCloudDiskUrlCacheRules extends AbstractMigration
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
        $authNode = [
            'name' => '获取网盘url缓存',
            'code' => 'get_cloud_disk_url_cache',
            'lang' => 'Get_Cloud_Disk_Url_Cache',
            'type' => 'route',
            'module' => 'api',
            'project_id' => 0,
            'module_id' => 0,
            'rules' => 'api/Project/getCloudDiskUrlCache',
            'uuid' => Webpatser\Uuid\Uuid::generate()->string
        ];

        $this->table('strack_auth_node')->insert($authNode)->save();

        /**
         * 视图单条查找
         */
        $viewFindRouteRows = [
            'group' => [
                'name' => '获取网盘url缓存',
                'code' => 'get_cloud_disk_url_cache',
                'lang' => 'Get_Cloud_Disk_Url_Cache',
                'type' => 'url',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'rules' => [
                [ // 视图单条查找路由
                    'auth_group_id' => 0,
                    'auth_node_id' => 752,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->saveAuthGroup($viewFindRouteRows);

        /**
         * 视图多条查找
         */
        $adminSignMethodPageRows = [
            'page' => [
                'name' => '获取网盘url缓存',
                'code' => 'get_cloud_disk_url_cache',
                'lang' => 'Get_Cloud_Disk_Url_Cache',
                'page' => 'api_project',
                'param' => 'getclouddiskurlcache',
                'type' => 'belong',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [
                    'page_auth_id' => 0,
                    'auth_group_id' => 492,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ]
        ];

        $this->savePageAuth($adminSignMethodPageRows, 819);
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

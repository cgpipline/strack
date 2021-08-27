<?php


use Phinx\Migration\AbstractMigration;

class FillDefaultRole extends AbstractMigration
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
    public function executeRawSql($sqlName)
    {
        $path = dirname(dirname(__FILE__))."/sql/{$sqlName}.sql";
        $sql = file_get_contents($path);

        $this->execute($sql);
    }


    /**
     * Migrate Up.
     */
    public function up()
    {

        $rows = [
            [
                "name" => "管理员",
                "code" => "admin",
                "type" => "system",
                "created_by" => 0,
                "created" => time(),
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "导演",
                "code" => "director",
                "type" => "system",
                "created_by" => 0,
                "created" => time(),
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "制片人",
                "code" => "producer",
                "type" => "system",
                "created_by" => 0,
                "created" => time(),
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "组长",
                "code" => "group_leader",
                "type" => "system",
                "created_by" => 0,
                "created" => time(),
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "组员",
                "code" => "member",
                "type" => "system",
                "created_by" => 0,
                "created" => time(),
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
        ];

        $this->table('strack_role')->insert($rows)->save();

        // 并执行sql文件
        $this->executeRawSql("strack_auth_access_data");
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM strack_role');
        $this->execute('DELETE FROM strack_auth_access');
    }
}

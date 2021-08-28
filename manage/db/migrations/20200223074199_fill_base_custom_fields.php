<?php


use Phinx\Migration\AbstractMigration;

class FillBaseCustomFields extends AbstractMigration
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
     * @throws Exception
     */
    public function up()
    {
        $rows = [
            [
                'name' => '分派人',
                'code' => 'assignor',
                'type' => 'horizontal_relationship',
                'action_scope' => 'current',
                'module_id' => 4,
                'project_id' => 0,
                'created_by' => 1,
                'created' => time(),
                'lock' => 'yes',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                'config' => json_encode([
                    "code" => "assignor",
                    "name" => "分派人",
                    "type" => "horizontal_relationship",
                    "editor" => "tagbox",
                    "module_id" => 4,
                    "project_id" => 0,
                    "member_type" => "assignor",
                    "action_scope" => "current",
                    "relation_type" => "has_one",
                    "relation_module_id" => 34,
                    "horizontal_config_id" => 2
                ])
            ],
            [
                'name' => '执行人',
                'code' => 'executor',
                'type' => 'horizontal_relationship',
                'action_scope' => 'current',
                'module_id' => 4,
                'project_id' => 0,
                'created_by' => 1,
                'created' => time(),
                'lock' => 'yes',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                'config' => json_encode([
                    "code" => "executor",
                    "name" => "执行人",
                    "type" => "horizontal_relationship",
                    "editor" => "tagbox",
                    "module_id" => 4,
                    "project_id" => 0,
                    "member_type" => "executor",
                    "action_scope" => "current",
                    "relation_type" => "has_one",
                    "relation_module_id" => 34,
                    "horizontal_config_id" => 2
                ])
            ],
            [
                'name' => '子任务',
                'code' => 'subtask',
                'type' => 'horizontal_relationship',
                'action_scope' => 'current',
                'module_id' => 4,
                'project_id' => 0,
                'created_by' => 1,
                'created' => time(),
                'lock' => 'yes',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                'config' => json_encode([
                    "code" => "subtask",
                    "name" => "子任务",
                    "type" => "horizontal_relationship",
                    "editor" => "tagbox",
                    "module_id" => 4,
                    "project_id" => 0,
                    "action_scope" => "current",
                    "relation_type" => "has_many",
                    "relation_module_id" => 4,
                    "horizontal_config_id" => 1
                ])
            ]
        ];

        $this->table('strack_variable')->insert($rows)->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM strack_variable');
    }
}

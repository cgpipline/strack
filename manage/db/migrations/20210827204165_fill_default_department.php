<?php


use Phinx\Migration\AbstractMigration;

class FillDefaultDepartment extends AbstractMigration
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
     * Migrate Up.
     */
    public function up()
    {

        $rows = [
            [
                "name" => "项目部",
                "code" => "project",
                "parent_id" => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "编剧",
                "code" => "screenwriter",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "分镜",
                "code" => "storyboard",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "概念设计",
                "code" => "design",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "模型",
                "code" => "model",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "材质",
                "code" => "texture",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "绑定",
                "code" => "rig",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "Layout",
                "code" => "layout",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "动画",
                "code" => "animation",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "关卡",
                "code" => "level",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "灯光",
                "code" => "lighting",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "特效",
                "code" => "efx",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "渲染",
                "code" => "render",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "合成",
                "code" => "compose",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "导演",
                "code" => "director",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                "name" => "制片",
                "code" => "produce",
                "parent_id" => 1,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
        ];

        $this->table('strack_department')->insert($rows)->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM strack_project_template');
    }
}

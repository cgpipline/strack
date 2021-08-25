<?php


use Phinx\Migration\AbstractMigration;

class FillDataRangeHorizontalPageAuthData extends AbstractMigration
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
                'name' => '显示，跟我相关的',
                'code' => 'view_related_to_me',
                'lang' => 'View_Related_To_Me',
                'page' => 'task_related_user',
                'param' => '',
                'category' => 'data_range',
                'type' => 'none',
                'menu' => '',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                'name' => '显示、编辑，跟我相关的',
                'code' => 'view_edit_related_to_me',
                'lang' => 'View_Edit_Related_To_Me',
                'page' => 'task_related_user',
                'param' => '',
                'category' => 'data_range',
                'type' => 'none',
                'menu' => '',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            [
                'name' => '显示、编辑、删除，跟我相关的',
                'code' => 'view_edit_delete_related_to_me',
                'lang' => 'View_Edit_Delete_Related_To_Me',
                'page' => 'task_related_user',
                'param' => '',
                'category' => 'data_range',
                'type' => 'none',
                'menu' => '',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ]
        ];

        $this->table('strack_page_auth')->insert($rows)->save();

    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM strack_page_auth');
    }
}

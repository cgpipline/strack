<?php


use Phinx\Migration\AbstractMigration;

class CreateMessageMemberTable extends AbstractMigration
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
    public function change()
    {
        $table = $this->table('strack_message_member', ['id' => false, 'primary_key' => ['id'], 'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']);

        //添加数据字段
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'limit' => 11, 'comment' => '消息成员ID'])
            ->addColumn('status', 'enum', ['values' => 'read,unread', 'default' => 'unread', 'comment' => '已读状态'])
            ->addColumn('message_id', 'integer', ['signed' => false, 'default' => 0, 'limit' => 11, 'comment' => '关联消息ID'])
            ->addColumn('user_id', 'integer', ['signed' => false, 'default' => 0, 'limit' => 11, 'comment' => '关联用户ID'])
            ->addColumn('name', 'string', ['default' => '', 'limit' => 255, 'comment' => '成员名称'])
            ->addColumn('email', 'string', ['default' => '', 'limit' => 128, 'comment' => '成员邮箱地址'])
            ->addColumn('belong_type', 'char', ['default' => '', 'limit' => 36, 'comment' => '类型'])
            ->addColumn('created_by', 'integer', ['signed' => false, 'default' => 0, 'limit' => 11, 'comment' => '创建者'])
            ->addColumn('created', 'integer', ['signed' => false, 'default' => 0, 'limit' => 11, 'comment' => '创建时间'])
            ->addColumn('json', 'json', ['null' => true, 'comment' => '更多信息'])
            ->addColumn('uuid', 'char', ['default' => '', 'limit' => 36, 'comment' => '全局唯一标识符']);

        //执行创建
        $table->create();
    }
}

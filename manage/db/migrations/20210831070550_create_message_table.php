<?php


use Phinx\Migration\AbstractMigration;

class CreateMessageTable extends AbstractMigration
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
        $table = $this->table('strack_message', ['id' => false, 'primary_key' => ['id'], 'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci']);

        //添加数据字段
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'limit' => 11, 'comment' => '消息ID'])
            ->addColumn('operate', 'string', ['default' => '', 'limit' => 128, 'comment' => '操作类型'])
            ->addColumn('type', 'string', ['default' => 'system,at', 'limit' => 128, 'comment' => '消息类型'])
            ->addColumn('module_id', 'integer', ['signed' => false, 'default' => 0, 'limit' => 11, 'comment' => '模块ID'])
            ->addColumn('project_id', 'integer', ['signed' => false, 'default' => 0, 'limit' => 11, 'comment' => '项目ID'])
            ->addColumn('primary_id', 'integer', ['signed' => false, 'default' => 0, 'limit' => 11, 'comment' => '主键ID'])
            ->addColumn('content', 'json', ['null' => true, 'comment' => '消息内容'])
            ->addColumn('emergent', 'enum', ['values' => 'normal,emergent', 'default' => 'normal', 'comment' => '紧急程度'])
            ->addColumn('sender', 'json', ['null' => true, 'comment' => '发送者成员'])
            ->addColumn('email_template', 'string', ['default' => '', 'limit' => 128, 'comment' => '使用邮件模板名称'])
            ->addColumn('from', 'string', ['default' => '', 'limit' => 64, 'comment' => '来自哪里'])
            ->addColumn('identity_id', 'string', ['default' => '', 'limit' => 64, 'comment' => '页面唯一标识符'])
            ->addColumn('created_by', 'integer', ['signed' => false, 'default' => 0, 'limit' => 11, 'comment' => '创建者'])
            ->addColumn('created', 'integer', ['signed' => false, 'default' => 0, 'limit' => 11, 'comment' => '创建时间'])
            ->addColumn('uuid', 'char', ['default' => '', 'limit' => 36, 'comment' => '全局唯一标识符']);

        //执行创建
        $table->create();
    }
}

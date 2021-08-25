<?php


use Phinx\Migration\AbstractMigration;

class ChangeViewTable extends AbstractMigration
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
        $this->table('strack_view')
            ->addColumn('type', 'enum', ['values' => 'grid,kanban', 'default' => 'grid', 'comment' => '视图类型'])
            ->save();

        $this->table('strack_view_default')
            ->addColumn('type', 'enum', ['values' => 'grid,kanban', 'default' => 'grid', 'comment' => '视图类型'])
            ->save();
    }
}

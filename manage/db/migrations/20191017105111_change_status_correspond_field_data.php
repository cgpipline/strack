<?php


use Phinx\Migration\AbstractMigration;

class ChangeStatusCorrespondFieldData extends AbstractMigration
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

    public function up()
    {
        $statusTable = $this->table('strack_status');
        $statusTable->changeColumn('correspond', 'enum', ['values' => 'un_evaluated,blocked,not_started,in_progress,daily,done,hide', 'default' => 'blocked', 'comment' => '状态从属关系']);
        $statusTable->save();
    }

    public function down()
    {
        $this->execute('DELETE FROM strack_status');
    }
}

<?php


use Phinx\Migration\AbstractMigration;

class ChangeDurationFieldData extends AbstractMigration
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
        $baseTable = $this->table('strack_base');
        $baseTable->changeColumn('duration', 'char', ['default' => '', 'limit' => 32,  'comment' => '制作时长']);
        $baseTable->changeColumn('plan_duration', 'char', ['default' => '', 'limit' => 32, 'comment' => '计划制作时长']);
        $baseTable->save();

        $entityTable = $this->table('strack_entity');
        $entityTable->changeColumn('duration', 'char', ['default' => '', 'limit' => 32,  'comment' => '制作时长']);
        $entityTable->save();
    }

    public function down()
    {
        $this->execute('DELETE FROM strack_base');
    }
}

<?php


use Phinx\Migration\AbstractMigration;

class ChangeAuthAccessFieldData extends AbstractMigration
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
        $authAccessTable = $this->table('strack_auth_access');
        $authAccessTable->changeColumn('type', 'enum', ['values' => 'page,field,data_range', 'default' => 'page', 'comment' => '权限类型']);
        $authAccessTable->save();
    }

    public function down()
    {
        $this->execute('DELETE FROM strack_auth_access');
    }
}

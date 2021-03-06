<?php


use Phinx\Migration\AbstractMigration;

class ChangeVariableTypeLimit extends AbstractMigration
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
        $baseTable = $this->table('strack_variable');
        $baseTable->changeColumn('type', 'enum', ['values' => 'text,checkbox,textarea,combobox,datebox,datetimebox,belong_to,horizontal_relationship,expression,timespinner', 'default' => 'text', 'comment' => ' 注册自定义字段类型']);

        $baseTable->save();
    }

    public function down()
    {
        $this->execute('DELETE FROM strack_variable');
    }
}

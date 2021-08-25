<?php


use Phinx\Migration\AbstractMigration;

class ChangeUserConfigTypeFieldData extends AbstractMigration
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
        $table = $this->table('strack_user_config');

        $table
            ->changeColumn('type', 'enum', ['values' => 'system,reminder,filter_stick,add_panel,update_panel,top_field,main_field,fields_show_mode', 'default' => 'system', 'comment' => '用户配置类型'])
            ->save();
    }
}

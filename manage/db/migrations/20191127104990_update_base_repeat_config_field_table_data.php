<?php


use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class UpdateBaseRepeatConfigFieldTableData extends AbstractMigration
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
        $config = "[{\"id\": \"id\", \"edit\": \"deny\", \"lang\": \"Id\", \"mask\": \"\", \"show\": \"no\", \"sort\": \"deny\", \"type\": \"int\", \"group\": \"\", \"table\": \"BaseRepeatConfig\", \"editor\": \"none\", \"fields\": \"id\", \"filter\": \"deny\", \"module\": \"base_repeat_config\", \"multiple\": \"no\", \"validate\": \"\", \"field_type\": \"built_in\", \"value_show\": \"id\", \"allow_group\": \"deny\"}, {\"id\": \"mode\", \"edit\": \"deny\", \"lang\": \"Mode\", \"mask\": \"\", \"show\": \"no\", \"sort\": \"deny\", \"type\": \"enum\", \"group\": \"\", \"table\": \"BaseRepeatConfig\", \"editor\": \"none\", \"fields\": \"mode\", \"filter\": \"deny\", \"module\": \"base_repeat_config\", \"multiple\": \"no\", \"validate\": \"\", \"field_type\": \"built_in\", \"value_show\": \"mode\", \"allow_group\": \"deny\"}, {\"id\": \"base_id\", \"edit\": \"deny\", \"lang\": \"Base_Id\", \"mask\": \"\", \"show\": \"no\", \"sort\": \"deny\", \"type\": \"int\", \"group\": \"\", \"table\": \"BaseRepeatConfig\", \"editor\": \"none\", \"fields\": \"base_id\", \"filter\": \"deny\", \"module\": \"base_repeat_config\", \"multiple\": \"no\", \"validate\": \"\", \"_selected\": true, \"field_type\": \"built_in\", \"value_show\": \"base_id\", \"allow_group\": \"deny\"}, {\"id\": \"config\", \"edit\": \"deny\", \"lang\": \"Config\", \"mask\": \"\", \"show\": \"no\", \"sort\": \"deny\", \"type\": \"json\", \"group\": \"\", \"table\": \"BaseRepeatConfig\", \"editor\": \"none\", \"fields\": \"config\", \"filter\": \"deny\", \"format\": \"json\", \"module\": \"base_repeat_config\", \"multiple\": \"no\", \"validate\": \"\", \"_selected\": true, \"field_type\": \"built_in\", \"value_show\": \"config\", \"allow_group\": \"deny\"}, {\"id\": \"uuid\", \"edit\": \"deny\", \"lang\": \"Uuid\", \"mask\": \"\", \"show\": \"no\", \"sort\": \"deny\", \"type\": \"char\", \"group\": \"\", \"table\": \"BaseRepeatConfig\", \"editor\": \"none\", \"fields\": \"uuid\", \"filter\": \"deny\", \"module\": \"base_repeat_config\", \"multiple\": \"no\", \"validate\": \"\", \"_selected\": true, \"field_type\": \"built_in\", \"value_show\": \"uuid\", \"allow_group\": \"deny\"}]";
        $this->execute("UPDATE `strack_field` SET `config`='{$config}' WHERE `table`= 'base_repeat_config'");
    }

    public function down()
    {
        $this->execute('DELETE FROM strack_field');
    }
}

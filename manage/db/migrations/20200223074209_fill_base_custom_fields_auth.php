<?php


use Phinx\Migration\AbstractMigration;

class FillBaseCustomFieldsAuth extends AbstractMigration
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
     * @throws Exception
     */
    public function up()
    {
        // 查询所有任务自定义字段
        $sql = 'select * from strack_variable where `module_id`=4';
        $customFieldData = $this->query($sql)->fetchAll();

        $rows = [];
        $baseFieldMap = [];
        foreach ($customFieldData as $customField) {
            $rows[] = [
                'name' => $customField['code'],
                'lang' => $customField['name'],
                'type' => 'custom',
                'variable_id' => $customField['id'],
                'project_id' => $customField['project_id'],
                'module_id' => $customField['module_id'],
                'module_code' => 'base',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ];

            $baseFieldMap[$customField['code']] = $customField['id'];
        }

        $this->table('strack_auth_field')->insert($rows)->save();

        // 插入字段配置
        $fieldSetting = [
            'name' => 'field_settings',
            'type' => 'system',
            'config' => json_encode([
                "view" => [
                    "grouping_of_stage" => $baseFieldMap['taskstage'],
                    "grouping_of_persons" => $baseFieldMap['executor']
                ],
                "formula" => [
                    "fields" => [
                        "sub_task" => $baseFieldMap['subtask'],
                        "reviewed_by" => $baseFieldMap['assignor'],
                        "assignee_field" => $baseFieldMap['executor'],
                        "grouping_of_stage" => $baseFieldMap['taskstage'],
                        "no_start_status" => 1,
                        "in_progress_status" => 7,
                        "end_by_status" => 15,
                        "reviewed_by_status" => 9,
                        "actual_time_consuming" => $baseFieldMap['actualtime'],
                        "examine_working_hours" => $baseFieldMap['examinetime'],
                        "estimate_working_hours" => $baseFieldMap['estimatetime'],
                        "settlement_time_consuming" => $baseFieldMap['settletime']
                    ],
                    "formula_data" => [

                    ]
                ]
            ]),
            'uuid' => Webpatser\Uuid\Uuid::generate()->string
        ];

        $this->table('strack_options')->insert($fieldSetting)->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM strack_auth_field');
        $this->execute('DELETE FROM strack_options');
    }
}

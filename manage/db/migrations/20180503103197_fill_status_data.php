<?php


use Phinx\Migration\AbstractMigration;

class FillStatusData extends AbstractMigration
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
     * Migrate Up.
     */
    public function up()
    {
        $rows = [
            [
                'name' => '未开始', //未开始
                'code' => 'not_started',
                'color' => 'cccccc',
                'icon' => 'icon-uniEA7E',
                'correspond' => 'not_started',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '已就绪', //准备开始
                'code' => 'ready_to_start',
                'color' => 'e7c025',
                'icon' => 'icon-uniEA7E',
                'correspond' => 'in_progress',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '进行中', //进行中
                'code' => 'ip',
                'color' => '4e74f2',
                'icon' => 'icon-uniE6B9',
                'correspond' => 'in_progress',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '暂停', //暂停
                'code' => 'on_hold',
                'color' => '6310e8',
                'icon' => 'icon-uniEA3F',
                'correspond' => 'blocked',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '外包', //外包
                'code' => 'outsource',
                'color' => 'ff2ef8',
                'icon' => 'icon-uniF045',
                'correspond' => 'in_progress',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '提交', //提交
                'code' => 'submitted',
                'color' => 'e7c025',
                'icon' => 'icon-uniEA39',
                'correspond' => 'in_progress',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '审核中', //评审
                'code' => 'pending_review',
                'color' => 'fabb1b',
                'icon' => 'icon-uniE96C',
                'correspond' => 'daily',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '客户审核', //客户审核
                'code' => 'client_review',
                'color' => '99e00b',
                'icon' => 'icon-uniF0C0',
                'correspond' => 'daily',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '反馈', //反馈
                'code' => 'feedback',
                'color' => 'f00707',
                'icon' => 'icon-uniF04A',
                'correspond' => 'in_progress',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '返修', //返修
                'code' => 'revision',
                'color' => '358500',
                'icon' => 'icon-uniF1B8',
                'correspond' => 'in_progress',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '通过', //批准
                'code' => 'approved',
                'color' => '05eb1c',
                'icon' => 'icon-uniE69A',
                'correspond' => 'done',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '导演通过', //导演通过
                'code' => 'director_approved',
                'color' => '44bd15',
                'icon' => 'icon-uniE9F5',
                'correspond' => 'done',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '交付', //交付
                'code' => 'delivered',
                'color' => '999999',
                'icon' => 'icon-uniE6BF',
                'correspond' => 'done',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '完成', // 完成
                'code' => 'complete',
                'color' => '00b321',
                'icon' => 'icon-uniEA02',
                'correspond' => 'done',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            [
                'name' => '删除', //删除
                'code' => 'omitted',
                'color' => '575757',
                'icon' => 'icon-uniF00D',
                'correspond' => 'done',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ]
        ];

        $this->table('strack_status')->insert($rows)->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM strack_status');
    }
}

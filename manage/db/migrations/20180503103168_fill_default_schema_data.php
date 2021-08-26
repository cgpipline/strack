<?php


use Phinx\Migration\AbstractMigration;

class FillDefaultSchemaData extends AbstractMigration
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
     * 获取所有模块数据
     * @return array
     */
    public function getAllModuleData()
    {
        $sql = 'select * from strack_module';
        $result = $this->query($sql)->fetchAll();
        $info = array();
        foreach ($result as $key => $val) {
            $info[$val['code']] = $val;
        }
        return $info;
    }

    /**
     * Migrate Up.
     */
    public function up()
    {
        $moduleMap = $this->getAllModuleData();

        // 关联模型配置
        $schemaConfig = [
            // 项目模型
            [
                'schema' => [
                    'name' => '项目模型',
                    'code' => 'project',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联磁盘
                        'src_module_code' => 'project',
                        'dst_module_code' => 'project_disk',
                        'type' => 'has_one',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'project',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联项目成员
                        'src_module_code' => 'project',
                        'dst_module_code' => 'project_member',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联项目模板
                        'src_module_code' => 'project',
                        'dst_module_code' => 'project_template',
                        'type' => 'has_one',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'project',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                ]
            ],
            // 项目成员模型
            [
                'schema' => [
                    'name' => '项目成员模型',
                    'code' => 'project_member',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联用户
                        'src_module_code' => 'project_member',
                        'dst_module_code' => 'user',
                        'type' => 'belong_to',
                        'link_id' => 'user_id'
                    ],
                    [ // 关联项目
                        'src_module_code' => 'project_member',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联角色
                        'src_module_code' => 'project_member',
                        'dst_module_code' => 'role',
                        'type' => 'belong_to',
                        'link_id' => 'role_id'
                    ]
                ]
            ],
            // 任务模型
            [
                'schema' => [
                    'name' => '任务模型',
                    'code' => 'base',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联工序
                        'src_module_code' => 'base',
                        'dst_module_code' => 'step',
                        'type' => 'belong_to',
                        'link_id' => 'step_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'base',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联项目
                        'src_module_code' => 'base',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'base',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'base_id'
                    ],
                    [ // 关联实体
                        'src_module_code' => 'base',
                        'dst_module_code' => 'entity',
                        'type' => 'belong_to',
                        'link_id' => 'entity_id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'base',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联实体模块
                        'src_module_code' => 'base',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'entity_module_id'
                    ]
                ]
            ],
            // 文件模型
            [
                'schema' => [
                    'name' => '文件模型',
                    'code' => 'file',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联文件类型
                        'src_module_code' => 'file',
                        'dst_module_code' => 'file_type',
                        'type' => 'belong_to',
                        'link_id' => 'file_type_id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'file',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'file_id'
                    ],
                    [ // 关联项目
                        'src_module_code' => 'file',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联文件提交
                        'src_module_code' => 'file',
                        'dst_module_code' => 'file_commit',
                        'type' => 'belong_to',
                        'link_id' => 'file_commit_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'file',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'file',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'file',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ]
                ]
            ],
            // 文件提交关联模型
            [
                'schema' => [
                    'name' => '文件提交关联模型',
                    'code' => 'file_commit',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'file_commit',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'file_commit',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'file_commit_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'file_commit',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'file_commit',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'file_commit',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ]
                ]
            ],
            // 回复模型
            [
                'schema' => [
                    'name' => '回复模型',
                    'code' => 'note',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联状态
                        'src_module_code' => 'note',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联项目
                        'src_module_code' => 'note',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联文件提交
                        'src_module_code' => 'note',
                        'dst_module_code' => 'file_commit',
                        'type' => 'belong_to',
                        'link_id' => 'file_commit_id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'note',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联模型
                        'src_module_code' => 'note',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ]
                ]
            ],
            // 现场数据模型
            [
                'schema' => [
                    'name' => '现场数据模型',
                    'code' => 'onset',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联媒体
                        'src_module_code' => 'onset',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'onset_id'
                    ],
                    [ // 关联项目
                        'src_module_code' => 'onset',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'onset',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'onset',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ]
                ]
            ],
            // 时间日志模型
            [
                'schema' => [
                    'name' => '时间日志模型',
                    'code' => 'timelog',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联用户
                        'src_module_code' => 'timelog',
                        'dst_module_code' => 'user',
                        'type' => 'belong_to',
                        'link_id' => 'user_id'
                    ],
                    [ // 关联项目
                        'src_module_code' => 'timelog',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'timelog',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'timelog',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ]
                ]
            ],
            // 文件类型模型
            [
                'schema' => [
                    'name' => '文件类型模型',
                    'code' => 'file_type',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'file_type',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联工序
                        'src_module_code' => 'file_type',
                        'dst_module_code' => 'step',
                        'type' => 'belong_to',
                        'link_id' => 'step_id'
                    ]
                ]
            ],
            // 用户模型
            [
                'schema' => [
                    'name' => '用户模型',
                    'code' => 'user',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联媒体
                        'src_module_code' => 'user',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'user_id'
                    ],
                    [ // 关联部门
                        'src_module_code' => 'user',
                        'dst_module_code' => 'department',
                        'type' => 'belong_to',
                        'link_id' => 'department_id'
                    ],
                    [ // 关联用户角色
                        'src_module_code' => 'user',
                        'dst_module_code' => 'role_user',
                        'type' => 'has_one',
                        'link_id' => 'user_id'
                    ]
                ]
            ],
            // 标签关联模型
            [
                'schema' => [
                    'name' => '标签关联模型',
                    'code' => 'tag_link',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联标签
                        'src_module_code' => 'tag_link',
                        'dst_module_code' => 'tag',
                        'type' => 'belong_to',
                        'link_id' => 'tag_id'
                    ]
                ]
            ],
            // 动作模型
            [
                'schema' => [
                    'name' => '动作模型',
                    'code' => 'action',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联常用动作
                        'src_module_code' => 'action',
                        'dst_module_code' => 'common_action',
                        'type' => 'has_one',
                        'link_id' => 'action_id'
                    ],
                    [ // 关联项目
                        'src_module_code' => 'action',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'action',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'action_id'
                    ]
                ]
            ],
            // 审核模型
            [
                'schema' => [
                    'name' => '审核模型',
                    'code' => 'review',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'review',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'review',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'review',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ],
                    [ // 关联任务
                        'src_module_code' => 'review',
                        'dst_module_code' => 'base',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'review',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'review',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'entity_id'
                    ]
                ]
            ],
            // 集数模型
            [
                'schema' => [
                    'name' => '集数模型',
                    'code' => 'episode',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'episode',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'episode',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'episode',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ],
                    [ // 关联任务
                        'src_module_code' => 'episode',
                        'dst_module_code' => 'base',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'episode',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'episode',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'entity_id'
                    ],
                    [ // 关联序列
                        'src_module_code' => 'episode',
                        'dst_module_code' => 'sequence',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ]
                ]
            ],
            // 序列模型
            [
                'schema' => [
                    'name' => '序列模型',
                    'code' => 'sequence',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'sequence',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联集数
                        'src_module_code' => 'sequence',
                        'dst_module_code' => 'episode',
                        'type' => 'belong_to',
                        'link_id' => 'parent_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'sequence',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'sequence',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ],
                    [ // 关联任务
                        'src_module_code' => 'sequence',
                        'dst_module_code' => 'base',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'sequence',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'sequence',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'entity_id'
                    ],
                    [ // 关联镜头
                        'src_module_code' => 'sequence',
                        'dst_module_code' => 'shot',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ]
                ]
            ],
            // 镜头模型
            [
                'schema' => [
                    'name' => '镜头模型',
                    'code' => 'shot',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'shot',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联序列
                        'src_module_code' => 'shot',
                        'dst_module_code' => 'sequence',
                        'type' => 'belong_to',
                        'link_id' => 'parent_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'shot',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'shot',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ],
                    [ // 关联任务
                        'src_module_code' => 'shot',
                        'dst_module_code' => 'base',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'shot',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'shot',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'entity_id'
                    ]
                ]
            ],
            // 前期制作模型
            [
                'schema' => [
                    'name' => '前期制作模型',
                    'code' => 'pre_production',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'pre_production',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'pre_production',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'pre_production',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ],
                    [ // 关联任务
                        'src_module_code' => 'pre_production',
                        'dst_module_code' => 'base',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'pre_production',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'pre_production',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'entity_id'
                    ]
                ]
            ],
            // 资产分类模型
            [
                'schema' => [
                    'name' => '资产分类模型',
                    'code' => 'asset_type',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'asset_type',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'asset_type',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'asset_type',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ],
                    [ // 关联任务
                        'src_module_code' => 'asset_type',
                        'dst_module_code' => 'base',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'asset_type',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'asset_type',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'entity_id'
                    ],
                    [ // 关联资产
                        'src_module_code' => 'asset_type',
                        'dst_module_code' => 'asset',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ]
                ]
            ],
            // 资产模型
            [
                'schema' => [
                    'name' => '资产模型',
                    'code' => 'asset',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'asset',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'asset',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'asset',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ],
                    [ // 关联任务
                        'src_module_code' => 'asset',
                        'dst_module_code' => 'base',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'asset',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'asset',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'entity_id'
                    ]
                ]
            ],
            // BUG模型
            [
                'schema' => [
                    'name' => 'BUG模型',
                    'code' => 'bug',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'bug',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'bug',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'bug',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ],
                    [ // 关联任务
                        'src_module_code' => 'bug',
                        'dst_module_code' => 'base',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'bug',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'bug',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'entity_id'
                    ]
                ]
            ],
            // 需求模型
            [
                'schema' => [
                    'name' => '需求模型',
                    'code' => 'requires',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'requires',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'requires',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'requires',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ],
                    [ // 关联任务
                        'src_module_code' => 'requires',
                        'dst_module_code' => 'base',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'requires',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'requires',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'entity_id'
                    ]
                ]
            ],
            // 产品模型
            [
                'schema' => [
                    'name' => '产品模型',
                    'code' => 'product',
                    'type' => 'system',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联项目
                        'src_module_code' => 'product',
                        'dst_module_code' => 'project',
                        'type' => 'belong_to',
                        'link_id' => 'project_id'
                    ],
                    [ // 关联状态
                        'src_module_code' => 'product',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联模块
                        'src_module_code' => 'product',
                        'dst_module_code' => 'module',
                        'type' => 'belong_to',
                        'link_id' => 'module_id'
                    ],
                    [ // 关联任务
                        'src_module_code' => 'product',
                        'dst_module_code' => 'base',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联标签
                        'src_module_code' => 'product',
                        'dst_module_code' => 'tag_link',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'product',
                        'dst_module_code' => 'media',
                        'type' => 'has_one',
                        'link_id' => 'entity_id'
                    ]
                ]
            ],
            // 剧集项目模型
            [
                'schema' => [
                    'name' => '剧集项目模型',
                    'code' => 'series',
                    'type' => 'project',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联状态
                        'src_module_code' => 'project',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'project',
                        'dst_module_code' => 'media',
                        'type' => 'belong_to',
                        'link_id' => 'media_id'
                    ],
                    [ // 关联集数
                        'src_module_code' => 'project',
                        'dst_module_code' => 'episode',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联序列
                        'src_module_code' => 'project',
                        'dst_module_code' => 'sequence',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联资产
                        'src_module_code' => 'project',
                        'dst_module_code' => 'asset',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联镜头
                        'src_module_code' => 'project',
                        'dst_module_code' => 'shot',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ]
                ]
            ],
            // 开发项目模型
            [
                'schema' => [
                    'name' => '开发项目模型',
                    'code' => 'develop',
                    'type' => 'project',
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                ],
                'module_relation' => [
                    [ // 关联状态
                        'src_module_code' => 'project',
                        'dst_module_code' => 'status',
                        'type' => 'belong_to',
                        'link_id' => 'status_id'
                    ],
                    [ // 关联媒体
                        'src_module_code' => 'project',
                        'dst_module_code' => 'media',
                        'type' => 'belong_to',
                        'link_id' => 'media_id'
                    ],
                    [ // 关联BUG
                        'src_module_code' => 'project',
                        'dst_module_code' => 'bug',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联需求
                        'src_module_code' => 'project',
                        'dst_module_code' => 'requires',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ],
                    [ // 关联产品
                        'src_module_code' => 'project',
                        'dst_module_code' => 'product',
                        'type' => 'has_many',
                        'link_id' => 'id'
                    ]
                ]
            ]
        ];


        $schemaTable = $this->table('strack_schema');
        $moduleRelationTable = $this->table('strack_module_relation');

        foreach ($schemaConfig as $schemaItem) {
            // 先写入schema
            //$schemaItem["schema"]['module_id'] = $moduleMap[$schemaItem["schema"]['code']]['id'];

            $schemaTable->insert($schemaItem["schema"])->save();

            // 获取当前写入schema id
            $query = $this->fetchRow('SELECT max(`id`) as id FROM `strack_schema`');

            foreach ($schemaItem["module_relation"] as $moduleRelationItem) {
                $moduleRelationData = $this->generateNodeConfig($query['id'], $moduleRelationItem, $moduleMap);
                $moduleRelationTable->insert($moduleRelationData)->save();
            }
        }
    }


    /**
     * 生成关联节点配置
     * @param $schemaId
     * @param $moduleRelationConfig
     * @param $moduleMap
     * @return array
     * @throws Exception
     */
    public function generateNodeConfig($schemaId, $moduleRelationConfig, $moduleMap)
    {
        $config = [
            'type' => $moduleRelationConfig['type'],
            'src_module_id' => $moduleMap[$moduleRelationConfig['src_module_code']]['id'],
            'dst_module_id' => $moduleMap[$moduleRelationConfig['dst_module_code']]['id'],
            'link_id' => $moduleRelationConfig['link_id'],
            'schema_id' => $schemaId,
            'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            'node_config' => json_encode([
                "edges" => [
                    "data" => [
                        "type" => "connection",
                        "label" => $moduleRelationConfig['type']
                    ],
                    "source" => $moduleMap[$moduleRelationConfig['src_module_code']]['uuid'],
                    "target" => $moduleMap[$moduleRelationConfig['dst_module_code']]['uuid']
                ],
                "node_data" => [
                    "source" => [
                        "h" => "80",
                        "w" => "120",
                        "id" => $moduleMap[$moduleRelationConfig['src_module_code']]['uuid'],
                        "top" => "147",
                        "left" => "405",
                        "text" => $moduleMap[$moduleRelationConfig['src_module_code']]['name'],
                        "type" => "module",
                        "module_id" => $moduleMap[$moduleRelationConfig['src_module_code']]['id'],
                        "module_code" => $moduleRelationConfig['src_module_code'],
                        "module_type" => $moduleMap[$moduleRelationConfig['src_module_code']]['type']
                    ],
                    "target" => [
                        "h" => "80",
                        "w" => "120",
                        "id" => $moduleMap[$moduleRelationConfig['dst_module_code']]['uuid'],
                        "top" => "387",
                        "left" => "229",
                        "text" => $moduleMap[$moduleRelationConfig['dst_module_code']]['name'],
                        "type" => "module",
                        "module_id" => $moduleMap[$moduleRelationConfig['dst_module_code']]['id'],
                        "module_code" => $moduleRelationConfig['dst_module_code'],
                        "module_type" => $moduleMap[$moduleRelationConfig['dst_module_code']]['type']
                    ]
                ]
            ])
        ];

        return $config;
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM strack_schema');
        $this->execute('DELETE FROM strack_module_relation');
    }
}

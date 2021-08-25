<?php


use Phinx\Migration\AbstractMigration;

class FillEntityModuleAuthRules extends AbstractMigration
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
     * 下划线单个字母大写返回分隔符自定义
     * @param $string
     * @param string $prefix
     * @return string
     */
    function string_initial_letter($string, $prefix = '')
    {
        if (strpos($string, '_')) {
            $stringArray = explode('_', $string);
            $doneString = [];
            foreach ($stringArray as $item) {
                array_push($doneString, ucfirst($item));
            }
            return join($prefix, $doneString);
        } else {
            return ucfirst($string);
        }
    }


    /**
     * 获取所有entity模块数据
     * @return array|mixed
     */
    public function getAllEntityModuleData()
    {
        $sql = 'select * from strack_module where `type`="entity"';
        $moduleList = $this->query($sql)->fetchAll();
        return $moduleList;
    }

    /**
     * 保存权限组
     * @param $data
     * @param int $parentId
     */
    protected function savePageAuth($data, $parentId = 0)
    {
        $pageAuthTable = $this->table('strack_page_auth');
        $pageLinkAuthTable = $this->table('strack_page_link_auth');

        $data["page"]["parent_id"] = $parentId;

        $pageAuthTable->insert($data["page"])->save();
        $query = $this->fetchRow('SELECT max(`id`) as id FROM strack_page_auth');

        if (!empty($data["auth_group"])) {
            foreach ($data["auth_group"] as $authGroup) {
                $authGroup["page_auth_id"] = $query["id"];
                $pageLinkAuthTable->insert($authGroup)->save();
            }
        }

        if (!empty($data["list"])) {
            foreach ($data["list"] as $children) {
                $this->savePageAuth($children, $query["id"]);
            }
        }
    }

    /**
     * 获取当前表的字段配置
     * @param string $tableName
     * @param array $moduleData
     * @return mixed
     */
    public function getTableFieldsConfig($tableName = '', $moduleData = [])
    {
        $tableModuleName = str_replace('strack_', "", $tableName);

        $sql = "select config from strack_field where `table`='{$tableModuleName}' limit 1";
        $tableConfig = $this->query($sql)->fetchAll();

        $fieldConfig = json_decode($tableConfig[0]['config'], true);


        // 给内置字段加上module_code标识
        foreach ($fieldConfig as &$fieldsItem) {
            if (!empty($moduleData)) {
                $fieldsItem["module_code"] = $moduleData["code"];
                $fieldsItem["module_type"] = $moduleData["type"];
                // 判断是不是外联字段
                if (array_key_exists("is_foreign_key", $fieldsItem) && $fieldsItem["is_foreign_key"] === "yes") {
                    $fieldsItem["foreign_key"] = $fieldsItem["fields"];
                    $fieldsItem["frozen_module"] = $fieldsItem["module_code"];
                }
            }
        }
        return $fieldConfig;
    }

    /**
     * 注册权限字段
     * @param $moduleData
     * @throws Exception
     */
    protected function saveFieldAuth($moduleData)
    {
        $moduleCode = $moduleData["type"] === "entity" ? $moduleData["type"] : $moduleData["code"];
        $fieldConfig = $this->getTableFieldsConfig($moduleCode);

        $authFieldTable = $this->table('strack_auth_field');
        foreach ($fieldConfig as $field) {
            $fieldData = [
                "name" => $field["fields"],
                "lang" => $field["lang"],
                "type" => "built_in",
                "project_id" => 0,
                "module_id" => $moduleData["id"],
                "module_code" => $moduleData["code"],
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ];
            $authFieldTable->insert($fieldData)->save();
        }
    }

    /**
     * 实体权限模版
     * @param $moduleData
     * @return array
     * @throws \Exception
     */
    protected function authEntityTemplate($moduleData)
    {
        $moduleId = $moduleData["id"];

        // 权限数据
        $homeEntityPageRows = [
            'page' => [
                'name' => $moduleData["code"] . '页面',
                'code' => $moduleData["code"],
                'lang' => $this->string_initial_letter($moduleData["code"], '_'),
                'page' => 'home_project_entity',
                'menu' => 'top_menu,project',
                'param' => $moduleId,
                'type' => 'children',
                'parent_id' => 0,
                'uuid' => Webpatser\Uuid\Uuid::generate()->string
            ],
            'auth_group' => [
                [ // 页面路由
                    'page_auth_id' => 0,
                    'auth_group_id' => 194,
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ]
            ],
            'list' => [
                [
                    'page' => [
                        'name' => $moduleData["code"] . '页面访问',
                        'code' => 'visit',
                        'lang' => 'Visit',
                        'page' => 'home_project_entity',
                        'param' => $moduleId,
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [ // 页面路由
                            'page_auth_id' => 0,
                            'auth_group_id' => 194,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '修改单个组件',
                        'code' => 'update_widget',
                        'lang' => 'Update_Widget',
                        'page' => 'home_project_entity',
                        'param' => $moduleId,
                        'type' => 'belong',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'auth_group' => [
                        [ // 页面路由
                            'page_auth_id' => 0,
                            'auth_group_id' => 171,
                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '工具栏',
                        'code' => 'toolbar',
                        'lang' => 'Toolbar',
                        'page' => 'home_project_entity',
                        'param' => $moduleId,
                        'type' => 'children',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'list' => [
                        [
                            'page' => [
                                'name' => '创建',
                                'code' => 'create',
                                'lang' => 'Create',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'belong',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'auth_group' => [
                                [
                                    'page_auth_id' => 0,
                                    'auth_group_id' => 1,
                                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                ]
                            ]
                        ],
                        [
                            'page' => [
                                'name' => '编辑',
                                'code' => 'edit',
                                'lang' => 'Edit',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'children',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'list' => [
                                [
                                    'page' => [
                                        'name' => '分配任务',
                                        'code' => 'add_task',
                                        'lang' => 'Add_Task',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 128,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '批量编辑',
                                        'code' => 'batch_edit',
                                        'lang' => 'Batch_Edit',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 2,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '批量删除',
                                        'code' => 'batch_delete',
                                        'lang' => 'Batch_Delete',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 123,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '批量添加反馈',
                                        'code' => 'batch_add_note',
                                        'lang' => 'Batch_Add_Note',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 449,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '动作',
                                        'code' => 'action',
                                        'lang' => 'Action',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 3,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '导入Excel',
                                        'code' => 'import_excel',
                                        'lang' => 'Import_Excel',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 4,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '导出Excel',
                                        'code' => 'export_excel',
                                        'lang' => 'Export_Excel',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 5,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '修改缩略图',
                                        'code' => 'modify_thumb',
                                        'lang' => 'Modify_Thumb',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 6,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '清除缩略图',
                                        'code' => 'clear_thumb',
                                        'lang' => 'Clear_Thumb',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 7,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'page' => [
                                'name' => '排序',
                                'code' => 'sort',
                                'lang' => 'Sort',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'belong',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'auth_group' => [
                                [
                                    'page_auth_id' => 0,
                                    'auth_group_id' => 8,
                                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                ]
                            ]
                        ],
                        [
                            'page' => [
                                'name' => '分组',
                                'code' => 'group',
                                'lang' => 'Group',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'belong',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'auth_group' => [
                                [
                                    'page_auth_id' => 0,
                                    'auth_group_id' => 9,
                                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                ]
                            ]
                        ],
                        [
                            'page' => [
                                'name' => '字段',
                                'code' => 'column',
                                'lang' => 'Field',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'children',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'list' => [
                                [
                                    'page' => [
                                        'name' => '管理自定义字段',
                                        'code' => 'manage_custom_fields',
                                        'lang' => 'Manage_Custom_Fields',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 10,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'page' => [
                                'name' => '工序',
                                'code' => 'step',
                                'lang' => 'Step',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'belong',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'auth_group' => [
                                [
                                    'page_auth_id' => 0,
                                    'auth_group_id' => 129,
                                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                ]
                            ]
                        ],
                        [
                            'page' => [
                                'name' => '视图',
                                'code' => 'view',
                                'lang' => 'View',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'children',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'list' => [
                                [
                                    'page' => [
                                        'name' => '保存默认视图',
                                        'code' => 'save_default_view',
                                        'lang' => 'Save_Default_View',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 456,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    "list" => []
                                ],
                                [
                                    'page' => [
                                        'name' => '保存视图',
                                        'code' => 'save_view',
                                        'lang' => 'Save_View',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 11,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '另存为视图',
                                        'code' => 'save_as_view',
                                        'lang' => 'Save_As_View',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 12,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '修改视图',
                                        'code' => 'modify_view',
                                        'lang' => 'Modify_View',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 13,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '删除视图',
                                        'code' => 'delete_view',
                                        'lang' => 'Delete_View',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 14,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [ // 过滤面板
                    'page' => [
                        'name' => '过滤面板',
                        'code' => 'filter_panel',
                        'lang' => 'Filter_Panel',
                        'page' => 'home_project_entity',
                        'param' => $moduleId,
                        'type' => 'children',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'list' => [
                        [
                            'page' => [
                                'name' => '保存过滤条件',
                                'code' => 'save_filter',
                                'lang' => 'Save_Filter',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'belong',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'auth_group' => [
                                [
                                    'page_auth_id' => 0,
                                    'auth_group_id' => 16,
                                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                ]
                            ]
                        ],
                        [
                            'page' => [
                                'name' => '保持显示',
                                'code' => 'keep_display',
                                'lang' => 'Keep_Display',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'belong',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'auth_group' => [
                                [
                                    'page_auth_id' => 0,
                                    'auth_group_id' => 187,
                                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                ]
                            ]
                        ],
                        [
                            'page' => [
                                'name' => '置顶过滤',
                                'code' => 'stick_filter',
                                'lang' => 'Stick_Filter',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'belong',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'auth_group' => [
                                [
                                    'page_auth_id' => 0,
                                    'auth_group_id' => 191,
                                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                ]
                            ]
                        ],
                        [
                            'page' => [
                                'name' => '删除过滤',
                                'code' => 'delete',
                                'lang' => 'Delete',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'belong',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'auth_group' => [
                                [
                                    'page_auth_id' => 0,
                                    'auth_group_id' => 192,
                                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'page' => [
                        'name' => '右键菜单',
                        'code' => 'right_button_menu',
                        'lang' => 'Right_Button_Menu',
                        'page' => 'home_project_entity',
                        'param' => $moduleId,
                        'type' => 'children',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ]
                ],
                [
                    'page' => [
                        'name' => '边侧栏',
                        'code' => 'side_bar',
                        'lang' => 'Side_Bar',
                        'page' => 'home_project_entity',
                        'param' => $moduleId,
                        'type' => 'children',
                        'parent_id' => 0,
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ],
                    'list' => [
                        [
                            'page' => [
                                'name' => '顶部面板',
                                'code' => 'top_panel',
                                'lang' => 'Top_Panel',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'children',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
                            ],
                            'list' => [
                                [
                                    'page' => [
                                        'name' => '字段配置',
                                        'code' => 'fields_rules',
                                        'lang' => 'Fields_rules',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 168,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '上一个/下一个',
                                        'code' => 'prev_next_one',
                                        'lang' => 'Prev_Next_One',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 169,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '动作',
                                        'code' => 'action',
                                        'lang' => 'Action',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 3,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '记录Timelog',
                                        'code' => 'timelog',
                                        'lang' => 'Timelog',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 193,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '修改缩略图',
                                        'code' => 'modify_thumb',
                                        'lang' => 'Modify_Thumb',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 6,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '清除缩略图',
                                        'code' => 'clear_thumb',
                                        'lang' => 'Clear_Thumb',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'belong',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 7,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'page' => [
                                'name' => '标签栏',
                                'code' => 'tab_bar',
                                'lang' => 'Tab_Bar',
                                'page' => 'home_project_entity',
                                'param' => $moduleId,
                                'type' => 'children',
                                'parent_id' => 0,
                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                            ],
                            'list' => [
                                [
                                    'page' => [
                                        'name' => '反馈',
                                        'code' => 'note',
                                        'lang' => 'Note',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 124,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '信息',
                                        'code' => 'info',
                                        'lang' => 'Info',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 170,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    'list' => [
                                        [
                                            'page' => [
                                                'name' => '修改单个组件信息',
                                                'code' => 'modify',
                                                'lang' => 'Modify',
                                                'page' => 'home_project_entity',
                                                'param' => $moduleId,
                                                'type' => 'belong',
                                                'parent_id' => 0,
                                                'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                            ],
                                            'auth_group' => [
                                                [
                                                    'page_auth_id' => 0,
                                                    'auth_group_id' => 171,
                                                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '现场数据',
                                        'code' => 'onset',
                                        'lang' => 'Onset',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 172,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '历史记录',
                                        'code' => 'history',
                                        'lang' => 'History',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 176,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '设置标签栏',
                                        'code' => 'template_fixed_tab',
                                        'lang' => 'Template_Fixed_Tab',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 177,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ]
                                ],
                                [
                                    'page' => [
                                        'name' => '任务',
                                        'code' => 'base',
                                        'lang' => 'Base',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 457,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    "list" => []
                                ],
                                [
                                    'page' => [
                                        'name' => '云盘',
                                        'code' => 'cloud_disk',
                                        'lang' => 'Cloud_Disk',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 467,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    "list" => []
                                ],
                                [
                                    'page' => [
                                        'name' => '文件',
                                        'code' => 'file',
                                        'lang' => 'File',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 173,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    "list" => []
                                ],
                                [
                                    'page' => [
                                        'name' => '文件提交批次',
                                        'code' => 'commit',
                                        'lang' => 'File_Commit',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 148,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    "list" => []
                                ],
                                [
                                    'page' => [
                                        'name' => '相关任务',
                                        'code' => 'correlation_task',
                                        'lang' => 'Correlation_Task',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 174,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    "list" => []
                                ],
                                [
                                    'page' => [
                                        'name' => '水平关联表格',
                                        'code' => 'horizontal_relationship',
                                        'lang' => 'Horizontal_Relationship',
                                        'page' => 'home_project_entity',
                                        'param' => $moduleId,
                                        'type' => 'children',
                                        'parent_id' => 0,
                                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                    ],
                                    'auth_group' => [
                                        [
                                            'page_auth_id' => 0,
                                            'auth_group_id' => 175,
                                            'uuid' => Webpatser\Uuid\Uuid::generate()->string
                                        ]
                                    ],
                                    "list" => []
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $homeEntityPageRows;
    }


    /**
     * 保存实体初始化关联数据
     * @return array|bool|mixed
     */
    public function addInitialHorizontalConfigData()
    {
        $entityModuleSql = 'select * from strack_module where `type`="entity"';
        $moduleData = $this->query($entityModuleSql)->fetchAll();
        $dstModuleList = $moduleData;


        $fixedModulesql = 'select * from strack_module where `code` IN ("user","media","status")';
        $otherModuleData = $this->query($fixedModulesql)->fetchAll();
        $dstModuleData = array_merge($dstModuleList, $otherModuleData);

        // 处理成对应的数组
        $horizontalConfigData = [];
        foreach ($moduleData as $srcItem) {
            $srcModuleId = $srcItem["id"];
            foreach ($dstModuleData as $dstItem) {
                $dstModuleId = $dstItem["id"];
                if (in_array($dstItem["code"], ["media", "status"])) {
                    $type = "belong_to";
                } else {
                    $type = "has_many";
                }
                array_push($horizontalConfigData, [
                    'src_module_id' => $srcModuleId,
                    'dst_module_id' => $dstModuleId,
                    'project_template_id' => 0,
                    'type' => $type
                ]);
            }
        }

        $horizontalConfigTable = $this->table('strack_horizontal_config');
        foreach ($horizontalConfigData as $dataItem) {
            $configExitSql = "select id from strack_horizontal_config where `src_module_id`='{$dataItem["src_module_id"]}' and `dst_module_id`='{$dataItem["dst_module_id"]}' limit 1";
            $configId = $this->query($configExitSql)->fetchAll();
            if (empty($configId)) {
                $horizontalConfigTable->insert($dataItem)->save();
            }
        }
    }

    /**
     * @throws Exception
     */
    public function up()
    {
        $allEntityModuleData = $this->getAllEntityModuleData();

        foreach ($allEntityModuleData as $moduleData) {
            // 获取权限模版 保存路由权限
            $data = $this->authEntityTemplate($moduleData);
            $this->savePageAuth($data);
            // 保存字段权限
            $this->saveFieldAuth($moduleData);
        }

        $this->addInitialHorizontalConfigData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM strack_auth_group_node');
        $this->execute('DELETE FROM strack_auth_node');
        $this->execute('DELETE FROM strack_auth_group');
    }
}

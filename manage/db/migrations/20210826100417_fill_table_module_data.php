<?php

use Phinx\Migration\AbstractMigration;

class FillTableModuleData extends AbstractMigration
{


    /**
     * 取得数据库的表信息
     * @param string $dbName
     * @return array
     */
    public function getTables($dbName = '')
    {
        $sql = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES ';
        $result = $this->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * 获取所有模块数据
     * @return array
     */
    public function getAllModuleData()
    {
        $sql = 'select * from strack_module';
        $result = $this->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$val['code']] = $val;
        }
        return $info;
    }

    /**
     * 取得数据表的字段信息
     * @param $tableName
     * @return array
     */
    public function getFields($tableName)
    {
        list($tableName) = explode(' ', $tableName);
        if (strpos($tableName, '.')) {
            list($dbName, $tableName) = explode('.', $tableName);
            $sql = 'SHOW COLUMNS FROM `' . $dbName . '`.`' . $tableName . '`';
        } else {
            $sql = 'SHOW COLUMNS FROM `' . $tableName . '`';
        }
        $result = $this->query($sql);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val, CASE_LOWER);
                $info[$val['field']] = array(
                    'name' => $val['field'],
                    'type' => $val['type'],
                    'notnull' => (bool)('' === $val['null']), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    /**
     * _ 名称转驼峰
     * @param $unCamelizeWords
     * @param string $separator
     * @return mixed
     */
    public function camelize($unCamelizeWords, $separator = '_')
    {
        $unCamelizeWords = $separator . str_replace($separator, " ", strtolower($unCamelizeWords));
        return str_replace(" ", "", ucwords(ltrim($unCamelizeWords, $separator)));
    }


    /**
     * 判断当前字段是否为必须
     * @param $field
     * @return string
     */
    public function checkFieldRequire($field)
    {
        if (in_array($field, ['name', 'phone', 'password', 'value', 'ptype', 'status', 'man_hour', 'type', 'attribute_value'])) {
            return 'yes';
        }

        if (strpos($field, '_id')) {
            return 'yes';
        }

        return 'no';
    }

    /**
     * 判断当前字段是否能编辑
     * @param $field
     * @return string
     */
    public function checkFieldEdit($field)
    {
        if (in_array($field, ['id', 'uuid', 'created_by', 'created', 'json', 'is_horizontal'])) {
            return 'deny';
        }

        if (strpos($field, '_id')) {
            return 'deny';
        }

        return 'allow';
    }

    /**
     * 判断当前字段是否能显示
     * @param $field
     * @return string
     */
    public function checkFieldShow($field)
    {
        if (in_array($field, ['json', 'password'])) {
            return 'no';
        }

        return 'yes';
    }


    /**
     * 判断当前字段是否能排序
     * @param $field
     * @return string
     */
    public function checkFieldSort($field)
    {
        if (in_array($field, ['name', 'code', 'attribute_id', 'start_time', 'end_time', 'type', 'created_by', 'created', 'project_id', 'category_id', 'step_category_id'])) {
            return 'allow';
        }


        return 'deny';
    }


    /**
     * 判断当前字段是否能分组
     * @param $field
     * @return string
     */
    public function checkFieldGroup($field)
    {
        if (strpos($field, '_id')) {
            return 'allow';
        }

        return 'deny';
    }

    /**
     * 判断当前字段是否能过滤
     * @param $field
     * @return string
     */
    public function checkFieldFilter($field)
    {
        if (in_array($field, ['id', 'uuid', 'json', 'config', 'param', 'admin_password', 'node_config'])) {
            return 'deny';
        }

        return 'allow';
    }

    /**
     * 判断当前字段是否能过滤
     * @param $field
     * @return string
     */
    public function checkFieldPrimaryKey($field)
    {
        if ($field === 'id') {
            return 'yes';
        }

        return 'no';
    }

    /**
     * 判断当前字段是否能过滤
     * @param $field
     * @return string
     */
    public function checkFieldForeignKey($field)
    {
        if (strpos($field, '_id')) {
            return 'yes';
        }

        return 'no';
    }

    /**
     * 获取固定字段的编辑器类型
     * @param $field
     * @param $type
     * @return string
     */
    public function getFixedFieldEditor($field, $type)
    {
        if (
            strpos($field, '_id')
            || strpos($field, 'enum')
            || in_array($field, ['created_by', 'is_horizontal', 'resolution', 'delivery_platform', 'ptype', 'assignee', 'executor'])
        ) {
            return 'select';
        }

        if (
            strpos($field, '_time')
            || in_array($field, ['created'])
        ) {
            return 'date';
        }

        if (in_array($field, ['ssl', 'tls'])) {
            return 'switch';
        }

        if (
            strpos($type, 'varchar')
            || strpos($type, 'char')
            || strpos($type, 'int')
        ) {
            return 'input';
        }

        if (in_array($type, ['text', 'longtext'])) {
            return 'text_area';
        }


        return 'none';
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
     * 生成字段配置
     * @param $modelName
     * @param $realName
     * @param $moduleID
     * @param $field
     * @param $param
     * @return array
     */
    public function generateFieldConfig($modelName, $realName, $moduleID, $field, $param)
    {
        // 默认 id 字段就是主键
        $isPrimary = $field === 'id' ? "yes" : "no";

        // 默认 带_id的参数都属于外键
        $isForeign = strpos($field, '_id') === false ? "no" : "yes";

        $fieldConfig = [
            "id" => $field, // 字段id, 固定字段是0，自定义字段是注册的id值
            "fields" => $field, // 字段名
            "value_show" => $field,
            "type" => $param['type'], //字段类型
            "field_type" => "built_in", //字段类型 built_in：固定字段，custom：自定义字段
            "disabled" => "no", // 是否禁用（yes, no）
            "require" => $this->checkFieldRequire($field), // 是否必须（yes, no）
            "table" => $modelName, // 所属表名
            "module" => $realName, // 所属模块名
            "module_id" => $moduleID, // 模块id
            "lang" => strtoupper($field), // 语言包KEY
            "editor" => $this->getFixedFieldEditor($field, $param['type']), // 编辑器类型
            "edit" => $this->checkFieldEdit($field), // 是否可以编辑（allow, deny）
            "show" => $this->checkFieldShow($field), // 是否在前台显示 （yes, no）
            "sort" => $this->checkFieldSort($field), // 是否可以排序（allow, deny）
            "allow_group" => $this->checkFieldGroup($field), // 是否可以分组
            "outreach_edit" => "deny",
            "group" => "", // 分组显示名称
            "filter" => $this->checkFieldFilter($field), // 是否可以过滤（allow, deny）
            "multiple" => "no", // 是否可以多选（yes, no）
            "format" => [], // 格式化配置
            "validate" => "", // 验证方法
            "mask" => "", // 掩码配置
            "is_primary_key" => $isPrimary, // 是否是主键（yes, no）
            "is_foreign_key" => $isForeign, // 是否是外键（yes, no）
            "placeholder" => "no", // 输入框占位文本 （yes, no）
            "show_word_limit" => "no", // 是否显示输入字数统计 （yes, no）
            "autocomplete" => "no", // 是否自动补全 （yes, no）
            "value_icon" => "", // 值图标
            "label_icon" => "", // 文本图标
            "label_width" => 0, // 文本宽度
            "value_width" => 0, //  值宽度
            "is_label" => "no",  //  是否显示文本 （yes, no）
            "default_value" => "", // 默认值
            "data_source" => [ // 数据源
                "type" => "fixed", // 数据源类型，fixed 固定 , dynamic 动态
                "data" => [] // 数据源，静态直接配置，动态是一个字符串标识
            ]
        ];

        return $fieldConfig;
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
        $tables = [];

        // 获取所有固定数据表
        $fixedModule = $this->getTables();

        // 获取所有存在的表
        $moduleMap = $this->getAllModuleData();

        foreach ($fixedModule as $fixedItem) {
            if (!array_key_exists(str_replace('strack_', '', $fixedItem), $moduleMap) || $fixedItem === 'strack_eventlog') {
                $tables[] = [
                    'type' => 'fixed',
                    'name' => $fixedItem
                ];
            }
        }

        $moduleRows = [];
        $fieldsRows = [];

        $result = $this->query("select max(id) as max_id from strack_module")->fetch();
        $insertId = $result['max_id'] ? $result['max_id'] + 1 : 1;
        foreach ($tables as $tableItem) {

            $tableNameSrc = str_replace('strack_', '', $tableItem["name"]);

            if ($tableNameSrc !== "phinxlog") {

                // 组装注册固定模块数据
                $modelName = $tableItem["type"] === "entity" ? 'Entity' : $this->camelize($tableNameSrc);

                if($tableNameSrc !== 'eventlog'){
                    $moduleRows[] = [
                        'type' => $tableItem["type"],
                        'active' => 'yes',
                        'name' => $this->camelize($tableNameSrc),
                        'code' => $tableNameSrc,
                        'icon' => '',
                        'uuid' => Webpatser\Uuid\Uuid::generate()->string
                    ];
                }

                // 组装当前模块字段配置数据
                $tableName = $tableItem["type"] === "entity" ? 'strack_entity' : $tableItem["name"];
                $currentTableFields = $this->getFields($tableName);

                $tempConfig = [];
                foreach ($currentTableFields as $field => $param) {
                    $tempConfig[] = $this->generateFieldConfig($modelName, $tableNameSrc, $insertId, $field, $param);
                }

                $fieldsRows[] = [
                    'table' => str_replace('strack_', '', $tableItem["name"]),
                    'config' => json_encode($tempConfig),
                    'uuid' => Webpatser\Uuid\Uuid::generate()->string
                ];

                $insertId++;
            }
        }

        // 写入模块数据表
        $this->table('strack_module')->insert($moduleRows)->save();

        // 写入字段数据表
        $this->table('strack_field')->insert($fieldsRows)->save();


        // 事件日志模型
        $schemaConfig = [[
            'schema' => [
                'name' => '事件日志模型',
                'code' => 'admin_eventlog',
                'type' => 'system',
                'uuid' => Webpatser\Uuid\Uuid::generate()->string,
            ],
            'module_relation' => [
                [ // 关联用户角色
                    'src_module_code' => 'eventlog',
                    'dst_module_code' => 'user',
                    'type' => 'belong_to',
                    'link_id' => 'created_by'
                ]
            ]
        ]];

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
}

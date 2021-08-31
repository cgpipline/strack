<?php
// +----------------------------------------------------------------------
// | View 视图服务
// +----------------------------------------------------------------------
// | * 视图层
// | * 1.查询所有关联表的字段
// | * 2.处理字段（可显示、可编辑、可排序、可分组、可过滤）、还要通过临时字段、字段权限进行判断，组装出view需要的字段
// | * 3.组装view视图的表格列
// +----------------------------------------------------------------------
// | 错误编码头 231xxx
// +----------------------------------------------------------------------

namespace Common\Service;

use Common\Model\HorizontalModel;
use Common\Model\ModuleModel;
use Common\Model\StatusModel;
use Common\Model\UserConfigModel;
use Common\Model\UserModel;
use Common\Model\VariableModel;
use Common\Model\ViewDefaultModel;
use Common\Model\ViewModel;
use Common\Model\ViewUseModel;
use Org\Util\Pinyin;

class ViewService
{

    // Module Model 对象
    protected $moduleModel;

    // Schema Service
    protected $schemaService;

    protected $authService;

    // 字段权限字典
    protected $authFieldConfig = [];

    // 当前登陆用户ID
    protected $userId;

    // 判断是否有用户表关联
    protected $hasUserModule = false;

    // 字段
    protected $fieldExitList = [];

    // api find select 查询方法
    protected $apiSelectMethod = '';

    // 自定义字段映射字典
    protected $customFieldsMap = [];

    /**
     * ViewService constructor.
     */
    public function __construct()
    {
        $this->moduleModel = new ModuleModel();
        $this->schemaService = new SchemaService();
        $this->authService = new AuthService();
        $this->userId = session("user_id");
    }

    /**
     * 获取module数据
     * @param $moduleId $moduleId 查询条件
     * @param string $field 指定查询字段
     * @return array|mixed
     */
    private function getModuleData($moduleId, $field = '')
    {
        $moduleData = $this->moduleModel->findData(['filter' => ['id' => $moduleId], 'fields' => 'id as module_id,code,type']);
        if (!empty($moduleData) && !empty($field)) {
            return $moduleData[$field];
        } else {
            return $moduleData;
        }
    }

    /**
     * 获取使用得视图模式
     * @param $userId
     * @param $projectId
     * @param $page
     * @return mixed
     */
    private function getUseViewMode($userId, $projectId, $page)
    {
        $viewUserModel = new ViewUseModel();
        $userViewId = $viewUserModel->where([
            'user_id' => $userId,
            'project_id' => $projectId,
            'page' => $page
        ])->getField('view_id');

        if (!empty($userViewId)) {
            // 用户视图
            $viewModel = new ViewModel();
            return $viewModel->where(['id' => $userViewId])->getField('type');
        } else {
            // 默认视图
            $viewDefaultModel = new ViewDefaultModel();
            return $viewDefaultModel->where([
                'project_id' => $projectId,
                'page' => $page
            ])->getField('type');
        }
    }

    /**
     * 前端数据表格视图结构
     * @param $param
     * @return array
     */
    public function getGirdViewConfig($param)
    {
        // 获取当前模块数据
        $moduleData = $this->getModuleData($param["module_id"]);

        // 组装数据表格列配置
        $viewData = $this->generateGridColumnConfig($param, $moduleData);

        // 获取当前使用视图类型
        if (array_key_exists('grid', $viewData)) {
            $viewData['grid']['view_mode'] = $this->getUseViewMode(session('user_id'), $param['project_id'], $param['page']);
        }

        return $viewData;
    }

    /**
     * 获取边侧栏数据表格配置
     * @param $param
     * @return array
     */
    public function getDataGridSliderTableConfig($param)
    {
        // 获取当前模块数据
        $moduleData = $this->getModuleData($param["module_id"]);
        // 组装数据表格列配置
        $viewData = $this->generateGridColumnConfig($param, $moduleData);
        return $viewData;
    }

    /**
     *  获取过滤面板数据
     * 1.当前模块可查询字段
     * 2.当前模块当前用户已经保存可用的 过滤条件
     * @param $param
     * @return array
     */
    public function getGridPanelData($param)
    {
        // 用户ID
        $userId = $this->userId;
        // 模块类型
        $moduleType = $this->getModuleData($param["module_id"], "type");
        // 获取schema_id 用schema_page做参数
        $schemaId = $this->schemaService->getPageSchemaId($moduleType, $param['schema_page'], 0);
        // 获取当前页面过滤数据
        $filterService = new FilterService();
        $filterBar = $filterService->getFilterList($userId, $param['page'], $param['project_id']);
        // 获取当前页面视图列表
        $viewData = $this->getViewList($param['project_id'], $userId, $param['page']);
        // 获取当前页面工序列表
        $stepService = new StepService();
        $stepData = $stepService->getStepCheckList($param['project_id'], $userId, $param['page'], $param['module_id']);
        // 获取常用动作
        $actionService = new ActionService();
        $actionData = $actionService->getCommonActionList($param);

        // 字段配置数据
        $fieldConfigData = $this->getSchemaConfig($param, $schemaId, "view");

        $resData = [
            'filter_bar' => $filterBar,
            'show_list' => $fieldConfigData["field_clean_data"]["field_auth_config"]["show_list"],
            'filter_list' => $fieldConfigData["field_clean_data"]["field_auth_config"]["filter_list"],
            'search_list' => $fieldConfigData["field_clean_data"]["field_auth_config"]["search_list"],
            'sort_list' => $fieldConfigData["field_clean_data"]["field_auth_config"]["sort_list"],
            'group_list' => $fieldConfigData["field_clean_data"]["field_auth_config"]["group_list"],
            'edit_list' => $fieldConfigData["field_clean_data"]["field_auth_config"]["edit_list"],
            'view_list' => $viewData,
            'step_list' => $stepData,
            'common_action_list' => $actionData
        ];

        return $resData;
    }

    /**
     * 获取看板可用视图
     * @param $param
     * @return array
     */
    public function getKanbanViewList($param)
    {
        $gridPanelData = $this->getGridPanelData($param);

        $kanbanMap = [
            "grouping_of_persons" => [
                'lang' => L('Persons_View_Mode')
            ],
            "status" => [
                'lang' => L('Status_View_Mode')
            ]
        ];

        if (!empty($gridPanelData)) {
            foreach ($gridPanelData['group_list'] as $groupData) {
                foreach ($groupData['built_in']['fields'] as $builtInItem) {
                    if ($builtInItem['module'] === 'status') {
                        $kanbanMap['status']['fields'] = $builtInItem;
                    }
                }

                foreach ($groupData['custom']['fields'] as $customItem) {
                    if ($customItem['can_use_kanban_view'] === 'yes') {
                        $kanbanMap[$customItem['kanban_view_mode']]['fields'] = $customItem;
                    }


                }
            }
        }

        return $kanbanMap;
    }

    /**
     * 获取表格数据
     * @param $param
     * @param string $type
     * @return array
     */
    public function getGridQuerySchemaConfig($param, $type = "query")
    {
        $moduleType = $this->getModuleData($param["module_id"], "type");

        // 查找页面数据结构设置
        $schemaId = $this->schemaService->getPageSchemaId($moduleType, $param['schema_page'], 0);

        // 获取schema配置
        $schemaFields = $this->getSchemaConfig($param, $schemaId, $type);

        return $schemaFields;
    }

    /**
     * 获取API接口关联查询要保留的字段
     * @param $retainFields
     * @param $key
     * @param $value
     */
    private function getApiGetRelationFilterRetainFields(&$retainFields, $key, $value)
    {
        if (is_numeric($key)) {
            if ($value['field_type'] === 'built_in') {
                $retainFields[$value['module_code']][] = $value['field'];
            } else {
                if (
                    in_array($value['editor'], ["horizontal_relationship", "belong_to"])
                    || (!empty($this->customFieldsMap[$value['module_code']]) && in_array($this->customFieldsMap[$value['module_code']]['type'], ["horizontal_relationship", "belong_to"]))
                ) {
                    // 水平关联
                    $retainFields[$value['module_code']][] = $value['field'];
                } else {
                    $retainFields[$value['module_code'] . '_' . $value['field']][] = 'value';
                }
            }
        }
    }

    /**
     * 获取自定义
     * @param $filter
     * @param string $field
     */
    private function getCustomFieldsMap($filter, $field = 'code')
    {
        $variableModel = new VariableModel();
        $customFields = $variableModel->field('id,name,code,type,module_id,project_id')->where($filter)->select();
        $this->customFieldsMap = array_column($customFields, null, $field);
    }

    /**
     * 处理API接口关联查询字段
     * @param $schemaConfig
     * @param $filter
     * @param $queryFields
     */
    private function handelApiGetRelationFields(&$schemaConfig, $filter, $queryFields)
    {
        // 获取要保留的字段
        $retainFields = [];

        $masterFields = [];
        $relationJoinFields = [];
        $relationHasManyFields = [];

        $masterCode = $schemaConfig['relation_structure']['table_alias'];

        $this->getCustomFieldsMap(['type' => ['IN', 'horizontal_relationship,belong_to']]);

        if (!empty($filter) && array_key_exists('number', $filter)) {
            if ((int)$filter['number'] === 1) {
                // 一层字段
                foreach ($filter as $key => $value) {
                    $this->getApiGetRelationFilterRetainFields($retainFields, $key, $value);
                }
            } else {
                // 两层字段
                foreach ($filter as $key => $value) {
                    if (is_numeric($key)) {
                        foreach ($value as $fieldKey => $fieldValue) {
                            $this->getApiGetRelationFilterRetainFields($retainFields, $fieldKey, $fieldValue);
                        }
                    }
                }
            }
        }

        foreach ($queryFields as $key => $queryField) {
            $queryFieldArr = explode(',', $queryField);
            if (!array_key_exists($key, $retainFields)) {
                if ($key === "media") {
                    $queryFieldArr[] = 'param';
                }
                $retainFields[$key] = $queryFieldArr;
            } else {
                foreach ($queryFieldArr as $item) {
                    if (!in_array($item, $retainFields[$key])) {
                        $retainFields[$key][] = $item;
                    }
                }
            }
        }

        // 重新处理查询字段，主表字段，关联表字段
        foreach ($schemaConfig['relation_structure']['fields'] as $fieldList) {
            foreach ($fieldList as $field => $fieldStr) {

                if ($field === 'id') {
                    // id 字段必须保留
                    $masterFields[] = [$field => $fieldStr];
                    continue;
                }

                if (!empty($retainFields[$masterCode]) && in_array($field, $retainFields[$masterCode])) {
                    // 存在字段
                    $masterFields[] = [$field => $fieldStr];
                    continue;
                }

                if (empty($queryFields[$masterCode]) && in_array($this->apiSelectMethod, ['find', 'select'])) {
                    // find 或者 select查询字段为空返回所有主表字段
                    $masterFields[] = [$field => $fieldStr];
                }
            }
        }

        foreach ($schemaConfig['relation_structure']['relation_join'] as $module => $item) {
            $tempFields = [];

            if ($item['module_type'] === 'fixed') {
                // 固定字段
                if (array_key_exists($module, $retainFields)) {
                    foreach ($item['fields'] as $fieldList) {
                        foreach ($fieldList as $field => $fieldStr) {
                            if (in_array($field, $retainFields[$module])) {
                                $tempFields[] = [$field => $fieldStr];
                            }
                        }
                    }

                    if (!empty($tempFields)) {
                        if (in_array($this->apiSelectMethod, ['find', 'select'])) {
                            if ($module === $masterCode) {
                                $item['fields'] = $tempFields;
                                $relationJoinFields[$module] = $item;
                            }
                        } else {
                            $item['fields'] = $tempFields;
                            $relationJoinFields[$module] = $item;
                        }
                    } else {
                        if (empty($queryFields[$masterCode]) && in_array($this->apiSelectMethod, ['find', 'select'])) {
                            // find 或者 select查询字段为空返回所有主表字段
                            $relationJoinFields[$module] = $item;
                        }
                    }
                }
            } else {
                // 自定义字段
                $moduleCustomParam = explode('_', $module);
                if (array_key_exists($moduleCustomParam[0], $retainFields) && in_array($moduleCustomParam[1], $retainFields[$moduleCustomParam[0]])) {
                    if (in_array($this->apiSelectMethod, ['find', 'select'])) {
                        if ($moduleCustomParam[0] === $masterCode) {
                            $relationJoinFields[$module] = $item;
                        }
                    } else {
                        $relationJoinFields[$module] = $item;
                    }
                } else {
                    if (empty($queryFields[$moduleCustomParam[0]]) && in_array($this->apiSelectMethod, ['find', 'select']) && $moduleCustomParam[0] === $masterCode) {
                        // find 或者 select查询字段为空返回所有主表字段
                        $relationJoinFields[$module] = $item;
                    }
                }
            }

        }


        foreach ($schemaConfig['relation_structure']['relation_has_many'] as $module => $item) {

            if ($item['module_type'] === 'fixed') {
                if (array_key_exists($module, $retainFields)) {
                    if (in_array($this->apiSelectMethod, ['find', 'select'])) {
                        if ($module === $masterCode) {
                            $relationHasManyFields[$module] = $item;
                        }
                    } else {
                        $relationHasManyFields[$module] = $item;
                    }
                }
            } else {
                if (
                    (array_key_exists($item['belong_module'], $retainFields) && in_array($module, $retainFields[$item['belong_module']]))
                    || (array_key_exists($module, $retainFields))
                ) {
                    if (in_array($this->apiSelectMethod, ['find', 'select'])) {
                        if ($item['belong_module'] === $masterCode) {
                            $relationHasManyFields[$module] = $item;
                        }
                    } else {
                        $relationHasManyFields[$module] = $item;
                    }
                } else {
                    if (empty($queryFields[$item['belong_module']]) && in_array($this->apiSelectMethod, ['find', 'select']) && $item['belong_module'] === $masterCode) {
                        // find 或者 select查询字段为空返回所有主表字段
                        $relationHasManyFields[$module] = $item;
                    }
                }
            }
        }


        $schemaConfig['relation_structure']['fields'] = $masterFields;
        $schemaConfig['relation_structure']['relation_join'] = $relationJoinFields;
        $schemaConfig['relation_structure']['relation_has_many'] = $relationHasManyFields;

        //echo json_encode($schemaConfig);die;
    }

    /**
     * 获取当前模块基础配置
     * @param $param
     * @param $schemaId
     * @param string $type
     * @param array $queryFields
     * @return array
     */
    public function getSchemaConfig($param, $schemaId, $type = "query", $queryFields = [])
    {
        // 获取当前数据结构字段
        $schemaFieldConfig = $this->getViewSchemaFields($param, $schemaId);

        if ($type !== 'query') {
            return $schemaFieldConfig;
        }

        $moduleData = $this->getModuleData($param["module_id"]);

        // 获取当前模块的项目模版配置
        if (array_key_exists("project_id", $param) && $param["project_id"] > 0) {
            $templateService = new TemplateService();
            $templateConfig = $templateService->getGridViewTemplateConfig([
                "filter" => ["project_id" => $param["project_id"]],
                "module_code" => $moduleData["code"]
            ]);

            if ($moduleData["type"] === "entity" &&
                array_key_exists("base", $schemaFieldConfig["field_clean_data"]["schema_fields"]) &&
                !empty($templateConfig["step_fields"])
            ) {
                // 当前实体下面存在
                $stepFields = [];

                foreach ($templateConfig["step_fields"] as $field) {
                    $stepFields[$field["field_type"]][] = $field;
                }

                $schemaFieldConfig["field_clean_data"]["schema_fields"]["base"]["field_configs"] = $stepFields;
            }
        }

        // 生成列字段字段
        $columnsConfig = $this->schemaService->generateColumnsConfig($schemaFieldConfig, $moduleData, false, [], true);

        // 根据权限视图过滤后的字段结构生成数据结构，关联查询结构
        $schemaConfig = [
            'module_id' => $param['module_id'],
            'relation_structure' => $this->schemaService->getModelRelation($param, [
                "schema_fields" => $columnsConfig,
                "relation_structure" => $schemaFieldConfig["relation_structure"]
            ], $moduleData)
        ];

        // 判断是否有分页参数
        if (array_key_exists("pagination", $param)) {
            $schemaConfig['pagination'] = $param['pagination'];
        }

        // 如果有指定字段
        if (empty($queryFields) && in_array($this->apiSelectMethod, ['find', 'select'])) {
            $queryFields[$moduleData['code']] = '';
        }

        if (!empty($queryFields)) {
            $this->handelApiGetRelationFields($schemaConfig, $param['filter']['filter_advance'], $queryFields);
        }


        return $schemaConfig;
    }

    /**
     * 获取字段的默认值
     * @param $fields
     * @param $moduleCode
     * @return mixed
     */
    protected function getFieldDefaultValue(&$fields, $moduleCode)
    {
        switch ($moduleCode) {
            case "user":
                // 用户的默认邮箱后缀填充
                $optionService = new OptionsService();
                foreach ($fields["built_in"]["fields"] as &$fieldItem) {
                    if ($fieldItem["fields"] === "email") {
                        $fieldItem["default_val"] = $optionService->getOptionsConfigItemData("default_settings", "default_emailsuffix");
                    }
                }
                break;
        }
        return $fields;
    }

    /**
     * 获取所有的字段
     * @param $param
     * @return array
     */
    public function getFields($param)
    {
        // 初始化返回数据
        $fieldList = [];

        // 必须字段
        $mustFields = [];

        if ($param["type"] === "combobox_add_panel") {
            $param["module_id"] = $param["from_module_id"];
            $page = $param['from_schema_page'];
        } else {
            $page = $param["type"] === "add_entity_task_panel" ? "project_base" : $param['schema_page'];
            $param["module_id"] = $page === "project_base" ? C("MODULE_ID")["base"] : $param["module_id"];
        }

        // module信息
        $moduleData = $this->getModuleData($param["module_id"], "");
        $moduleType = $moduleData["type"];

        // 获取schema_id
        $schemaId = $this->schemaService->getPageSchemaId($moduleType, $page, 0);

        // 字段配置数据
        $fieldConfigData = $this->getSchemaConfig($param, $schemaId, "view");

        switch ($param["type"]) {
            case "combobox_add_panel":
                // 获取当前的module信息
                $currentModuleData = $this->schemaService->getModuleFindData(["code" => $param["module_code"]]);

                $moduleCodeData = $currentModuleData["type"] === "entity" ? $currentModuleData["type"] : $currentModuleData["code"];

                $hasOneTableName = [$param["module_code"]];
                $masterCode = $param["module_code"];
                $userConfigData = [];
                break;
            default:
                // 获取Module Code 数据
                $moduleCodeData = $moduleType === "entity" ? $moduleType : $moduleData["code"];
                // 获取要返回表的名称
                $masterTableName = $fieldConfigData['relation_structure']['module_type'] == "fixed" ? $fieldConfigData['relation_structure']['table_name'] : $fieldConfigData['relation_structure']['table_alias'];
                $masterCode = $masterTableName;
                $hasOneTableName = [$masterTableName, "tag"];
                // 获取关联结构
                foreach ($fieldConfigData['relation_structure']['relation_join'] as $relationKey => $relationItem) {
                    if ($relationItem['mapping_type'] === "has_one") {
                        array_push($hasOneTableName, $relationKey);
                    }
                }

                // 获取当前用户配置的必须字段面板
                $userConfigModel = new UserConfigModel();
                $userConfigData = $userConfigModel->findData(['filter' => ['type' => $param['type'], 'page' => $param['page'], "user_id" => $this->userId]]);
                break;
        }


        // 可编辑字段
        if (in_array("edit", $param['field_list_type'])) {

            // 判断编辑框取的数据源
            switch ($param['mode']) {
                case 'create':
                    $allowFieldList = $fieldConfigData["field_clean_data"]["field_auth_config"]['create_list'];
                    break;
                default:
                    $allowFieldList = $fieldConfigData["field_clean_data"]["field_auth_config"]['edit_list'];
                    break;
            }

            foreach ($allowFieldList as $key => $item) {

                if (in_array($key, $hasOneTableName)) {
                    $newFields = [
                        "built_in" => ["title" => $item["built_in"]["title"], "fields" => []],
                        "custom" => ["title" => $item["custom"]["title"], "fields" => []],
                    ];
                    if (array_key_exists("not_fields", $param)) {
                        foreach ($item["built_in"]["fields"] as $fieldItem) {

                            // 项目id字段可编辑
                            if ($fieldItem['fields'] === 'project_id' && !in_array($param['page'], ['my_scheduler'])) {
                                continue;
                            }

                            if (!in_array($fieldItem["fields"], $param["not_fields"])) {
                                $newFields["built_in"]["fields"][] = $fieldItem;
                            }
                        }
                        // 实体新增任务时屏蔽媒体字段
                        if ($param["type"] === "add_entity_task_panel") {
                            foreach ($item["custom"]["fields"] as $customItem) {
                                if ($customItem["relation_module_code"] !== "media") {
                                    $newFields["custom"]["fields"][] = $customItem;
                                }
                            }
                        } else {
                            $newFields["custom"]["fields"] = $item["custom"]["fields"];
                        }
                    } else {
                        // 屏蔽项目团队中用户修改
                        if ($param["type"] === "update_panel" && $param["page"] == "project_member") {
                            foreach ($item["built_in"]["fields"] as $builtInItem) {

                                // 项目id字段可编辑
                                if ($builtInItem['fields'] === 'project_id' && !in_array($param['page'], ['my_scheduler'])) {
                                    continue;
                                }

                                if (!in_array($builtInItem["fields"], ["user_id"])) {
                                    $newFields["built_in"]["fields"][] = $builtInItem;
                                }
                            }
                            $newFields["custom"]["fields"] = $item["custom"]["fields"];
                        } else {
                            foreach ($item["built_in"]["fields"] as $builtInItem) {

                                // 项目id字段可编辑
                                if ($builtInItem['fields'] === 'project_id' && !in_array($param['page'], ['my_scheduler'])) {
                                    continue;
                                }

                                $newFields["built_in"]["fields"][] = $builtInItem;
                            }
                            $newFields["custom"]["fields"] = $item["custom"]["fields"];
                        }
                    }

                    $fieldList[$key] = $newFields;

                    // 获取字段的默认值
                    $this->getFieldDefaultValue($fieldList[$key], $key);

                    if ($key == $masterCode) {
                        $fieldList[$key]['built_in']['title'] = "";
                    }
                }

                // 处理固定字段中的必须字段
                foreach ($item["built_in"]["fields"] as $fieldItem) {
                    // 必须字段只获取主表的必须字段
                    if ($key == $masterCode) {
                        if (array_key_exists("require", $fieldItem) && $fieldItem["require"] === "yes") {
                            array_push($mustFields, $moduleCodeData . "-" . $fieldItem["fields"]);
                        }
                    } else {
                        switch ($key) {
                            case "role_user":
                                if ($fieldItem["fields"] === "role_id") {
                                    array_push($mustFields, $key . "-" . $fieldItem["fields"]);
                                }
                                break;
                        }
                    }
                }
            }
        }

        $mustConfig = [];
        $userFieldConfig = [];
        $requiredSetting = [];
        $keepStatus = !empty($userConfigData['config']['keep_status']) ? $userConfigData['config']['keep_status'] : ["keep_data" => "no", "continue_next" => "no"];

        switch ($param["type"]) {
            case "add_panel":
            case "add_entity_task_panel":
                if (in_array($param["page"], ['my_scheduler', 'project_base'])) {
                    // 日程页面必须
                    $allowModuleList = $param["page"] === 'my_scheduler' ? [
                        'project_id', // 项目ID
                        'name', // 任务名称
                        'status_id', // 状态ID
                        'end_time', // 任务截止时间
                        'description' // 任务描述
                    ] : '';
                    $mustConfigData = $this->getMySchedulerAddBaseMustFields($allowModuleList);

                    $mustConfig = $mustConfigData['must'];
                    $requiredSetting = $mustConfigData['required'];
                    $userFieldConfig = !empty($userConfigData['config']['fields']) ? $userConfigData["config"]['fields'] : $mustConfigData['must'];
                } else {
                    $mustConfig = $mustFields;
                    $userFieldConfig = !empty($userConfigData['config']['fields']) ? $userConfigData["config"]['fields'] : $mustFields;
                }

                break;
            case "update_panel":
                $userFieldConfig = !empty($userConfigData['config']['fields']) ? $userConfigData["config"]['fields'] : [];
                break;
        }

        // 获取系统字段配置
        $fieldFormulaConfigSettings = (new OptionsService())->getFormulaFieldNameConfigData();

        if (!empty($param['field_list_type'])) {
            return ['field_list' => $fieldList, 'field_formula_config' => $fieldFormulaConfigSettings, 'user_setting' => $userFieldConfig, 'must_setting' => $mustConfig, 'required_setting' => $requiredSetting, 'keep_status' => $keepStatus];
        } else {
            $fieldConfigData["field_clean_data"]["field_auth_config"]['keep_status'] = $keepStatus;
            $fieldConfigData["field_clean_data"]["field_auth_config"]['field_formula_config'] = $fieldFormulaConfigSettings;
            return $fieldConfigData["field_clean_data"]["field_auth_config"];
        }
    }

    /**
     * 获取我的日程页面添加任务必须字段
     * @param array $allowFieldList
     * @return array
     */
    public function getMySchedulerAddBaseMustFields($allowFieldList = [])
    {
        $fieldList = !empty($allowFieldList) ? $allowFieldList : [
            'name', // 任务名称
            'status_id', // 状态ID
            'end_time', // 任务截止时间
            'description' // 任务描述
        ];

        // 获取自定义字段
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();

        // 分派人
        $reviewedBy = $formulaConfigData['reviewed_by'];

        // 执行人
        $assignee = $formulaConfigData['assignee_field'];

        // 获取当前字段自定义属性
        $variableModel = new VariableModel();
        $variableData = $variableModel->field('id,code')
            ->where(['id' => ['IN', join(',', [$reviewedBy, $assignee])]])
            ->select();

        $variableIdMap = array_column($variableData, 'code', 'id');

        $fieldList[] = $variableIdMap[$reviewedBy];
        $fieldList[] = $variableIdMap[$assignee];


        $requiredFieldList = [];

        foreach ($fieldList as &$item) {
            if (in_array($item, ['project_id'])) {
                $requiredFieldList[] = 'base-' . $item;
            }
            $item = 'base-' . $item;
        }

        return ['must' => $fieldList, 'required' => $requiredFieldList];
    }

    /**
     * 获取可导入的字段
     * @param $param
     * @return array
     */
    public function getImportFields($param)
    {
        $moduleData = $this->getModuleData($param["module_id"], "");
        // 获取schema_id
        $schemaId = $this->schemaService->getPageSchemaId($moduleData["type"], $param['schema_page'], 0);
        // 字段配置数据
        $fieldConfigData = $this->getSchemaConfig($param, $schemaId, "view");

        $moduleBaseSchemaConfig = [];
        $fieldSchemaConfig = [];
        // 将 built_in 的数据 和 custom 的数据 合并在一起
        foreach ($fieldConfigData["field_clean_data"]["field_auth_config"]['edit_list'] as $key => $schemaField) {
            if (in_array($key, [$moduleData["code"], "media", "role_user", "base"])) {
                if (!empty($schemaField['custom']["fields"])) {
                    $customFieldData = [];
                    foreach ($schemaField['custom']["fields"] as $customFields) {
                        if (!in_array($customFields["type"], ["belong_to"])) {
                            array_push($customFieldData, $customFields);
                        }
                    }
                    $fieldSchemaConfig[] = array_merge($schemaField['built_in']["fields"], $customFieldData);
                } else {
                    $fieldSchemaConfig[] = $schemaField['built_in']["fields"];
                }
            }
        }
        foreach ($fieldSchemaConfig as $fieldItem) {
            if (!empty($fieldItem)) {
                foreach ($fieldItem as $v) {
                    $moduleBaseSchemaConfig[] = $v;
                }
            }
        }

        $primaryKey = "";
        $gridColumnConfig = ["columns_field_config" => [], "fields" => []];
        // 可编辑字段
        foreach ($moduleBaseSchemaConfig as $key => $item) {
            // 是否存在必须字段值，不存在默认赋值为no
            if (!array_key_exists("require", $item)) {
                $item["require"] = "no";
            }

            if (array_key_exists("is_primary_key", $item) && $item["is_primary_key"] == "yes") {
                $primaryKey = $item["fields"];
            }

            array_push($gridColumnConfig['fields'], $item);
            $gridColumnConfig['columns_field_config'][$item["value_show"]] = $this->getFieldColumnsMapConfig($item, $primaryKey, $moduleData['code']);
        }

        return $gridColumnConfig;
    }

    /**
     * 获取数据结构字段
     * @param $param
     * @param $schemaId
     * @return array
     */
    protected function getViewSchemaFields($param, $schemaId)
    {
        // 获取数据结构字段
        $schemaFieldData = $this->schemaService->getSchemaFields($param, $schemaId);

        // 数据结构字段
        $schemaFields = $schemaFieldData["schema_fields"];

        // 数据结构
        $relationStructure = $schemaFieldData["relation_structure"];

        // 得到根据视图权限，处理后的数据结构
        $fieldCleanData = $this->getFieldCleanConfig($param, $schemaFields, $relationStructure);

        $resData = [
            "field_clean_data" => $fieldCleanData,
            "relation_structure" => $relationStructure
        ];

        return $resData;
    }

    /**
     * 得到根据视图权限，处理后的数据结构
     * @param $param
     * @param $schemaFields
     * @param $relationStructure
     * @return array
     */
    private function getFieldCleanConfig($param, $schemaFields, $relationStructure)
    {
        $tempFieldConfig = [];
        if (array_key_exists("filter", $param)) {
            $tempFieldConfig = array_key_exists("temp_fields", $param['filter']) ? $param['filter']["temp_fields"] : [];
        }
        $param['page'] = empty($param['page']) ? "" : $param['page'];

        $viewFieldConfig = $this->getViewFieldConfig($param);

        $viewFieldLimitData = [
            // 哪些排序被选中
            'sort_checked' => [],
            // 哪些分组被选中
            'group_checked' => [],
            // 哪些字段可以被显示
            'allow_show_fields' => [],
            // 哪些工序可以被选中
            'step_checked' => []
        ];

        if (!empty($viewFieldConfig)) {
            // 排序被选中字段
            if (array_key_exists('sort_data', $viewFieldConfig["sort"])) {
                foreach ($viewFieldConfig["sort"]["sort_query"] as $key => $sortValue) {
                    array_push($viewFieldLimitData['sort_checked'], $key);
                }
            }

            // 分组字段
            foreach ($viewFieldConfig["group"] as $key => $groupValue) {
                $groupArray = explode('_', $key);
                if (in_array("value", $groupArray)) {
                    // 自定义字段
                    $groupKey = $key;
                } else {
                    // 固定字段
                    $groupKey = $groupArray[0] . '_' . $groupArray[1];
                }
                array_push($viewFieldLimitData['group_checked'], $groupKey);
            }

            if (!empty($param["view_type"])) {
                // 可以显示字段
                switch ($param["view_type"]) {
                    case "grid":
                        $dataIndex = count($viewFieldConfig["fields"]);
                        if ($dataIndex > 2) {
                            foreach ($viewFieldConfig["fields"] as $key => $columns) {
                                $viewFieldLimitData['allow_show_fields'][$columns['field']] = $columns;
                            }
                        } else {
                            foreach ($viewFieldConfig["fields"][1] as $key => $columns) {
                                $viewFieldLimitData['allow_show_fields'][$columns['field']] = $columns;
                            }
                        }
                        break;
                    default:
                        foreach ($viewFieldConfig["fields"] as $columns) {
                            $viewFieldLimitData['allow_show_fields'][$columns["field"]] = $columns;
                        }
                        break;
                }
            }
        }

        $fieldAuthConfig = $this->getFieldAuthConfig($param, [
            'schema_fields' => $schemaFields,
            'view_field_limit_data' => $viewFieldLimitData,
            'temp_field_config' => $tempFieldConfig,
            'relation_structure' => $relationStructure
        ]);

        $fieldLimitData = [
            'view_config' => $viewFieldConfig,
            'schema_fields' => $schemaFields,
            'field_auth_config' => $fieldAuthConfig, //返回字段权限设置
        ];

        return $fieldLimitData;
    }


    /**
     * 判断字段权限
     * @param $moduleCode
     * @param $field
     * @param $mode
     * @param array $baseHorizontalUserData
     * @return bool
     */
    public function checkFieldPermission($moduleCode, $field, $mode, $baseHorizontalUserData = [])
    {
        // 获取字段权限字典
        if (empty($this->authFieldConfig)) {
            $this->authFieldConfig = $this->authService->getUserAllFieldPermission(session("user_id"));
        }

        if (array_key_exists($moduleCode, $this->authFieldConfig)) {

            // 任务关联用户模块
            if ($moduleCode === 'base' && $this->authService->checkBaseRelatedUserPermission($field, $mode, $baseHorizontalUserData)) {
                return true;
            }

            if (array_key_exists($field, $this->authFieldConfig[$moduleCode])) {
                $fieldAuthPermissionConfig = $this->authFieldConfig[$moduleCode][$field]["permission"];
                if ($fieldAuthPermissionConfig[$mode] === "allow") {
                    return true;
                } else {
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * 判断表格字段权限
     * @param $field
     * @param array $baseHorizontalUserData
     * @return mixed
     */
    public function checkTableFieldAuth($field, $baseHorizontalUserData = [])
    {
        if ($this->checkFieldPermission($field['module_code'], $field["fields"], "view", $baseHorizontalUserData)) {
            $field['show'] = 'yes';
        } else {
            $field['show'] = 'no';
        }

        if ($field['show'] === 'yes' && $this->checkFieldPermission($field['module_code'], $field["fields"], "modify", $baseHorizontalUserData)) {
            $field['edit'] = 'allow';
        } else {
            $field['edit'] = 'deny';
        }
        return $field;
    }

    /**
     * 判断是否能应用
     * @param $fieldParam
     * @param array $moduleData
     * @return bool
     */
    private function checkWhetherUseKanbanGroup($fieldParam, $moduleData = [])
    {
        if (in_array($fieldParam['module_code'], ['status'])) {
            return 'yes';
        } else {
            return 'no';
        }
    }

    /**
     * 获取当前用户在当前模块的字段权限
     * @param $param
     * @param $schemaFields
     * @return array
     */
    private function getFieldAuthConfig($param, $schemaFields)
    {
        $moduleType = $schemaFields["relation_structure"]["module_type"];

        // 获取权限配置
        $moduleData = $this->getModuleData($param['module_id'], "");
        $hasManyDataMap = $this->schemaService->getHasManyConfigDataMap($schemaFields["relation_structure"]["relation_has_many"], $moduleData);

        $relationHasMany = $schemaFields["relation_structure"]["relation_has_many"];

        // 获取模版配置
        $templateService = new TemplateService();
        $templateConfig = $templateService->getGridViewTemplateConfig([
            "filter" => ["project_id" => $param["project_id"]],
            "module_code" => $moduleData["code"]
        ]);

        // 获取实体父级
        $entityParentModule = $this->schemaService->getEntityBelongParentModule([
            "module_code" => $moduleData["code"]
        ]);

        // 返回字段配置结构
        $allowFieldData = [
            // 后端查询数据
            'query_config' => [],
            // 前端返回数据
            'show_list' => [],
            'sort_list' => [],
            'group_list' => [],
            'filter_list' => [],
            'search_list' => [],
            'edit_list' => [],
            'create_list' => []
        ];

        $allowFieldConfigKey = ['query_config', 'show_list', 'sort_list', 'group_list', 'filter_list', 'edit_list', 'search_list', 'create_list'];

        // 获取任务关联用户数据
        $baseService = new BaseService();
        $baseHorizontalUserData = $baseService->getBaseHorizontalUserAuthData(check_param_int_empty($param, 'item_id'));

        // 组装可用字段
        foreach ($schemaFields["schema_fields"] as $key => $schemaField) {

            $langKey = $schemaField["type"] == "fixed" ? string_initial_letter($key, "_") : string_initial_letter($schemaField["code"], "_");

            $moduleBuiltInTitle = L($langKey); // 固定字段标题
            $moduleCustomTitle = L($langKey . '_Custom'); // 自定义字段标题

            $moduleCode = $schemaField["code"];
            foreach ($allowFieldConfigKey as $keyItem) {
                if ($keyItem === "query_config") {
                    $allowFieldData[$keyItem][$key] = ['type' => $schemaField["type"], 'built_in' => [], 'custom' => []];
                } else {
                    $allowFieldData[$keyItem][$key] = ['type' => $schemaField["type"], 'built_in' => ['title' => $moduleBuiltInTitle, 'fields' => []], 'custom' => ['title' => $moduleCustomTitle, 'fields' => []]];
                }
            }
            $schemaField["field_configs"]["built_in"] = empty($schemaField["field_configs"]["built_in"]) ? [] : $schemaField["field_configs"]["built_in"];

            // 内置字段
            foreach ($schemaField["field_configs"]["built_in"] as $builtInField) {
                $builtInField['belong_module'] = $moduleData["code"];

                // 判断has many直接关联是否显示
                $fieldColumnsShow = [
                    "show" => true,
                    "is_has_many" => false
                ];
                if (array_key_exists($builtInField["module_code"], $hasManyDataMap)) {
                    $fieldColumnsShow = $hasManyDataMap[$builtInField["module_code"]];
                    $fieldColumnsShow["is_has_many"] = true;
                }

                // 组装字段格式
                $valueShowFields = $this->schemaService->getFieldColumnName($builtInField, $moduleData["code"]);
                // 处理语言包
                if ($moduleType === "entity" && $builtInField["fields"] === "parent_id") {
                    if (!empty($entityParentModule)) {
                        $lang = L($entityParentModule["code"]);
                    } else {
                        $builtInField["show"] = "no";
                    }
                } else {
                    if (array_key_exists("outreach_lang", $builtInField) && !empty($builtInField["outreach_lang"])) {
                        $lang = L($builtInField["outreach_lang"]);
                    } else {
                        $lang = L($builtInField["lang"]);
                    }
                }

                // 处理关联表编辑控件
                if ($moduleData["code"] !== $builtInField["module_code"]) {
                    if (array_key_exists("outreach_editor", $builtInField) && $builtInField["outreach_editor"] !== "none") {
                        $builtInField['editor'] = $builtInField['outreach_editor'];
                    }
                }
                $builtInField['is_checked'] = false;
                $builtInField['project_id'] = $param["project_id"];
                $builtInField['module_id'] = $param["module_id"];
                $builtInField["lang"] = $lang;
                $builtInField['belong'] = $builtInField["module"];
                $builtInField['value_show'] = $valueShowFields;
                $builtInField['custom_type'] = "";
                $builtInField['custom_config'] = [];
                $builtInField['is_has_many'] = $this->getFieldIsHasMany($builtInField);
                $builtInField["frozen_module"] = $moduleData["code"];

                if ($fieldColumnsShow["is_has_many"]) {
                    // 处理一对多关联字段显示字段
                    if ($builtInField["fields"] !== "name") {
                        continue;
                    }
                    $builtInField["value_show"] = "{$moduleData["code"]}_{$fieldColumnsShow["module_code"]}";
                }

                // 前端显示字段key值
                $viewKey = $builtInField['value_show'];

                // 追加固定模块和本身模块标识
                if (!array_key_exists("is_primary_key", $builtInField) || $builtInField['is_primary_key'] === "no") {
                    $fieldsExplode = explode("_", $builtInField["fields"]);
                    if (count($fieldsExplode) > 1 && end($fieldsExplode) === "id") {
                        $builtInField['flg_module'] = $fieldsExplode[0];
                    }
                }

                // 显示权限为true，所有字段操作前提首先能显示
                if ($builtInField['show'] === 'yes' && $this->checkFieldPermission($key, $builtInField["fields"], "view", $baseHorizontalUserData)) {
                    if (empty($schemaFields["view_field_limit_data"]['allow_show_fields'])) {
                        $builtInField['is_checked'] = true;
                    } else {
                        if (array_key_exists($viewKey, $schemaFields["view_field_limit_data"]['allow_show_fields'])) {
                            $builtInField['is_checked'] = true;
                        }
                    }
                    if (!array_key_exists($key, $relationHasMany) || (array_key_exists($key, $relationHasMany) && !array_key_exists("belong_to_config", $relationHasMany[$key]))) {

                        // 可过滤字段
                        if ($builtInField['filter'] === 'allow') {

                            $builtInField["is_master"] = $builtInField["module_code"] === $moduleData["code"] ? "yes" : "no";
                            array_push($allowFieldData['filter_list'][$key]['built_in']['fields'], $builtInField);
                            // 查询字段
                            if (in_array($builtInField["type"], ["varchar", "char", "text"])) {
                                array_push($allowFieldData['search_list'][$key]["built_in"]['fields'], $builtInField);
                            }
                        }

                        if (!$fieldColumnsShow["show"]) {
                            // 过滤字段以外直接一对多关联字段不显示
                            continue;
                        } else {
                            if ($fieldColumnsShow["is_has_many"]) {
                                // 处理一对多关联字段显示字段
                                if ($builtInField["fields"] !== "name") {
                                    continue;
                                }
                                $builtInField["value_show"] = "{$moduleData["code"]}_{$fieldColumnsShow["module_code"]}";
                            }
                        }

                        // 判断外联显示（外联表使用外联显示字段判断）
                        if (($moduleData["code"] === $key)
                            || ($moduleData["code"] !== $key && array_key_exists("outreach_display", $builtInField) && $builtInField["outreach_display"] === "yes")) {
                            array_push($allowFieldData['query_config'][$key]['built_in'], $builtInField);
                            array_push($allowFieldData['show_list'][$key]['built_in']['fields'], $builtInField);
                        }

                        // 可排序字段
                        if ($builtInField['sort'] === 'allow') {
                            $builtInField['is_checked'] = in_array($viewKey, $schemaFields["view_field_limit_data"]['sort_checked']) ? true : false;
                            array_push($allowFieldData['sort_list'][$key]['built_in']['fields'], $builtInField);
                        }

                        // 可分组字段
                        if ($builtInField['allow_group'] === 'allow') {
                            if ($builtInField['module_code'] === $moduleData["code"]) {
                                $groupCheckKey = "{$moduleData["code"]}_{$builtInField['fields']}";
                            } else {
                                $groupCheckKey = "{$moduleData["code"]}_{$builtInField['module_code']}";
                            }

                            $builtInField['is_checked'] = in_array($groupCheckKey, $schemaFields["view_field_limit_data"]['group_checked']) ? true : false;
                            $builtInField['can_use_kanban_view'] = $this->checkWhetherUseKanbanGroup($builtInField);
                            array_push($allowFieldData['group_list'][$key]['built_in']['fields'], $builtInField);
                        }
                    }
                }

                // 可修改字段
                if ($builtInField['edit'] === 'allow' && $this->checkFieldPermission($key, $builtInField["fields"], "modify", $baseHorizontalUserData)) {
                    array_push($allowFieldData['edit_list'][$key]['built_in']['fields'], $builtInField);
                }

                // 可创建字段
                if ($this->checkFieldPermission($key, $builtInField["fields"], "create", $baseHorizontalUserData)) {
                    array_push($allowFieldData['create_list'][$key]['built_in']['fields'], $builtInField);
                }
            }

            // 自定义字段
            foreach ($schemaField["field_configs"]["custom"] as $customField) {
                // 判断has many直接关联是否显示
                $fieldColumnsShow = [
                    "show" => true,
                    "is_has_many" => false
                ];

                // 处理是否为has_many
                if (array_key_exists($customField["module_code"], $hasManyDataMap)) {
                    $fieldColumnsShow = $hasManyDataMap[$customField["module_code"]];
                    $fieldColumnsShow["is_has_many"] = true;
                }

                // 处理水平关联字段
                if (in_array($customField["type"], ["belong_to", "horizontal_relationship"])) {

                    if ($customField["type"] === "horizontal_relationship" && (array_key_exists('relation_type', $customField) && $customField['relation_type'] === 'has_many')) {
                        $customField["editor"] = "tagbox";
                    }

                    // TODO 水平关联和belong_to 字段不允许排序分组
                    $customField["sort"] = "deny";
                    $customField["allow_group"] = "deny";

                    if ($customField["type"] === "horizontal_relationship" && (array_key_exists('relation_type', $customField) && $customField['relation_type'] === 'has_one')) {
                        $customField["allow_group"] = "allow";
                    }


                    // media 不允许过滤
                    if ($customField["relation_module_code"] == "media") {
                        $customField["filter"] = "deny";
                    }
                    $customField["data_source"] = $customField["type"];
                }

                // 组装字段格式
                $valueShowFields = $this->schemaService->getFieldColumnName($customField, $moduleData["code"]);

                $customField['is_checked'] = false;
                $customField['project_id'] = $param["project_id"];
                $customField['module_type'] = $schemaField["type"];
                $customField['belong'] = $moduleCode;
                $customField['module'] = $moduleCode;
                $customField['value_show'] = $valueShowFields;
                $customField['custom_type'] = $customField["type"];
                $customField['custom_config'] = $this->getFieldCustomConfig($customField);
                $customField['is_has_many'] = $this->getFieldIsHasMany($customField);
                $customField["is_master"] = $customField["module_code"] === $moduleData["code"] ? "yes" : "no";

                $viewKey = $valueShowFields;

                if (empty($schemaFields["view_field_limit_data"]['allow_show_fields'])) {
                    $customField['is_checked'] = true;
                } else {
                    if (array_key_exists($viewKey, $schemaFields["view_field_limit_data"]['allow_show_fields'])) {
                        $customField['is_checked'] = true;
                    }
                }

                // 显示权限为true，所以字段操作前提首先能显示
                if ($this->checkFieldPermission($key, $customField["fields"], "view", $baseHorizontalUserData)) {
                    // 可过滤字段
                    if ($customField['filter'] === 'allow') {
                        // 单独处理实体下任务过滤字段 && !empty($templateConfig["step_fields"])
                        if ($moduleType === "entity" && $key === "base") {
                            if (!empty($templateConfig["step_fields"])) {
                                $stepFieldMapData = array_column($templateConfig["step_fields"], null, "fields");
                                if (array_key_exists($customField["fields"], $stepFieldMapData)) {
                                    $stepFieldMapData[$customField["fields"]]['project_id'] = $param["project_id"];
                                    $stepFieldMapData[$customField["fields"]]["is_master"] = $customField["module_code"] === $moduleData["code"] ? "yes" : "no";
                                    array_push($allowFieldData['filter_list'][$key]["custom"]['fields'], $stepFieldMapData[$customField["fields"]]);
                                    // 可查询字段
                                    if (in_array($stepFieldMapData[$customField["fields"]]["type"], ["varchar", "char", "text"])) {
                                        array_push($allowFieldData['search_list'][$key]["custom"]['fields'], $stepFieldMapData[$customField["fields"]]);
                                    }
                                }
                            }
                        } else {
                            array_push($allowFieldData['filter_list'][$key]["custom"]['fields'], $customField);
                            // 可查询字段
                            if (in_array($customField["type"], ["varchar", "char", "text"])) {
                                array_push($allowFieldData['search_list'][$key]["custom"]['fields'], $customField);
                            }
                        }
                    }

                    if (!$fieldColumnsShow["show"]) {
                        // 过滤字段以外直接一对多关联字段不显示
                        continue;
                    }

                    // 可以在过滤盒子过滤字段
                    array_push($allowFieldData['query_config'][$key]["custom"], $customField);

                    // 可显示字段
                    array_push($allowFieldData['show_list'][$key]["custom"]['fields'], $customField);

                    // 可排序字段
                    if ($customField['sort'] === 'allow' && $customField['type'] !== 'horizontal_relationship') {
                        $customField['is_checked'] = in_array($viewKey, $schemaFields["view_field_limit_data"]['sort_checked']) ? true : false;
                        array_push($allowFieldData['sort_list'][$key]["custom"]['fields'], $customField);
                    }

                    // 可分组字段
                    if ($customField['allow_group'] === 'allow') {
                        $customField['is_checked'] = in_array($viewKey, $schemaFields["view_field_limit_data"]['group_checked']) ? true : false;

                        // 判断能不能作为看板视图 can_use_kanban_view
                        $viewModeFieldIds = (new OptionsService())->getViewModeConfigData();
                        if ($viewModeFieldIds !== false && array_key_exists($customField['variable_id'], $viewModeFieldIds)) {
                            $customField['can_use_kanban_view'] = 'yes';
                            $customField['kanban_view_mode'] = $viewModeFieldIds[$customField['variable_id']];
                        } else {
                            $customField['can_use_kanban_view'] = 'no';
                            $customField['kanban_view_mode'] = '';
                        }

                        array_push($allowFieldData['group_list'][$key]["custom"]['fields'], $customField);
                    }
                }

                // 可修改字段
                if ($customField['edit'] === 'allow' && $this->checkFieldPermission($key, $customField["fields"], "modify", $baseHorizontalUserData)) {
                    array_push($allowFieldData['edit_list'][$key]["custom"]['fields'], $customField);
                }

                // 可创建字段
                if ($this->checkFieldPermission($key, $customField["fields"], "create", $baseHorizontalUserData)) {
                    array_push($allowFieldData['create_list'][$key]['custom']['fields'], $customField);
                }
            }
        }

        return $allowFieldData;
    }

    /**
     * 获取两层列默认配置
     * @return mixed
     */
    protected function getTowColumnsConfig()
    {
        $columnConfig["column_index"] = 1;
        $columnConfig["noFrozenColumns"] = [[], []];
        $columnConfig["frozenColumns"] = [
            [
                ['colspan' => 1]
            ],
            [
                ['field' => 'id', 'align' => 'center', 'checkbox' => true]
            ]
        ];
        $columnConfig["columns"] = [[], []];
        return $columnConfig;
    }

    /**
     * 生成前台数据表格列显示配置
     * @param $param
     * @param $moduleData
     * @return array
     */
    protected function generateGridColumnConfig($param, $moduleData)
    {

        if (array_key_exists("schema_page", $param) && !empty($param["schema_page"])) {

            // 声明变量
            $stepConfig = [];
            $columnNumber = "one";

            // 默认获取系统模块结构、字段配置

            $templateId = empty($param['template_id']) ? 0 : $param['template_id'];
            // 获取当前 Module schema_id
            $schemaId = $this->schemaService->getPageSchemaId($moduleData['type'], $param["schema_page"], $templateId);
            // 当前数据结构配置
            $moduleSchemaConfig = $this->getSchemaConfig($param, $schemaId, 'view');
            if (array_key_exists("side_bar", $param) && $param["side_bar"]) {
                $schemaFields = [];
                foreach ($moduleSchemaConfig["field_clean_data"]["schema_fields"] as $key => $itemFields) {
                    if (in_array($key, [$moduleData["code"], "status", "media"])) {
                        $schemaFields[$key] = $itemFields;
                    }
                }
                $moduleSchemaConfig["field_clean_data"]["schema_fields"] = $schemaFields;
            }
            // 组装字段数据
            $moduleBaseSchemaConfig = $this->schemaService->generateColumnsConfig($moduleSchemaConfig, $moduleData);

            $relationStructure = $moduleSchemaConfig['relation_structure'];
            $viewConfig = $moduleSchemaConfig['field_clean_data']['view_config'];

            $groupConfig = [];
            $sortConfig = ["sort_data" => [], "sort_query" => []];
            $filterConfig = [];
            $haveUserViewSetting = false;
            $columnConfig = [
                "column_index" => 0,
                "noFrozenColumns" => [],
                "frozenColumns" => [
                    [['field' => 'id', 'align' => 'center', 'checkbox' => true]]
                ],
                "columns" => [
                    []
                ],
            ];

            if (!array_key_exists("side_bar", $param)) {
                if ($param['project_id'] > 0) {
                    // 获取项目模板配置
                    $templateService = new TemplateService();
                    $moduleTemplateConfig = $templateService->getGridViewTemplateConfig([
                        'filter' => [
                            "project_id" => $param['project_id']
                        ],
                        'module_code' => $moduleData["code"]
                    ]);

                    if (!empty($moduleTemplateConfig["step_list"])) {
                        $columnNumber = "two";
                        $stepConfig = ["step_list" => $moduleTemplateConfig["step_list"], "step_fields" => $moduleTemplateConfig["step_fields"]];
                        $columnConfig = $this->getTowColumnsConfig();
                    }

                    $groupConfig = $moduleTemplateConfig["group"];
                    $sortConfig = $moduleTemplateConfig["sort"];
                }

                if (!empty($viewConfig)) {
                    // 获取视图配置数据

                    if (array_key_exists("group", $viewConfig) && !empty($viewConfig["group"])) {
                        $groupConfig = $viewConfig["group"];
                    }

                    if (array_key_exists("sort", $viewConfig) && !empty($viewConfig["sort"])) {
                        $sortConfig = $viewConfig["sort"];
                    }

                    if (array_key_exists("filter", $viewConfig) && !empty($viewConfig["filter"])) {
                        $filterConfig = $viewConfig["filter"];
                    }

                    // 获取整合用户视图数据
                    $haveUserViewSetting = true;
                    $this->getUserViewColumnConfigDictData($viewConfig);

                    if (count($viewConfig["fields"]) === 2) {
                        $columnNumber = "two";
                    }
                }
            }
            // 当前过滤面板是否显示
            $userService = new UserService();
            $filterBarAllowShow = $userService->getUserFilterBarConfig($param['page']);

            // 动态列设置
            $gridConfigData = [
                'column_number' => $columnNumber,
                'module_data' => $moduleData,
                'grid_column_config' => [
                    "columnsFieldConfig" => [],
                    "column_index" => $columnConfig["column_index"],
                    "noFrozenColumns" => $columnConfig["noFrozenColumns"],
                    "frozenColumns" => $columnConfig["frozenColumns"],
                    "columns" => $columnConfig["columns"],
                    "sort_config" => $sortConfig,
                    "group_name" => $groupConfig,
                    "filter_config" => $filterConfig,
                    "frozen_field" => '',
                    "frozen_module" => '',
                    "filter_bar_show" => $filterBarAllowShow,
                    "step_columns_config" => $stepConfig,
                ],
                'module_base_config' => [
                    'has_user_view_config' => $haveUserViewSetting,
                    'module_base_config' => $moduleBaseSchemaConfig,
                    'relation_structure' => $relationStructure,
                    'view_config' => $viewConfig
                ],
                'step_config' => $stepConfig,
            ];

            return ['status' => 200, 'grid' => $this->generateColumnSchema($gridConfigData)];
        } else {
            // 不存在 schema_page 返回异常
            throw_strack_exception(L("Illegal_Operation"));
        }
    }

    /**
     * 生成详情页面顶部字段配置数据
     * @param $userId
     * @param $param
     * @param $fieldConfig
     * @return array
     */
    public function generateDetailsTopColumnsConfig($userId, $param, $fieldConfig)
    {
        // 获取用户个人配置
        $userService = new UserService();
        $userConfigTopFields = $userService->getUserCustomConfig([
            "user_id" => $userId,
            "template_id" => $param["template_id"],
            "type" => $param["category"],
            "page" => $param["module_code"]
        ]);

        if (array_key_exists("config", $userConfigTopFields) && !empty($userConfigTopFields["config"])) {
            $topFieldConfig = $userConfigTopFields["config"];
        } else {
            // 根据项目模板找出当前模块可以显示字段配置
            $templateService = new TemplateService();
            $topFieldConfig = $templateService->getTemplateConfig([
                'filter' => ["project_id" => $param['project_id']], "module_code" => $param["module_code"], "category" => $param["category"]
            ]);
        }

        $resFieldConfig = [];
        if (!empty($topFieldConfig)) {

            // 获取任务关联用户数据
            $baseService = new BaseService();
            $baseHorizontalUserData = $baseService->getBaseHorizontalUserAuthData(check_param_int_empty($param, 'item_id'));

            foreach ($fieldConfig as $key => $fieldItem) {
                // 自定义字段不需要格式语言包
                if ($fieldItem["field_type"] !== "custom") {
                    $fieldItem["lang"] = L($fieldItem["lang"]);
                }
                $valueShowKey = $this->schemaService->getFieldColumnName($fieldItem, $param["module_code"]);
                $fieldItem["value_show"] = $valueShowKey;
                foreach ($topFieldConfig as $mainKey => $item) {
                    if ($item["fields"] === $fieldItem["fields"] && $fieldItem["module_code"] === $item["module_code"]) {
                        $fieldItem["index"] = $mainKey;
                        if (array_key_exists("is_foreign_key", $fieldItem) && $fieldItem["is_foreign_key"] === "yes") {

                            if (array_key_exists("foreign_key_map", $fieldItem) && !empty($fieldItem["foreign_key_map"])) {
                                $fieldItem["flg_module"] = str_replace("_id", "", $fieldItem["foreign_key_map"]);
                                $fieldItem["foreign_key"] = $fieldItem["foreign_key_map"];
                            } else {
                                $fieldItem["flg_module"] = str_replace("_id", "", $fieldItem["fields"]);
                                $fieldItem["foreign_key"] = $fieldItem["fields"];
                            }

                            $lang = L($fieldItem["fields"]);
                            $belongModule = L($fieldItem["module_code"]);
                            $fieldItem["lang"] = "{$lang} ({$belongModule})";
                            $fieldItem["frozen_module"] = $param["module_code"];
                            $fieldItem["title"] = $fieldItem["flg_module"];
                        }

                        // 判断权限
                        $resFieldConfig[] = $this->checkTableFieldAuth($fieldItem, $baseHorizontalUserData);
                    }
                }
            }
        }

        $resFieldConfig = array_sort_by($resFieldConfig, "index");
        return $resFieldConfig;
    }

    /**
     * 获取用户视图数据，组装字段引用字典
     * @param $viewConfig
     * @return mixed
     */
    protected function getUserViewColumnConfigDictData(&$viewConfig)
    {
        if (count($viewConfig['fields']) === 2) {
            // 两层表头，带工序的实体数据表格
            $firstFieldsMap = [];
            foreach ($viewConfig['fields'][0] as $firstColumns) {
                if (array_key_exists("step", $firstColumns) && $firstColumns["step"] === "yes") {
                    $firstFieldsMap[$firstColumns["but"]] = $firstColumns;
                }
            }
            $secondFieldsMap = array_column($viewConfig['fields'][1], NULL, 'field');
            $viewConfig['fields'] = [$firstFieldsMap, $secondFieldsMap];
        } else {
            // 一层表头，数据表格
            $fieldsMap = array_column($viewConfig['fields'], NULL, 'field');
            $viewConfig['fields'] = $fieldsMap;
        }
        return $viewConfig;
    }

    /**
     * 获取字段主键
     * @param $field
     * @param bool $isForeign
     * @return string
     */
    protected function getFieldPrimaryKey($field, $isForeign = false)
    {
        if (!$isForeign) {
            return "{$field["module_code"]}_id";
        } else {
            if (array_key_exists("is_has_many", $field) && $field["is_has_many"]) {
                // 一对多主键
                return "{$field["belong_module"]}_id";
            } else {
                if (array_key_exists("belong_module", $field)) {
                    return "{$field["belong_module"]}_id";
                } else {
                    return "{$field["from_module_code"]}_id";
                }
            }
        }
    }

    /**
     * 获取字段标题
     * @param $field
     * @param bool $isForeign
     * @return mixed|string
     */
    protected function getFieldTitle($field, $isForeign = false)
    {
        $title = $field["field_type"] == 'custom' ? $field["lang"] : L($field["lang"]);

        if ($isForeign) {
            $belongTitle = L(string_initial_letter($field["module_code"], '_'));
            if (array_key_exists("belong_module", $field) && $field["belong_module"] === "base" && $field["module"] === "module") {
                $entity = L("Entity");
                $title = "{$entity}{$belongTitle}  ({$belongTitle})";
            } else {
                $title = "{$title} ({$belongTitle})";
            }
        }

        return $title;
    }

    /**
     * 获取字段宽度
     * @param $field
     * @param bool $isForeign
     * @return int
     */
    protected function getFieldWidth($field, $isForeign = false)
    {
        $width = 140;
        if (array_key_exists("width", $field)) {
            $width = $field["width"];
        }

        if ($isForeign && array_key_exists("format", $field) && $field["format"] !== "") {
            $width += 90;
        }

        return $width;
    }

    /**
     * 获取字段格式化配置
     * @param $field
     * @param bool $isForeign
     * @return string
     */
    protected function getFieldFormat($field, $isForeign = false)
    {
        if ($isForeign) {
            return array_key_exists("outreach_formatter", $field) ? htmlspecialchars_decode($field["outreach_formatter"]) : "";
        } else {
            return array_key_exists("formatter", $field) ? htmlspecialchars_decode($field["formatter"]) : "";
        }
    }

    /**
     * 获取自定义字段配置ID
     * @param $field
     * @return int
     */
    protected function getFieldVariableId($field)
    {
        return $field["field_type"] == "custom" ? (int)$field["variable_id"] : 0;
    }

    /**
     * 获取当前字段是否可以排序
     * @param $field
     * @return bool
     */
    protected function getFieldSortable($field)
    {
        if (array_key_exists("is_has_many", $field) && $field["is_has_many"]) {
            return false;
        }

        if ($field["field_type"] === "custom" && in_array($field["type"], ["belong_to", "horizontal_relationship"])) {
            return false;
        }

        return $field["sort"] == "allow" ? true : false;
    }

    /**
     * 获取当前字段是否可以排序
     * @param $field
     * @return bool
     */
    protected function getFieldGroup($field)
    {
        if (array_key_exists("is_has_many", $field) && $field["is_has_many"]) {
            return 'deny';
        }

        return $field["group"];
    }

    /**
     * 获取字段控件数据来源
     * @param $field
     * @return string
     */
    protected function getFieldDataSource($field)
    {
        return array_key_exists("data_source", $field) ? $field["data_source"] : "";
    }

    /**
     * 获取字段编辑控件
     * @param $field
     * @param bool $isForeign
     * @param bool $isStep
     * @return array|string
     */
    protected function getFieldEditor($field, $isForeign = false, $isStep = false)
    {
        if ($this->checkFieldPermission($field["module_code"], $field["fields"], "modify")) {
            if ($field["field_type"] == 'custom' && $field["type"] == 'horizontal_relationship') {
                if (!$isForeign) {
                    // 主表处理
                    $editor = $field["editor"];
                    if (array_key_exists('relation_type', $field) && $field['relation_type'] === 'has_one') {
                        $editor = 'combobox';
                    }
                    return ['type' => $editor, 'relation_module_id' => $field['relation_module_id']];
                } else {
                    // 关联表处理
                    return "";
                }
            } else {
                if (!$isForeign) {
                    if ($isStep) {
                        return $field["edit"] === 'allow' ? $field["editor"] : 'none';
                    } else {
                        return $field["edit"] === 'allow' ? ['type' => $field["editor"]] : '';
                    }
                } else {
                    if ($isStep) {
                        return !empty($field["outreach_editor"]) && $field["outreach_editor"] !== 'none' ? $field["outreach_editor"] : 'none';
                    } else {
                        return !empty($field["outreach_editor"]) && $field["outreach_editor"] !== 'none' ? ['type' => $field["outreach_editor"]] : '';
                    }
                }
            }
        }
        return '';
    }

    /**
     * 获取字段所属映射模块Code
     * @param $field
     * @param $masterCode
     * @param bool $isForeign
     * @return mixed
     */
    protected function getFieldFrozenModule($field, $masterCode, $isForeign = false)
    {
        if (!$isForeign) {
            return $masterCode;
        } else {
            if (array_key_exists("frozen_module", $field) && !empty($field["frozen_module"])) {
                return $field["frozen_module"];
            }

            if (array_key_exists("belong_module", $field) && !empty($field["belong_module"])) {
                return $field["belong_module"];
            }

            return '';
        }
    }

    /**
     * 获取字段是否为一对多
     * @param $field
     * @return string
     */
    private function getFieldIsHasMany($field)
    {
        if ($field["field_type"] === "custom") {
            $isHasMany = $field["type"] === "horizontal_relationship" ? "yes" : "no";
        } else {
            $isHasMany = array_key_exists("is_has_many", $field) ? "yes" : "no";
        }

        return $isHasMany;
    }

    /**
     * 获取自定义字段配置
     * @param $field
     * @return array
     */
    private function getFieldCustomConfig($field)
    {
        // 自定义字段并且为belong_to类型的才会有
        if ($field["field_type"] === "custom" && $field["type"] === "belong_to") {
            $customConfig = [
                "editor" => $field["editor"],
                "module_id" => $field["module_id"],
                "relation_module_id" => $field["relation_module_id"],
                "relation_module_code" => $field["relation_module_code"]
            ];
        } else {
            $customConfig = [];
        }

        return $customConfig;
    }

    /**
     * 获取字段列配置
     * @param $index
     * @param $field
     * @param $masterCode
     * @param bool $isForeign
     * @param bool $isStep
     * @return array
     */
    protected function getFieldColumnConfig($index, $field, $masterCode, $isForeign = false, $isStep = false)
    {
        $fieldsColumnConfig = [
            'field' => $this->schemaService->getFieldColumnName($field, $masterCode),
            'title' => $this->getFieldTitle($field, $isForeign),
            'align' => 'center',
            'width' => $this->getFieldWidth($field, $isForeign),
            'frozen' => "frozen",
            'findex' => $index,
            "field_type" => $field["field_type"],
            "group" => $this->getFieldGroup($field),
            "module" => $field["module"],
            "module_code" => $field["module_code"],
            "variable_id" => $this->getFieldVariableId($field),
            'drag' => true,
            "sortable" => $this->getFieldSortable($field),
            'editor' => $this->getFieldEditor($field, $isForeign, $isStep),
            'outreach_formatter' => $this->getFieldFormat($field, $isForeign),
            'data_source' => $this->getFieldDataSource($field),
            'frozen_module' => $this->getFieldFrozenModule($field, $masterCode, $isForeign),
            'is_has_many' => $this->getFieldIsHasMany($field),
            'custom_type' => $field["field_type"] === "custom" ? $field["type"] : "",
            'custom_config' => $this->getFieldCustomConfig($field)
        ];

        return $fieldsColumnConfig;
    }

    /**
     * 获取字段映射配置表
     * @param $fieldColumnConfig
     * @param string $primaryKey
     * @param string $masterCode
     * @param array $field
     * @return array
     */
    protected function getFieldColumnsMapConfig($fieldColumnConfig, $primaryKey = '', $masterCode = '', $field = [])
    {
        // 获取编辑器
        $editor = '';
        if (!empty($fieldColumnConfig["editor"])) {
            if (is_array($fieldColumnConfig["editor"])) {
                $editor = $fieldColumnConfig["editor"]['type'];
            } else {
                $editor = $fieldColumnConfig["editor"];
            }
        }

        // 获取映射字段
        if (!empty($field)) {
            if (array_key_exists("is_foreign_key", $field) && $field["is_foreign_key"] === "yes") {
                $fieldValueMap = $field["foreign_key"];
            } else {
                $fieldValueMap = $field["fields"] !== $field["value_show"] ? $field["fields"] : $field["value_show"];
            }
        } else {
            if (array_key_exists("is_foreign_key", $fieldColumnConfig) && $fieldColumnConfig["is_foreign_key"] === "yes") {
                $fieldValueMap = $fieldColumnConfig["foreign_key"];
            } else {
                $fieldValueMap = $fieldColumnConfig["fields"] !== $fieldColumnConfig["value_show"] ? $fieldColumnConfig["fields"] : $fieldColumnConfig["value_show"];
            }
        }

        // 自定义字段id值
        $variableId = 0;
        if (array_key_exists("variable_id", $fieldColumnConfig)) {
            $variableId = $fieldColumnConfig["variable_id"];
        }

        // 获取主键
        if (empty($primaryKey)) {
            $primaryKey = $this->getFieldPrimaryKey($fieldColumnConfig);
        }

        // 获取数据源
        $dataSource = '';
        if (array_key_exists("data_source", $fieldColumnConfig)) {
            $dataSource = $fieldColumnConfig["data_source"];
        }

        // 获取当前显示字段
        if (array_key_exists("field", $fieldColumnConfig)) {
            $fieldMap = $fieldColumnConfig["field"];
        } else {
            $fieldMap = $fieldColumnConfig["module_code"] . '_' . $fieldColumnConfig["fields"];
        }

        // 原始字段
        if (!empty($field)) {
            $fields = $field["fields"];
        } else {
            $fields = $fieldColumnConfig["fields"];
        }

        // 是否为一对多
        if (!empty($field)) {
            $isHasMany = $this->getFieldIsHasMany($field);
        } else {
            $isHasMany = $fieldColumnConfig["is_has_many"];
        }

        // 自定义字段类型
        if (!empty($field)) {
            $customType = $field["field_type"] === "custom" ? $field["type"] : "";
        } else {
            $customType = $fieldColumnConfig["custom_type"];
        }

        // 自定义字段配置
        if (!empty($field)) {
            $customConfig = $this->getFieldCustomConfig($field);
        } else {
            $customConfig = $fieldColumnConfig["custom_config"];
        }

        $fieldMapConfig = [
            'data_source' => $dataSource,
            'editor' => $editor,
            'fields' => $fields,
            'field_type' => $fieldColumnConfig["field_type"],
            'field_map' => $fieldMap,
            'field_value_map' => $fieldValueMap,
            'module' => $fieldColumnConfig["module"],
            'module_code' => $fieldColumnConfig["module_code"],
            'primary' => $primaryKey,
            'table' => string_initial_letter($fieldColumnConfig["module"]),
            'variable_id' => $variableId,
            'is_has_many' => $isHasMany,
            'custom_type' => $customType,
            'custom_config' => $customConfig
        ];

        return $fieldMapConfig;
    }

    /**
     * 获取工序列字段配置
     * @param $stepFields
     * @param $stepItem
     * @param array $param
     * @param bool $isView
     * @return array
     */
    protected function getStepColumnFieldMap($stepFields, $stepItem, $param = [], $isView = false)
    {
        if ($stepFields["module_code"] === "base") {
            $stepFormatFields = $this->getFieldColumnConfig($param['index'], $stepFields, "base", false, true);
            $stepFormatFields["table"] = $stepFields["table"];
        } else {
            $stepFormatFields = $this->getFieldColumnConfig($param['index'], $stepFields, "base", true, true);
            $stepFormatFields["is_foreign_key"] = 'yes';
            $stepFormatFields["foreign_key"] = "{$stepFields["module_code"]}_id";
            $stepFormatFields["frozen_module"] = 'base';
            $stepFormatFields["table"] = 'base';
        }

        if (in_array($stepFields["type"], ["horizontal_relationship", "belong_to"])) {
            if ($stepFields["type"] === "horizontal_relationship" && (array_key_exists('relation_type', $stepFields) && $stepFields['relation_type'] === 'has_many')) {
                $stepFormatFields["editor"] = "tagbox";
            }
            $stepFormatFields["editor_type"] = $stepFields["type"] === "belong_to" ? "belong_to" : "has_many";
            $stepFormatFields["data_source"] = $stepFields["type"];
            $stepFormatFields["table"] = $stepFields["module"];
        } else {
            $stepFormatFields["editor"] = $stepFields["editor"];
        }

        $stepBaseFieldName = $stepFormatFields["field"];

        if ($isView) {
            $stepFormatFields["fields"] = $stepFields["fields"];
            $stepFormatFields["field_type"] = $stepFields["field_type"];
            $stepFormatFields["value_show"] = $stepFields["value_show"];
            $stepFormatFields = array_merge($stepFormatFields, $stepItem);
            return ["step_fields" => $stepFormatFields, "step_base" => $stepBaseFieldName];
        } else {
            $stepFormatFields["field"] = "{$stepItem["code"]}_{$stepFormatFields["field"]}";
            $stepFormatFields["fields"] = $stepFields["fields"];
            $stepFormatFields["hidden"] = true;
            $stepFormatFields["drag"] = false;
            $stepFormatFields["step_index"] = '';
            $stepFormatFields["step"] = true;
            $stepFormatFields["belong"] = $stepItem["code"];
            $stepFormatFields["field_type"] = $stepFields["field_type"];
            $stepFormatFields["value_show"] = $stepFields["value_show"];

            //第一个元素
            if ($param['field_index'] == 0) {
                $stepFormatFields['bdc'] = $stepItem["color"];
                $stepFormatFields['cbd'] = "colboth";
                $stepFormatFields['cellClass'] = "datagrid-cell-c1-" . $stepFormatFields["field"];
                $stepFormatFields['deltaWidth'] = 1;
                $stepFormatFields['hidden'] = false;
                $stepFormatFields['step_index'] = 'first';
            }

            //最后一个元素
            if ($param['field_index'] == ($param["count_fields"] - 1)) {
                $stepFormatFields['bdc'] = $stepItem["color"];
                $stepFormatFields['cbd'] = "colright";
                $stepFormatFields['cellClass'] = "datagrid-cell-c1-" . $stepFormatFields["field"];
                $stepFormatFields['deltaWidth'] = 1;
                $stepFormatFields['step_index'] = 'last';
            }

            $stepFormatFields["frozen"] = "no_frozen";

            return ["step_fields" => $stepFormatFields, "step_base" => $stepBaseFieldName];
        }
    }

    /**
     *  一层数据表头结构
     * @param $gridData
     * @return mixed
     */
    protected function generateColumnSchema($gridData)
    {

        // 获取基础字段结构
        $moduleBaseConfig = $gridData['module_base_config'];
        $gridColumnConfig = $gridData['grid_column_config'];
        $columnIndex = $gridColumnConfig["column_index"];
        $frozenColumns = $gridColumnConfig["frozenColumns"];
        $noFrozenColumns = $gridColumnConfig["frozenColumns"];

        // 列索引
        $index = 0;

        // 判断是否是动态模块，如果是动态模块表名为他的类型，固定模块表名为他的编码
        $masterModuleCode = $gridData['module_data']['code'];
        $masterModuleTable = $gridData['module_data']['type'] === "entity" ? $gridData['module_data']['type'] : $masterModuleCode;

        foreach ($moduleBaseConfig["module_base_config"] as $item) {
            if (($item["show"] == "yes" && $this->checkFieldPermission($item["module_code"], $item["fields"], "view"))
                || ($item["show"] == "yes" && $item["module_code"] == "eventlog")
                || ($item["module_code"] === "base" && $item["fields"] === "entity_id")) {

                if ($masterModuleTable === "entity" && $item["module_code"] === "base" && array_key_exists("is_has_many", $item) && $item["is_has_many"]) {
                    continue;
                }

                // 处理主表列信息
                if ($item["module_code"] === $masterModuleCode) {
                    $primaryKey = $this->getFieldPrimaryKey($item);
                    $itemFields = $this->getFieldColumnConfig($index, $item, $masterModuleCode);
                    $itemFields['frozen_field'] = $primaryKey;
                    $gridColumnConfig['columnsFieldConfig'][$itemFields["field"]] = $this->getFieldColumnsMapConfig($itemFields, $primaryKey, $masterModuleCode, $item);
                } else { // 关联表列信息
                    $primaryKey = $this->getFieldPrimaryKey($item, true);
                    $itemFields = $this->getFieldColumnConfig($index, $item, $masterModuleCode, true);
                    $gridColumnConfig['columnsFieldConfig'][$itemFields["field"]] = $this->getFieldColumnsMapConfig($itemFields, $primaryKey, $masterModuleCode, $item);
                }

                // 存在自定义视图，不用考虑第一层数据结构
                if ($moduleBaseConfig["has_user_view_config"] == true) {
                    if ($gridData['column_number'] === "two") {
                        // 两层结构
                        if (array_key_exists($itemFields["field"], $moduleBaseConfig['view_config']["fields"][1])) {
                            $itemFields["width"] = $moduleBaseConfig['view_config']["fields"][1][$itemFields["field"]]['width'];
                            if ($moduleBaseConfig['view_config']["fields"][1][$itemFields["field"]]['frozen_status']) {
                                $itemFields["frozen"] = "nofrozen";
                                $frozenColumns[1][$itemFields["field"]] = $itemFields;
                            } else {
                                $noFrozenColumns[1][$itemFields["field"]] = $itemFields;
                            }
                        }
                    } else {
                        // 一层结构
                        if (array_key_exists($itemFields["field"], $moduleBaseConfig['view_config']["fields"])) {
                            $itemFields["width"] = $moduleBaseConfig['view_config']["fields"][$itemFields["field"]]['width'];
                            if ($moduleBaseConfig['view_config']["fields"][$itemFields["field"]]['frozen_status']) {
                                $itemFields["frozen"] = "nofrozen";
                                $frozenColumns[$itemFields["field"]] = $itemFields;
                            } else {
                                $noFrozenColumns[$itemFields["field"]] = $itemFields;
                            }
                        }
                    }
                } else {
                    if ($gridData['column_number'] == "two") {
                        //第一层
                        $endData = end($gridColumnConfig['columns'][0]);
                        if (!empty($gridColumnConfig['columns'][0]) && array_key_exists("colspan", $endData)) {
                            $gridColumnConfig['columns'][0][count($gridColumnConfig['columns'][0]) - 1]['colspan'] = $endData['colspan'] + 1;
                        } else {
                            array_push($gridColumnConfig['columns'][0], [
                                'bgc' => "",
                                'but' => "",
                                'class' => "",
                                'colspan' => 1,
                                'fhcol' => true,
                                'fname' => "",
                                'step' => "no",
                                'title' => ""
                            ]);
                        }
                        // 二层结构
                        array_push($gridColumnConfig['columns'][$columnIndex], $itemFields);
                    } else {
                        if (($masterModuleCode === $item["module_code"])
                            || ($masterModuleCode !== $item["module_code"] && array_key_exists("outreach_display", $item) && $item["outreach_display"] === "yes")) {
                            // 一层结构
                            array_push($gridColumnConfig['columns'][$columnIndex], $itemFields);
                        }
                    }
                }

                $index++;
            }
        }

        if ($moduleBaseConfig["has_user_view_config"] == true) {
            // 当为用户自定义视图时候重新排序
            if ($gridData['column_number'] == "two") {
                // 两层数据结构，第一层考虑字段权限问题重新生成或者判断删减
                // 1.生成第一层step数据索引
                $firstColumnStepMap = [];
                foreach ($moduleBaseConfig['view_config']["fields"][0] as $firstField) {
                    if (array_key_exists("step", $firstField) && $firstField["step"] == 'yes') {
                        $firstColumnStepMap[$firstField["fname"]] = $firstField;
                    }
                }

                // 2.遍历第二层字段数据
                $stepFieldsMap = [];
                foreach ($gridData['step_config']["step_fields"] as $stepFields) {

                    if ($stepFields["field_type"] === "custom") {
                        if (in_array($stepFields["type"], ["horizontal_relationship", "belong_to"])) {
                            $stepFieldsMap["{$stepFields['module_code']}_{$stepFields['fields']}"] = $stepFields;
                        } else {
                            $stepFieldsMap["{$stepFields['module_code']}_{$stepFields['fields']}_value"] = $stepFields;
                        }
                        $stepFieldsMap["{$stepFields['module_code']}_{$stepFields['fields']}_value"] = $stepFields;
                    } else {
                        if ($stepFields['module_code'] === "base") {
                            $stepFieldsMap["{$stepFields['module_code']}_{$stepFields['fields']}"] = $stepFields;
                        } else {
                            $stepFieldsMap["base_{$stepFields['module_code']}_{$stepFields['fields']}"] = $stepFields;
                        }
                    }
                }

                foreach ($moduleBaseConfig['view_config']["fields"][1] as $secondField) {
                    if (array_key_exists("frozen_status", $secondField) && $secondField["frozen_status"]) {
                        //冻结字段
                        if (array_key_exists($secondField["field"], $frozenColumns[1])) {
                            //视图应用给别人时候受权限影响可能当前字段不可见
                            array_push($gridColumnConfig['frozenColumns'][$columnIndex], $frozenColumns[1][$secondField["field"]]);
                        }
                    } else {
                        // 非冻结字段
                        $endData = end($gridColumnConfig['columns'][0]);
                        if (array_key_exists($secondField["field"], $noFrozenColumns[1])) {
                            // 视图应用给别人时候受权限影响可能当前字段不可见
                            array_push($gridColumnConfig['columns'][$columnIndex], $noFrozenColumns[1][$secondField["field"]]);
                            // 更新第一层非冻结字段columns
                            if (!empty($gridColumnConfig['columns'][0]) && array_key_exists("colspan", $endData) && $endData["step"] == "no") {
                                $gridColumnConfig['columns'][0][count($gridColumnConfig['columns'][0]) - 1]['colspan'] = $endData['colspan'] + 1;
                            } else {
                                array_push($gridColumnConfig['columns'][0], [
                                    'bgc' => "",
                                    'but' => "",
                                    'class' => "",
                                    'colspan' => 1,
                                    'fhcol' => true,
                                    'fname' => "",
                                    'step' => "no",
                                    'title' => ""
                                ]);
                            }
                        } else if (array_key_exists("step", $secondField) && $secondField["step"]) {
                            // 处理step字段

                            if ($endData["step"] == "no" || $endData["fname"] !== $secondField["belong"]) {
                                array_push($gridColumnConfig['columns'][0], $firstColumnStepMap[$secondField["belong"]]);
                            }

                            $stepKey = str_replace($secondField["belong"] . "_", "", $secondField["field"]);

                            $stepFormatData = $this->getStepColumnFieldMap($stepFieldsMap[$stepKey], $secondField, [
                                'index' => $index,
                            ], true);

                            $gridColumnConfig['columnsFieldConfig'][$stepFormatData["step_fields"]["field"]] = $stepFormatData["step_fields"];
                            $stepFormatData["step_fields"]["editor"] = '';
                            array_push($gridColumnConfig['columns'][$columnIndex], $stepFormatData["step_fields"]);
                        }
                    }
                }

                $gridColumnConfig['frozenColumns'][0][0]["colspan"] = count($gridColumnConfig['frozenColumns'][$columnIndex]);

            } else {
                // 一层数据结构
                foreach ($moduleBaseConfig['view_config']["fields"] as $field) {
                    if (array_key_exists("frozen_status", $field) && $field["frozen_status"]) {
                        if (array_key_exists($field["field"], $frozenColumns)) {
                            // 视图应用给别人时候受权限影响可能当前字段不可见
                            array_push($gridColumnConfig['frozenColumns'][$columnIndex], $frozenColumns[$field["field"]]);
                        }
                    } else {
                        if (array_key_exists($field["field"], $noFrozenColumns)) {
                            // 视图应用给别人时候受权限影响可能当前字段不可见
                            array_push($gridColumnConfig['columns'][$columnIndex], $noFrozenColumns[$field["field"]]);
                        }
                    }
                }
            }
        } else if ($gridData['column_number'] == "two") {
            // 没有保存自定义视图则需要额外加入 step 列
            $countFields = count($gridData['step_config']["step_fields"]);
            if ($countFields < 1 || empty($gridData['step_config']["step_list"])) {
                return false;
            }
            foreach ($gridData['step_config']["step_list"] as $stepItem) {
                // 第一个字段为折叠字段
                $fieldIndex = 0;
                $fieldList = [];

                // 工序第一个字段
                $stepFirstFields = $this->schemaService->getFieldColumnName($gridData['step_config']["step_fields"][0], "base");

                // 第二层
                foreach ($gridData['step_config']["step_fields"] as $stepFields) {

                    $stepFormatData = $this->getStepColumnFieldMap($stepFields, $stepItem, [
                        'index' => $index,
                        'field_index' => $fieldIndex,
                        'count_fields' => $countFields
                    ]);

                    if ($fieldIndex > 0) {
                        //第一个元素不隐藏
                        array_push($fieldList, $stepFormatData["step_base"]);
                    }

                    $gridColumnConfig['columnsFieldConfig'][$stepFormatData["step_fields"]["field"]] = $stepFormatData["step_fields"];

                    $stepFormatData["step_fields"]["editor"] = '';
                    array_push($gridColumnConfig['columns'][$columnIndex], $stepFormatData["step_fields"]);

                    $fieldIndex++;
                    $index++;
                }

                // 第一层
                array_push($gridColumnConfig['columns'][0], [
                    'bgc' => $stepItem["color"],
                    'but' => $stepItem["code"],
                    'class' => $stepItem["code"] . "_h",
                    'colspan' => 1,
                    'fhcol' => true,
                    'fname' => $stepItem["code"],
                    'step' => "yes",
                    'title' => $stepItem["name"],
                    'first_field' => $stepFirstFields,
                    'field_list' => join(",", $fieldList)
                ]);
            }
        }

        return $gridColumnConfig;
    }

    /**
     * 当前页面视图配置
     * @param $param
     * @return array|mixed
     */
    private function getViewFieldConfig($param)
    {
        $viewConfig = [];

        // 获取用户个人视图
        $viewUseModel = new ViewUseModel();
        $userViewConfig = $viewUseModel->alias("vu")
            ->join('LEFT JOIN strack_view v ON v.id = vu.view_id')
            ->where(["vu.page" => $param["page"], "vu.user_id" => $this->userId, "vu.project_id" => $param['project_id']])
            ->getField('v.config');
        if (!empty($userViewConfig)) {
            $viewConfig = json_decode($userViewConfig, true);
        } else {
            // 获取默认视图配置
            $viewDefaultModel = new ViewDefaultModel();
            $viewDefaultConfig = $viewDefaultModel->where(['page' => $param['page'], 'project_id' => $param['project_id']])->getField('config');
            if (!empty($viewDefaultConfig)) {
                $viewConfig = json_decode($viewDefaultConfig, true);
            }
        }

        return $viewConfig;
    }

    /**
     * 获取高级过滤字段列表
     * @param $param
     * @return array
     */
    public function getAdvanceFilterFields($param)
    {
        $filterList = [];
        $moduleData = $this->getModuleData($param['module_id']);

        // 获取schema_id
        $schemaId = $this->schemaService->getPageSchemaId($moduleData["type"], $param['schema_page'], 0);

        // 字段配置数据
        $fieldConfigData = $this->getSchemaConfig($param, $schemaId, "view");

        // 返回一层
        foreach ($fieldConfigData["field_clean_data"]["field_auth_config"]["filter_list"] as $item) {
            foreach ($item["built_in"]["fields"] as $field) {
                if ($moduleData["code"] != $field["module_code"] && array_key_exists("outreach_editor", $field) && $field["outreach_editor"] === "combobox") {
                    $field["fields"] = "id";
                }
                $field["frozen_module"] = $moduleData["code"];
                $field['id'] = $field["module"] . '.' . $field["fields"];

                array_push($filterList, $field);
            }

            foreach ($item["custom"]["fields"] as $field) {
                if (in_array($field["type"], ['horizontal_relationship', 'belong_to'])) {
                    $field['id'] = $field["fields"] . ".id";
                    $field['module_code'] = $field["fields"];
                    $field['fields'] = "id";
                    $field['table'] = $field["module"];
                } else {
                    $field['id'] = $field["module"] . '.' . $field["fields"];
                }

                array_push($filterList, $field);
            }
        }
        return $filterList;
    }

    /**
     * 切换显示视图
     * @param $param
     * @return array
     */
    public function toggleView($param)
    {
        $userId = session("user_id");
        $viewUseModel = new ViewUseModel();
        $viewUseData = $viewUseModel->findData([
            'filter' => ['project_id' => $param['project_id'], 'page' => $param['page'], 'user_id' => $userId],
            'fields' => 'id'
        ]);

        if (!empty($viewUseData) && $viewUseData['id'] > 0) {
            //存在更新当前用户使用视图
            $modifyData = [
                'id' => $viewUseData['id'],
                'view_id' => $param['view_id']
            ];
            $resData = $viewUseModel->modifyItem($modifyData);
        } else {
            //不存在新建当前用户使用视图
            $param['user_id'] = $userId;
            $resData = $viewUseModel->addItem($param);
        }
        if (!$resData) {
            // 切换视图失败 - 231001
            throw_strack_exception($viewUseModel->getError(), 231001);
        } else {
            return success_response($viewUseModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 保存视图
     * @param $param
     * @return array
     * @throws \Exception
     */
    public function saveView($param)
    {
        $viewModel = new ViewModel();
        if ($param['id'] > 0) {
            $resData = $viewModel->modifyItem($param);
        } else {
            $resData = $viewModel->addItem($param);
        }
        if (!$resData) {
            // 保存视图失败 - 231002
            throw_strack_exception($viewModel->getError(), 231002);
        } else {
            // 将当前视图切换到刚保存的View 视图
            $viewUseModel = new ViewUseModel();
            $viewUseData = $viewUseModel->findData(['filter' => ['page' => $param['page'], 'project_id' => $param['project_id'], 'user_id' => $param['user_id']]]);
            // 如果之前存在视图，更新即可；否则新增
            if (!empty($viewUseData)) {
                $updateViewUseParam = [
                    'id' => $viewUseData['id'],
                    'view_id' => $resData['id']
                ];
                $viewUseModel->modifyItem($updateViewUseParam);
            } else {
                $saveViewUseData = [
                    'view_id' => $resData['id'],
                    'page' => $param['page'],
                    'project_id' => $param['project_id'],
                    'user_id' => $param['user_id']
                ];
                $viewUseModel->addItem($saveViewUseData);
            }
            return success_response($viewModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 另存为视图
     * @param $param
     * @return array
     * @throws \Exception
     */
    public function saveAsView($param)
    {
        $viewModel = new ViewModel();
        $resData = $viewModel->addItem($param);
        if (!$resData) {
            // 另存为视图失败 - 231003
            throw_strack_exception($viewModel->getError(), 232003);
        } else {
            // 将当前视图切换到刚保存的View 视图
            $viewUseModel = new ViewUseModel();
            $viewUseData = $viewUseModel->findData(['filter' => ['page' => $param['page'], 'project_id' => $param['project_id'], 'user_id' => $param['user_id']]]);
            // 如果之前存在视图，更新即可；否则新增
            if (!empty($viewUseData)) {
                $updateViewUseParam = [
                    'id' => $viewUseData['id'],
                    'view_id' => $resData['id']
                ];
                $viewUseModel->modifyItem($updateViewUseParam);
            } else {
                $saveViewUseData = [
                    'view_id' => $resData['id'],
                    'page' => $param['page'],
                    'project_id' => $param['project_id'],
                    'user_id' => $param['user_id']
                ];
                $viewUseModel->addItem($saveViewUseData);
            }
            return success_response($viewModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 修改视图
     * @param $param
     * @return array
     */
    public function modifyView($param)
    {
        $viewModel = new ViewModel();
        $resData = $viewModel->modifyItem($param);
        if (!$resData) {
            // 修改视图失败 - 231004
            throw_strack_exception($viewModel->getError(), 231004);
        } else {
            return success_response($viewModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 删除视图
     * @param $param
     * @return array
     */
    public function deleteView($param)
    {
        if ($param["id"] > 0) {
            // 获取view use data
            $viewUseModel = new ViewUseModel();
            $viewUseData = $viewUseModel->findData([
                "filter" => ["page" => $param["page"], "view_id" => $param["id"], "user_id" => session("user_id")],
                "fields" => "id"
            ]);

            $viewModel = new ViewModel();
            $viewModel->startTrans();
            try {
                // 先将视图切换到默认视图
                $viewUseData = $viewUseModel->modifyItem(["id" => $viewUseData["id"], "view_id" => 0]);
                if (!$viewUseData) {
                    throw new \Exception($viewUseModel->getError());
                } else {
                    $viewModel->deleteItem($param);
                }
                $viewModel->commit(); // 提交事物
                return success_response($viewModel->getSuccessMassege());
            } catch (\Exception $e) {
                $viewModel->rollback(); // 事物回滚
                // 删除视图失败 - 232005
                throw_strack_exception($viewModel->getError(), 231005);
            }
        } else {
            return $this->deleteViewDefault($param);
        }
    }

    /**
     * 删除默认视图
     * @param $param
     * @return array
     */
    public function deleteViewDefault($param)
    {
        $viewDefaultModel = new ViewDefaultModel();
        $resData = $viewDefaultModel->deleteItem([
            "page" => $param["page"],
            "project_id" => $param["project_id"]
        ]);

        if (!$resData) {
            throw_strack_exception($viewDefaultModel->getError(), 231008);
        } else {
            return success_response($viewDefaultModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 获取排序字段
     * @param $param
     * @return array
     */
    public function getSortFields($param)
    {
        $moduleType = $this->getModuleData($param["module_id"], "type");
        // 获取schema_id
        $schemaId = $this->schemaService->getPageSchemaId($moduleType, $param['schema_page'], 0);
        // 字段配置数据
        $fieldConfigData = $this->getSchemaConfig($param, $schemaId, "view");

        //返回一层
        $fieldList = [];
        foreach ($fieldConfigData['field_clean_data']['field_auth_config']['sort_list'] as $fields) {
            foreach ($fields["built_in"]["fields"] as $item) {
                $item['id'] = $item["module"] . '.' . $item["fields"];
                array_push($fieldList, $item);
            }

            foreach ($fields["custom"]["fields"] as $item) {
                $item['id'] = $item["module"] . '.' . $item["fields"];
                array_push($fieldList, $item);
            }
        }

        return $fieldList;
    }

    /**
     * 视图列表
     * @param $projectId
     * @param $userId
     * @param $page
     * @return mixed
     */
    private function getViewList($projectId, $userId, $page)
    {
        $viewModel = new ViewModel();
        $viewList = $viewModel->selectData([
            'filter' => ['page' => $page, 'project_id' => $projectId],
            'order' => 'created desc'
        ]);

        // 添加默认视图项
        $viewDefaultModel = new ViewDefaultModel();
        $defaultView = $viewDefaultModel->findData([
            'fields' => 'name,code,page,project_id,type',
            'filter' => ['page' => $page, 'project_id' => $projectId],
        ]);

        if (!empty($defaultView)) {
            $defaultView['id'] = 0;
            $defaultView['user_id'] = 0;
            $defaultView['public'] = 'yes';
            $defaultView['allow_edit'] = 'deny';
            $defaultView['checked'] = false;
            array_unshift($viewList['rows'], $defaultView);
        } else {
            array_unshift($viewList['rows'], ['id' => 0, 'name' => L("Default_View"), 'public' => 'yes', 'user_id' => 0, "allow_edit" => "deny", "checked" => false, 'type' => 'grid']);
        }

        //获取当前用户正在使用视图
        $viewUseId = $this->getCurrentViewUse($projectId, $userId, $page);

        $selfViewList = [];
        $publicViewList = [];
        $checked = [];
        $viewMode = [
            'grid' => false,
            'kanban' => false
        ];

        $userService = new UserService();
        //判断当前视图是否允许编辑
        foreach ($viewList['rows'] as $viewItem) {
            $userData = $userService->getUserFindField(["id" => $viewItem["user_id"]], "name");
            if ($viewItem["user_id"] == $userId || $viewItem["id"] == 0) {
                $viewItem["allow_edit"] = "allow";
                $viewItem["show_artist"] = false;
                if ($viewItem["id"] == $viewUseId) {
                    $viewItem["checked"] = true;
                    $viewMode[$viewItem['type']] = true;
                    $checked = $viewItem;
                } else {
                    $viewItem["checked"] = false;
                }
                array_push($selfViewList, $viewItem);
            } else {
                if ($viewItem["public"] === "yes") {
                    $viewItem["allow_edit"] = "deny";
                    $viewItem["show_artist"] = true;
                    $viewItem["user_name"] = $userData["name"];
                    if ($viewItem["id"] == $viewUseId) {
                        $viewItem["checked"] = true;
                        $viewMode[$viewItem['type']] = true;
                        $checked = $viewItem;
                    } else {
                        $viewItem["checked"] = false;
                    }
                    array_push($publicViewList, $viewItem);
                }
            }
        }

        $views = [];
        foreach ($viewMode as $mode => $modeChecked) {
            $views[] = [
                'id' => 0,
                'name' => L(ucfirst($mode) . "_View"),
                'public' => 'yes',
                'user_id' => 0,
                "allow_edit" => "deny",
                "checked" => $modeChecked,
                "view_mode" => $mode
            ];
        }

        return [
            "checked" => $checked,
            "views" => $views,
            "self" => $selfViewList,
            "public" => $publicViewList
        ];
    }

    /**
     * 获取当前用户正在使用试图，无则使用默认视图
     * @param $projectId
     * @param $userId
     * @param $page
     * @return int|mixed
     */
    private function getCurrentViewUse($projectId, $userId, $page)
    {
        $viewUserModel = new ViewUseModel();
        $map = [
            "project_id" => $projectId,
            "user_id" => $userId,
            "page" => $page
        ];
        $viewId = $viewUserModel->where($map)->getField("view_id");
        if (!isset($viewId)) {
            $viewId = 0;
        }
        return $viewId;
    }

    /**
     * 保存默认视图
     * @param $param
     * @return array
     */
    public function saveViewDefault($param)
    {
        $viewDefaultModel = new ViewDefaultModel();
        $viewDefaultId = $viewDefaultModel->where(['page' => $param['page'], 'project_id' => $param['project_id']])->getField('id');

        if ($viewDefaultId > 0) {
            $param['id'] = $viewDefaultId;
            return $this->modifyDefaultView($param);
        } else {
            // 新增默认视图
            $resData = $viewDefaultModel->addItem($param);
            if (!$resData) {
                // 保存默认视图失败 - 231006
                throw_strack_exception($viewDefaultModel->getError(), 231006);
            } else {
                return success_response($viewDefaultModel->getSuccessMassege(), $resData);
            }
        }
    }

    /**
     * 修改默认视图
     * @param $param
     * @return array
     */
    public function modifyDefaultView($param)
    {
        $viewDefaultModel = new ViewDefaultModel();
        $resData = $viewDefaultModel->modifyItem($param);
        if (!$resData) {
            // 保存默认视图失败 - 231006
            throw_strack_exception($viewDefaultModel->getError(), 231007);
        } else {
            return success_response($viewDefaultModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 获取视图列表数据
     * @param $param
     * @return mixed
     */
    public function getViewListData($param)
    {
        $class = '\\Common\\Model\\' . string_initial_letter($param["module_code"]) . 'Model';
        $modelObj = new $class();
        $resData = $modelObj->selectData($param["filter"]);

        return $resData;
    }

    /**
     * 获取默认视图数据
     * @param $filter
     * @return array|mixed
     */
    public function getDefaultView($filter)
    {
        $viewDefaultModel = new ViewDefaultModel();
        $resData = $viewDefaultModel->findData(["filter" => $filter['filter']]);

        return $resData;
    }

    /**
     * 获取看板分组数据配置
     * @param $param
     * @return array
     */
    public function getGridCollaborators($param)
    {
        $moduleParam = $param["param"];
        $groupParam = $param["group"];
        $collaborators = [];
        $groupModuleData = [];
        $dragAuth = 'no';

        switch ($groupParam['field_type']) {
            case 'built_in':
                // 由于某些分组数据量巨大暂时只支持
                if (strtolower($groupParam['module_code']) === 'status') {
                    $templateService = new TemplateService();
                    $templateId = $templateService->getProjectTemplateID($moduleParam['project_id']);

                    // 获取当前分组模块数据
                    $groupModuleData = (new ModuleModel())->field('id,type,code')->where(['code' => $groupParam['module_code']])->find();
                    $groupModuleData['table'] = $groupModuleData['type'] === 'entity' ? 'Entity' : string_initial_letter($groupModuleData['code']);

                    // 状态， 查询当前项目模板的状态配置
                    $templateConfig = $templateService->getTemplateConfig([
                        "template_id" => $templateId,
                        "module_code" => $moduleParam['module_code']
                    ]);


                    $statusIds = !empty($templateConfig) ? array_column($templateConfig['status'], 'id') : [];

                    $statusModel = new StatusModel();
                    $collaborators = $statusModel->field('id,name,code,color,icon,correspond')
                        ->where(['id' => ['IN', join(',', $statusIds)]])
                        ->select();

                    array_unshift($collaborators, [
                        "id" => "0",
                        "name" => "没有状态",
                        "code" => "none",
                        "color" => "000000",
                        "icon" => "icon-uniEA37",
                        "correspond" => "not_started"
                    ]);

                    $dragAuth = $this->checkFieldPermission('base', 'status_id', "modify");
                }
                break;
            case 'custom':
                // 自定义字段
                $moduleIds = C('MODULE_ID');
                $variableModel = new VariableModel();
                $variableConfig = $variableModel->field('id,type,config')->where([
                    'code' => $groupParam['field'],
                    'module_id' => $moduleIds[$groupParam['module_code']]
                ])->find();

                $fieldConfig = json_decode($variableConfig['config'], true);

                switch ($variableConfig['type']) {
                    case 'combobox':
                        // 固定下拉框
                        $pinyin = new Pinyin();
                        foreach ($fieldConfig['combo_list'] as $id => $name) {
                            $code = $pinyin->getAllPY($name);
                            $collaborators[] = [
                                "id" => $id,
                                'link_id' => 0,
                                "variable_id" => $variableConfig['id'],
                                "name" => $name,
                                "code" => $code,
                                "color" => "000000",
                                "icon" => "",
                                'relation_type' => '',
                                "correspond" => $code
                            ];
                        }

                        array_unshift($collaborators, [
                            "id" => "0",
                            'link_id' => 0,
                            "variable_id" => 0,
                            "name" => "没有分类",
                            "code" => "none",
                            "color" => "000000",
                            "icon" => "",
                            'relation_type' => '',
                            "correspond" => "not_type"
                        ]);

                        $dragAuth = $this->checkFieldPermission('base', $groupParam['field'], "modify");
                        break;
                    case 'horizontal_relationship':
                        // 用户水平一对一
                        if ($fieldConfig['relation_type'] === 'has_one' && (int)$fieldConfig['relation_module_id'] === $moduleIds['user']) {
                            // 查找当前水平关联的所有用户列表数据
                            $horizontalModel = new HorizontalModel();
                            $userData = $horizontalModel
                                ->distinct(true)
                                ->alias('horiz')
                                ->join('LEFT JOIN strack_base base ON base.id = horiz.src_link_id')
                                ->field('horiz.dst_link_id')
                                ->where([
                                    'horiz.src_module_id' => (int)$fieldConfig['module_id'],
                                    'horiz.dst_module_id' => (int)$fieldConfig['relation_module_id'],
                                    'horiz.variable_id' => (int)$variableConfig['id'],
                                    'base.project_id' => (int)$moduleParam['project_id']
                                ])
                                ->select();

                            $userIds = array_column($userData, 'dst_link_id');

                            // 获取用户列表数据
                            $userModel = new UserModel();
                            $collaborators = $userModel->field('id,name,phone as code,login_name as correspond')
                                ->where(['id' => ['IN', join(',', $userIds)]])->select();

                            foreach ($collaborators as &$userItem) {
                                $userItem['color'] = '000000';
                                $userItem['icon'] = '';
                                $userItem['link_id'] = $userItem['id'];
                                $userItem['relation_type'] = 'has_one';
                                $userItem['variable_id'] = $variableConfig['id'];
                            }

                            array_unshift($collaborators, [
                                "id" => "0",
                                'link_id' => 0,
                                "variable_id" => 0,
                                "name" => "没有分派",
                                "code" => "none",
                                "color" => "000000",
                                "icon" => "",
                                'relation_type' => 'has_one',
                                "correspond" => "not_assign"
                            ]);

                            $dragAuth = $this->checkFieldPermission('base', $groupParam['field'], "modify");
                        }
                        break;
                }
                break;
        }

        // 字段配置
        $optionsService = new OptionsService();
        $formulaConfigData = $optionsService->getFormulaConfigData();

        // 查询自定义字段配置
        $variableIds = join(',', array_values($formulaConfigData));
        $variableModel = new VariableModel();
        $variableCodeData = $variableModel->field('id,code,type')->where(['id' => ["IN", $variableIds]])->select();
        $variableCodeMap = array_column($variableCodeData, null, 'id');

        $formulaConfig = [];
        foreach ($formulaConfigData as $key => $formulaConfigItemId) {
            if (in_array($key, ['reviewed_by', 'assignee_field'])) {
                if (in_array($variableCodeMap[$formulaConfigItemId]['type'], ['belong_to', 'horizontal_relationship'])) {
                    $variableCodeMap[$formulaConfigItemId]['field'] = "base_{$variableCodeMap[$formulaConfigItemId]['code']}";
                } else {
                    $variableCodeMap[$formulaConfigItemId]['field'] = "base_{$variableCodeMap[$formulaConfigItemId]['code']}_value";
                }
                $formulaConfig[$key] = $variableCodeMap[$formulaConfigItemId];
            }
        }

        // 状态配置
        $statusService = new StatusService();
        $statusConfig = $statusService->getStatusDict();

        // 时间配置
        $currentTime = date('Y-m-d H:i:s', time());
        $urgentTime = date('Y-m-d H:i:s', time() + 86400);

        // 我的id
        $userId = session('user_id');

        // 我当前进行中的timelog ids
        $timelogService = new TimelogService();
        $timelogData = $timelogService->getSideTimelogMyTimer($userId);

        return [
            'collaborators' => $collaborators,
            'group_module' => $groupModuleData,
            'drag_auth' => $dragAuth,
            'formula_config' => $formulaConfig,
            'status_config' => $statusConfig,
            'time_config' => [
                'current_time' => $currentTime,
                'urgent_time' => $urgentTime
            ],
            'timelog_config' => [
                'my_user_id' => $userId,
                'active_timelog' => array_column($timelogData, 'id', 'link_id')
            ]
        ];
    }

    /**
     * 生成Relation查询结构
     * @param $param
     * @param null $method
     * @param $request
     * @return array
     */
    public function generateApiModuleRelation($param, $method = null)
    {
        $this->apiSelectMethod = $method;

        // 获取模块字典数据
        $schemaService = new SchemaService();
        $moduleMapData = $schemaService->getModuleMapData('code');

        $request = [];

        if ($moduleMapData[$param['module']['code']]['type'] === 'entity') {
            // 实体带上module_id必须条件
            $request[] = [
                'field' => 'module_id',
                'field_type' => 'built_in',
                'editor' => "combobox",
                'value' => $moduleMapData[$param['module']['code']]['id'],
                'condition' => 'EQ',
                'module_code' => $moduleMapData[$param['module']['code']]['code'],
                'table' => 'Entity'
            ];
        }

        // 处理过滤条件
        $filter = [
            "temp_fields" => ["add" => [], "cut" => []],
            "group" => [],
            "sort" => [],
            "request" => $request,
            "filter_input" => [],
            "filter_panel" => [],
            "filter_advance" => []
        ];

        if (!empty($param['param']['filter'])) {
            $filter["filter_advance"] = $param['param']['filter'];
        }

        if (!empty($param['param']['order'])) {
            $filter["sort"] = $param['param']['order'];
        }

        // 请求参数
        $projectId = !empty($param["project_id"]) ? $param["project_id"] : 0;
        $requestParam = [
            "filter" => $filter,
            "module_id" => $moduleMapData[$param['module']['code']]['id'],
            "module_type" => $param['module']['type'],
            "module_code" => $param['module']['code'],
            "project_id" => $projectId
        ];

        // 组装分页参数
        if (!empty($param['param']['page'])) {
            $requestParam['pagination'] = [
                'page_number' => $param['param']['page'][0],
                'page_size' => $param['param']['page'][1]
            ];
        }

        $queryFields = [];
        if (!empty($param['param']['fields'])) {
            $fieldsParamIsset = false;
            foreach ($param['param']['fields'] as $module => $fields) {
                if (!empty($fields)) {
                    $fieldsParamIsset = true;
                }
            }

            if (!empty($fieldsParamIsset)) {
                $queryFields = $param['param']['fields'];
            }
        }

        // 获取当前结构关联结构所有字段配置
        $schemaService = new SchemaService();
        $schemaData = $schemaService->getSchemaData(["code" => $param['module']['code']]);
        $schemaId = 0;
        if (!empty($schemaData)) {
            $schemaId = $schemaData['id'];
        }
        // 获取schema配置
        $schemaFields = $this->getSchemaConfig($requestParam, $schemaId, "query", $queryFields);

        return $schemaFields;
    }

    /**
     * 填充字段
     * @param $fieldList
     * @param $field
     * @param $moduleKey
     */
    protected function fillExpressionFieldData(&$fieldList, $field, $moduleKey)
    {
        $field['id'] = $moduleKey . "." . $field['id'];
        if (array_key_exists($moduleKey, $fieldList)) {
            $fieldList[$moduleKey][] = $field;
        } else {
            $fieldList[$moduleKey] = [$field];
        }
    }

    /**
     * 生成计算字段配置项
     * @param $fieldList
     * @param $field
     * @param $moduleKey
     * @param $schemaParam
     * @param array $userModuleData
     */
    protected function generateExpressionField(&$fieldList, $field, $moduleKey, $schemaParam, $userModuleData = [])
    {
        if (!in_array($field['id'], $this->fieldExitList) && strpos($field['id'], '_id') === false && !in_array($field['id'], ['id', 'created_by'])) {

            if ($field['field_type'] === 'built_in') {
                if (in_array($field['type'], ['int', 'integer', 'bigint', 'decimal', 'double', 'mediumint', 'tinyint', 'float'])) {
                    // int 整型
                    // integer 整型
                    // bigint 大的整数
                    // decimal 小数的
                    // double 双精度浮点数
                    // mediumint 中的整数
                    // tinyint 小的整型
                    // float 浮点型

                    $field['name'] = L($field['lang']);
                    $this->fillExpressionFieldData($fieldList, $field, $moduleKey);
                    $this->fieldExitList[] = $field['id'];
                }
            } else {
                $field['name'] = L($field['lang']);

                switch ($field['editor']) {
                    case 'timespinner':
                        // 时间微调
                        $this->fillExpressionFieldData($fieldList, $field, $moduleKey);
                        $this->fieldExitList[] = $field['id'];
                        break;
                    case 'text':
                        // 判断掩码类型
                        if (in_array($field['mask'], ['integer_no_range', 'decimal', 'integer', 'percentage', 'phone'])) {
                            $this->fillExpressionFieldData($fieldList, $field, $moduleKey);
                            $this->fieldExitList[] = $field['id'];
                        }
                        break;
                    case 'combobox':
                        if ($field['type'] === 'horizontal_relationship') {
                            // 判断是不是用户管理表
                            if (!$this->hasUserModule && (int)$field['relation_module_id'] === $userModuleData['id']) {

                                $this->fieldExitList[] = $field['id'];

                                $this->hasUserModule = true;
                                // 获取用户表字段
                                $schemaService = new SchemaService();
                                $fieldsData = $schemaService->getUserTableFields($schemaParam['project_id'], $userModuleData);

                                $userGroupName = L('User');

                                foreach ($fieldsData['master']['built_in'] as $userField) {
                                    $userField['group_name'] = $userGroupName;
                                    $this->generateExpressionField($fieldList, $userField, 'user', $schemaParam);
                                }

                                foreach ($fieldsData['master']['custom'] as $userField) {
                                    $userField['group_name'] = $userGroupName;
                                    $this->generateExpressionField($fieldList, $userField, 'user', $schemaParam, $userModuleData);
                                }
                            }
                        } else {
                            // 下拉列表
                            $this->fillExpressionFieldData($fieldList, $field, $moduleKey);
                            $this->fieldExitList[] = $field['id'];
                        }
                        break;
                }
            }
        }
    }

    /**
     * 获取计算自定义字段列表
     * @param $param
     * @return array
     */
    public function getExpressionFields($param)
    {
        $schemaParam = [];

        $schemaParam['module_id'] = $param['module_id'];
        $schemaParam['project_id'] = !empty($param["project_id"]) ? $param["project_id"] : 0;

        $moduleModel = new ModuleModel();
        $moduleCode = $moduleModel->where(['id' => $param['module_id']])->getField('code');


        // 获取当前结构关联结构所有字段配置
        $schemaService = new SchemaService();
        $schemaData = $schemaService->getSchemaData(["code" => $moduleCode]);
        $schemaId = 0;
        if (!empty($schemaData)) {
            $schemaId = $schemaData['id'];
        }

        // 获取当前数据结构字段
        $schemaFieldConfig = $this->getViewSchemaFields($schemaParam, $schemaId);

        // 判断是否有用户表关联
        $userModuleData = $moduleModel->where(['code' => 'user'])->find();

        if (array_key_exists('user', $schemaFieldConfig['field_clean_data']['schema_fields'])) {
            $this->hasUserModule = true;
        }

        $fieldList = [];

        foreach ($schemaFieldConfig['field_clean_data']['schema_fields'] as $moduleKey => $config) {

            $groupName = L(ucfirst($moduleKey));

            foreach ($config['field_configs']['built_in'] as $field) {
                // 固定字段
                $field['group_name'] = $groupName;
                $this->generateExpressionField($fieldList, $field, $moduleKey, $schemaParam);
            }

            foreach ($config['field_configs']['custom'] as $field) {
                // 自定义字段
                $field['group_name'] = $groupName;
                $this->generateExpressionField($fieldList, $field, $moduleKey, $schemaParam, $userModuleData);
            }
        }

        $fieldListData = [];
        foreach ($fieldList as $item) {
            foreach ($item as $value) {
                $fieldListData[] = $value;
            }
        }

        return $fieldListData;
    }
}

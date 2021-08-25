<?php
// +----------------------------------------------------------------------
// | Variable 自定义服务
// +----------------------------------------------------------------------
// | 主要服务于Variable数据处理
// +----------------------------------------------------------------------
// | 错误编码头 230xxx
// +----------------------------------------------------------------------

namespace Common\Service;

use Common\Model\AuthAccessModel;
use Common\Model\AuthFieldModel;
use Common\Model\HorizontalConfigModel;
use Common\Model\HorizontalModel;
use Common\Model\ModuleModel;
use Common\Model\RoleModel;
use Common\Model\ProjectMemberModel;
use Common\Model\StatusModel;
use Common\Model\VariableModel;
use Common\Model\VariableValueModel;
use Think\Console\Command\Make\Model;

class VariableService
{

    /**
     * 改变权限字段配置
     * @param $param
     * @param $mode
     * @throws \Think\Exception
     */
    public function changeAuthFieldConfig($param, $mode)
    {
        $authFieldModel = new AuthFieldModel();
        $authAccessModel = new AuthAccessModel();
        switch ($mode) {
            case "add":
                // 新增操作
                $addData = [
                    "name" => $param["code"],
                    "lang" => $param["name"],
                    "type" => 'custom',
                    "variable_id" => $param['variable_id'],
                    "project_id" => $param['project_id'],
                    "module_id" => $param['module_id'],
                    "module_code" => $param['module_code']
                ];
                $authFieldResult = $authFieldModel->addItem($addData);
                // 获取所有角色列表
                $roleModel = new RoleModel();
                $roleList = $roleModel->selectData([]);
                if ($roleList["total"] > 0) {
                    foreach ($roleList["rows"] as $roleItem) {
                        $accessAddData = [
                            "role_id" => $roleItem["id"],
                            "auth_id" => $authFieldResult["id"],
                            "page" => $authFieldResult["module_code"],
                            "permission" => "view,create,modify,delete,clear",
                            "type" => "field",
                            "module_id" => $authFieldResult["module_id"],
                            "project_id" => $authFieldResult["project_id"]
                        ];
                        $authAccessModel->addItem($accessAddData);
                    }
                }
                break;
            case "delete":
                // 删除操作
                $deleteData = [
                    "type" => 'custom',
                    "variable_id" => $param['variable_id'],
                    "project_id" => $param['project_id'],
                    "module_id" => $param['module_id']
                ];
                $authFieldData = $authFieldModel->where($deleteData)->select();
                $authFieldId = array_column($authFieldData, "id");
                $authFieldModel->deleteItem(["id" => ["IN", join(",", $authFieldId)]]);
                $authAccessModel->deleteItem([
                    "auth_id" => ["IN", join(",", $authFieldId)],
                    "project_id" => $param['project_id'],
                    "module_id" => $param['module_id']
                ]);
                break;
            case "update":
                // 修改操作，更新名称和语言包
                $updateData = [
                    "name" => $param["code"],
                    "lang" => $param["name"]
                ];
                $filter = [
                    "type" => 'custom',
                    "variable_id" => $param['id'],
                    "project_id" => $param['project_id'],
                    "module_id" => $param['module_id']
                ];
                $authFieldModel->where($filter)->save($updateData);
                break;
        }
    }

    /**
     * 改变模版字段配置
     * @param $param
     * @param $mode
     */
    protected function changeTemplateFieldConfig($param, $mode)
    {
        $templateService = new TemplateService();
        // 获取当前分类的模版配置
        $templateId = $templateService->getProjectTemplateID($param["project_id"]);
        $tabTemplateConfig = $templateService->getTemplateConfig([
            "template_id" => $templateId, "module_code" => $param["module_code"], "category" => "tab"
        ]);
        $tabMapData = array_column($tabTemplateConfig, null, "tab_id");
        $saveData = [];
        switch ($mode) {
            case "update":
                $customFields = "{$param["module_code"]}_{$param["old_code"]}";
                if (array_key_exists($customFields, $tabMapData)) {
                    // 修改成现在的名称
                    $tabMapData[$customFields]["tab_id"] = "{$param["module_code"]}_{$param["code"]}";
                    $tabMapData[$customFields]["name"] = $param["name"];
                }
                $saveData = array_column($tabMapData, null);
                break;
            case "delete":
                $customFields = "{$param["module_code"]}_{$param["code"]}";
                if (array_key_exists($customFields, $tabMapData)) {
                    // 删除传入的字段
                    $diffData = array_diff_key($tabMapData, [$customFields => []]);
                    $saveData = array_column($diffData, null);
                }
                break;
        }

        // 执行保存
        $templateService->modifyTemplateConfig([
            "template_id" => $templateId,
            "category" => "tab",
            "config" => $saveData,
            "module_code" => $param["module_code"]
        ]);
    }

    /**
     * 添加自定义字段
     * @param $param
     * @return array
     */
    public function addCustomFields($param)
    {
        $param["param"]['project_id'] = 0;

        $mergeData = array_merge($param["field_data"], $param["param"]);
        $configParam = $this->generateCustomSaveData($mergeData);

        // 添加自定义字段表
        $variableModel = new VariableModel();
        $variableModel->startTrans();
        try {
            $resData = $variableModel->addItem($configParam);
            if (!$resData) {
                throw new \Exception($variableModel->getError());
            }
            $moduleModel = new ModuleModel();
            $moduleData = $moduleModel->findData(['filter' => ['id' => $param["param"]['module_id']]]);
            $mergeData['module_type'] = $moduleData['type'];
            $mergeData['module_code'] = $moduleData['code'];
            $mergeData['variable_id'] = $resData['id'];

            if (!in_array($mergeData['field_type'], ["horizontal_relationship", "belong_to"])) {
                // 批量添加已经存在数据自定义字段值
                $this->addVariableDefaultValue($mergeData, "batch");
            }

            // 添加当前字段到权限字段表
            $configParam["variable_id"] = $resData['id'];
            $configParam["module_code"] = $moduleData['code'];
            $this->changeAuthFieldConfig($configParam, 'add');

            // 添加当前字段到视图配置
            $configParam['page'] = $mergeData["page_name"];
            $configParam['field_type'] = $mergeData["field_type"];
            $configParam["module_type"] = $moduleData['type'];
            $configParam["project_id"] = $param["param"]['project_id'];
            $templateService = new TemplateService();
            $templateService->changeViewCustomFields($configParam, "add");

            // 提交事务
            $variableModel->commit();
            return success_response($variableModel->getSuccessMassege(), $resData);
        } catch (\Exception $e) {
            // 添加自定义字段失败错误码 001
            $variableModel->rollback();
            throw_strack_exception($e->getMessage(), 230001);
        }
    }

    /**
     * 修改自定义字段数据
     * @param $param
     * @return array
     */
    public function modifyCustomFields($param)
    {
        $mergeData = array_merge($param["field_data"], $param["param"]);

        // 获取当前字段数据
        $variableModel = new VariableModel();
        $variableFindData = $variableModel->findData(["filter" => ["id" => $mergeData["variable_id"]]]);

        $configParam = $this->generateCustomSaveData($mergeData);
        $configParam["id"] = $mergeData["variable_id"];

        // 修改自定义字段表
        $variableUpdateData = [];
        foreach ($configParam as $field => $item) {
            if (!in_array($field, ['project_id'])) {
                $variableUpdateData[$field] = $item;
            }
        }

        $variableModel->startTrans();
        try {
            $resData = $variableModel->modifyItem($variableUpdateData);
            if (!$resData) {
                throw new \Exception($variableModel->getError());
            }

            $moduleModel = new ModuleModel();
            $moduleData = $moduleModel->findData(['filter' => ['id' => $param["param"]['module_id']]]);

            // 修改当前字段在权限字段表中的数据
            $configParam["variable_id"] = $resData['id'];
            $configParam["module_code"] = $moduleData['code'];
            $this->changeAuthFieldConfig($configParam, 'update');

            // 修改当前字段到视图配置
            $configParam['page'] = $mergeData["page_name"];
            $configParam['field_type'] = $mergeData["field_type"];
            $configParam["module_type"] = $moduleData['type'];
            $configParam["project_id"] = $param["param"]['project_id'];
            $configParam["old_code"] = $variableFindData["code"];

            try {
                $templateService = new TemplateService();
                $templateService->changeViewCustomFields($configParam, "update");
                $this->changeTemplateFieldConfig([
                    "old_code" => $variableFindData["code"], "code" => $configParam["code"],
                    "name" => $configParam["name"], "module_code" => $moduleData['code'],
                    "project_id" => $param["param"]['project_id']
                ], "update");
            } catch (\Exception $e) {

            }

            // 提交事务
            $variableModel->commit();
            return success_response($variableModel->getSuccessMassege(), $resData);
        } catch (\Exception $e) {
            // 添加自定义字段失败错误码 001
            $variableModel->rollback();
            throw_strack_exception($e->getMessage(), 230001);
        }
    }

    /**
     * 生成自定义字段保存数据
     * @param $param
     * @return array
     */
    protected function generateCustomSaveData($param)
    {
        $configParam = [];
        foreach ($param['base_data'] as $item) {
            if ($param['field_type'] == 'horizontal_relationship' && $item['field'] === 'relation_module') {
                $horizontalConfigModel = new HorizontalConfigModel();
                $relationModuleId = $horizontalConfigModel->where(['id' => $item['value']])->getField('dst_module_id');
                $configParam['horizontal_config_id'] = $item['value'];
                $configParam['relation_module_id'] = $relationModuleId;
            } else {
                $configParam[$item['field']] = $item['value'];
            }
        }

        if ($param['field_type'] == "combobox") {
            $startIndex = 10;
            foreach ($param["comb_data"] as $item) {
                $configParam["combo_list"][$startIndex] = $item;
                $startIndex += 10;
            }
        }

        if ($param['field_type'] == "belong_to") {
            switch ($param["relation_module_code"]) {
                case "status":
                    $configParam["status_ids"] = $param["comb_data"];
                    $configParam["relation_module_id"] = C("MODULE_ID")["status"];
                    break;
                case "media":
                    $configParam["relation_module_id"] = C("MODULE_ID")["media"];
                    break;
            }
            $configParam["editor"] = $param["editor"];
            $configParam["relation_module_code"] = $param["relation_module_code"];
        }

        $projectId = $param['project_id'] > 0 ? $param['project_id'] : 0;
        if (array_key_exists("relation_module_id", $configParam)) {
            if ($configParam["relation_module_id"] == 34) {
                $configParam["member_type"] = $configParam["code"];
            }
            $projectId = 0;
        }
        $configParam['type'] = $param['field_type'];
        $configParam['module_id'] = $param['module_id'];
        $configParam['project_id'] = $projectId;
        $configParam['config'] = $configParam;

        if (strpos($configParam["code"], "-") !== false) {
            throw_strack_exception(L("No_Midline_Is_Allowed"), 230005);
        }

        // 检查字段是否存在
        if (check_table_fields($configParam["code"])) {
            throw_strack_exception(L("Field_Already_Exists"), 230004);
        }

        $schemaService = new SchemaService();
        $moduleCodeMapData = $schemaService->getModuleMapData("code");

        // 检查字段是否合法
        $variableCode = explode("_", $configParam["code"]);
        if (array_key_exists(array_first($variableCode), $moduleCodeMapData)) {
            throw_strack_exception(L("Illegal_Field"), 230006);
        }

        return $configParam;
    }

    /**
     * 删除自定义字段
     * @param $deleteData
     * @param $param
     * @return array
     */
    public function deleteCustomField($deleteData, $param)
    {
        // 删除自定义字段
        $variableModel = new VariableModel();
        $variableData = $variableModel->findData(["filter" => ["id" => $deleteData["id"]]]);
        if (!in_array($variableData["type"], ["horizontal_relationship", "belong_to"])) {
            // 删除自定义字段值
            $variableValueModel = new VariableValueModel();
            $variableValueModel->deleteItem(["variable_id" => $deleteData["id"]]);
        } else {
            // 删除水平关联数据
            $horizontalModel = new HorizontalModel();
            $horizontalModel->deleteItem(["variable_id" => $deleteData["id"]]);
        }

        $variableResult = $variableModel->deleteItem($deleteData);
        if (!$variableResult) {
            // 删除自定义字段失败错误码 003
            throw_strack_exception($variableModel->getError(), 230003);
        }

        // 添加当前字段到权限字段表
        $this->changeAuthFieldConfig($param, 'delete');

        $moduleModel = new ModuleModel();
        $moduleData = $moduleModel->findData(['filter' => ['id' => $param['module_id']]]);
        $templateService = new TemplateService();
        try {
            // 删除水平关联字段时相应的删除详情tab导航配置
            if ($variableData["type"] === "horizontal_relationship") {
                $this->changeTemplateFieldConfig([
                    "code" => $variableData["code"], "name" => $variableData["name"],
                    "module_code" => $moduleData['code'], "project_id" => $param['project_id']
                ], "delete");
            }
            $templateService->changeViewCustomFields([
                "page" => "project_{$moduleData["code"]}",
                "code" => $variableData["code"],
                "project_id" => $param["project_id"],
                "module_id" => $param['module_id'],
                "module_code" => $moduleData["code"],
                "module_type" => $moduleData["type"],
                "field_type" => $variableData["type"],
            ], "delete");
        } catch (\Exception $e) {

        }

        return success_response(L('Custom_Field_Delete_SC'), $param);
    }

    /**
     * 获取组装当前模块自定义字段列表
     * @param $param
     * @return array
     */
    public function getCustomFieldsList($param)
    {
        // 获取当前模块所有自定义字段
        $filter = $this->generateFilter($param['module_id'], $param['project_id']);
        $variableModel = new VariableModel();
        $variableData = $variableModel->selectData([
            'filter' => $filter,
            'fields' => 'id,name,code,type,config,lock'
        ]);

        return $variableData['rows'];
    }

    /**
     * 获取所有自定义字段列表
     * @param $moduleId
     * @param $projectId
     * @return mixed
     */
    public function getAllCustomFieldsList($moduleId, $projectId)
    {
        // 获取当前模块所有自定义字段
        $filter = $this->generateFilter($moduleId, $projectId);
        $variableModel = new VariableModel();
        $variableData = $variableModel->selectData([
            'filter' => $filter,
            'fields' => 'id,name,code,type,config'
        ]);
        return $variableData["rows"];
    }

    /**
     * 获取组装当前模块自定义字段列表
     * @param $param
     * @return array
     */
    public function getCustomFields($param)
    {
        // 获取当前模块所有自定义字段
        $filter = $this->generateFilter($param['module_id'], $param['project_id']);
        $variableModel = new VariableModel();
        $variableData = $variableModel->selectData([
            'filter' => $filter,
            'fields' => 'id,name,code,type,module_id,config'
        ]);

        $customFields = [];
        foreach ($variableData['rows'] as $fieldData) {
            array_push($customFields, $this->getCustomFieldItem($param, $fieldData));
        }

        return $customFields;
    }

    /**
     * 获取指定
     * @param $variableId
     * @param $moduleId
     * @return array
     */
    public function getOneCustomFields($variableId, $moduleId)
    {
        $variableModel = new VariableModel();
        $fieldData = $variableModel->findData([
            'filter' => [
                'id' => $variableId
            ],
            'fields' => 'id,name,code,type,module_id,config'
        ]);

        $moduleModel = new ModuleModel();

        $moduleData = $moduleModel->field('id,type,name,code,icon')->where(['id' => $moduleId])->find();

        return $this->getCustomFieldItem($moduleData, $fieldData);
    }

    /**
     * 获取自定义控件数据
     * @param $variableId
     * @return mixed
     */
    public function getWidgetData($variableId)
    {
        $variableModel = new VariableModel();
        $variableData = $variableModel->findData([
            'filter' => ['id' => $variableId],
            'fields' => 'type,config'
        ]);
        $configData = $variableData["config"];
        switch ($variableData["type"]) {
            case "combobox":
                $list = [];
                foreach ($configData["combo_list"] as $key => $value) {
                    $comboData = [
                        'id' => $key,
                        'name' => $value
                    ];
                    array_push($list, $comboData);
                }
                return $list;
            case "checkbox":
                $checkboxCombList = [
                    ["id" => "off", "name" => L("UnChecked")],
                    ["id" => "on", "name" => L("Checked")]
                ];
                return $checkboxCombList;
            case "belong_to":
                if (array_key_exists("relation_module_code", $configData) && $configData["relation_module_code"] === "status") {
                    $statusModel = new StatusModel();
                    $statusList = $statusModel->where(["id" => ["IN", join(",", $configData["status_ids"])]])->select();
                    return $statusList;
                }
        }
    }

    /**
     * 给当前模块已经存在的数据增加空值（保持自定义字段行数与固定字段列数量一致性）
     * @param $param
     * @param string $mode
     * @return array
     */
    protected function addVariableDefaultValue($param, $mode = "single")
    {
        $allProject = false;
        if ($param["project_id"] == 0) {
            $allProject = true;
        }
        $projectId = array_key_exists("project_id", $param) ? $param["project_id"] : 0;

        switch ($mode) {
            case "batch":
                // 获取module数据
                $schemaService = new SchemaService();
                $moduleData = $schemaService->getModuleFindData(["id" => $param["module_id"]]);
                $moduleCode = $moduleData["type"] == "entity" ? $moduleData["type"] : $moduleData["code"];

                // 查询当前模块项目下所有的数据
                $class = '\\Common\\Model\\' . string_initial_letter($moduleCode) . 'Model';
                $modelObj = new $class();
                $filter = [];
                if ($moduleCode === "entity") {
                    if ($allProject) {
                        $filter = ["module_id" => $param["module_id"]];
                    } else {
                        $filter = ["project_id" => $projectId, "module_id" => $param["module_id"]];
                    }
                } elseif ($moduleCode === "user") {
                    $filter = ["id" => ["NOT IN", "1,2"]];
                } else {
                    if (!$allProject) {
                        $filter = ["project_id" => $projectId];
                    }
                }
                $listData = $modelObj->selectData(["filter" => $filter, "fields" => "id"]);
                $linkIds = array_column($listData["rows"], "id");
                break;
            default:
                $linkIds = [$param["link_id"]];
                break;
        }

        // 默认值
        $saveVariableValueData = [
            "module_id" => $param["module_id"],
            "variable_id" => $param["variable_id"],
            "link_id" => 0
        ];

        switch ($param["field_type"]) {
            case "checkbox":
                // checkbox 默认值为 off 未选中状态
                $saveVariableValueData["value"] = "off";
                break;
        }

        // 添加数据
        $variableValueModel = new VariableValueModel();
        $variableValueModel->startTrans();

        try {

            foreach ($linkIds as $linkItem) {
                $saveVariableValueData["link_id"] = $linkItem;
                $resData = $variableValueModel->addItem($saveVariableValueData);
                if (!$resData) {
                    throw new \Exception($variableValueModel->getError());
                }
            }
            $variableValueModel->commit(); // 提交事物
            // 返回成功数据
            return success_response($variableValueModel->getSuccessMassege());
        } catch (\Exception $e) {
            $variableValueModel->rollback(); // 事物回滚
            // 添加数据失败错误码 004
            throw_strack_exception($e->getMessage(), 230004);
        }
    }


    /**
     * 生成自定义字段数据结构
     * @param $moduleData
     * @param $fieldData
     * @return array
     */
    protected function getCustomFieldItem($moduleData, $fieldData)
    {

        // 字段基础信息
        $fieldConfig = [
            'id' => $fieldData["code"],
            'edit' => "allow",
            'lang' => $fieldData["name"],
            'mask' => "",
            'show' => "yes",
            'sort' => "allow",
            // 为了方便查询，默认格式为文本
            'type' => $fieldData["type"],
            'group' => '',
            'table' => 'variable_value',
            // 宽度默认为120
            'width' => '120',
            'editor' => $fieldData["type"],
            'fields' => $fieldData["code"],
            // 允许查询
            'filter' => "allow",
            'module' => 'variable_value',
            'format' => '',
            'multiple' => 'no',
            'validate' => '',
            // 自定义字段注册id
            'variable_id' => $fieldData["id"],
            // 所属自定义字段
            'field_type' => 'custom',
            'value_show' => $fieldData["code"],
            'allow_group' => "allow",
            'is_primary_key' => "no",
            'is_foreign_key' => "no",
            "outreach_editor" => $fieldData["type"],
            "outreach_formatter" => "",
            "data_source" => $fieldData["type"],
            "combobox_list" => [],
            "outreach_display" => "yes",
            "module_alias" => $moduleData["code"] . '_custom',
            "module_code" => $moduleData["code"],
            "module_type" => $moduleData["type"],
            "belong_primary_key" => "variable_value_id",
            "module_id" => $fieldData['module_id'],
            'relation_type' => '',
            "relation_module_id" => 0,
            "relation_module_code" => '',
        ];

        switch ($fieldData["type"]) {
            case "text":
                // 把输入掩码写入
                $fieldConfig["mask"] = $fieldData["config"]["mask"];
                break;
            case "combobox":
                // 如果为下拉菜单，显示值
                $fieldConfig["outreach_formatter"] = 'row["' . $moduleData["code"] . '_' . $fieldData["code"] . '_format' . '"]';
                $fieldConfig["format"] = "combobox";
                $comboValueIndex = [];
                $comboData = $this->getWidgetData($fieldData["id"]);
                foreach ($comboData as $item) {
                    $comboValueIndex[$item["id"]] = $item["name"];
                }
                $fieldConfig["combobox_list"] = $comboValueIndex;
                break;
            case "datebox":
                // 日期框格式化
                $fieldConfig["format"] = "date";
                break;
            case "datetimebox":
                // 日期时间框格式化
                $fieldConfig["format"] = "datetime";
                break;
            case "checkbox":
                // 复选框
                $fieldConfig["format"] = "checkbox";
                break;
            case "belong_to":
                // 一对一水平关联
                $fieldConfig["relation_module_id"] = $fieldData['config']['relation_module_id'];
                $fieldConfig["relation_module_code"] = $fieldData['config']['relation_module_code'];
                if (array_key_exists("editor", $fieldData['config'])) {
                    $editor = $fieldData['config']['editor'];
                } else {
                    $editor = $fieldConfig["relation_module_code"] === "media" ? "upload" : "combobox";
                }
                $fieldConfig["editor"] = $editor;
                break;
            case "horizontal_relationship":
                // 一对多水平关联
                $fieldConfig["relation_module_id"] = $fieldData['config']['relation_module_id'];
                if (array_key_exists('relation_type', $fieldData['config']) && $fieldData['config']['relation_type'] === 'has_one') {
                    $fieldConfig['relation_type'] = $fieldData['config']['relation_type'];
                    $fieldConfig['editor'] = 'combobox';
                    $fieldConfig['multiple'] = 'no';
                } else {
                    $fieldConfig['multiple'] = 'yes';
                    $fieldConfig["editor"] = array_key_exists("editor", $fieldData['config']) ? $fieldData['config']['editor'] : "tagbox";
                }
                break;
        }

        return $fieldConfig;
    }

    /**
     * 生成查询条件
     * @param $moduleId
     * @param $projectId
     * @return array
     */
    protected function generateFilter($moduleId, $projectId)
    {
        $filter = [
            "module_id" => $moduleId
        ];
        if ($projectId !== 0) {
            $filter["project_id"] = ["IN", [0, $projectId]];
        } else {
            $filter["project_id"] = 0;
        }
        return $filter;
    }

    /**
     * 获取自定义水平关联数据
     * @param $moduleId
     * @return mixed
     */
    public function getVariableHorizontalList($moduleId)
    {
        $variableModel = new VariableModel();
        $variableData = $variableModel->selectData([
            "filter" => ["module_id" => $moduleId, "type" => "horizontal_relationship"],
            "fields" => "id,name,module_id,code,config"
        ]);
        return $variableData["rows"];
    }

    /**
     * 获取指定用户水平自定义字段值
     * @param $variableId
     * @param $srcLinkId
     * @param $srcModuleId
     * @param $dstModuleId
     * @return mixed
     */
    public function getUserVariableHorizontalValue($variableId, $srcLinkId, $srcModuleId, $dstModuleId)
    {
        $horizontalModel = new HorizontalModel();
        $userData = $horizontalModel->alias('horizon')
            ->join("LEFT JOIN strack_user user ON user.id = horizon.dst_link_id")
            ->field('user.name as user_name,horizon.dst_link_id as user_id')
            ->where([
                'horizon.variable_id' => $variableId,
                'horizon.src_link_id' => $srcLinkId,
                'horizon.src_module_id' => $srcModuleId,
                'horizon.dst_module_id' => $dstModuleId,
            ])
            ->find();
        return $userData;
    }

    /**
     * 获取被自定义水平关联数据
     * @param $moduleId
     * @return mixed
     */
    public function getBeRelatedVariableHorizontalList($moduleId)
    {
        $variableModel = new VariableModel();
        $relationModuleIdData = $variableModel->selectData([
            "filter" => "json_extract(config, '$.relation_module_id') = {$moduleId}",
            "fields" => "module_id,id"
        ]);

        $relationModuleIds = array_column($relationModuleIdData["rows"], "module_id");
        $relationModuleMap = array_column($relationModuleIdData["rows"], null, "module_id");
        $moduleModel = new ModuleModel();
        $relationModuleData = $moduleModel->selectData([
            "filter" => ["id" => ["IN", join(",", $relationModuleIds)]],
            "fields" => "id,type,name,code"
        ]);
        foreach ($relationModuleData["rows"] as &$row) {
            $row["dst_module_id"] = $moduleId;
            $row["variable_id"] = $relationModuleMap[$row["id"]]["id"];
        }
        return $relationModuleData["rows"];
    }

    /**
     * 更新自定义字段相关数据
     * @param $data
     */
    public function upDateVariableConfig($data)
    {
        // 获取当前自定义字段值
        $variableModel = new VariableModel();
        $variableConfig = $variableModel->findData(["filter" => ["id" => $data["link_id"]]]);

        // 更新权限自定义字段数据
        $this->changeAuthFieldConfig($variableConfig, 'update');
    }

    /**
     * 修正自定义字段
     * @param $moduleId
     * @param $linkId
     */
    public function correctCustomFieldValue($moduleId, $linkId)
    {
        $variableModel = new VariableModel();
        $variableValueModel = new VariableValueModel();

        // 获取当前所有的自定义字段
        $variableData = $variableModel->selectData([
            "filter" => ["module_id" => $moduleId]
        ]);

        if ($variableData["total"] > 0) {
            // 获取当前module信息
            $moduleData = D("module")->where(["id" => $moduleId])->find();

            $moduleCode = $moduleData["type"] == "entity" ? $moduleData["type"] : $moduleData["code"];

            // 查询当前模块项目下所有的数据
            $class = '\\Common\\Model\\' . string_initial_letter($moduleCode) . 'Model';
            $modelObj = new $class();

            $data = $modelObj->where(['id' => $linkId])->find();

            $projectId = array_key_exists("project_id", $data) ? $data["project_id"] : 0;

            // 所有存在自定义字段ID
            $variableIds = array_column($variableData["rows"], "id");

            // 查询自定义字段值是否存在
            $variableValueData = $variableValueModel->selectData([
                "filter" => [
                    "module_id" => $moduleId,
                    "link_id" => $data["id"],
                    "variable_id" => ["IN", join(",", $variableIds)]]
            ]);

            // 存在值得自定义字段ID
            $valueVariableIds = array_column($variableValueData["rows"], "variable_id");

            // 保存数据
            foreach ($variableData["rows"] as $variableItem) {
                if (!in_array($variableItem['id'], $valueVariableIds)) {
                    $mergeData['module_type'] = $moduleData['type'];
                    $mergeData['module_code'] = $moduleData['code'];
                    $mergeData['module_id'] = $moduleId;
                    $mergeData['project_id'] = $projectId;
                    $mergeData['field_type'] = $variableItem['type'];
                    $mergeData['variable_id'] = $variableItem['id'];
                    $mergeData['link_id'] = $data["id"];
                    $mergeData['comb_data'] = empty($variableItem["config"]["combo_list"]) ? [] : $variableItem["config"]["combo_list"];
                    $this->addVariableDefaultValue($mergeData);
                }
            }
        }
    }

    /**
     * 对于有自定义字段的模块当新增或者删除操作时候做出相应操作
     * @param $data
     * @return array
     */
    public function changeCustomFieldValue($data)
    {
        $variableValueModel = new VariableValueModel();
        $variableModel = new VariableModel();

        // 获取当前表所属 module_id
        $moduleId = $data["module_id"];

        $projectId = array_key_exists("project_id", $data) ? $data["project_id"] : 0;

        switch ($data["operate"]) {
            case "create":

                // 获取当前所有的自定义字段
                $variableData = $variableModel->selectData([
                    "filter" => ["module_id" => $moduleId]
                ]);

                if ($variableData["total"] > 0) {

                    // 获取当前module信息
                    $moduleData = D("module")->where(["id" => $moduleId])->find();

                    // 所有存在自定义字段ID
                    $variableIds = array_column($variableData["rows"], "id");

                    // 查询自定义字段值是否存在
                    $variableValueData = $variableValueModel->selectData([
                        "filter" => [
                            "module_id" => $moduleId,
                            "link_id" => $data["link_id"],
                            "variable_id" => ["IN", join(",", $variableIds)]]
                    ]);

                    // 存在值得自定义字段ID
                    $valueVariableIds = array_column($variableValueData["rows"], "variable_id");

                    // 保存数据
                    foreach ($variableData["rows"] as $variableItem) {
                        if (!in_array($variableItem['id'], $valueVariableIds)) {
                            $mergeData['module_type'] = $moduleData['type'];
                            $mergeData['module_code'] = $moduleData['code'];
                            $mergeData['module_id'] = $moduleId;
                            $mergeData['project_id'] = $projectId;
                            $mergeData['field_type'] = $variableItem['type'];
                            $mergeData['variable_id'] = $variableItem['id'];
                            $mergeData['link_id'] = $data["link_id"];
                            $mergeData['comb_data'] = empty($variableItem["config"]["combo_list"]) ? [] : $variableItem["config"]["combo_list"];
                            $this->addVariableDefaultValue($mergeData);
                        }
                    }
                }

                return success_response("", $data);
                break;
            case "delete":
                // 删除自定义字段数据
                $resData = $variableValueModel->deleteItem(["module_id" => $moduleId, "link_id" => $data["link_id"]]);
                if (!$resData) {
                    // 有自定义字段的模块的自定义字段的值删除失败 005
                    throw_strack_exception($variableValueModel->getError(), 230005);
                } else {
                    return success_response($variableValueModel->getSuccessMassege(), $data);
                }
                break;
        }
    }

    /**
     * 获取指定自定义字段配置
     * @param $variableId
     * @return mixed
     */
    public function getVariableConfig($variableId)
    {
        $variableModel = new VariableModel();
        $variableData = $variableModel->findData([
            "filter" => [
                "id" => $variableId
            ]
        ]);

        return $variableData["config"];
    }

    /**
     * 获取项目下的自定义字段
     * @param $projectId
     * @return array
     */
    public function getProjectCustomFields($projectId)
    {
        $variableModel = new VariableModel();
        $variableList = $variableModel->selectData([
            "filter" => ["project_id" => $projectId],
            "fields" => "name,code,type,action_scope,module_id,config"
        ]);
        if ($variableList["total"] > 0) {
            return $variableList["rows"];
        } else {
            return [];
        }
    }

    /**
     * 获取自定义字段列表
     * @return array
     */
    public function getCustomFieldList()
    {
        $variableModel = new VariableModel();
        $variableList = $variableModel->selectData([
            "fields" => "id,name"
        ]);
        if ($variableList["total"] > 0) {
            return $variableList["rows"];
        } else {
            return [];
        }
    }

    /**
     * 获取指定自定义水平关联信息
     * @param $variableItem
     * @param int $project_id
     * @return array
     */
    public function getVariableInfo($variableItem, $project_id = 0)
    {
        $info = $this->getVariableConfig($variableItem['variable_id']);

        if (empty($info)) {
            return ['code' => 1, 'message' => '水平关联字段不存在' . $variableItem['variable_id']];
        }

        if (empty($info['type'])) {
            return ['code' => 1, 'message' => '水平关联字段类型不存在' . $variableItem['variable_id']];
        }

        if ($info['type'] != 'horizontal_relationship') {
            return ['code' => 0, 'message' => '不为水平关联的字段直接跳过' . $variableItem['variable_id']];
        }

        if (empty($info['relation_module_id'])) {
            return ['code' => 1, 'message' => '水平关联字段数据异常' . $variableItem['variable_id']];
        }

        $module = new ModuleModel();
        $moduleData = $module->findData([
            "filter" => [
                "id" => $info['relation_module_id']
            ],
            "fields" => "code,type"
        ]);

        if (empty($moduleData['code'])) {
            return ['code' => 1, 'message' => '模板编码不能为空' . $info['relation_module_id']];
        }

        $moduleCode = $moduleData["type"] === "entity" ? $moduleData["type"] : $moduleData["code"];
        $commonService = new CommonService(string_initial_letter($moduleCode));
        $commonInfo = $commonService->find([
            "filter" => [
                "name" => $variableItem['value']
            ],
            "fields" => "id"
        ]);

        if (empty($commonInfo['data']['id'])) {
            return ['code' => 1, 'message' => $variableItem['value'] . '，此用户不存在'];
        }

        $param = [
            'src_link_id' => $variableItem['src_link_id'],
            'src_module_id' => $variableItem['module_id'],
            'dst_link_id' => $commonInfo['data']['id'],
            'dst_module_id' => $info['relation_module_id'],
            'variable_id' => $variableItem['variable_id'],
        ];
        $horizontal = new HorizontalService();
        $horizontal->modifyHorizontal($param);

        //给用户加默认加入成员组中
        if ($info['relation_module_id'] == 34) {
            $userId = $commonInfo['data']['id']; //用户id
            $project = new ProjectService();
            $project->appendProjectMember($project_id, $userId);
        }

        return ['code' => 0, 'message' => 'success'];
    }

    /**
     * 获取关联ID对应的值集合
     * @param $link_idArr
     * @param int $module_id
     * @param int $variable_id
     * @return array
     */
    public function getVariableValueList($linkIdArr, $moduleId = 0, $variableId = 0)
    {
        $variableValue = new VariableValueModel();
        $filter = [
            'link_id' => ["in", join(',', $linkIdArr)],
            'module_id' => $moduleId,
            'variable_id' => $variableId
        ];
        $list = $variableValue->field("link_id,value")
            ->where($filter)
            ->select();
        $outArr = [];
        foreach ($list as $item) {
            $outArr[$item['link_id']] = $item['value'];
        }
        return $outArr;
    }

    /**
     * 获取指定自定义字段值
     * @param $linkId
     * @param int $moduleId
     * @param int $variableId
     * @return mixed
     */
    public function getCustomVariableValue($linkId, $moduleId = 0, $variableId = 0)
    {
        $variableModel = new VariableModel();
        $variableConfig = $variableModel->findData([
            'fields' => 'config',
            'filter' => ['id' => $variableId]
        ]);
        $variableValue = new VariableValueModel();
        $filter = [
            'link_id' => $linkId,
            'module_id' => $moduleId,
            'variable_id' => $variableId
        ];
        $value = $variableValue->where($filter)
            ->getField('value');

        if (!empty($variableConfig['config']['combo_list'][$value])) {
            return $variableConfig['config']['combo_list'][$value];
        }

        return '';
    }
}

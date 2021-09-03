<?php
// +----------------------------------------------------------------------
// | 通用服务层
// +----------------------------------------------------------------------
// | 主要服务于API接口基类
// +----------------------------------------------------------------------
// | 错误编码头 202xxx
// +----------------------------------------------------------------------
namespace Common\Service;

use Common\Model\RoleModel;
use Common\Model\TimelogModel;
use Think\Request as TPRequest;
use Common\Model\BaseModel;
use Common\Model\ConfirmHistoryModel;
use Common\Model\EntityModel;
use Common\Model\FollowModel;
use Common\Model\HorizontalModel;
use Common\Model\ModuleModel;
use Common\Model\NoteModel;
use Common\Model\OnsetLinkModel;
use Common\Model\PlanModel;
use Common\Model\ProjectModel;
use Common\Model\ProjectTemplateModel;
use Common\Model\ReviewLinkModel;
use Common\Model\RoleUserModel;
use Common\Model\VariableValueModel;

class CommonService
{
    // model对象
    protected $modelObject;

    public function __construct($moduleName = '')
    {
        // 实例化当前model
        if (!empty($moduleName)) {
            $class = '\\Common\\Model\\' . string_initial_letter($moduleName) . 'Model';

            if (class_exists($class)) {
                $this->modelObject = new $class();
            } else {
                throw_strack_exception(L('Illegal_Operation'), 202006);
            }
        }
    }

    /**
     * 查询一条基础方法
     * @param $param
     * @return array
     */
    public function find($param)
    {
        $resData = $this->modelObject->findData($param);
        return success_response($this->modelObject->getSuccessMassege(), $resData);
    }

    /**
     * 查询多条基础方法
     * @param $param
     * @return mixed
     */
    public function select($param)
    {
        $resData = $this->modelObject->selectData($param);
        return success_response($this->modelObject->getSuccessMassege(), $resData);
    }

    /**
     * 创建基础方法
     * @param $param
     * @return array
     */
    public function create($param)
    {
        $resData = $this->modelObject->addItem($param);
        if (!$resData) {
            // 通用创建失败错误码 001
            throw_strack_exception($this->modelObject->getError(), 202001);
        } else {
            // 返回成功数据
            return success_response($this->modelObject->getSuccessMassege(), $resData);
        }
    }

    /**
     * 更新基础方法
     * @param $param
     * @return array
     */
    public function update($param)
    {
        $resData = $this->modelObject->modifyItem($param);
        if (!$resData) {
            // 通用修改失败错误码 002
            throw_strack_exception($this->modelObject->getError(), 202002);
        } else {
            // 返回成功数据
            return success_response($this->modelObject->getSuccessMassege(), $resData);
        }
    }

    /**
     * 删除基础方法
     * @param $param
     * @return array
     */
    public function delete($param)
    {
        $resData = $this->modelObject->deleteItem($param);
        if (!$resData) {
            // 通用删除失败错误码 003
            throw_strack_exception($this->modelObject->getError(), 202003);
        } else {
            // 返回成功数据
            return success_response($this->modelObject->getSuccessMassege(), $resData);
        }
    }

    /**
     * 关联查询
     * @param $param
     * @return array
     */
    public function relation($param)
    {

        $schemaService = new SchemaService();

        $schemaFields = $schemaService->generateModuleRelation($param);


        $resData = $this->modelObject->getRelationData($schemaFields, 'api');


        if (!isset($resData)) {
            // 通用关联查询失败错误码 004
            throw_strack_exception($this->modelObject->getError(), 202004);
        } else {
            // 返回成功数据
            return success_response($this->modelObject->getSuccessMassege(), $resData);
        }
    }

    /**
     * 获取保存数据
     * @param $masterData
     * @param $param
     * @return mixed
     */
    protected function getMasterSaveData(&$masterData, $param)
    {
        $entityModel = new EntityModel();
        switch ($param["module"]) {
            case "project_member":
                if (!array_key_exists("role_id", $masterData)) {
                    $roleUserModel = new RoleUserModel();
                    $masterData["role_id"] = $roleUserModel->where(["user_id" => $masterData["user_id"]])->getField("role_id");
                }
                break;
            case "entity":
                if (array_key_exists("parent_id", $masterData)) {
                    $masterData["parent_module_id"] = $entityModel->where(["id" => $masterData["parent_id"]])->getField("module_id");
                }
                break;
            case "file":
            case "file_commit":
                if (array_key_exists("from_module_id", $param) && $param["from_module_id"] > 0) {
                    $masterData["module_id"] = $param["from_module_id"];
                }

                if (array_key_exists("from_item_id", $param) && $param["from_item_id"] > 0) {
                    $masterData["link_id"] = $param["from_item_id"];
                }
                break;
            case "base":
                $schemaService = new SchemaService();
                $baseModel = new BaseModel();
                if (array_key_exists("from_module_id", $param) && $param["from_module_id"] > 0 && !array_key_exists("entity_id", $masterData)) {
                    $moduleData = $schemaService->getModuleFindData(["id" => $param["from_module_id"]]);
                    if ($moduleData["code"] === "base") {
                        $baseData = $baseModel->findData([
                            "filter" => ["id" => $param["from_item_id"]],
                            "fields" => "entity_id,entity_module_id"
                        ]);
                        if (!empty($baseData)) {
                            $masterData["entity_id"] = $baseData["entity_id"];
                            $masterData["entity_module_id"] = $baseData["entity_module_id"];
                        } else {
                            $masterData["entity_id"] = 0;
                            $masterData["entity_module_id"] = 0;
                        }
                    } else {
                        $masterData["entity_id"] = $param["from_item_id"];
                        $masterData["entity_module_id"] = $param["from_module_id"];
                    }
                }

                // 如果是修改实体id
                if (array_key_exists("entity_id", $masterData)) {
                    $moduleId = $entityModel->where(["id" => $masterData["entity_id"]])->getField("module_id");
                    $masterData["entity_module_id"] = $moduleId > 0 ? $moduleId : 0;
                }
                break;
        }

        return $masterData;
    }

    /**
     * 更新数据
     * @param $dataItem
     * @param $dataRows
     * @param $column
     * @param $otherModel
     * @return array
     */
    protected function modifyItemBatchData($dataItem, $dataRows, $column, $otherModel)
    {
        $resData = [];

        $primaryIdData = explode(",", $dataItem["primary_string_ids"]);
        $relationLinkIds = array_column($dataRows, $column);

        // 如果不为空，保存数据，否则更新数据
        if (!empty($relationLinkIds)) {
            // 数组比较，取差异值
            $diffValue = array_diff($relationLinkIds, $primaryIdData);
            // 存在差异，新增数据
            if (!empty($diffValue)) {
                foreach ($diffValue as $linkIdItem) {
                    $dataItem[$column] = $linkIdItem;
                    // 执行添加数据
                    $resData = $otherModel->addItem($dataItem);
                }
            } else { // 更新数据
                $relationPrimaryIds = array_column($dataRows, "id");
                $dataItem['id'] = ["IN", join(",", $relationPrimaryIds)];
                // 执行添加数据
                $resData = $otherModel->modifyItem($dataItem);
            }
        } else {
            foreach ($primaryIdData as $linkIdItem) {
                // 如果为自定义字段表时，关联条件为link_id
                $dataItem[$column] = $linkIdItem;
                // 执行添加数据
                $resData = $otherModel->addItem($dataItem);
            }
        }

        return $resData;
    }


    /**
     * 生成自定义字段保存数据
     * @param $field
     * @param $customData
     * @return mixed
     */
    protected function generateCustomFieldData($field, $customData)
    {
        $variableService = new VariableService();
        $variableConfig = $variableService->getVariableConfig($field['variable_id']);
        $relationModuleId = array_key_exists("relation_module_id", $variableConfig) ? $variableConfig["relation_module_id"] : 0;
        $relationModuleCode = array_key_exists("relation_module_code", $variableConfig) ? $variableConfig["relation_module_code"] : "";

        $customData["variable_id"] = $field["variable_id"];
        $customData["value"] = $field["value"];
        $customData["field_type"] = $field["field_type"];
        $customData["module_code"] = "variable_value";
        $customData["module_id"] = $field["module_id"];
        $customData["fields"] = $variableConfig["code"];
        $customData["type"] = $variableConfig["type"];
        $customData["relation_module_id"] = $relationModuleId;
        $customData["relation_module_code"] = $relationModuleCode;

        return $customData;
    }

    /**
     * 组装面板保存数据格式
     * @param $param
     * @param string $mode
     * @return array
     */
    protected function generateAddOrModifyBatchDataFormat($param, $mode = 'create')
    {
        // 获取module字段数据
        $schemaService = new SchemaService();
        $moduleCodeMapData = $schemaService->getModuleMapData("code");

        // 初始化返回数据
        $resData = [
            "master_data" => [],
            "relation_data" => []
        ];

        if ($mode === 'modify') {
            // 通过主表得过滤条件获取主键id
            $masterIdData = $this->modelObject->field('id')->where($param["param"]['filter'])->select();
            $resData["master_data"]["id"] = !empty($masterIdData) ? ["IN", join(',', array_column($masterIdData, 'id'))] : 0;
        }

        // 主表module code
        $masterModuleCode = $param["module"]["type"] === "entity" ? $param["module"]["type"] : $param["module"]["code"];

        foreach ($param["data"] as $key => $fieldData) {

            // entity模块增加 module_id参数
            if ($key === "entity") {
                $resData["master_data"]['module_id'] = $moduleCodeMapData[$param["module"]["code"]]["id"];
            }

            // 处理字段数据
            foreach ($fieldData as $fieldItem) {
                if ($fieldItem['field_type'] == "built_in") {
                    // 处理固定字段
                    if ($key === $masterModuleCode) {
                        // 处理主表
                        $resData["master_data"][$fieldItem['field']] = $fieldItem["value"];
                    } else {
                        // 关联表固定字段
                        $relationData = [];
                        $relationData['field_type'] = $fieldItem['field_type'];
                        $relationData[$fieldItem['field']] = $fieldItem['value'];
                        $relationData['module_id'] = $moduleCodeMapData[$param["module"]["code"]]["id"];
                        $relationData['module_code'] = $key;

                        $resData["relation_data"][$key][] = $relationData;
                    }
                } else {
                    // 处理自定义字段
                    $fieldItem["module_id"] = $moduleCodeMapData[$param["module"]["code"]]["id"];
                    // 生成自定义字段数据结构
                    $resData["relation_data"][$key][] = $this->generateCustomFieldData($fieldItem, $fieldItem);
                }
            }
        }

        return [
            'master_module_code' => $masterModuleCode,
            'data' => $resData
        ];
    }

    /**
     * api通用添加方法
     * @param $param
     * @return array
     */
    public function apiAddItemDialog($param)
    {
        // 格式化数据
        $formatData = $this->generateAddOrModifyBatchDataFormat($param, 'create');
        $param["module"] = $formatData['master_module_code'];

        return $this->addItemDialog($formatData['data'], $param);
    }

    /**
     * 添加数据-自定义字段和固定字段添加打平 同时还可以添加关联表信息
     * @param $updateData
     * @param $param
     * @return array
     */
    public function addItemDialog($updateData, $param)
    {
        $moduleCode = $param["module"];

        $tagService = new TagService();
        $mediaService = new MediaService();
        $horizontalService = new HorizontalService();

        $createModel = $this->modelObject; // 实例化主模块Model
        $createModel->startTrans(); // 开启事务
        try {
            $this->getMasterSaveData($updateData["master_data"], $param);

            if (!array_key_exists("module_id", $updateData["master_data"]) && $moduleCode === "entity") {
                $updateData["master_data"]["module_id"] = $param["module_id"];
            }

            // 保存主表信息
            $masterData = $createModel->addItem($updateData["master_data"]);

            if (!$masterData) {
                throw new \Exception($createModel->getError());
            }

            // 保存关联表信息
            foreach ($updateData["relation_data"] as $key => $dataItem) {
                foreach ($dataItem as $item) {
                    $class = '\\Common\\Model\\' . string_initial_letter($item["module_code"]) . 'Model';
                    $otherModel = new $class();
                    $resData = [];
                    if ($item["field_type"] == "built_in") {
                        switch ($key) {
                            case "media":
                                $mediaServerData = $mediaService->getMediaServerItem(['id' => $item["media_server_id"]]);
                                $resData = $mediaService->saveMediaData([
                                    "media_data" => $item["param"],
                                    "media_server" => $mediaServerData,
                                    'link_id' => $masterData["id"],
                                    'module_id' => $item["module_id"],
                                    "mode" => "single"
                                ]);
                                break;
                            case "tag":
                                $resData = $tagService->modifyDiffTagLink([
                                    "module_id" => $item["module_id"],
                                    "link_id" => $masterData["id"],
                                    "tag_id" => $item["name"]
                                ]);
                                break;
                            default:
                                $item[$moduleCode . '_id'] = $masterData['id'];
                                $resData = $otherModel->addItem($item);
                                if (!$resData) {
                                    throw new \Exception($otherModel->getError());
                                }
                                break;
                        }
                        $masterData[$item["module_code"]] = $resData;
                    } else {
                        $item['link_id'] = $key === $moduleCode ? $masterData["id"] : $resData["id"];
                        switch ($item["type"]) {
                            case "belong_to":
                            case "horizontal_relationship":
                                if ($item["relation_module_code"] == "media") {
                                    $mediaServerData = $mediaService->getMediaServerItem(['id' => $item["media_data"]["media_server_id"]]);
                                    $mediaService->saveMediaData([
                                        "media_data" => $item["media_data"]["param"],
                                        "media_server" => $mediaServerData,
                                        "link_id" => $item['link_id'],
                                        "module_id" => $item["module_id"],
                                        "mode" => "single",
                                        "variable_id" => $item["variable_id"],
                                        "field_type" => "custom"
                                    ]);
                                } else {
                                    $mode = $item["type"] === "belong_to" ? "single" : "batch";
                                    $horizontalService->saveHorizontalRelationData($item, $mode);
                                }
                                $masterData[$item["fields"]] = [];
                                $ids = explode(",", $item["value"]);
                                foreach ($ids as $id) {
                                    $push = ["id" => intval($id)];
                                    array_push($masterData[$item["fields"]], $push);
                                }
                                break;
                            default:
                                if (in_array($item["type"], ["datebox", "datetimebox"])) {
                                    $item['value'] = strtotime($item['value']);
                                }

                                // 因为存在异步event队列首先判断是否存在
                                $customFilter = [
                                    "module_id" => $item["module_id"],
                                    "link_id" => $item["link_id"],
                                    "variable_id" => $item["variable_id"]
                                ];

                                if ($otherModel->where($customFilter)->count() > 0) {
                                    $resData = $otherModel->where($customFilter)->save(["value" => $item["value"]]);
                                } else {
                                    $customFilter["value"] = $item["value"];
                                    $resData = $otherModel->addItem($customFilter);
                                }

                                if (!$resData) {
                                    throw new \Exception($otherModel->getError());
                                }
                                $masterData[$item["fields"]] = $item["value"];
                                break;
                        }
                    }
                }
            }

            $createModel->commit(); // 提交事物

            $message = $createModel->getSuccessMassege();
            TPRequest::$serviceOperationResData = $masterData;

            return success_response($message, $masterData);
        } catch (\Exception $e) {
            $createModel->rollback(); // 事物回滚
            // 添加数据失败错误码 005
            throw_strack_exception($e->getMessage(), 202005);
        }
    }

    /**
     * api通用添加接口
     * @param $param
     * @return array
     */
    public function apiModifyItemDialog($param)
    {
        // 格式化数据
        $formatData = $this->generateAddOrModifyBatchDataFormat($param, 'modify');
        $param["module"] = $formatData['master_module_code'];
        $param["primary_id"] = $formatData['data']["master_data"]["id"][1];

        return $this->modifyItemDialog($formatData['data'], $param);
    }

    /**
     * 修改数据-自定义字段和固定字段修改打平 同时还可以修改关联表信息
     * @param $updateData
     * @param $param
     * @return array
     */
    public function modifyItemDialog($updateData, $param)
    {
        $moduleCode = $param["module"];

        // api处理
        if (strtolower($moduleCode) == "task") {
            $moduleCode = "base";
        }

        $message = "";

        $tagService = new TagService();
        $mediaService = new MediaService();
        $horizontalService = new HorizontalService();

        $createModel = $this->modelObject; // 实例化主模块Model
        $createModel->startTrans(); // 开启事务

        try {
            $masterData = [];
            if (array_key_exists("master_data", $updateData) && !empty($updateData["master_data"])) {
                $this->getMasterSaveData($updateData["master_data"], $param);

                if (!array_key_exists("module_id", $updateData["master_data"]) && $moduleCode === "entity") {
                    $updateData["master_data"]["module_id"] = $param["module_id"];
                }

                // 保存主表信息
                $masterData = $createModel->modifyItem($updateData["master_data"]);
                if (!$masterData) {
                    $masterData = array_diff_key($updateData["master_data"], ["field_type" => "built_in"]);
                } else {
                    $message = $createModel->getSuccessMassege();
                }
            }

            $primaryIds = $param["primary_id"]; // 获取主键id
            foreach ($updateData["relation_data"] as $key => $dataItem) {
                // 修改关联表信息
                foreach ($dataItem as $item) {
                    $class = '\\Common\\Model\\' . string_initial_letter($item["module_code"]) . 'Model';
                    $otherModel = new $class();
                    $resData = [];

                    if ($item["field_type"] == "built_in") {
                        $masterPrimaryIds = explode(",", $primaryIds);
                        switch ($key) {
                            case "media": // media批量修改
                                $mediaServerData = $mediaService->getMediaServerItem(['id' => $item["media_server_id"]]);
                                foreach ($masterPrimaryIds as $primaryId) {
                                    $resData = $mediaService->saveMediaData([
                                        "media_data" => $item["param"],
                                        "media_server" => $mediaServerData,
                                        'link_id' => $primaryId,
                                        'module_id' => $item["module_id"],
                                        "mode" => "single"
                                    ]);
                                }
                                break;
                            case "tag": // tag批量修改
                                $masterPrimaryIds = explode(",", $primaryIds);
                                foreach ($masterPrimaryIds as $primaryId) {
                                    $resData = $tagService->modifyDiffTagLink([
                                        "module_id" => $item["module_id"],
                                        "tag_id" => $item["name"],
                                        "link_id" => $primaryId,
                                    ]);
                                }
                                break;
                            default:  // 批量更新数据
                                $selectData = $otherModel->selectData([
                                    "filter" => [$moduleCode . '_id' => ["IN", $primaryIds]],
                                    "fields" => "id," . $moduleCode . '_id'
                                ]);

                                $item["primary_string_ids"] = $primaryIds;
                                $resData = $this->modifyItemBatchData($item, $selectData["rows"], "{$moduleCode}_id", $otherModel);
                                break;
                        }
                    } else {
                        $primaryStringIds = $key === $moduleCode ? $primaryIds : $resData['id'];
                        $primaryLinkIds = explode(",", $primaryStringIds);
                        switch ($item["type"]) {
                            case "belong_to":
                            case "horizontal_relationship":
                                if ($item["relation_module_code"] === "media") {
                                    foreach ($primaryLinkIds as $primaryLinkId) {
                                        $mediaServerData = $mediaService->getMediaServerItem(['id' => $item["media_data"]["media_server_id"]]);
                                        $resData = $mediaService->saveMediaData([
                                            "media_data" => $item["media_data"]["param"],
                                            "media_server" => $mediaServerData,
                                            "link_id" => $primaryLinkId,
                                            "module_id" => $item["module_id"],
                                            "mode" => "single",
                                            "variable_id" => $item["variable_id"],
                                            "field_type" => "custom"
                                        ]);
                                    }
                                } else {
                                    $mode = $item["type"] === "belong_to" ? "single" : "batch";
                                    foreach ($primaryLinkIds as $primaryLinkId) {
                                        $item["link_id"] = $primaryLinkId;
                                        $resData = $horizontalService->saveHorizontalRelationData($item, $mode);
                                    }
                                }
                                break;
                            default:
                                // 如果为date类型 需要格式化后保存
                                if (in_array($item["type"], ["datebox", "datetimebox"])) {
                                    $item['value'] = strtotime($item['value']);
                                }
                                $linkId = $key === $moduleCode ? ["IN", $primaryIds] : ["IN", $resData['id']];
                                // 获取当前的批量编辑的自定义字段数据
                                $selectData = $otherModel->selectData([
                                    'filter' => ['link_id' => $linkId, 'module_id' => $item['module_id'], 'variable_id' => $item['variable_id']],
                                    'fields' => 'id,link_id'
                                ]);
                                // 批量更新数据
                                $item["primary_string_ids"] = $primaryStringIds;
                                $resData = $this->modifyItemBatchData($item, $selectData["rows"], "link_id", $otherModel);
                                break;
                        }
                    }

                    $message = L('Modify_' . string_initial_letter($moduleCode, '_') . '_SC');

                    if (array_key_exists("type", $item)) {
                        if (!in_array($item["type"], ["horizontal_relationship"])) {
                            $masterData[$item["fields"]] = $item["value"];
                        } else {
                            $masterData[$item["fields"]] = [];
                            $ids = explode(",", $item["value"]);
                            foreach ($ids as $id) {
                                $push = ["id" => intval($id)];
                                array_push($masterData[$item["fields"]], $push);
                            }
                        }
                    } else {
                        $masterData[$item["module_code"]] = $resData;
                    }
                }
            }

            $createModel->commit(); // 提交事物

            $masterData['id'] = $param['primary_id'];
            TPRequest::$serviceOperationResData = $masterData;

            return success_response($message, $masterData);
        } catch (\Exception $e) {
            $createModel->rollback(); // 事物回滚
            // 修改关联数据失败错误码 002
            throw_strack_exception($e->getMessage(), 202006);
        }
    }


    /**
     * 通用关联查询关联查询
     * @param $param
     * @param null $method
     * @return array
     */
    public function getRelation($param, $method = null)
    {
        // 生成关联查询结构
        $viewService = new ViewService();
        $schemaFields = $viewService->generateApiModuleRelation($param, $method);

        //echo json_encode($schemaFields);die;

        // 关联查询
        $resData = $this->modelObject->getRelationData($schemaFields, 'api');

        if (!isset($resData)) {
            // 通用关联查询失败错误码 004
            throw_strack_exception($this->modelObject->getError(), -202004);
        } else {
            // 返回成功数据
            if ($method === 'find') {
                return success_response($this->modelObject->getSuccessMassege(), $resData['rows'][0]);
            }
            return success_response($this->modelObject->getSuccessMassege(), $resData);
        }
    }

    /**
     * 保存新增的面板控件数据
     * @param $updateData
     * @param $param
     * @return array
     */
    public function saveNewItemDialog($updateData, $param)
    {
        $templateTable = ["status", "step"];
        $createModel = $this->modelObject;// 实例化主模块Model

        // 获取模版ID
        $templateService = new TemplateService();
        $templateId = $templateService->getProjectTemplateID($param["project_id"]);

        // 开启事务
        $createModel->startTrans();
        try {

            // 保存主表信息
            $masterData = $createModel->addItem($updateData["master_data"]);
            if (!$masterData) {
                throw new \Exception($createModel->getError());
            }

            if (in_array($param["module_code"], $templateTable)) {
                // 获取模版配置数据
                $templateConfig = $templateService->getTemplateConfig([
                    'filter' => [
                        'project_id' => $param['project_id']
                    ],
                    'module_code' => $param["form_module_data"]["code"],
                    'category' => $param["module_code"]
                ]);

                switch ($param["module_code"]) {
                    case 'status':
                        array_push($templateConfig, ["id" => $masterData["id"]]);
                        break;
                    case 'step':
                        array_push($templateConfig, $masterData);
                        break;
                }

                $templateParam = [
                    "category" => $param["module_code"],
                    "module_code" => $param["form_module_data"]["code"],
                    "template_id" => $templateId,
                    "config" => $templateConfig
                ];

                $templateService->modifyTemplateConfig($templateParam);
            }
            $createModel->commit(); // 提交事物
            return success_response($createModel->getSuccessMassege());
        } catch (\Exception $e) {
            $createModel->rollback(); // 事物回滚
            // 修改关联数据失败错误码 002
            throw_strack_exception($e->getMessage(), 202009);
        }
    }

    /**
     * 获取修改单个组件的提示信息
     * @param $module
     * @param $moduleCode
     * @param $field
     * @return mixed
     */
    private function getUpdateWidgetMessage($module, $moduleCode, $field)
    {
        switch ($module) {
            case "variable_value":
            case "variable":
                return L('Modify_Variable_SC') . ":(" . $field . ")";
            case "tag_link":
                return L('Modify_Tag_Name_Sc');
            default:
                return L('Modify_' . string_initial_letter($moduleCode, '_') . '_' . $field . '_SC');
        }

    }

    /**
     * 修改单个组件
     * @param $param
     * @param $updateData
     * @return array
     */
    public function updateWidget($param, $updateData)
    {
        // 实例化主模块Model
        $modelObj = $this->modelObject;

        switch ($param["module"]) {
            case "tag_link":
            case "tag":
                $tagService = new TagService();
                $modifyData = $tagService->modifyDiffTagLink($updateData);

                // 格式化tagName显示
                $tagData = $tagService->getTagDataList(["filter" => ["id" => ["IN", $updateData["tag_id"]]]]);
                $updateData["name"] = $tagData;
                $updateData["tag_name"] = "";
                if ($tagData["total"] > 0) {
                    $nameList = array_column($tagData["rows"], "name");
                    $updateData["tag_name"] = join(",", $nameList);
                }

                break;
            case "role_user":
                $roleUserModel = new RoleUserModel();
                $itemData = [
                    'user_id' => $updateData["id"],
                    'role_id' => $updateData["role_id"],
                ];

                $roleUserId = $roleUserModel->where(['user_id' => (int)$updateData["id"]])->getField("id");

                if (!empty($roleUserId)) {
                    $itemData['id'] = $roleUserId;
                    $modifyData = $modelObj->modifyItem($itemData);
                } else {
                    $modifyData = $modelObj->addItem($itemData);
                }
                break;
            case "variable_value":
                $horizontalService = new HorizontalService();
                switch ($param["data_source"]) {
                    case "horizontal_relationship":
                    case "belong_to":
                        $mode = $param["data_source"] === "belong_to" ? "single" : "batch";
                        $modifyData = $horizontalService->saveHorizontalRelationData($updateData, $mode);
                        $updateData["custom_config"] = $updateData;
                        break;
                    default:
                        // 调用修改单个组件的方法
                        $modifyData = $modelObj->modifyItem($updateData);
                        break;
                }
                break;
            case 'role':
                $modifyData = $modelObj->modifyItem($updateData);
                if ($param['primary'] === 'project_member_id') {
                    $roleModel = new RoleModel();
                    $updateData['name'] = $roleModel->where(['id' => $updateData['role_id']])->getField('name');
                }
                break;
            default:
                // 调用修改单个组件的方法
                $modifyData = $modelObj->modifyItem($updateData);
                break;
        }
        if (!$modifyData) {
            // 修改单个组件数据失败错误码 007
            throw_strack_exception($modelObj->getError(), 202007);
        } else {
            if (array_key_exists("module_id", $updateData)) {
                $moduleFilter = ['id' => $updateData['module_id']];
            } else {
                switch ($param["module"]) {
                    case "entity":
                        $entityModel = new EntityModel();
                        $moduleId = $entityModel->where(["id" => $modifyData["id"]])->getField("module_id");
                        $moduleFilter = ["id" => $moduleId];
                        if (array_key_exists("parent_module_id", $updateData)) {
                            $name = $entityModel->where(["id" => $updateData["parent_id"]])->getField("name");
                            $updateData["name"] = $name;
                        }
                        break;
                    default :
                        $moduleFilter = ['code' => $param['module']];
                        break;
                }
            }

            $schemaService = new SchemaService();
            $moduleData = $schemaService->getModuleFindData($moduleFilter);
            $message = $this->getUpdateWidgetMessage($param["module"], $moduleData["code"], $param["field"]);

            $updateData["id"] = $param["primary_value"];
            if (!array_key_exists("name", $updateData)) {
                $updateData["name"] = array_key_exists("name", $modifyData) ? $modifyData["name"] : "";
            }

            TPRequest::$serviceOperationResData = $updateData;
            TPRequest::$serviceOperationModuleFilter = $moduleFilter;

            return success_response($message, $modifyData);
        }
    }

    /**
     * 删除共同数据
     * @param $param
     */
    protected function deleteCommonLinkData($param)
    {
        // 删除反馈
        $noteModel = new NoteModel();
        $noteData = $noteModel->where([
            'link_id' => ['IN', $param['primary_ids']],
            'module_id' => $param["module_id"]
        ])->select();

        if (!empty($noteData)) {
            $noteIds = array_column($noteData, 'id');
            $noteParam = [
                'id' => join(",", $noteIds),
                'module_id' => C("MODULE_ID")["note"],
                'type' => "attachment"
            ];
            $noteService = new NoteService();
            $noteService->deleteNote($noteParam);
        }

        try {
            // 统一删除媒体数据
            $mediaService = new MediaService();
            $mediaService->batchClearMediaThumbnail([
                'link_id' => $param['primary_ids'],
                'module_id' => $param["module_id"],
                'mode' => 'batch'
            ]);
        } catch (\Exception $e) {

        }

        try {
            // 删除标签关联数据
            $tagService = new TagService();
            $tagService->deleteTagLink([
                'link_id' => ['IN', $param['primary_ids']],
                'module_id' => $param["module_id"]
            ]);
        } catch (\Exception $e) {

        }

        try {
            // 删除时间日志关联数据
            $timelogModel = new TimelogModel();
            $timelogModel->deleteItem([
                'link_id' => ['IN', $param['primary_ids']],
                'module_id' => $param["module_id"]
            ]);
        } catch (\Exception $e) {

        }

        try {
            // 删除水平关联数据
            $horizontalService = new HorizontalService();
            $horizontalService->deleteHorizontal([
                'src_link_id' => ['IN', $param['primary_ids']],
                'src_module_id' => $param["module_id"]
            ]);
        } catch (\Exception $e) {

        }
    }

    /**
     * 删除联动数据
     * @param $param
     */
    public function deleteLinkageData($param)
    {
        $module = $param["module_type"] === "entity" ? $param["module_type"] : $param["module_code"];
        switch ($module) {
            case "entity":
                $linkFilter = ["link_id" => ["IN", $param["primary_ids"]], "module_id" => $param["module_id"]];

                // 删除审核关联
                $reviewLinkModel = new ReviewLinkModel();
                $reviewLinkModel->deleteItem(["entity_id" => ["IN", $param["primary_ids"]]]);

                // 删除现场数据关联
                $onsetLinkModel = new OnsetLinkModel();
                $onsetLinkModel->deleteItem($linkFilter);

                // 删除关注信息
                $followModel = new FollowModel();
                $followModel->deleteItem($linkFilter);

                // 删除任务信息
                $baseModel = new BaseModel();
                $baseModel->deleteItem(["entity_id" => ["IN", $param["primary_ids"]]]);
                $baseData = $baseModel->selectData(["filter" => ["entity_id" => ["IN", $param["primary_ids"]]]]);
                if ($baseData["total"] > 0) {
                    $baseIds = array_column($baseData["rows"], "id");
                    $this->deleteCommonLinkData([
                        "primary_ids" => join(",", $baseIds),
                        "module_id" => C("MODULE_ID")["base"],
                    ]);
                }
                break;
            case "base":
                // 任务需要删除关联的计划数据
                $linkFilter = ["link_id" => ["IN", $param["primary_ids"]], "module_id" => $param["module_id"]];
                $planModel = new PlanModel();
                $planModel->deleteItem($linkFilter);
                break;
        }

        $this->deleteCommonLinkData($param);
    }

    /**
     * 删除表格数据
     * @param $param
     * @return array
     * @throws \Think\Exception
     */
    public function deleteGridData($param)
    {
        // 存放消息参数
        $moduleId = $param['module_id'];

        $message = '';
        // 存在form信息
        if (array_key_exists("from_module_id", $param)
            && !empty($param["from_module_id"])
            && array_key_exists("from_item_id", $param)
            && !empty($param["from_item_id"])
            && !in_array($param["module_type"], ["file", "file_commit", "correlation_base"])
        ) {

            if (strpos($param['page'], '_child_') !== false) {
                // 删除
                $entityModel = new EntityModel();
                $deleteData = $entityModel->updateWidget([
                    "id" => ["IN", $param['primary_ids']],
                    'parent_id' => 0,
                    'parent_module_id' => 0
                ]);

            } else {
                // 删除水平关联数据
                $horizontalService = new HorizontalService();
                $deleteData = $horizontalService->deleteHorizontal([
                    'dst_link_id' => ['IN', $param['primary_ids']],
                    'dst_module_id' => $moduleId,
                    'src_module_id' => $param["from_module_id"],
                    'src_link_id' => $param["from_item_id"],
                ]);

                $message = $deleteData["message"];
            }

        } else {

            switch ($param['module_code']) {
                case "note":
                    $noteService = new NoteService();
                    $noteParam = [
                        'module_id' => $moduleId,
                        'id' => $param['primary_ids'],
                    ];
                    $deleteData = $noteService->deleteNote($noteParam, "widget_grid");
                    $message = $deleteData["message"];
                    break;
                default:
                    $modelObj = $this->modelObject;// 实例化主模块Model
                    $deleteData = $modelObj->deleteItem(['id' => ["IN", $param['primary_ids']]]);
                    if (!$deleteData) {
                        // 删除表格数据失败 008
                        throw_strack_exception($this->modelObject->getError(), 202008);
                    }
                    $message = $this->modelObject->getSuccessMassege();
                    break;
            }
            // 删除联动数据
            $this->deleteLinkageData($param);
        }

        // 返回当新增数据
        return success_response($message, $deleteData);
    }

    /**
     * 获取指定详细信息数据
     * @param $param
     * @param $moduleCode
     * @return array
     */
    public function getModuleItemInfo($param, $moduleCode)
    {
        $resData = [];
        $mediaData = [];
        $resFieldConfig = [
            "group" => "yes",
            "data" => []
        ];

        // 实例化主模块Model
        $modelObj = $this->modelObject;
        $viewService = new ViewService();
        $schemaService = new SchemaService();

        $moduleData = [
            "id" => $param["module_id"],
            "code" => $param["module_code"],
            "type" => $param["module_type"]
        ];

        // 当前数据结构配置
        $param["filter"] = [
            "sort" => [],
            "group" => [],
            "request" => [
                [
                    "field" => "id",
                    "field_type" => 'built_in',
                    "editor" => "combobox",
                    "value" => $param["item_id"],
                    "condition" => "EQ",
                    "module_code" => $param["module_code"],
                    "table" => string_initial_letter($moduleCode),
                ]
            ]
        ];

        // 获取查询数据
        $schemaQueryFields = $viewService->getGridQuerySchemaConfig($param);
        $getRelationData = $modelObj->getRelationData($schemaQueryFields);
        if ($getRelationData["total"] > 0) {
            $resData = $getRelationData["rows"][0];
        }

        // 组装字段数据
        $schemaViewFields = $viewService->getGridQuerySchemaConfig($param, "view");
        $schemaFieldsConfig = $schemaService->generateColumnsConfig($schemaViewFields, $moduleData, false, ["media"]);

        $viewService = new ViewService();

        // 获取任务关联用户数据
        $baseService = new BaseService();
        $baseHorizontalUserData = $baseService->getBaseHorizontalUserAuthData(check_param_int_empty($param, 'item_id'));

        foreach ($schemaFieldsConfig as $fieldItem) {
            $valueShowKey = $schemaService->getFieldColumnName($fieldItem, $param["module_code"]);
            $fieldItem["value_show"] = $valueShowKey;
            $fieldItem["lang"] = $fieldItem["field_type"] === "built_in" ? L($fieldItem["lang"]) : $fieldItem["lang"];

            // 判断权限字段权限
            $resFieldConfig["data"][string_initial_letter($fieldItem["module_code"], "_")][] = $viewService->checkTableFieldAuth($fieldItem, $baseHorizontalUserData);
        }

        // 获取缩略图
        if ($param["category"] !== "main_field") {
            $mediaService = new MediaService();
            $mediaData = $mediaService->getMediaData([
                'link_id' => $param["item_id"],
                'module_id' => $param["module_id"],
                'relation_type' => 'direct',
                'type' => 'thumb'
            ]);
        }

        // 单独判断顶部字段
        $allowGetUserConfig = true;
        if ($param["category"] === "main_field") {
            $userService = new UserService();
            $mainFieldModeConfig = $userService->getUserCustomConfig([
                'type' => 'fields_show_mode',
                'user_id' => session('user_id')
            ]);

            if (!empty($mainFieldModeConfig) && $mainFieldModeConfig['config']['mode'] === 'all') {
                $allowGetUserConfig = false;
            }
        }

        if (in_array($param["category"], ["top_field", "main_field"]) && $allowGetUserConfig) {

            $detailsColumnConfig = $viewService->generateDetailsTopColumnsConfig(
                session("user_id"),
                $param,
                $schemaFieldsConfig
            );

            if (empty($detailsColumnConfig) && $param["category"] === "main_field") {
                return ["config" => $resFieldConfig, "data" => $resData, "media_data" => $mediaData];
            } else {
                $resFieldConfig["group"] = "no";
                $resFieldConfig["data"] = $detailsColumnConfig;

                $resFieldConfig["data"] = array_column($resFieldConfig["data"], null);
                $topFieldResData = [];
                foreach ($resFieldConfig["data"] as $fieldItem) {
                    if (array_key_exists($fieldItem["value_show"], $resData)) {
                        $topFieldResData[$fieldItem["value_show"]] = $resData[$fieldItem["value_show"]];
                    }
                }

                return ["config" => $resFieldConfig, "data" => $topFieldResData, "media_data" => $mediaData];
            }
        }

        return ["config" => $resFieldConfig, "data" => $resData, "media_data" => $mediaData];
    }

    /**
     * 获取面包屑导航
     * @param $param
     * @return mixed
     */
    public function getModuleBreadcrumb($param)
    {
        $resData = [];

        $entityModel = new EntityModel();

        // 获取module字典数据
        $schemaService = new SchemaService();
        $moduleIdMapData = $schemaService->getModuleMapData("id");

        // 获取当前数据
        $itemData = $this->modelObject->findData([
            "filter" => ["id" => $param["item_id"]],
        ]);

        // 区分本身
        $itemData["is_self"] = "yes";
        $itemData["is_details"] = "yes";
        switch ($param["module_type"]) {
            case "entity":
                $entityListData = $entityModel->selectData([
                    "filter" => [
                        "id" => $itemData["parent_id"],
                        "project_id" => $param["project_id"]
                    ],
                    "fields" => "id as item_id,name,module_id,parent_id,parent_module_id,project_id"
                ]);

                if (!empty($itemData) && $itemData["parent_id"] > 0) {
                    $resData = [];
                    $schemaService->getEntityParentModuleData($resData, $entityListData["rows"], $itemData["parent_id"]);
                }
                break;
            default:
                switch ($param["module_code"]) {
                    case "base":
                    case "correlation_base":
                        if (!empty($itemData["entity_id"]) && !empty($itemData["entity_module_id"])) {
                            $entityData["is_self"] = "no";
                            $entityData["is_details"] = "yes";
                            $entityInfoData = [
                                "is_self" => "no",
                                "name" => $entityModel->where(["id" => $itemData["entity_id"], "module_id" => $itemData["entity_module_id"]])->getField("name"),
                                "module_id" => $itemData["entity_module_id"],
                                "item_id" => $itemData["entity_id"],
                                "project_id" => $param["project_id"],
                                "module_lang" => L(string_initial_letter($moduleIdMapData[$itemData["entity_module_id"]]["code"], "_")),
                            ];
                            $listData = array_merge($itemData, $entityInfoData);
                            array_push($resData, $listData);
                        }
                        break;
                }
                break;
        }

        $itemData["item_id"] = $itemData["id"];
        array_push($resData, $itemData);

        // 增加项目名称显示
        if (array_key_exists('project_id', $itemData)) {
            $projectModel = new ProjectModel();
            $projectName = $projectModel->where(['id' => $itemData['project_id']])->getField('name');
            $url = rebuild_url(U('project/base'), $itemData['project_id']);

            array_unshift($resData, [
                'name' => $projectName,
                'is_self' => 'no',
                'url' => $url,
                'is_details' => 'no',
                'module_lang' => L('Project')
            ]);
        }
        return $resData;
    }

    /**
     * 获取任务审核状态
     * @param $itemData
     * @param $baseId
     */
    public function getBaseConfirmStatus(&$itemData, $baseId)
    {
        // 获取系统字段配置
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        $itemData['af_settlement_bnt'] = 'no';
        $itemData['cf_settlement_bnt'] = 'no';
        $itemData['jj_settlement_bnt'] = 'no';

        $itemData['assignee_id'] = '';
        $itemData['reviewed_id'] = '';
        $itemData['assignee_eq_reviewed'] = 'no';
        $itemData['af_settlement_bnt_lang'] = L('Application_For_Settlement');

        $currentUserId = session('user_id');

        if ($formulaConfigData !== false) {
            $moduleIds = C('MODULE_ID');
            // 查询当前模块是否发起了结算或者已经完成结算
            $confirmHistoryModel = new ConfirmHistoryModel();
            $confirmData = $confirmHistoryModel
                ->field('id,link_id,module_id,user_id,operation')
                ->where([
                    'link_id' => $baseId,
                    'module_id' => $moduleIds['base'],
                ])
                ->order('created desc')
                ->find();
            if (!isset($confirmData) || $confirmData['operation'] !== 'confirm') {
                // 获取当前任务执行人
                $horizontalModel = new HorizontalModel();
                $assignee = $horizontalModel->where([
                    'src_link_id' => $baseId,
                    'src_module_id' => $moduleIds['base'],
                    'dst_module_id' => $moduleIds['user'],
                    'variable_id' => $formulaConfigData['assignee_field']
                ])->getField('dst_link_id');

                $itemData['assignee_id'] = $assignee;

                // 获取当前任务分派人
                $reviewer = $horizontalModel->where([
                    'src_link_id' => $baseId,
                    'src_module_id' => $moduleIds['base'],
                    'dst_module_id' => $moduleIds['user'],
                    'variable_id' => $formulaConfigData['reviewed_by']
                ])->getField('dst_link_id');

                $itemData['reviewed_id'] = $reviewer;

                if ($assignee === $reviewer) {
                    $itemData['assignee_eq_reviewed'] = 'yes';
                    $itemData['af_settlement_bnt_lang'] = L('Confirmation_For_Settlement');
                }

                // 我既不属于执行人也不属于分派人就没有任何权限
                if ($currentUserId == $assignee || $currentUserId == $reviewer) {
                    /**
                     * 1、没有确认前申请按钮一直存在
                     * 2、申请发起方没有确认按钮，也没有申请按钮，拒绝后恢复
                     * 3、没人申请就没有确认按钮
                     */
                    if (!empty($confirmData)) {
                        // 有人发起申请我不是申请人就拥有确认按钮和拒绝按钮
                        if ($confirmData['operation'] === 'apply') {
                            if ($confirmData['user_id'] != $currentUserId) {
                                // 申请人不是发起人
                                $itemData['af_settlement_bnt'] = 'no';
                                $itemData['jj_settlement_bnt'] = 'yes';
                                $itemData['cf_settlement_bnt'] = 'yes';
                            } else {
                                // 是当前申请人
                                $itemData['af_settlement_bnt'] = 'no';
                            }
                        } else {
                            // 拒绝跟没人申请一样
                            $itemData['af_settlement_bnt'] = 'yes';
                        }
                    } else {
                        // 没人申请
                        $itemData['af_settlement_bnt'] = 'yes';
                    }
                }
            }
        }
    }

    /**
     * 更新任务审核数据
     * @param $param
     * @return array
     */
    public function updateBaseConfirmationData($param)
    {
        $confirmHistoryModel = new ConfirmHistoryModel();

        // 拒绝判断是否填写了拒绝理由
        $mediaData = [];
        $noteId = 0;
        if ($param['type'] === 'reject' && !empty($param['text'])) {
            $noteService = new NoteService();
            $addNoteData = $noteService->addTaskRejectNote($param);
            if ($addNoteData['status'] === 200) {
                $noteId = $addNoteData['data']['id'];
                if (!empty($addNoteData['data']['media_data'])) {
                    $mediaData = $addNoteData['data']['media_data'];
                }
            }
        }

        $data = [
            'link_id' => $param['link_id'],
            'module_id' => $param['module_id'],
            'user_id' => session('user_id'),
            'operation' => $param['type']
        ];

        // 更新消息
        $messageService = new MessageService();
        $messageService->clearSettlementBnt([$param['link_id']]);

        $resData = $confirmHistoryModel->addItem($data);

        // 自己分配人和执行人自动执行结算
        if (
            $param['type'] === 'apply'
            && array_key_exists('assignee_eq_reviewed', $param)
            && $param['assignee_eq_reviewed'] === 'yes'
        ) {
            $data['operation'] = 'confirm';
            $confirmHistoryModel->addItem($data);
        }

        if (!$resData) {
            // 添加状态失败错误码 001
            throw_strack_exception($confirmHistoryModel->getError(), 202022);
        } else {
            $baseModel = new BaseModel();
            $baseData = $baseModel->field('project_id,name')->where('id=' . $param['link_id'])->find();
            $resData['project_id'] = $baseData['project_id'];
            $resData['id'] = (int)$param['link_id'];
            $resData['name'] = $baseData['name'];

            $resData['reject_text'] = '';
            if ($param['type'] === 'reject' && !empty($param['text'])) {
                $resData['reject_text'] = $param['text'];
            }

            if (!empty($mediaData)) {
                $resData['media_data'] = $mediaData;
            }

            $resData['note_id'] = $noteId;

            TPRequest::$serviceOperationResData = $resData;

            // 返回当新增数据
            return success_response($confirmHistoryModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 保存公共信息
     * @param $param
     * @return array
     */
    public function commonAddItem($param)
    {
        // 实例化主模块Model
        $modelObj = $this->modelObject;
        switch ($param["param"]["module"]) {
            case "tag":
            case "tag_link":
                $addParam = [
                    "name" => $param["value"],
                    "type" => "custom",
                    "color" => "000000"
                ];
                break;
            default:
                $addParam = [];
                break;
        }

        $resData = $modelObj->addItem($addParam);
        if (!$resData) {
            throw_strack_exception($modelObj->getError(), 202010);
        } else {
            return success_response($modelObj->getSuccessMassege(), $resData);
        }
    }
}

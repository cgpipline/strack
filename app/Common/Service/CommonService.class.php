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
     * @throws \Ws\Http\Exception
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

        foreach ($schemaFieldsConfig as $key => $fieldItem) {
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
     * @throws \Ws\Http\Exception
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

    /**
     * 生成日历事项
     * @param $scheduleData
     * @param $data
     * @param mixed ...$param
     * @return mixed
     */
    protected function generateScheduleEvent($scheduleData, $data, ...$param)
    {
        list($type, $color, $moduleMapData, $moduleId, $formulaConfigData) = $param;
        $timelogService = new TimelogService();
        $memberService = new MemberService();
        $variableValueModel = new VariableValueModel();
        $viewService = new ViewService();

        $moduleIds = C('MODULE_ID');

        $assign = 0;
        $actualTimeConsuming = 0;
        $settlementTimeConsuming = 0;
        $estimateWorkingHours = 0;
        $calculateAuto = 0;
        $reviewStatus = '';
        $completeStatus = '';

        $reviewedBy = 0;
        $assigneeField = 0;
        $reviewedByUserName = '';
        $assigneeUserName = '';
        $reviewedByUserId = '';
        $assigneeUserId = '';


        $variableService = new VariableService();

        $settlementTimeCustomFieldConfig = [];

        // 时间配置
        $currentTime = date('Y-m-d H:i:s', time());
        $urgentTime = date('Y-m-d H:i:s', time() + 86400);

        if ($formulaConfigData !== false) {
            $assign = $formulaConfigData['assignee_field'];
            $actualTimeConsuming = (int)$formulaConfigData['actual_time_consuming'];
            $estimateWorkingHours = (int)$formulaConfigData['estimate_working_hours'];

            $settlementTimeConsuming = (int)$formulaConfigData['settlement_time_consuming'];
            $reviewStatus = (int)$formulaConfigData['reviewed_by_status'];
            $completeStatus = (int)$formulaConfigData['end_by_status'];

            $reviewedBy = (int)$formulaConfigData['reviewed_by'];
            $assigneeField = (int)$formulaConfigData['assignee_field'];

            $settlementTimeCustomFieldConfig = $variableService->getOneCustomFields($settlementTimeConsuming, $moduleIds['base']);
        }


        // 今天凌晨时间戳
        $todayTime = strtotime(date('Y-m-d 23:59:59', time()));

        foreach ($data as $item) {
            $item['type'] = $type;
            $item['status_name'] = $item['status_name'] ?: '没状态';
            $item['project_name'] = $item['project_name'] ?: '';

            // 获取当前项是否被启用时间日志
            $item['module_id'] = $moduleId;

            $item["timelog"] = $timelogService->getModuleTimelogStatus($item);

            // module id
            $item["module_id"] = $moduleId;

            // 是否是我执行的任务
            $item["is_my_task"] = $memberService->getBelongMyTaskMember(["src_module_id" => $moduleId, "src_link_id" => $item["item_id"]], $assign);

            // 判断是否拥有申请结算和当前状态
            $this->getBaseConfirmStatus($item, $item["item_id"]);

            // 生成详情页面
            $item['details_page_url'] = generate_details_page_url($item['project_id'], $moduleId, $item["item_id"]);

            if (!empty($item['end_time'])) {
                $currentTime = time();
                $endTime = $item['end_time'];
                if ($currentTime > $endTime) {
                    $item['remaining_time'] = '-' . duration_format_show(duration_format(($currentTime - $endTime) / 60));
                } else {
                    $item['remaining_time'] = duration_format_show(duration_format(($endTime - $currentTime) / 60));
                }

            } else {
                $item['remaining_time'] = '';
            }

            if (!empty($item['duration'])) {
                $item['duration'] = duration_format_show($item['duration']);
            }

            $item['actual_time_consuming'] = '';
            if ($actualTimeConsuming > 0) {
                // 实际工时
                $actualTimeFormatShow = $variableValueModel->where([
                    'link_id' => $item["item_id"],
                    'module_id' => $moduleIds['base'],
                    'variable_id' => $actualTimeConsuming
                ])->getField('value');

                if (!empty($actualTimeFormatShow)) {
                    $item['actual_time_consuming'] = duration_format_show($actualTimeFormatShow);
                }
            }

            $item['estimate_working_hours'] = '';
            $item['estimate_working_hours_val'] = '';
            if ($estimateWorkingHours > 0) {
                // 预估工时
                $estimateTimeFormatShow = $variableValueModel->where([
                    'link_id' => $item["item_id"],
                    'module_id' => $moduleIds['base'],
                    'variable_id' => $estimateWorkingHours
                ])->getField('value');

                if (!empty($estimateTimeFormatShow)) {
                    // 预估时间转换成分钟
                    $item['estimate_working_hours_val'] = convert_time_to_minute($estimateTimeFormatShow);

                    // 预估时间格式化
                    $item['estimate_working_hours'] = duration_format_show($estimateTimeFormatShow);
                }
            }

            // 判断是不是一口价
            $item['calculate_auto'] = 'off';

            $item['settlement_time_consuming'] = [
                'config' => '',
                'primary' => $item["item_id"],
                'has_title' => false,
                'val' => [
                    'bg_clolor' => "",
                    'font_set' => "",
                    'project_id' => $item["project_id"],
                    'show_val' => "",
                    'value' => ""
                ],
                'param' => [
                    'module_id' => $moduleIds['base']
                ]
            ];
            if ($settlementTimeConsuming > 0) {
                $settlementTimeFormatShow = $variableValueModel->where([
                    'link_id' => $item["item_id"],
                    'module_id' => $moduleIds['base'],
                    'variable_id' => $settlementTimeConsuming
                ])->getField('value');

                if (!empty($settlementTimeFormatShow)) {
                    $item['settlement_time_consuming']['val']['value'] = $settlementTimeFormatShow;
                    $item['settlement_time_consuming']['val']['show_val'] = $settlementTimeFormatShow;
                }

                if (!empty($settlementTimeCustomFieldConfig)) {

                    if (
                        (
                            $viewService->checkFieldPermission('base', $settlementTimeCustomFieldConfig["fields"], "modify")
                            && $item['calculate_auto'] === 'off'
                            && (int)$item['status_id'] !== $completeStatus
                        )
                        || in_array(session('user_id'), [1, 2])
                    ) {
                        $settlementTimeCustomFieldConfig['edit'] = 'allow';
                    } else {
                        $settlementTimeCustomFieldConfig['edit'] = 'deny';
                    }

                    $item['settlement_time_consuming']['config'] = $settlementTimeCustomFieldConfig;
                }
            }

            // 获取当前任务的分配人和执行人
            if ($reviewedBy > 0) {
                $reviewedByUserData = $variableService->getUserVariableHorizontalValue($reviewedBy, $item["item_id"], $moduleIds['base'], $moduleIds['user']);
                $reviewedByUserName = $reviewedByUserData['user_name'];
                $reviewedByUserId = $reviewedByUserData['user_id'];
            }

            if ($assigneeField > 0) {
                $assigneeUserData = $variableService->getUserVariableHorizontalValue($assigneeField, $item["item_id"], $moduleIds['base'], $moduleIds['user']);
                $assigneeUserName = $assigneeUserData['user_name'];
                $assigneeUserId = $assigneeUserData['user_id'];
            }

            // 判断截止时间紧急程度
            $eventBorderColor = '#e1e1e1';
            $item['available_plan_time'] = 0;
            if ($item['end_time']) {
                if ($currentTime < $item['end_time']) {
                    if ($item['end_time'] < $urgentTime) {
                        // 即将超时
                        $eventBorderColor = '#faad14';
                    } else {
                        // 正常时间
                        $eventBorderColor = '#1890ff';
                    }

                    $item['available_plan_time'] = $item['estimate_working_hours_val'];
                } else {
                    // 已经超时
                    $eventBorderColor = '#f5222d';
                }
            }
            $item['event_border_color'] = $eventBorderColor;


            $item['reviewed_by_user_name'] = $reviewedByUserName;
            $item['reviewed_by_user_id'] = $reviewedByUserId;
            $item['assignee_user_name'] = $assigneeUserName;
            $item['assignee_user_id'] = $assigneeUserId;

            $item['color'] = '#' . $color;
            $item['title'] = $item['name'];
            $item['id'] = "{$type}_{$item['item_id']}";

            $item['start_time'] = get_format_date($item['start_time'], 1);
            $item['end_time'] = get_format_date($item['end_time'], 1);

            if (!empty($item['plan_start_time'])) {
                $item['start'] = get_format_date($item['plan_start_time'], 1);
            } else {
                $item['start'] = get_format_date(time(), 1);
            }


            if (!empty($item['plan_end_time'])) {
                $item['end'] = get_format_date($item['plan_end_time'], 1);
            } else {
                // 如果没有截至时间
                if ($item['plan_start_time'] <= $todayTime) {
                    $item['end'] = get_format_date($todayTime, 1);;
                }
            }

            if ($type === 'entity') {
                $item['table_name'] = 'entity';
                $item['module_code'] = $moduleMapData['entity'][$item['module_id']]['code'];
                $item['module_type'] = 'entity';
                $item['page'] = "project_{$moduleMapData['entity'][$item['module_id']]['code']}";
            } else {
                $item['module_id'] = C('MODULE_ID')['base'];
                $item['table_name'] = 'base';
                $item['module_code'] = 'base';
                $item['module_type'] = 'fixed';
                $item['page'] = 'project_base';
            }

            // 任务都为全天事件
            $item['is_all_day'] = 'yes';
            $item['event_type'] = 'base';
            $item['lock_status'] = 'no';
            $item['complete'] = 'no';

            // 标记重复请求结算任务
            $item['is_repeated_settlement'] = 'no';

            if (!empty($reviewStatus) && (int)$item['status_id'] === $reviewStatus) {
                $scheduleData['review'][] = $item;
            } elseif (!empty($completeStatus) && (int)$item['status_id'] === $completeStatus) {
                // 完成的任务设为全局事件
                $item['is_all_day'] = 'yes';
                $item["is_my_task"]['status'] = 'no';
                $item['remaining_time'] = '0分钟';
                $item['complete'] = 'yes';
                $scheduleData['complete'][] = $item;
            } else {
                $scheduleData['normal'][] = $item;
            }
        }

        return $scheduleData;
    }

    /**
     * 获取我的日程任务数据
     * @param $param
     * @param $requestFilter
     * @param $baseModuleId
     * @param $formulaConfigData
     * @param array $appendBaseIds
     * @return array
     */
    public function getMyBaseScheduleData($param, $requestFilter, $baseModuleId, $formulaConfigData, $appendBaseIds = [])
    {
        $baseBaseSrcIds = [];
        if (!empty($param['filter']['user_sent']) || !empty($param['filter']['user_receive'])) {
            // 存在执行人或者分派人过滤
            $horizontalModel = new HorizontalModel();
            $userSentIds = [];
            $userReceiveIds = [];


            if (!empty($param['filter']['user_sent'])) {
                $userSentIdsData = $horizontalModel->field('src_link_id')
                    ->where([
                        'src_module_id' => $baseModuleId,
                        'variable_id' => (int)$formulaConfigData['reviewed_by'],
                        'dst_link_id' => ['IN', $param['filter']['user_sent']]
                    ])
                    ->select();
                $userSentIds = array_column($userSentIdsData, 'src_link_id');
            }

            if (!empty($param['filter']['user_receive'])) {
                $userReceiveIdsData = $horizontalModel->field('src_link_id')
                    ->where([
                        'src_module_id' => $baseModuleId,
                        'variable_id' => (int)$formulaConfigData['assignee_field'],
                        'dst_link_id' => ['IN', $param['filter']['user_receive']]
                    ])
                    ->select();
                $userReceiveIds = array_column($userReceiveIdsData, 'src_link_id');
            }

            if (!empty($param['filter']['task_type']) && !empty($param['filter']['user_sent']) && !empty($param['filter']['user_receive'])) {
                $baseUserSrcIds = array_intersect($userSentIds, $userReceiveIds);
            } else {
                $baseUserSrcIds = array_merge($userSentIds, $userReceiveIds);
            }

            if (!empty($baseUserSrcIds)) {
                $requestFilter['base.id'] = ['IN', join(',', $baseUserSrcIds)];
            } else {
                $requestFilter['base.id'] = 0;
            }

            // 状态列表
            if (!empty($param['filter']['status'])) {
                $requestFilter['base.status_id'] = ['IN', $param['filter']['status']];
            }

            $baseModel = new BaseModel();
            $baseCreatedByData = $baseModel->alias('base')
                ->join("LEFT JOIN strack_status status ON status.id = base.status_id")
                ->join("LEFT JOIN strack_project project ON project.id = base.project_id")
                ->field('base.id as item_id')
                ->where($requestFilter)
                ->order('base.status_id asc')
                ->select();

            $baseBaseSrcIds = array_column($baseCreatedByData, 'item_id');
        }


        if (!empty($baseBaseSrcIds)) {
            if (!empty($param['filter']['task_type'])) {
                switch ($param['filter']['task_type']) {
                    case "my_create":
                        $requestFilter['base.id'] = ['IN', join(',', array_intersect($baseBaseSrcIds, $appendBaseIds))];
                        break;
                    default:
                        $requestFilter['base.id'] = ['IN', join(',', $baseBaseSrcIds)];
                        break;
                }
            } else {
                $requestFilter['base.id'] = ['IN', join(',', unique_arr(array_merge($baseBaseSrcIds, $appendBaseIds)))];
            }
        } else if (!empty($appendBaseIds)) {
            if (!empty($param['filter']['task_type']) && (!empty($param['filter']['user_sent']) || !empty($param['filter']['user_receive']))) {
                $requestFilter['base.id'] = 0;
            } else {
                $requestFilter['base.id'] = ['IN', join(',', $appendBaseIds)];
            }
        } else {
            $requestFilter['base.id'] = 0;
        }

        return $requestFilter;
    }

    /**
     * 生成我的日程过滤条件
     * @param $param
     * @return array
     */
    protected function generateMyScheduleFilter($param)
    {
        // 任务基础过滤条件
        $baseFilter = [];

        if (!empty($param['filter']['project'])) {
            // 限定项目ids
            $baseFilter['base.project_id'] = ['IN', $param['filter']['project']];
        } else {
            // 获取当前已经存在项目ids
            $projectModel = new ProjectModel();
            $projectData = $projectModel->field('id')->select();
            $projectIds = array_column($projectData, 'id');
            $baseFilter['base.project_id'] = ['IN', join(',', $projectIds)];
        }

        if (!empty($param['filter']['end_time']['value'])) {
            // 任务截止时间限定条件
            $baseStartTime = 0;
            $baseEndTime = 0;
            if ($param['filter']['end_time']['type'] === 'fixed') {
                // 今天0点
                $todayZeroClockTime = strtotime(date('Y-m-d 00:00:00', time()));
                switch ($param['filter']['end_time']['value']) {
                    case 'today':
                        $baseStartTime = $todayZeroClockTime;
                        $baseEndTime = $todayZeroClockTime + 86400;
                        break;
                    case 'tomorrow':
                        $baseStartTime = $todayZeroClockTime + 86400;
                        $baseEndTime = $todayZeroClockTime + 172800;
                        break;
                    case 'yesterday':
                        $baseStartTime = $todayZeroClockTime - 86400;
                        $baseEndTime = $todayZeroClockTime;
                        break;
                }
            } else {
                // 指定时间范围
                $baseStartTime = strtotime($param['filter']['end_time']['value']['start']);
                $baseEndTime = strtotime($param['filter']['end_time']['value']['end']) + 86400;
            }

            $baseFilter['base.end_time'] = ['BETWEEN', [$baseStartTime, $baseEndTime]];
        }

        return $baseFilter;
    }

    /**
     * 获取任务最新结算状态
     * @param $ids
     * @return array
     */
    public function getTaskNewConfirmApplyHistory($ids, $exitCompleteTaskList)
    {
        $confirmHistoryModel = new ConfirmHistoryModel();
        $filter = [
            'link_id' => $ids,
            'operation' => 'apply'
        ];

        $filter['_complex'] = [
            'link_id' => ['NOT IN', join(',', $exitCompleteTaskList)]
        ];

        $confirmData = $confirmHistoryModel->where($filter)
            ->order('created desc')
            ->select();

        $confirmNewLinkIds = [];
        foreach ($confirmData as $confirmItem) {
            $tempKey = $confirmItem['link_id'] . '_' . $confirmItem['module_id'];
            if (!array_key_exists($tempKey, $confirmNewLinkIds)) {
                $confirmNewLinkIds[$tempKey] = $confirmItem['link_id'];
            }
        }

        return array_values($confirmNewLinkIds);
    }

    /**
     * 获取日程项权限规则所需要的数据
     * @param $linkId
     * @param $eventId
     * @return array
     */
    protected function getMyScheduleBaseEventAuthRulesData($linkId, $eventId)
    {
        //  item_id: 0, 任务id
        //  entity_id: 0, 所属实体id
        //  project_id: 0, 项目id
        //  template_id: 0, 模板id
        $baseModel = new BaseModel();
        $baseData = $baseModel->field('id,project_id,entity_id')
            ->where(['id' => $linkId])
            ->find();

        $projectTemplate = new ProjectTemplateModel();
        $templateId = $projectTemplate->where(['project_id' => $baseData['project_id']])->getField('id');

        return [
            'event_id' => $eventId,
            'item_id' => $baseData['id'],
            'entity_id' => $baseData['entity_id'],
            'project_id' => $baseData['project_id'],
            'template_id' => $templateId,
        ];
    }

    /**
     * 获取当前用户指定日程任务数据
     * @param $param
     * @param array $sideRules
     * @return array
     */
    public function getMyScheduleData($param, $sideRules = [])
    {
        /**
         * 代码逻辑：
         * 1、只要是（未完成）的任务都显示在当天的全天任务，当前时间范围存在计划后就不显示；
         * 2、日程里面显示（当前时间段）已经排期的任务计划；
         * 3、在锁定范围内增加的任务计划默认锁定（现在只支持day天数据视图）；
         * 4、筛选还是按（任务）筛选
         *
         * 迭代需求：1.0
         * 1、已经结算完成的任务、计划也显示出来，锁定编辑和样式置灰。
         * 2、返回的当前任务可剩余拖到时间，和截止时间
         */


        // 获取base,user,entity module_id
        $moduleModel = new ModuleModel();
        $moduleData = $moduleModel->field('id,type,code')->where([
            'code' => ["IN", 'base,user,plan'],
            'type' => 'entity',
            '_logic' => 'OR'
        ])->select();

        $moduleMapData = [];
        foreach ($moduleData as $module) {
            if ($module['type'] === 'fixed') {
                $moduleMapData[$module['code']] = $module;
            } else {
                $moduleMapData[$module['type']][$module['id']] = $module;
            }
        }

        $baseModuleId = $moduleMapData['base']['id'];
        $baseModel = new BaseModel();
        $planModel = new PlanModel();
        $appendBaseIds = [];

        // assign 发送者
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();

        $filterType = $param['filter']['task_type'];

        // 把时间范围按天切算为时间列表
        $timeRangeStartStamp = strtotime(date('Y-m-d 00:00:00', $param['start']));
        $timeRangeEndStamp = strtotime(date('Y-m-d 00:00:00', $param['end']));

        if (!in_array($filterType, ['my_receive', 'my_sent'])) {
            // base数据 created_by
            $createFilter = [
                'created_by' => $param['user_id']
            ];

            // 状态列表
            if (!empty($param['filter']['status'])) {
                $statusIdArr = explode(',', $param['filter']['status']);
                $statusInIds = [];
                $endByStatusBaseIds = [];
                foreach ($statusIdArr as $statusId) {
                    if ((int)$statusId === (int)$formulaConfigData['end_by_status']) {
                        // 存在完成状态，必须加上时间范围限制，不然巨慢，查询当前时间范围内存在计划的任务id
                        $planData = $planModel->field('link_id')->where([
                            'module_id' => $baseModuleId,
                            [
                                // 开始时间在这个范围内
                                'start_time' => ["BETWEEN", [$timeRangeStartStamp, $timeRangeEndStamp]],
                                // 结束时间在这个范围内
                                'end_time' => ["BETWEEN", [$timeRangeStartStamp, $timeRangeEndStamp]],
                                '_logic' => 'OR'
                            ]
                        ])->select();

                        $endByStatusBaseIds = array_column($planData, 'link_id');
                    } else {
                        $statusInIds[] = $statusId;
                    }
                }

                if (empty($endByStatusBaseIds) && !empty($statusInIds)) {
                    $createFilter['status_id'] = ['IN', join(',', $statusInIds)];
                }

                if (empty($statusInIds) && !empty($endByStatusBaseIds)) {
                    $createFilter['status_id'] = ['EQ', (int)$formulaConfigData['end_by_status']];
                    $createFilter['id'] = ['IN', join(',', $endByStatusBaseIds)];
                }

                if (!empty($endByStatusBaseIds) && !empty($statusInIds)) {
                    $createFilter['_complex'] = [
                        [
                            'status_id' => ['IN', join(',', $statusInIds)]
                        ],
                        [
                            'status_id' => ['EQ', (int)$formulaConfigData['end_by_status']],
                            'id' => ['IN', join(',', $endByStatusBaseIds)]
                        ],
                        '_logic' => 'OR'
                    ];
                }

            } else {
                $createFilter['status_id'] = ['NEQ', (int)$formulaConfigData['end_by_status']];
            }

            $createBaseData = $baseModel->field('id')
                ->where($createFilter)->select();

            $appendBaseIds = array_column($createBaseData, 'id');
        }

        if ($filterType !== 'my_create') {
            // 增加更多过滤条件
            switch ($filterType) {
                case 'my_receive':
                    // 接收人
                    $param['filter']['user_receive'] = (string)$param['user_id'];
                    break;
                case 'my_sent':
                    // 分配人
                    $param['filter']['user_sent'] = (string)$param['user_id'];
                    break;
                default:
                    $param['filter']['user_receive'] = (string)$param['user_id'];
                    $param['filter']['user_sent'] = (string)$param['user_id'];
                    break;
            }
        }

        // 1. 组装过滤条件获取任务ids
        $baseFilter = $this->generateMyScheduleFilter($param);
        $baseFullFilter = $this->getMyBaseScheduleData($param, $baseFilter, $baseModuleId, $formulaConfigData, $appendBaseIds);

        // 2. 查询任务数据
        $baseData = $baseModel->alias('base')
            ->join("LEFT JOIN strack_status status ON status.id = base.status_id")
            ->join("LEFT JOIN strack_project project ON project.id = base.project_id")
            ->field('base.id as item_id,base.name,base.status_id,base.plan_start_time,base.plan_end_time,base.start_time,base.end_time,base.duration,base.project_id,base.created_by as user_id,base.description,status.name status_name,status.color status_color,project.name as project_name')
            ->where($baseFullFilter)
            ->order('base.end_time desc,base.status_id asc')
            ->select();

        $scheduleFormatData = [
            'review' => [],
            'complete' => [],
            'normal' => []
        ];

        $scheduleData = $this->generateScheduleEvent($scheduleFormatData, $baseData, 'base', '13c2c2', $moduleMapData, C('MODULE_ID')['base'], $formulaConfigData);


        // 已经是完成状态的任务列表
        $exitCompleteTaskList = [];

        /**
         * 任务剩余可排期时间
         * 1、首先当前时间减去截止时间，如果大于预估时间，那就以预估时间为最大可用排期时间，否则是当前时间减去截止时间
         * 2、得到的最大可用排期时间减去计划时间，如果为负数那么为0
         */
        $taskRemainderTime = [];
        $taskRemainderEndTime = [];

        // 合并三个数组
        $scheduleList = [];
        foreach (['review', 'normal', 'complete'] as $status) {
            foreach ($scheduleData[$status] as $scheduleItem) {
                if ($status === 'complete') {
                    $exitCompleteTaskList[] = $scheduleItem['item_id'];
                }
                $scheduleList[] = $scheduleItem;

                // 处理任务最大剩余时间
                $taskRemainderTime[$scheduleItem['item_id']] = $scheduleItem['available_plan_time'];
                $taskRemainderEndTime[$scheduleItem['item_id']] = strtotime($scheduleItem['end_time']);
            }
        }

        // 当前任务映射字典
        $baseDataMap = array_column($scheduleList, null, 'item_id');


        $timeRangeList = [];
        $currentRangeTimeStamp = $timeRangeStartStamp;
        while ($currentRangeTimeStamp <= ($timeRangeEndStamp - 86400)) {
            $dayTimeRange = [$currentRangeTimeStamp, $currentRangeTimeStamp + 86399];
            $currentRangeTimeStamp += 86400;
            $timeRangeList[] = $dayTimeRange;
        };

        // 查询当前任务集合的计划数据
        $planData = $planModel->field('id,lock,link_id,start_time,end_time,user_id')->where([
            'link_id' => $baseFullFilter['base.id'],
            'module_id' => $baseModuleId,
            [
                [
                    // 开始时间在这个范围内
                    'start_time' => ["BETWEEN", [$timeRangeStartStamp, $timeRangeEndStamp]],
                ],
                [
                    // 结束时间在这个范围内
                    'end_time' => ["BETWEEN", [$timeRangeStartStamp, $timeRangeEndStamp]],
                ],
                [
                    // 开始时间小于等于当前开始时间，结束时间大于等于当前时间
                    'start_time' => ["elt", $timeRangeStartStamp],
                    'end_time' => ["egt", $timeRangeStartStamp],
                    '_logic' => 'AND'
                ],
                '_logic' => 'OR'
            ]
        ])->select();

        // 把申请结算的任务加入到日程列表顶部
        // 1.按时间倒叙排序取当前任务结算记录
        // 2.循环遍历取每个任务最新的一条结算状态

        // 已经完成任务不显示到全天任务
        //$baseFullFilter['base.status_id'] = ['NEQ', (int)$formulaConfigData['end_by_status']];

        $applyBaseIds = $this->getTaskNewConfirmApplyHistory($baseFullFilter['base.id'], $exitCompleteTaskList);

        // 把plan数据加入到日程列表
        $eventList = [];
        $exitPlanTaskList = [];
        $exitRepeatedSettlementItem = [];
        $pageAuthRules = [
            'base' => $sideRules,
            'list' => []
        ];

        // 存在计划任务列表
        $exitPlanIdList = [];
        $timelogService = new TimelogService();

        foreach ($planData as $planItem) {
            if (array_key_exists($planItem['link_id'], $baseDataMap)) {

                $tempBaseData = $baseDataMap[$planItem['link_id']];

                foreach ($timeRangeList as $timeRangeItem) {
                    if (check_time_in_range($planItem['start_time'], $planItem['end_time'], $timeRangeItem)) {
                        $tempKey = join('-', $timeRangeItem);
                        $exitPlanTaskList[$tempKey][] = $planItem['link_id'];
                    }
                }

                $exitPlanIdList[] = $planItem['link_id'];

                // 任务排期可用时间排除已经使用的计划时间
                if (array_key_exists($planItem['link_id'], $taskRemainderTime)) {
                    if ($taskRemainderTime[$planItem['link_id']] > 0) {
                        $planInterval = ($planItem['end_time'] - $planItem['start_time']) / 60;

                        $currentAvailablePlanTime = $taskRemainderTime[$planItem['link_id']] - $planInterval;
                        if ($currentAvailablePlanTime <= 0) {
                            $taskRemainderTime[$planItem['link_id']] = 0;
                        } else {
                            $taskRemainderTime[$planItem['link_id']] = $currentAvailablePlanTime;
                        }
                    }
                }

                // 计划数据格式化
                $tempBaseData['start_time'] = get_format_date($planItem['start_time'], 1);
                $tempBaseData['base_end_time'] = $tempBaseData['end_time'];
                $tempBaseData['end_time'] = get_format_date($planItem['end_time'], 1);
                $tempBaseData['start'] = $tempBaseData['start_time'];
                $tempBaseData['end'] = $tempBaseData['end_time'];
                $tempBaseData['is_all_day'] = 'no';
                $tempBaseData['event_type'] = 'event';

                if (in_array($planItem['link_id'], $exitCompleteTaskList)) {
                    // 如果是完成任务的计划不允许编辑
                    $tempBaseData['lock_status'] = 'yes';
                    $tempBaseData['is_my_task']['status'] = 'no';
                } else {
                    // 判断任务锁定状态
                    $tempBaseData['lock_status'] = $planItem['lock'];

                    // 判断当前计划是否是审核人的，如果是也显示timelog开始按钮状态
                    if (
                        (int)$tempBaseData['reviewed_by_user_id'] === (int)$planItem['user_id']
                        && (int)$planItem['user_id'] === session('user_id')
                        && $tempBaseData['lock_status'] === 'no'
                    ) {
                        $tempBaseData['is_my_task']['status'] = 'yes';

                        $tempBaseData["timelog"] = $timelogService->getModuleTimelogStatusByFilter([
                            "module_id" => $tempBaseData["module_id"],
                            "link_id" => $tempBaseData["item_id"],
                            "user_id" => $planItem['user_id'],
                            "complete" => "no"
                        ]);
                    }
                }


                $tempBaseData['item_id'] = $planItem['id'];
                $tempBaseData['link_id'] = $planItem['link_id'];
                $tempBaseData['id'] = "plan_event_{$planItem['id']}";
                $tempBaseData['table_name'] = $moduleMapData['plan']['code'];
                $tempBaseData['type'] = $moduleMapData['plan']['code'];
                $tempBaseData['module_code'] = $moduleMapData['plan']['code'];
                $tempBaseData['module_id'] = $moduleMapData['plan']['id'];
                $tempBaseData['base_module_id'] = $moduleMapData['base']['id'];

                $eventList[] = $tempBaseData;

                $pageAuthRules['list'][] = $this->getMyScheduleBaseEventAuthRulesData($planItem['link_id'], $tempBaseData['id']);


                // 把正在结算的任务置顶显示
                if (in_array($planItem['link_id'], $applyBaseIds) && !in_array($planItem['link_id'], $exitRepeatedSettlementItem)) {
                    $repeatedSettlementItem = $baseDataMap[$planItem['link_id']];
                    $repeatedSettlementItem['is_repeated_settlement'] = 'yes';

                    // 多个plan结算只存入一份
                    $exitRepeatedSettlementItem[] = $planItem['link_id'];

                    $pageAuthRules['list'][] = $this->getMyScheduleBaseEventAuthRulesData($planItem['link_id'], 'base_' . $planItem['link_id']);

                    array_unshift($eventList, $repeatedSettlementItem);
                }
            }
        }

        /**
         * 处理计划可用时间跟当前时间和任务截止时间做对比
         */
        foreach ($taskRemainderTime as $key => $value) {
            $taskRemainderTime[$key] = generate_available_plan_time($taskRemainderEndTime[$key], $value);
        }

        // 今天零点时间戳
        $todayZeroTimeStamp = strtotime(date('Y-m-d 00:00:00', time()));

        // 把当前天未做计划的任务加入日程列表
        // 1、当前天之前的待计划不要显示
        // 2、今天已经排了待计划，顶部不显示
        // 3、已经完成状态任务不显示
        foreach ($scheduleList as $scheduleItem) {
            foreach ($timeRangeList as $timeRangeItem) {
                $tempKey = join('-', $timeRangeItem);
                if ($timeRangeItem[0] >= $todayZeroTimeStamp
                    && (
                        (
                            !array_key_exists($tempKey, $exitPlanTaskList)
                            || !in_array($scheduleItem['item_id'], $exitPlanTaskList[$tempKey])
                        )
                        && !in_array($scheduleItem['item_id'], $exitPlanIdList)
                        && ($scheduleItem['complete'] === 'no')
                    )
                ) {
                    $scheduleItem['start'] = get_format_date($timeRangeItem[0], 1);
                    $scheduleItem['end'] = get_format_date($timeRangeItem[1], 1);
                    $scheduleItem['id'] .= md5($tempKey);

                    $pageAuthRules['list'][] = $this->getMyScheduleBaseEventAuthRulesData($scheduleItem['item_id'], $scheduleItem['id']);

                    $eventList[] = $scheduleItem;
                }
            }
        }

        // 获取当前视图时间范围的锁定的天列表
        $scheduleService = new ScheduleService();
        $lockDateList = $scheduleService->getDateRangeLockDateList($timeRangeStartStamp, $timeRangeEndStamp);

        $lockBntStatus = false;
        if (in_array(date('Y-m-d', $param['start']), $lockDateList)) {
            $lockBntStatus = true;
        }

        // 右键菜单方法

        return [
            'events_list' => $eventList,
            'lock_date_list' => $lockDateList,
            'lock_bnt_status' => $lockBntStatus,
            'page_auth_rules' => $pageAuthRules,
            'task_remainder_time' => $taskRemainderTime,
            'task_remainder_end_time' => $taskRemainderEndTime,
            'right_menu_config' => $this->getScheduleRightMenuConfig()
        ];
    }

    /**
     * 获取我的日程页面右键菜单配置，暂时仅支持删除操作
     * @return array
     */
    public function getScheduleRightMenuConfig()
    {
        $menuConfig = [];
        $deleteBnt = [
            "attr_data" => [
                "grid" => "",
                "lang" => L('Delete'),
                "moduleid" => C("MODULE_ID")["base"],
            ],
            "click" => "obj.base_delete(this);",
            "iconCls" => "icon-uniE9D5",
            "id" => "right_menu_463881",
            "lang" => L('Delete'),
            "type" => "function"
        ];

        $menuConfig[] = $deleteBnt;

        return $menuConfig;
    }
}

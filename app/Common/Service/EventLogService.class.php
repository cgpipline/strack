<?php
// +----------------------------------------------------------------------
// | 事件日志服务层
// +----------------------------------------------------------------------
// | 主要服务于事件日志数据处理
// +----------------------------------------------------------------------
// | 错误编码头 206xxx
// +----------------------------------------------------------------------
namespace Common\Service;

use Common\Model\BaseModel;
use Common\Model\EventLogModel;
use Common\Model\FieldModel;
use Common\Model\HorizontalModel;
use Common\Model\PlanModel;
use Common\Model\ProjectModel;
use Common\Model\TimelogModel;
use Common\Model\VariableValueModel;
use Ws\Http\Request;
use Ws\Http\Request\Body;

class EventLogService
{

    protected $_headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ];

    // 错误信息
    protected $errorMsg = '';

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->errorMsg;
    }

    /**
     * 获取事件服务器配置
     * @return array|mixed
     */
    public function getEventServer()
    {
        // 获取事件服务器配置
        $optionsService = new OptionsService();
        $logServerConfig = $optionsService->getOptionsData("log_settings");

        if (!empty($logServerConfig)) {
            // 获取事件服务器状态
            $getServerStatus = check_http_code($logServerConfig["request_url"]);
            $logServerConfig['status'] = $getServerStatus['http_code'];
            $logServerConfig['connect_time'] = $getServerStatus['connect_time'];
            $logServerConfig['token'] = md5($logServerConfig["access_key"] . $logServerConfig["secret_key"]);
            $logServerConfig["add_url"] = $logServerConfig["request_url"] . "/event/add?sign={$logServerConfig['token']}";
            $logServerConfig["find_url"] = $logServerConfig["request_url"] . "/event/find?sign={$logServerConfig['token']}";
            $logServerConfig["select_url"] = $logServerConfig["request_url"] . "/event/select?sign={$logServerConfig['token']}";
            $logServerConfig["fields_url"] = $logServerConfig["request_url"] . "/event/fields?sign={$logServerConfig['token']}";
        } else {
            // 没有配置事件服务器
            $logServerConfig = [
                'status' => 404
            ];
        }
        return $logServerConfig;
    }

    /**
     * 记录到Event服务器
     * @param $data
     * @param $controllerMethod
     * @return bool
     * @throws \Ws\Http\Exception
     */
    protected function postToServer($data, $controllerMethod)
    {
        //  TODO 改成队列入库
        $eventModel = new EventLogModel();
        switch ($controllerMethod){
            case "add":
                // 写入数据库
                $eventModel->add($data);
                break;
        }

    }

    /**
     * 获取当前模块使用的邮件模板
     * @param $modelName
     * @return string
     */
    protected function getModuleUseEmailTemplate($modelName)
    {
        return "default";
    }

    /**
     * 写入到当前操作批次缓存
     * @param $data
     */
    protected function writeToOperationCache($data)
    {
        $oldCache = S($data['batch_number']);
        if (!empty($oldCache)) {
            $oldCache[] = $data;
        } else {
            $oldCache = [$data];
        }

        S($data['batch_number'], $oldCache);
    }

    /**
     * 处理记录事件
     * @param $operate
     * @param $data
     * @throws \Exception
     * @throws \Ws\Http\Exception
     */
    protected function afterEvent($operate, $data)
    {
        if (in_array($operate, ["create", "delete"])) {
            if (!in_array($data["table"], ["Variable", "VariableValue"]) && $data['type'] === 'built_in') {
                try {
                    // 对于有自定义字段的模块当新增或者删除操作时候做出相应操作
                    $variableService = new VariableService();
                    $variableService->changeCustomFieldValue($data);
                } catch (\Exception $e) {

                }
            }
        }

        if ($operate === "update" && $data["table"] === "Variable") {
            // 更新自定义字段相关数据
            $variableService = new VariableService();
            $variableService->upDateVariableConfig($data);
        }

        if ($operate === 'update' && $data["table"] === "Timelog") {
            // 结束时间日志计算时间消耗工时
            $formulaConfigData = (new OptionsService())->getFormulaConfigData();
            if ($formulaConfigData !== false) {
                $this->updateActualTimeConsumingByTimeLog($data, $formulaConfigData);
            }
        }

        if (in_array($operate, ['create', 'update']) && $data["table"] === "Plan") {
            // 根据任务计划数据动态计算计划工时
            $formulaConfigData = (new OptionsService())->getFormulaConfigData();
            if ($formulaConfigData !== false) {
                $this->updatePlanDurationConsumingByTimeLog($operate, $data, $formulaConfigData);
            }
        }

        if ($operate === 'create' && $data["table"] === "ConfirmHistory") {
            // 结束时间日志计算时间消耗工时
            $formulaConfigData = (new OptionsService())->getFormulaConfigData();
            if ($formulaConfigData !== false) {
                $this->updateBaseStatusByConfirmHistory($data, $formulaConfigData);
            }
        }

        // 增加Event数据来源系统配置
        $data['belong_system'] = C('BELONG_SYSTEM');

        // 写入到当前批次缓存
        $this->writeToOperationCache($data);

        // 记录到Event服务器
        $this->postToServer($data, "add");
    }

    /**
     * 保存配置到消息日志服务器
     * @param $name
     * @param $param
     * @throws \Ws\Http\Exception
     */
    public function addLogServiceConfig($name, $param)
    {
        $data = [
            'name' => $name,
            'type' => 'system',
            'config' => $param
        ];
        $this->postToServer($data, "add");
    }

    /**
     * 发送测试邮件
     * @param $data
     * @return bool
     * @throws \Ws\Http\Exception
     */
    public function testSendEmail($data)
    {
        $testResult = $this->postToServer($data, "email_test");
        if ($testResult !== false) {
            return $testResult;
        } else {
            throw_strack_exception(L("Please_Configure_Event_Server"));
        }
    }

    /**
     * 直接发送邮件
     * @param $data
     * @return bool
     * @throws \Ws\Http\Exception
     */
    public function directSendEmail($data)
    {
        $sendResult = $this->postToServer($data, "email_send");
        if ($sendResult !== false) {
            return $sendResult;
        } else {
            throw_strack_exception(L("Please_Configure_Event_Server"));
        }
    }

    /**
     * 获取水平关联类型project_id
     * @param $moduleData
     * @param $linkId
     * @return int
     */
    protected function getHorizontalProjectId($moduleData, $linkId)
    {
        $tableName = $moduleData["type"] === "entity" ? "Entity" : string_initial_letter($moduleData["code"]);
        $itemData = M($tableName)->where(["id" => $linkId])->find();

        if (!empty($itemData) && array_key_exists("project_id", $itemData)) {
            return $itemData["project_id"];
        }

        return 0;
    }

    /**
     * 判断是否是水平关联
     * @param $operate
     * @param $moduleCode
     * @param $data
     * @param $moduleIdMapData
     * @return int
     */
    protected function checkHorizontalProjectId($operate, $moduleCode, $data, $moduleIdMapData)
    {
        // 查找水平关联的project_id
        $projectId = 0;
        switch ($moduleCode) {
            case "horizontal":
                switch ($operate) {
                    case "update":
                        $fullItemData = M("Horizontal")->where(["id" => $data["primary_id"]])->find();
                        $projectId = $this->getHorizontalProjectId($moduleIdMapData[$fullItemData["src_module_id"]], $fullItemData["src_link_id"]);
                        break;
                    case "delete":
                        $projectId = $this->getHorizontalProjectId($moduleIdMapData[$data["src_module_id"]], $data["src_link_id"]);
                        break;
                    default:
                        $projectId = $this->getHorizontalProjectId($moduleIdMapData[$data["data"]["src_module_id"]], $data["data"]["src_link_id"]);
                        break;
                }
                break;
            case "tag_link":
                switch ($operate) {
                    case "update":
                        $fullItemData = M("TagLink")->where(["id" => $data["primary_id"]])->find();
                        $projectId = $this->getHorizontalProjectId($moduleIdMapData[$fullItemData["module_id"]], $fullItemData["link_id"]);
                        break;
                    case "delete":
                        $projectId = $this->getHorizontalProjectId($moduleIdMapData[$data["module_id"]], $data["link_id"]);
                        break;
                    default:
                        $projectId = $this->getHorizontalProjectId($moduleIdMapData[$data["data"]["module_id"]], $data["data"]["link_id"]);
                        break;
                }
                break;
            case "variable_value":
                switch ($operate) {
                    case "update":
                        $fullItemData = M("VariableValue")->where(["id" => $data["primary_id"]])->find();
                        $projectId = $this->getHorizontalProjectId($moduleIdMapData[$fullItemData["module_id"]], $fullItemData["link_id"]);
                        break;
                    case "delete":
                        $projectId = $this->getHorizontalProjectId($moduleIdMapData[$data["module_id"]], $data["link_id"]);
                        break;
                    default:
                        $projectId = $this->getHorizontalProjectId($moduleIdMapData[$data["data"]["module_id"]], $data["data"]["link_id"]);
                        break;
                }
                break;
            case "project":
                if (array_key_exists('primary_id', $data)) {
                    $projectId = $data["primary_id"];
                } else {
                    $projectId = $data["id"];
                }
                break;
        }
        return $projectId;
    }

    /**
     * 获取module信息
     * @param $operate
     * @param $moduleCode
     * @param $data
     * @param $moduleIdMapData
     * @return array
     */
    protected function getEventModuleInfo($operate, $moduleCode, $data, $moduleIdMapData, $moduleCodeMapData)
    {
        // 查找水平关联的project_id
        switch ($moduleCode) {
            case "horizontal":
                switch ($operate) {
                    case "update":
                        $fullItemData = M("Horizontal")->where(["id" => $data["primary_id"]])->find();
                        $moduleInfo = $moduleIdMapData[$fullItemData["src_module_id"]];
                        break;
                    case "delete":
                        $moduleInfo = $moduleIdMapData[$data["src_module_id"]];
                        break;
                    default:
                        $moduleInfo = $moduleIdMapData[$data["data"]["src_module_id"]];
                        break;
                }
                $moduleInfo["table_name"] = string_initial_letter($moduleCode);
                break;
            case "tag_link":
                switch ($operate) {
                    case "update":
                        $fullItemData = M("TagLink")->where(["id" => $data["primary_id"]])->find();
                        $moduleInfo = $moduleIdMapData[$fullItemData["module_id"]];
                        break;
                    case "delete":
                        $moduleInfo = $moduleIdMapData[$data["module_id"]];
                        break;
                    default:
                        $moduleInfo = $moduleIdMapData[$data["data"]["module_id"]];
                        break;
                }
                $moduleInfo["table_name"] = string_initial_letter($moduleCode);
                break;
            case "variable_value":
                switch ($operate) {
                    case "update":
                        $fullItemData = M("VariableValue")->where(["id" => $data["primary_id"]])->find();
                        $moduleInfo = $moduleIdMapData[$fullItemData["module_id"]];
                        break;
                    case "delete":
                        $moduleInfo = $moduleIdMapData[$data["module_id"]];
                        break;
                    default:
                        $moduleInfo = $moduleIdMapData[$data["data"]["module_id"]];
                        break;
                }
                $table = $moduleInfo["type"] === "entity" ? $moduleInfo["type"] : $moduleInfo["code"];
                $moduleInfo["table_name"] = string_initial_letter($table);
                break;
            default:
                switch ($operate) {
                    case "update":
                        $fullItemData = M(string_initial_letter($moduleCode))->where(["id" => $data["primary_id"]])->find();
                        if (array_key_exists("module_id", $fullItemData) && !empty($fullItemData['module_id'])) {
                            $moduleInfo = $moduleIdMapData[$fullItemData["module_id"]];
                        } else {
                            $moduleInfo = $moduleCodeMapData[$moduleCode];
                        }
                        break;
                    case "delete":
                        if (array_key_exists("module_id", $data) && !empty($data['module_id'])) {
                            $moduleInfo = $moduleIdMapData[$data["module_id"]];;
                        } else if (!empty($moduleCodeMapData[$moduleCode])) {
                            $moduleInfo = $moduleCodeMapData[$moduleCode];
                        }
                        break;
                    default:
                        if (array_key_exists("module_id", $data["data"]) && !empty($data["data"]['module_id'])) {
                            $moduleInfo = $moduleIdMapData[$data["data"]["module_id"]];;
                        } else {
                            $moduleInfo = $moduleCodeMapData[$moduleCode];
                        }
                        break;
                }
                $moduleInfo["table_name"] = string_initial_letter($moduleCode);
                break;
        }
        return $moduleInfo;
    }

    /**
     * 添加处理框架内部事件日志
     * @param $from
     * @param $data
     * @param $userInfo
     * @throws \Exception
     * @throws \Ws\Http\Exception
     */
    public function addInsideEventLog($from, $data, $userInfo)
    {
        $modelName = ucfirst($data["param"]["model"]);

        // 获取当前表所属 module_id
        $moduleCode = un_camelize($modelName);

        $notInModuleList = [
            "auth_access", "auth_field", "auth_group", "auth_group_node", "auth_node", "page_auth", "page_link_auth",
            "horizontal_config", "password_history", "field", "module_relation", "page_schema_use", "schema", "module"
        ];

        // 不在以上module中才会添加event
        if (!in_array($moduleCode, $notInModuleList)) {
            $addData = [
                "operate" => $data["operate"],
                "type" => in_array($moduleCode, ["variable_value"]) ? "custom" : "built_in",
                "batch_number" => $data['batch_number'],
                "from" => $from,
                "user_uuid" => $userInfo["uuid"],
                "created_by" => $userInfo["name"]
            ];

            // 获取module字典数据
            $schemaService = new SchemaService();
            $moduleIdMapData = $schemaService->getModuleMapData("id");
            $moduleCodeMapData = $schemaService->getModuleMapData("code");

            switch ($data["operate"]) {
                case "delete":
                    // 删除操作
                    foreach ($data["data"] as $item) {
                        // 检查是否是水平关联字段
                        $horizontalProjectId = $this->checkHorizontalProjectId($data["operate"], $moduleCode, $item, $moduleIdMapData);
                        if ($horizontalProjectId > 0) {
                            $item["project_id"] = $horizontalProjectId;
                        }

                        // 判断是否为项目相关事件
                        if (array_key_exists("project_id", $item)) {
                            $addData["project_id"] = $item["project_id"];
                            $addData["project_name"] = M("Project")->where(["id" => $item["project_id"]])->getField("name");
                        } else {
                            $addData["project_id"] = 0;
                            $addData["project_name"] = "";
                        }

                        $moduleInfo = $this->getEventModuleInfo($data["operate"], $moduleCode, $item, $moduleIdMapData, $moduleCodeMapData);

                        $addData["module_id"] = !empty($moduleInfo["id"]) ? $moduleInfo["id"] : 0;
                        $addData["module_name"] = !empty($moduleInfo["name"]) ? $moduleInfo["name"] : 0;
                        $addData["module_code"] = !empty($moduleInfo["code"]) ? $moduleInfo["code"] : 0;
                        $addData["table"] = !empty($moduleInfo["table_name"]) ? $moduleInfo["table_name"] : 0;
                        $addData["link_id"] = $item[$data["primary_field"]];
                        $addData["record"] = $item;
                        $this->afterEvent($data["operate"], $addData);
                    }
                    break;
                default:
                    $variableService = new VariableService();
                    switch ($moduleCode) {
                        case "variable_value":
                            if ($data["operate"] === 'create') {
                                $fullItemData = $data['data'];
                                $variableId = $data['data']['variable_id'];
                            } else {
                                $fullItemData = M(string_initial_letter($moduleCode))->where($data["param"]["where"])->find();
                                $variableId = $fullItemData["variable_id"];
                            }

                            $variableConfig = $variableService->getVariableConfig($variableId);

                            $addData["project_id"] = $this->checkHorizontalProjectId($data["operate"], $moduleCode, $data, $moduleIdMapData);
                            $addData["project_name"] = M("Project")->where(["id" => $addData["project_id"]])->getField("name");

                            $moduleInfo = $this->getEventModuleInfo($data["operate"], $moduleCode, $data, $moduleIdMapData, $moduleCodeMapData);
                            $addData["module_id"] = $moduleInfo["id"];
                            $addData["module_name"] = $moduleInfo["name"];
                            $addData["module_code"] = $moduleInfo["code"];
                            $addData["table"] = $moduleInfo["table_name"];
                            $addData["link_id"] = $fullItemData["link_id"];
                            $addData["link_name"] = $variableConfig["name"];
                            $addData["type"] = "custom";
                            $data["data"]["variable_value"] = $fullItemData;
                            $addData["record"] = $data["data"];
                            break;
                        case "horizontal":
                            $variableConfig = $variableService->getVariableConfig($data["data"]["variable_id"]);
                            $addData["project_id"] = $this->checkHorizontalProjectId($data["operate"], $moduleCode, $data, $moduleIdMapData);
                            if ($addData["project_id"] > 0) {
                                $addData["project_name"] = M("Project")->where(["id" => $addData["project_id"]])->getField("name");
                            } else {
                                $addData["project_name"] = "";
                            }
                            $moduleInfo = $this->getEventModuleInfo($data["operate"], $moduleCode, $data, $moduleIdMapData, $moduleCodeMapData);
                            $addData["module_id"] = $moduleInfo["id"];
                            $addData["module_name"] = $moduleInfo["name"];
                            $addData["module_code"] = $moduleInfo["code"];
                            $addData["table"] = $moduleInfo["table_name"];
                            $addData["link_id"] = $data["data"]["src_link_id"];
                            $addData["link_name"] = $variableConfig["name"];
                            $addData["type"] = "built_in";
                            $addData["record"] = $data["data"];
                            break;
                        default:
                            $horizontalProjectId = $this->checkHorizontalProjectId($data["operate"], $moduleCode, $data, $moduleIdMapData);

                            if ($horizontalProjectId > 0) {
                                $data["project_id"] = $horizontalProjectId;
                            }

                            $addData["module_id"] = 0;
                            $addData["module_name"] = "";
                            $addData["module_code"] = $modelName;
                            $addData["project_id"] = 0;
                            $addData["project_name"] = "";

                            $fieldModel = new FieldModel();

                            // 获取添加的数据
                            $addData["link_id"] = $data["primary_id"];
                            $addData["table"] = $modelName;
                            $addData["record"] = $data["data"];
                            switch ($data["operate"]) {
                                case "create":
                                    $addData["link_name"] = array_key_exists("name", $data["data"]) ? $data["data"]["name"] : "";
                                    break;
                                case "update":
                                    $addData["link_name"] = array_key_exists("name", $data["data"]["new"]) ? $data["data"]["new"]["name"] : "";
                                    break;
                            }

                            // 如果存在project_id字段 存在取出来
                            if ($fieldModel->checkTableField($data["table"], "project_id")) {
                                $projectId = M($modelName)->where([$data["primary_field"] => $data["primary_id"]])->getField("project_id");
                                $addData["project_id"] = $projectId;
                                $addData["project_name"] = M("Project")->where(["id" => $projectId])->getField("name");
                            } else if (array_key_exists("project_id", $data)) {
                                $addData["project_id"] = $data["project_id"];
                                $addData["project_name"] = M("Project")->where(["id" => $data["project_id"]])->getField("name");
                            }

                            // 如果存在module_id字段 存在取出来
                            if (array_key_exists($moduleCode, $moduleCodeMapData)) {
                                $moduleInfo = $this->getEventModuleInfo($data["operate"], $moduleCode, $data, $moduleIdMapData, $moduleCodeMapData);

                                $addData["module_id"] = $moduleInfo["id"];
                                $addData["module_name"] = $moduleInfo["name"];
                                $addData["module_code"] = $moduleInfo["code"];
                                $addData["table"] = $moduleInfo["table_name"];
                            } else {
                                $addData["module_name"] = L(un_camelize($modelName));
                            }
                            break;
                    }

                    $this->afterEvent($data["operate"], $addData);
                    break;
            }
        }
    }

    /**
     * 获取事件日志数据
     * @param $param
     * @return array|mixed
     * @throws \Ws\Http\Exception
     */
    public function getModuleItemHistory($param)
    {
        $filter = [
            "filter" => [
                "event_log" => [
                    "module_id" => ["-eq", $param["module_id"]],
                    "link_id" => ["-eq", $param["item_id"]],
                    "project_id" => ["-in", [$param["project_id"], 0]],
                    "belong_system" => ["-eq", C('BELONG_SYSTEM')]
                ]
            ],
            "order" => [
                'event_log.id' => "desc"
            ],
            "page" => [
                "page_number" => $param["page"],
                "page_size" => $param["rows"]
            ]
        ];

        // 查询event列表数据
        $resData = $this->postToServer($filter, "select");
        if ($resData !== false) {
            $eventLogData = object_to_array($resData);
            $userService = new UserService();
            foreach ($eventLogData["rows"] as &$item) {
                if ($item["operate"] == "update") {
                    $record = json_decode($item["record"], true);
                    if (array_key_exists("new", $record) && !empty($record['new'])) {
                        list($value) = array_values($record["new"]);
                        if ($item["type"] === "custom") {
                            $item["link_name"] = "{$item["link_name"]}：$value";
                        } else {
                            list($key) = array_keys($record["new"]);
                            $item["link_name"] = "{$key}：{$value}";
                        }
                    } else {
                        $item['link_name'] = '';
                    }
                }
                // 获取用户头像
                $item["avatar"] = $userService->getUserAvatarByUUID($item["user_uuid"]);
                $item["created"] = date_friendly('Y', $item["created"]);
            }
            return $eventLogData;
        } else {
            return ["total" => 0, "rows" => []];
        }
    }

    /**
     * 获取eventLog表格数据
     * @param $param
     * @return mixed
     * @throws \Ws\Http\Exception
     */
    public function getEventLogGridData($param)
    {
        $filter = [
            "filter" => [
                "event_log" => []
            ],
            "page" => [
                "page_size" => $param["pagination"]["page_size"],
                "page_number" => $param["pagination"]["page_number"]
            ]
        ];

        // 排序条件
        if (!empty($param["filter"]["sort"])) {
            $sortKey = array_keys($param["filter"]["sort"]);
            $sortValue = array_values($param["filter"]["sort"]);
            $key = str_replace('eventlog_', '', $sortKey[0]);
            $filter["order"] = [
                'event_log.' . $key => $sortValue[0]["type"]
            ];
        }

        // 分组条件
        if (!empty($param["filter"]["group"])) {
            $sortKey = array_keys($param["filter"]["group"]);
            $sortValue = array_values($param["filter"]["group"]);
            $key = str_replace('eventlog_', '', $sortKey[0]);
            $filter["order"] = [
                'event_log.' . $key => $sortValue[0]
            ];
        }

        // 过滤条件
        if (!empty($param["filter"]["request"])) {
            foreach ($param["filter"]["request"] as $item) {
                $filter["filter"]["event_log"][$item["field"]] = [parserFilterCondition($item["condition"]), $item["value"]];
            }
        }

        // 过滤框过滤条件
        if (!empty($param["filter"]["filter_input"])) {
            foreach ($param["filter"]["filter_input"] as $item) {
                $filter["filter"]["event_log"][$item["field"]] = [parserFilterCondition($item["condition"]), $item["value"]];
            }
        }

        // 过滤面板过滤条件
        if (!empty($param["filter"]["filter_panel"])) {
            foreach ($param["filter"]["filter_panel"] as $item) {
                $filter["filter"]["event_log"][$item["field"]] = [parserFilterCondition($item["condition"]), $item["value"]];
            }
        }

        // 高级过滤面板过滤条件
        if (!empty($param["filter"]["filter_advance"])) {

            if ($param["filter"]["filter_advance"]['number'] === 1) {
                //  未分组
                $logic = 'and';
                foreach ($param["filter"]["filter_advance"] as $key => $item) {
                    switch (strval($key)) {
                        case 'logic':
                            $logic = $item;
                            break;
                        case 'number':
                            break;
                        default:
                            array_push($filter["filter"]["event_log"], [$item["field"] => [$item["condition"], $item["value"]]]);
                            break;
                    }
                }
            }
        }

        // 添加belong_system必要条件
        $filter["filter"]["event_log"]['belong_system'] = ['-eq', C('BELONG_SYSTEM')];


        $resData = $this->postToServer($filter, "select");
        if ($resData !== false) {
            $resData = object_to_array($resData);

            $listData = [];
            foreach ($resData["rows"] as $key => &$item) {
                foreach ($item as $fieldKey => $fieldItem) {
                    if ($fieldKey === "created") {
                        $fieldItem = date("Y-m-d H:i:s", $fieldItem);
                    }
                    $tableKey = "eventlog_" . $fieldKey;
                    $item[$tableKey] = $fieldItem;
                    $listData[$key][$tableKey] = $fieldItem;
                }
            }

            return ["total" => $resData["total"], "rows" => $listData];
        } else {
            return ["total" => 0, "rows" => []];
        }

    }

    /**
     * 动态加载事件日志数据表格列字段 TODO
     * @return array|mixed
     * @throws \Ws\Http\Exception
     */
    public function getEventLogGridFields()
    {
        // 获取eventlog表的字段配置
        $fieldInfo = $this->postToServer([], "event/fields");
        if ($fieldInfo !== false) {
            return object_to_array($fieldInfo);
        } else {
            return [];
        }
    }

    /**
     * 获取事件日志过滤面板数据
     * @param $param
     * @return array
     * @throws \Ws\Http\Exception
     */
    public function getEventLogGridPanelData($param)
    {
        $userId = session("user_id");

        // 获取当前页面过滤数据
        $filterService = new FilterService();
        $filterBar = $filterService->getFilterList($userId, $param['page']);

        // 获取event_log表的字段配置
        $fieldInfo = $this->postToServer([], "event/fields");
        $fieldConfig = object_to_array($fieldInfo);

        // 字段配置
        $fieldConfigData = [
            "filter_list" => [
                "eventlog" => ["built_in" => ["fields" => [], "title" => L("eventlog"),], "custom" => []]
            ],
            "search_list" => [
                "eventlog" => ["built_in" => ["fields" => [], "title" => L("eventlog"),], "custom" => []]
            ],
            "sort_list" => [
                "eventlog" => ["built_in" => ["fields" => [], "title" => L("eventlog"),], "custom" => []]
            ],
            "group_list" => [
                "eventlog" => ["built_in" => ["fields" => [], "title" => L("eventlog"),], "custom" => []]
            ]
        ];

        foreach ($fieldConfig['config'] as $fieldItem) {
            $fieldItem['module'] = "eventlog";
            $fieldItem['module_type'] = 'fixed';
            $fieldItem['belong'] = $fieldItem['module'];
            $fieldItem['value_show'] = $fieldItem['module'] . '_' . $fieldItem['value_show'];

            if ($fieldItem["sort"] === "allow") {
                array_push($fieldConfigData["sort_list"]["eventlog"]["built_in"]["fields"], $fieldItem);
            }

            if ($fieldItem["group"] === "allow") {
                array_push($fieldConfigData["group_list"]["eventlog"]["built_in"]["fields"], $fieldItem);
            }

            if ($fieldItem["filter"] === "allow") {
                array_push($fieldConfigData["filter_list"]["eventlog"]["built_in"]["fields"], $fieldItem);
                if (in_array($fieldItem["type"], ["string", "char", "text"])) {
                    array_push($fieldConfigData['search_list']["eventlog"]["built_in"]["fields"], $fieldItem);
                }
            }
        }

        $resData = [
            'filter_bar' => $filterBar,
            'filter_list' => $fieldConfigData["filter_list"],
            'search_list' => $fieldConfigData["search_list"],
            'sort_list' => $fieldConfigData["sort_list"],
            'group_list' => $fieldConfigData["group_list"],
        ];
        return $resData;
    }

    /**
     * 更新该项目任务所有积分数据
     * @param $data
     * @param $formulaConfigData
     * @param $adjustCoefficientValue
     * @param $areaValue
     * @throws \Exception
     */
    protected function updateBaseFormulaData($data, $formulaConfigData, $adjustCoefficientValue, $areaValue)
    {

        $baseModel = new BaseModel();
        $variableValueModel = new VariableValueModel();
        $moduleIds = C('MODULE_ID');

        $taskData = $baseModel->alias('t')
            ->join("LEFT JOIN strack_variable_value as price_per ON price_per.link_id = t.id AND price_per.module_id = {$moduleIds['base']} AND price_per.variable_id = {$formulaConfigData['formula_price_per_square']}")
            ->join("LEFT JOIN strack_variable_value as time_per ON time_per.link_id = t.id AND time_per.module_id = {$moduleIds['base']}  AND time_per.variable_id = {$formulaConfigData['formula_time_per_square']}")
            ->field('t.id,price_per.value as price_per_square,time_per.value as time_per_square')
            ->where(['t.project_id' => $data['project_id']])
            ->select();

        // 更新积分和duration数据
        foreach ($taskData as $taskItem) {
            // 更新duration数据
            $duration = $adjustCoefficientValue * $areaValue * floatval($taskItem['time_per_square']);

            $durationFormat = duration_format($duration / 60);
            $baseModel->where(['id' => $taskItem['id']])->setField('plan_duration', $durationFormat);

            // $baseModel->where(['id' => $taskItem['id']])->setField('duration', $duration);

            // 更新积分数据
            $points = $adjustCoefficientValue * $areaValue * floatval($taskItem['price_per_square']);
            $variableValueModel->where([
                'link_id' => $taskItem['id'],
                'module_id' => $moduleIds['base'],
                'variable_id' => $formulaConfigData['points']
            ])->setField('value', $points);
        }
    }

    /**
     * duration改变更新人力成本数据
     * @param $data
     */
    protected function updateHumanCostFormulaDataByPlanDuration($data)
    {
        // 获取系统字段配置
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();

        if ($formulaConfigData !== false) {
            $moduleIds = C('MODULE_ID');

            // 获取执行人ID
            $baseModel = new BaseModel();
            $userId = $baseModel->alias('t')
                ->join("LEFT JOIN strack_horizontal af ON af.src_link_id = t.id AND af.src_module_id = {$moduleIds['base']} AND af.variable_id = {$formulaConfigData['assignee_field']}")
                ->field('af.dst_link_id as value')
                ->where([
                    't.id' => $data['link_id']
                ])
                ->find();

            if (!empty($userId)) {
                // 获取执行人时薪
                $variableValueModel = new VariableValueModel();
                $hourlyWage = $variableValueModel->where([
                    'link_id' => $userId['value'],
                    'module_id' => $moduleIds['user'],
                    'variable_id' => $formulaConfigData['formula_hourly_wage']
                ])->getField('value');

                // 计算人力成本
                $humanCost = (trans_duration($data['record']['new']['plan_duration']) / 60) * floatval($hourlyWage);
                $humanCost = sprintf("%.2f", $humanCost);
                // 更新人力成本
                $variableValueModel->where([
                    'link_id' => $data['link_id'],
                    'module_id' => $moduleIds['base'],
                    'variable_id' => $formulaConfigData['human_cost']
                ])->setField('value', $humanCost);
            }
        }
    }

    /**
     * 积分改变时候改变职级工资
     * @param $data
     * @param $formulaConfigData
     */
    protected function updateGradeTimeConsumingByPoints($data, $formulaConfigData)
    {
        $taskPrice = floatval($data['record']['new']['value']);
        $moduleIds = C('MODULE_ID');

        // 获取执行人id
        $horizontalModel = new HorizontalModel();
        $assigneeId = $horizontalModel->where([
            'src_link_id' => $data['link_id'],
            'src_module_id' => $moduleIds['base'],
            'dst_module_id' => $moduleIds['user'],
            'variable_id' => $formulaConfigData['assignee_field']
        ])->getField('dst_link_id');

        // 获取用户月薪
        $variableValueModel = new VariableValueModel();
        $monthlySalary = $variableValueModel->where([
            'link_id' => $assigneeId,
            'module_id' => $moduleIds['user'],
            'variable_id' => $formulaConfigData['formula_hourly_wage']
        ])->getField('value');


        $gradeTimeConsuming = 0;
        if ($taskPrice != 0 && $monthlySalary != 0) {
            $gradeTimeConsuming = sprintf("%.2f", $taskPrice / ($monthlySalary / 21.75 / 8));
        }

        // 更新职级工资
        $variableValueModel->where([
            'link_id' => $data['link_id'],
            'module_id' => $moduleIds['base'],
            'variable_id' => $formulaConfigData['grade_time_consuming']
        ])->setField('value', $gradeTimeConsuming);
    }

    /**
     * 根据time log 填充实际耗时
     * @param $data
     * @param $formulaConfigData
     */
    protected function updateActualTimeConsumingByTimeLog($data, $formulaConfigData)
    {
        // 获取当前timelog数据
        $timelogModel = new TimelogModel();
        $moduleIds = C('MODULE_ID');
        $timelogData = $timelogModel->field('link_id,module_id,start_time,end_time,user_id')
            ->where(['id' => $data['link_id']])
            ->find();

        $assigneeField = (int)$formulaConfigData['assignee_field']; // 执行人
        $reviewedBy = (int)$formulaConfigData['reviewed_by']; // 分派人

        $baseService = new BaseService();
        $assigneeFieldUserId = $baseService->getTaskHorizontalUserId($timelogData['link_id'], $assigneeField);
        $reviewedByUserId = $baseService->getTaskHorizontalUserId($timelogData['link_id'], $reviewedBy);

        if ((int)$timelogData['module_id'] === $moduleIds['base']) {
            // 获取当前任务所有时间日志值
            $moduleTimelogData = $timelogModel->field('start_time,end_time,user_id')
                ->where([
                    'link_id' => $timelogData['link_id'],
                    'module_id' => $timelogData['module_id']
                ])
                ->select();

            $timelogTotal = 0; // 执行人实际工时
            $examineTotal = 0; // 审核工时

            foreach ($moduleTimelogData as $timelogItem) {
                if ($timelogItem['user_id'] === $assigneeFieldUserId) {
                    $timelogTotal += $timelogItem['end_time'] - $timelogItem['start_time'];
                } else if ($timelogItem['user_id'] === $reviewedByUserId) {
                    $examineTotal += $timelogItem['end_time'] - $timelogItem['start_time'];
                }

            }
        }
    }


    /**
     * 根据 plan 填充预计耗时
     * @param $operate
     * @param $data
     * @param $formulaConfigData
     * @throws \Think\Exception
     */
    protected function updatePlanDurationConsumingByTimeLog($operate, $data, $formulaConfigData)
    {

        $linkId = 0;
        $planId = 0;
        $planModel = new PlanModel();
        if ($operate === 'create') {
            // 直接计算
            $linkId = $data['record']['link_id'];
            $planId = $data['record']['id'];

            // 创建计划同时把相关任务状态改成进行中
            $baseModel = new BaseModel();

            // 修改成进行中
            $baseModel->where([
                'id' => $data['record']['link_id']
            ])->setField('status_id', $formulaConfigData['in_progress_status']);

        } else {
            if (array_key_exists('start_time', $data['record']['old']) || array_key_exists('end_time', $data['record']['old'])) {
                $planId = $data['link_id'];
                $linkId = $planModel->where(['id' => $data['link_id']])->getField('link_id');
            }
        }

        if ($linkId > 0) {

            $taskPlanData = $planModel->field('user_id,start_time,end_time')
                ->where([
                    'link_id' => $linkId,
                    'module_id' => $data['module_id']
                ])->select();

            $planTotal = 0; // 计划工时
            $examineTotal = 0; // 审核工时

            $assigneeField = (int)$formulaConfigData['assignee_field']; // 执行人
            $reviewedBy = (int)$formulaConfigData['reviewed_by']; // 分派人

            $baseService = new BaseService();
            $assigneeFieldUserId = $baseService->getTaskHorizontalUserId($linkId, $assigneeField);
            $reviewedByUserId = $baseService->getTaskHorizontalUserId($linkId, $reviewedBy);

            $minPlanStartTime = 9999999999000;
            $maxPlanEndTime = 0;

            foreach ($taskPlanData as $taskItem) {

                if ($taskItem['user_id'] === $assigneeFieldUserId) {
                    // 执行人的
                    $planTotal += $taskItem['end_time'] - $taskItem['start_time'];

                    // 查找最小开始时间戳
                    $minPlanStartTime = $taskItem['start_time'] < $minPlanStartTime ? $taskItem['start_time'] : $minPlanStartTime;

                    // 查找最大结束时间戳
                    $maxPlanEndTime = $taskItem['end_time'] > $maxPlanEndTime ? $taskItem['end_time'] : $maxPlanEndTime;
                } else if ($taskItem['user_id'] === $reviewedByUserId) {
                    // 分派人的
                    $examineTotal += $taskItem['end_time'] - $taskItem['start_time'];
                }
            }

            // 秒转分钟
            if ($minPlanStartTime !== 9999999999000) {
                $planMin = $planTotal > 0 ? ($planTotal / 60) : 0;
                $baseModel = new BaseModel();
                $planData = [
                    'plan_start_time' => $minPlanStartTime,
                    'plan_end_time' => $maxPlanEndTime,
                    'plan_duration' => $planMin
                ];

                $baseModel->where(['id' => $linkId])->save($planData);
            }

            // 自动设置任务提醒
            if ($operate === 'create' && $planId > 0 && $linkId > 0) {
                $eventService = new EventService();
                $eventService->addTaskReminder($planId, $linkId);
            }
        }
    }

    /**
     * 更新任务状态当用户提交结算处理时候
     * @param $data
     * @param $formulaConfigData
     */
    protected function updateBaseStatusByConfirmHistory($data, $formulaConfigData)
    {
        $confirmHistoryOperation = $data['record']['operation'];
        $baseModel = new BaseModel();
        $moduleIds = C('MODULE_ID');

        // 结算操作都需要暂停当前结算任务时间日志
        $timelogService = new TimelogService();
        $timelogService->stopTomelogTimerByFilter([
            'module_id' => $moduleIds['base'],
            'complete' => 'no',
            'link_id' => $data['record']['link_id']
        ]);

        switch ($confirmHistoryOperation) {
            case 'apply':
                // 修改成结算中
                $baseModel->where([
                    'id' => $data['record']['link_id']
                ])->setField('status_id', $formulaConfigData['reviewed_by_status']);
                break;
            case 'reject':
                // 修改成进行中
                $baseModel->where([
                    'id' => $data['record']['link_id']
                ])->setField('status_id', $formulaConfigData['in_progress_status']);
                break;
            case 'confirm':
                // 修改成完成
                $baseModel->where([
                    'id' => $data['record']['link_id']
                ])->setField('status_id', $formulaConfigData['end_by_status']);

                // 判断当前任务是否是重复任务，自动创建下一个周期任务
                $baseService = new BaseService();
                $baseService->dealRepeatBase($data['record']['link_id']);
                break;
        }
    }

    /**
     * 更新计划周期
     * @param $data
     * @param $type
     */
    public function updatePlanDurationByTime($data, $type)
    {
        $baseModel = new BaseModel();
        $newVal = $data['record']['new'][$type];

        $baseData = $baseModel->field('plan_start_time,plan_end_time')->where(['id' => $data['link_id']])->find();

        if ($type === 'plan_start_time') {
            $duration = $baseData['plan_end_time'] - $newVal;
        } else {
            $duration = $newVal - $baseData['plan_start_time'];
        }

        $durationFormat = duration_format($duration / 60);

        $baseModel->where(['id' => $data['link_id']])->setField('plan_duration', $durationFormat);
    }

}

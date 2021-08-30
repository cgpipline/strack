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
     * @throws \Think\Exception
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
     * @throws \Think\Exception
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

        // 增加Event数据来源系统配置
        $data['belong_system'] = C('BELONG_SYSTEM');

        // 写入到当前批次缓存
        $this->writeToOperationCache($data);

        // 记录到Event服务器
        $this->postToServer($data, "add");
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
     * 获取项目名通过project_id
     * @param $projectId
     * @return array|mixed|string
     */
    protected function getProjectNameById($projectId)
    {
        $projectName = M("Project")->where(["id" => $projectId])->getField("name");
        return !empty($projectName) ? $projectName : "";
    }

    /**
     * 添加处理框架内部事件日志
     * @param $from
     * @param $data
     * @param $userInfo
     * @throws \Think\Exception
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
                            $addData["project_name"] = $this->getProjectNameById($item["project_id"]);
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
                            $addData["project_name"] = $this->getProjectNameById($addData["project_id"]);

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
                                $addData["project_name"] = $this->getProjectNameById($addData["project_id"]);
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
                                $addData["project_name"] = $this->getProjectNameById($projectId);
                            } else if (array_key_exists("project_id", $data)) {
                                $addData["project_id"] = $data["project_id"];
                                $addData["project_name"] = $this->getProjectNameById( $data["project_id"]);
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
        $viewService = new ViewService();
        $schemaFields = $viewService->getGridQuerySchemaConfig($param);

        // 查询关联模型数据
        $eventLogModel = new EventLogModel();
        $resData = $eventLogModel->getRelationData($schemaFields);
        return $resData;
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

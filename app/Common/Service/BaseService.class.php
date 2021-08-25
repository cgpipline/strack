<?php
// +----------------------------------------------------------------------
// | 基础类型服务层
// +----------------------------------------------------------------------
// | 主要服务于任务相关数据操作
// +----------------------------------------------------------------------
// | 错误编码头 201xxx
// +----------------------------------------------------------------------

namespace Common\Service;

use Common\Model\AuthAccessModel;
use Common\Model\BaseModel;
use Common\Model\BaseRepeatConfigModel;
use Common\Model\EntityModel;
use Common\Model\HorizontalModel;
use Common\Model\ModuleModel;
use Common\Model\VariableModel;
use Common\Model\VariableValueModel;

class BaseService
{
    /**
     * 获取表格数据
     * @param $param
     * @return mixed
     */
    public function getBaseGridData($param)
    {
        // 获取schema配置
        $viewService = new ViewService();
        $schemaFields = $viewService->getGridQuerySchemaConfig($param);

        // 查询关联模型数据
        $baseModel = new BaseModel();
        $resData = $baseModel->getRelationData($schemaFields);

        return $resData;
    }

    /**
     * 获取详情页面的表格数据
     * @param $param
     * @return mixed
     */
    public function getDetailGridData($param)
    {
        $schemaService = new SchemaService();

        if ($param["page"] === "details_base") {
            $param['filter']['request'] = [
                [
                    "field" => "entity_id",
                    "field_type"  => 'built_in',
                    "editor" => "combobox",
                    "value" => $param["item_id"],
                    "condition" => "EQ",
                    "module_code" => "base",
                    "table" => "Base"
                ],
                [
                    "field" => "entity_module_id",
                    "field_type"  => 'built_in',
                    "editor" => "combobox",
                    "value" => $param["parent_module_id"],
                    "condition" => "EQ",
                    "module_code" => "base",
                    "table" => "Base"
                ]
            ];
        }

        if (in_array($param['module_type'], ["horizontal_relationship", "be_horizontal_relationship"])) {
            $moduleData = $schemaService->getModuleFindData(["id" => $param["module_id"]]);
            $table = $moduleData["type"] === "entity" ? $moduleData["type"] : $moduleData["code"];
            $moduleCode = $moduleData["code"];
            $horizontalService = new HorizontalService();
            switch ($param['module_type']) {
                case "horizontal_relationship":
                    $dstLinkIds = $horizontalService->getModuleRelationIds([
                        'src_module_id' => $param['parent_module_id'],
                        'src_link_id' => $param['item_id'],
                        'dst_module_id' => $param["module_id"]
                    ], "dst_link_id");

                    $horizontalValue = !empty($dstLinkIds) ? join(',', $dstLinkIds) : 0;
                    $condition = !empty($dstLinkIds) ? "IN" : "EQ";

                    $request = [
                        "field" => "id",
                        "field_type"  => 'built_in',
                        "editor" => "combobox",
                        "value" => $horizontalValue,
                        "condition" => $condition,
                        "module_code" => $moduleCode,
                        "table" => string_initial_letter($table)
                    ];

                    array_push($param['filter']['request'], $request);
                    break;
                case "be_horizontal_relationship":
                    $dstLinkIds = $horizontalService->getModuleRelationIds([
                        'src_module_id' => $param['module_id'],
                        'dst_link_id' => $param['item_id'],
                        'dst_module_id' => $param["parent_module_id"]
                    ], "src_link_id");

                    $horizontalValue = !empty($dstLinkIds) ? join(',', $dstLinkIds) : 0;
                    $condition = !empty($dstLinkIds) ? "IN" : "EQ";

                    $request = [
                        "field" => "id",
                        "field_type"  => 'built_in',
                        "editor" => "combobox",
                        "value" => $horizontalValue,
                        "condition" => $condition,
                        "module_code" => $moduleCode,
                        "table" => string_initial_letter($table)
                    ];
                    array_push($param['filter']['request'], $request);
                    break;
            }
        } else {
            $moduleCode = "base";
        }

        // 获取schema配置
        $viewService = new ViewService();
        $schemaFields = $viewService->getGridQuerySchemaConfig($param);

        // 查询关联模型数据
        $modelClassName = '\\Common\\Model\\' . string_initial_letter($moduleCode) . 'Model';
        $modelClass = new $modelClassName();
        $resData = $modelClass->getRelationData($schemaFields);

        return $resData;
    }

    /**
     * 获取审核页面审核任务列表数据
     * @param $param
     * @return array
     */
    public function getReviewTaskList($param)
    {
        // 获取当前页面的实体ID
        $entityModel = new EntityModel();
        $entityData = $entityModel->selectData(['filter' => ["module_id" => $param["review_module_id"], "project_id" => $param["project_id"]], "fields" => "id"]);

        if ($entityData["total"] > 0) {
            $entityIds = array_column($entityData["rows"], "id");

            // 初始化条件
            $options = [
                "filter" => [
                    "entity_id" => ["IN", join(",", $entityIds)],
                    "project_id" => $param["project_id"]
                ],
                "fields" => "id,name,code,project_id,step_id,status_id,priority,created_by,created",
                "order" => "created desc",
                "page" => [$param["page_number"], $param["page_size"]]
            ];

            // 名称搜索
            if (array_key_exists("filter", $param) && !empty($param["filter"])) {
                $options["filter"]["name"] = $param["filter"]["name"];
            }

            // 查询任务数据
            $baseModel = new BaseModel();
            if ($param["type"] === "my") {
                // 获取是我的任务
                $horizontalService = new HorizontalService();
                $taskMemberData = $horizontalService->getHorizontalRelationData([
                    "src_module_id" => C("MODULE_ID")["base"],
                    "dst_module_id" => C("MODULE_ID")["user"],
                    "dst_link_id" => session("user_id"),
                    "code" => "assign",
                ]);
                $linkIds = array_column($taskMemberData, 'src_link_id');

                if (!empty($linkIds)) {
                    $options["filter"]["id"] = ["IN", join(",", $linkIds)];
                    $resData = $baseModel->selectData($options);
                } else {
                    $resData = ["total" => 0, "rows" => []];
                }
            } else {
                $resData = $baseModel->selectData($options);
            }

            $mediaService = new MediaService();
            $userService = new UserService();
            foreach ($resData["rows"] as &$item) {
                $item["thumb"] = $mediaService->getMediaThumb(["link_id" => $item["id"], "module_id" => 4]);
                // 格式化时间分组
                $item["group_md5"] = get_date_group_md5($item["created"]);
                $item["group_name"] = $item["created"];
                $userData = $userService->getUserFindField(["id" => $item["created_by"]], "name");
                $item["created_by"] = !empty($userData) ? $userData["name"] : "";
            }
        } else {
            $resData = ["total" => 0, "rows" => []];
        }

        return $resData;
    }

    /**
     * 删除指定的审核任务
     * @param $param
     * @return array
     */
    public function deleteReviewTask($param)
    {
        $baseModel = new BaseModel();
        $resData = $baseModel->deleteItem(["id" => $param["base_id"]]);
        if (!$resData) {
            // 删除任务失败错误码 002
            throw_strack_exception($baseModel->getError(), 212002);
        } else {
            // 返回成功数据
            return success_response($baseModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 获取水平关联源数据
     * @param $param
     * @param $searchValue
     * @param $mode
     * @return array
     */
    public function getHRelationSourceData($param, $searchValue, $mode)
    {
        if ($mode === "all") {
            $filter = [
                "id" => ["NOT IN", join(",", $param["link_data"])],
                "project_id" => $param["project_id"]
            ];
        } else {
            $filter = [
                "id" => ["IN", join(",", $param["link_data"])],
                "project_id" => $param["project_id"]
            ];
        }

        // 有额外过滤条件
        if (!empty($searchValue)) {
            $filter = [
                $filter,
                [
                    "name" => ["LIKE", "%{$searchValue}%"],
                    "code" => ["LIKE", "%{$searchValue}%"],
                    "_logic" => "OR"
                ],
                "_logic" => "AND"
            ];
        }

        $option = [
            "filter" => $filter,
            "fields" => "id,name,code",
        ];

        if (array_key_exists("pagination", $param)) {
            $option["page"] = [$param["pagination"]["page_number"], $param["pagination"]["page_size"]];
        }

        $baseModel = new BaseModel();
        $horizontalRelationData = $baseModel->selectData($option);

        return $horizontalRelationData;
    }

    /**
     * 获取指定任务的关联用户ID
     * @param $taskId
     * @param $variableId
     * @return int|mixed
     */
    public function getTaskHorizontalUserId($taskId, $variableId = 0)
    {
        $userId = 0;
        $moduleIds = C('MODULE_ID');

        if ($variableId > 0) {
            // 获取执行人id
            $horizontalModel = new HorizontalModel();
            $assigneeId = $horizontalModel->where([
                'src_link_id' => $taskId,
                'src_module_id' => $moduleIds['base'],
                'dst_module_id' => $moduleIds['user'],
                'variable_id' => $variableId
            ])->getField('dst_link_id');

            if (!empty($assigneeId)) {
                $userId = $assigneeId;
            }
        }

        return $userId;
    }


    /**
     * 获取用户时间范围内的任务列表
     * @param int $userId
     * @param int $startTime
     * @param int $endTime
     * @return mixed
     */
    public function getTimeFrameListbyUserid($userId = 0, $startTime = 0, $endTime = 0)
    {
        $moduleIds = C('MODULE_ID');
        $horizontalModel = new HorizontalModel();
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        $assigneeField = $formulaConfigData['assignee_field']; //执行人
        $reviewedBy = $formulaConfigData['reviewed_by']; //分派人

        $filter = [
            'horizontal.src_module_id' => $moduleIds['base'],
            'horizontal.dst_module_id' => $moduleIds['user'],
            'horizontal.dst_link_id' => $userId,
            "horizontal.variable_id" => ["in", "$assigneeField,$reviewedBy"],
            "base.end_time" => ["BETWEEN", [$startTime, $endTime]],
        ];
        $horizontalListData = $horizontalModel
            ->alias("horizontal")
            ->join('LEFT JOIN strack_base base ON base.id = horizontal.src_link_id')
            ->where($filter)
            ->field("
                base.id,
                base.name,
                base.status_id,
                base.project_id,
                base.end_time,
                base.plan_start_time,
                horizontal.variable_id
            ")
            ->order('base.id desc')
            ->select();
        return $horizontalListData;
    }

    /**
     * 获取用户时间范围内的项目、任务数据
     * @param int $userId
     * @param int $startTime
     * @param int $endTime
     * @return array
     */
    public function getTimeFrameDatabyUserid($userId = 0, $startTime = 0, $endTime = 0)
    {
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        $horizontalListData = $this->getTimeFrameListbyUserid($userId, $startTime, $endTime);
        $projectIdArr = []; //所有项目id集合
        $completeCount = $incompleteCount = $overtimeCount = 0; //已完成、未完成、已超时
        $nowtime = time();
        $i = 0;
        foreach ($horizontalListData as $item) {
            $projectIdArr[] = $item['project_id'];
            switch ($item['status_id']) {
                case $formulaConfigData['end_by_status']: //已完成
                    $completeCount++;
                    break;
                default: //未完成
                    if ($item['end_time'] < $nowtime) { //如果已超时
                        $overtimeCount++;
                    } else {
                        $incompleteCount++;
                    }
            }
            $i++;
        }
        //任务数据
        $baseData = [
            'total' => $i,
            'completeCount' => $completeCount,
            'incompleteCount' => $incompleteCount,
            'overtimeCount' => $overtimeCount,
        ];
        //获取项目数据
        $projectData = [
            'total' => 0,
            'completeCount' => 0,
            'incompleteCount' => 0,
            'overtimeCount' => 0,
        ];
        if (!empty($projectIdArr)) {
            $projectService = new ProjectService();
            $projectData = $projectService->getProjectDataByids($projectIdArr);
        }
        return ['projectData' => $projectData, 'baseData' => $baseData];
    }

    /**
     * 获取时间范围内的积分信息
     * @param int $userId
     * @param int $startTime
     * @param int $endTime
     * @return array
     */
    public function getTimeFrameIntegralList($userId = 0, $startTime = 0, $endTime = 0)
    {
        $moduleIds = C('MODULE_ID');
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        $assigneeField = $formulaConfigData['assignee_field']; //执行人
        $reviewedBy = $formulaConfigData['reviewed_by']; //分派人
        $points = $formulaConfigData['points']; //积分字段id
        $horizontalListData = $this->getTimeFrameListbyUserid($userId, $startTime, $endTime);
        if (empty($horizontalListData)) {
            return [
                'integralTotal' => 0,
                'expenditure' => 0,
                'income' => 0,
                'dueIn' => 0,
                'list' => [],
            ];
        }
        //所有项目id集合
        $projectIdArr = array_column($horizontalListData, 'project_id');

        //获取项目名称
        $projectService = new ProjectService();
        $projectNameList = $projectService->getProjectIdList($projectIdArr);

        //所有任务id集合
        $baseIdArr = array_column($horizontalListData, 'id');

        //获取所有任务的积分数
        $variableService = new VariableService();
        $variableArr = $variableService->getVariableValueList($baseIdArr, $moduleIds['base'], $points);
        $baseList = [];
        $integralTotal = $expenditure = $income = $dueIn = 0; //总数、支出、收入、待收
        foreach ($horizontalListData as $item) {
            //获取积分数
            $integralNum = $variableArr[$item['id']];
            $integralNum = empty($integralNum) ? 0 : floatval($integralNum);
            if ($item['variable_id'] == $assigneeField) { //当前用户为执行人
                if ($formulaConfigData['end_by_status'] == $item['status_id']) { //此任务状态为已完成
                    $income = $income + $integralNum;
                } else {
                    $dueIn = $dueIn + $integralNum;
                }
                $integralNum = ($integralNum > 0) ? $integralNum : 0;
            } elseif ($item['variable_id'] == $reviewedBy) { //当前用户为分派人
                $expenditure = $expenditure + $integralNum; //支出
                $integralNum = ($integralNum > 0) ? '-' . $integralNum : 0;
            } else {
                continue;
            }
            $integralTotal = $integralTotal + $integralNum;
            $baseList[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'end_time' => $item['end_time'],
                'project_name' => $projectNameList[$item['project_id']],
                'integralNum' => $integralNum
            ];
        }
        $outArr = [
            'integralTotal' => $integralTotal,
            'expenditure' => $expenditure,
            'income' => $income,
            'dueIn' => $dueIn,
            'list' => $baseList,
        ];
        return $outArr;
    }

    /**
     * 获取任务表单字段
     * @return array
     */
    public function getTaskFormFieldsConfig()
    {
        // 获取系统字段配置
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();

        // 获取任务模块字段配置
        $schemaService = new SchemaService();
        $moduleModel = new ModuleModel();

        $moduleMapData = $schemaService->getModuleMapData('id');

        $moduleData = $moduleModel->field('id as module_id,code,type')->where(['code' => 'base'])->find();
        $fieldsData = $schemaService->getTableFields($moduleData, 0, $moduleMapData);

        /**
         * 需要添加的字段
         */
        $needTaskField = [
            'built_in' => ['project_id', 'name', 'status_id', 'end_time', 'description'],
            'custom' => [$formulaConfigData['estimate_working_hours'], $formulaConfigData['reviewed_by'], $formulaConfigData['assignee_field'], $formulaConfigData['grouping_of_stage']]
        ];

        $formFieldsConfig = [];

        // 加入固定字段
        foreach ($fieldsData['master']['built_in'] as $builtInItem) {
            if (in_array($builtInItem['fields'], $needTaskField['built_in'])) {
                $formFieldsConfig[] = $builtInItem;
            }
        }

        // 加入自定义字段
        $customKeyMap = [];
        foreach ($fieldsData['master']['custom'] as $customItem) {
            if (in_array($customItem['variable_id'], $needTaskField['custom'])) {
                $formFieldsConfig[] = $customItem;
                switch ($customItem['variable_id']) {
                    case  $formulaConfigData['estimate_working_hours']:
                        $customKeyMap[$customItem['fields']] = 'estimate_working_hours';
                        break;
                    case  $formulaConfigData['reviewed_by']:
                        $customKeyMap[$customItem['fields']] = 'reviewed_by';
                        break;
                    case  $formulaConfigData['assignee_field']:
                        $customKeyMap[$customItem['fields']] = 'assignee_field';
                        break;
                    case  $formulaConfigData['grouping_of_stage']:
                        $customKeyMap[$customItem['fields']] = 'grouping_of_stage';
                        break;
                }
            }
        }


        $resData = [
            'custom_key_map' => $customKeyMap,
            'form_fields_config' => $formFieldsConfig
        ];

        return $resData;
    }

    /**
     * 获取用户中心任务列表
     * @param int $userId
     * @return mixed
     */
    public function getTaskListbyUserid($userId, $param)
    {
        $moduleIds = C('MODULE_ID');
        $horizontalModel = new HorizontalModel();
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        $assigneeField = $formulaConfigData['assignee_field']; //执行人
        $reviewedBy = $formulaConfigData['reviewed_by']; //分派人
        $type = empty($param['type']) ? 1 : $param['type']; //1：参与任务 2：完成任务 3：未完成任务 4：超时任务
        $page = empty($param['page']) ? 1 : $param['page'];
        $limit = empty($param['pagesize']) ? 10 : $param['pagesize'];
        $offset = ($page - 1) * $limit;
        $nowtime = time();

        $filter = [
            'horizontal.src_module_id' => $moduleIds['base'],
            'horizontal.dst_module_id' => $moduleIds['user'],
            'horizontal.dst_link_id' => $userId,
            "horizontal.variable_id" => ["in", "$assigneeField,$reviewedBy"],
        ];
        if (!empty($param['start']) && !empty($param['end'])) {
            $filter['base.end_time'] = ["BETWEEN", [$param['start'], $param['end']]];
        }
        if ($type == 2) { //已完成
            $filter['base.status_id'] = $formulaConfigData['end_by_status'];
        } elseif ($type == 3) { //未完成
            $filter['_complex'] = [
                'base.status_id' => ['neq', $formulaConfigData['end_by_status']],
                'base.end_time' => ['gt', $nowtime],
                '_logic' => 'AND'
            ];
        } elseif ($type == 4) { //超时
            $filter['_complex'] = [
                'base.status_id' => ['neq', $formulaConfigData['end_by_status']],
                'base.end_time' => ['lt', $nowtime],
                '_logic' => 'AND'
            ];
        }
        $horizontalListData = $horizontalModel
            ->alias("horizontal")
            ->join('LEFT JOIN strack_base base ON base.id = horizontal.src_link_id')
            ->join('LEFT JOIN strack_project project ON base.project_id = project.id')
            ->where($filter)
            ->field("
                base.id,
                base.name,
                base.status_id,
                base.project_id,
                base.end_time,
                base.plan_start_time,
                horizontal.variable_id,
                project.name as project_name
            ")
            ->order('base.id desc')
            ->limit($offset, $limit)
            ->select();
        return $horizontalListData;
    }

    /**
     * 获取用户任务列表（用于计算用户时间范围内的超时工时）
     * @param int $userId
     * @param int $statusId 任务状态id 大于0则启动状态筛选
     * @return mixed
     */
    public function getOverTimeTaskListbyUserid($userId, $startTime = 0, $endTime = 0, $statusId = 0)
    {
        $moduleIds = C('MODULE_ID');
        $horizontalModel = new HorizontalModel();
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        $assigneeField = $formulaConfigData['assignee_field']; //执行人

        $filter = [
            'horizontal.src_module_id' => $moduleIds['base'],
            'horizontal.dst_module_id' => $moduleIds['user'],
            'horizontal.dst_link_id' => $userId,
            "horizontal.variable_id" => $assigneeField,
            [
                [
                    // 开始时间在这个范围内
                    'base.start_time' => ["BETWEEN", [$startTime, $endTime]],
                ],
                [
                    // 结束时间在这个范围内
                    'base.end_time' => ["BETWEEN", [$startTime, $endTime]],
                ],
                [
                    // 开始时间小于等于当前开始时间，结束时间大于等于当前时间
                    'base.start_time' => ["elt", $startTime],
                    'base.end_time' => ["egt", $startTime],
                    '_logic' => 'AND'
                ],
                '_logic' => 'OR'
            ]
        ];
        if (!empty($statusId)) {
            $filter['base.status_id'] = $statusId;
        }
        $horizontalListData = $horizontalModel
            ->alias("horizontal")
            ->join('LEFT JOIN strack_base base ON base.id = horizontal.src_link_id')
            ->where($filter)
            ->field("
                base.id,
                base.name,
                base.end_time
            ")
            ->order('base.id')
            ->select();
        return $horizontalListData;
    }


    /**
     * 获取指定任务的重复任务配置
     * @param $baseId
     * @return array|mixed
     */
    public function getBaseRepeatConfig($baseId)
    {
        $baseRepeatConfigModel = new BaseRepeatConfigModel();
        $baseRepeatConfig = $baseRepeatConfigModel->findData([
            'filter' => ['base_id' => $baseId]
        ]);

        if (!empty($baseRepeatConfig)) {
            return $baseRepeatConfig;
        } else {
            return [
                'mode' => 'none',
                'config' => []
            ];
        }
    }

    /**
     * 更新指定任务的重复任务配置
     * @param $baseId
     * @param $updateData
     * @return array
     */
    public function updateBaseRepeatConfig($baseId, $updateData)
    {
        $baseRepeatConfigModel = new BaseRepeatConfigModel();
        $baseRepeatId = $baseRepeatConfigModel->where(['base_id' => $baseId])->getField('id');
        if (!empty($baseRepeatId)) {
            // 更新数据
            $updateData['id'] = $baseRepeatId;
            $resData = $baseRepeatConfigModel->modifyItem($updateData);
            if (!$resData) {
                // 修改重复任务配置失败错误码 002
                throw_strack_exception($baseRepeatConfigModel->getError(), 212004);
            } else {
                // 返回成功数据
                return success_response($baseRepeatConfigModel->getSuccessMassege(), $resData);
            }
        } else {
            // 添加数据
            $resData = $baseRepeatConfigModel->addItem($updateData);
            if (!$resData) {
                // 添加重复任务配置失败错误码 001
                throw_strack_exception($baseRepeatConfigModel->getError(), 212003);
            } else {
                // 返回成功数据
                return success_response($baseRepeatConfigModel->getSuccessMassege(), $resData);
            }
        }
    }

    /**
     * 生成自定义周重复下个指定日期
     * @param $currentBaseEndTime
     * @param $start
     * @param $dateList
     * @param $inter
     * @return int
     */
    protected function generateCustomWeekRepeatBaseEndTime($currentBaseEndTime, $start, $dateList, $inter)
    {
        $nextBaseEndTime = 0;
        $intInter = 0;
        $oldStart = $start;
        do {

            $start = $start + 1;

            if ($start > 7) {
                $start = 1;
                $intInter++;
            }

            if (in_array($start, $dateList)) {
                $nextBaseEndTime = $currentBaseEndTime + (86400 * ($start - $oldStart)) + ($inter * 7 * $intInter);
            }

        } while ($nextBaseEndTime === 0);

        return $nextBaseEndTime;
    }

    /**
     * 生成自定义月重复下个指定日期
     * @param $currentBaseEndTime
     * @param $start
     * @param $dateList
     * @param $inter
     * @return float|int
     */
    protected function generateCustomMonthlyRepeatBaseEndTime($currentBaseEndTime, $start, $dateList, $inter)
    {
        $nextBaseEndTime = 0;
        $intInter = 0;
        $oldStart = $start;
        do {

            $start = $start + 1;

            if ($start > 31) {
                $start = 1;
                $intInter++;
            }

            $tempNextBaseEndTime = $currentBaseEndTime + (86400 * ($start - $oldStart)) + ($inter * 31 * $intInter);

            if (in_array(date('d', $tempNextBaseEndTime), $dateList)) {
                $nextBaseEndTime = $tempNextBaseEndTime;
            }

        } while ($nextBaseEndTime === 0);

        return $nextBaseEndTime;

    }

    /**
     * 复制重复任务数据
     * @param $baseData
     * @param $baseRepeatConfig
     * @param $nextBaseEndTime
     */
    protected function copyRepeatBaseData($baseData, $baseRepeatConfig, $nextBaseEndTime)
    {
        // 查询任务自定义字段值
        $variableValueModel = new VariableValueModel();
        $variableValueData = $variableValueModel->field('link_id,module_id,variable_id,value')->where([
            'link_id' => $baseData['id'],
            'module_id' => C('MODULE_ID')['base']
        ])->select();

        $horizontalModel = new HorizontalModel();
        $horizontalData = $horizontalModel->field('src_link_id,src_module_id,dst_link_id,dst_module_id,variable_id')
            ->where([
                'src_link_id' => $baseData['id'],
                'src_module_id' => C('MODULE_ID')['base']
            ])
            ->select();

        // 1. 复制出来的结算工时要等于预估工时
        // 2. 状态改成未完成
        // 3. 复制重复配置

        // 当前任务已经完成状态后也不显示时间日志按钮
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();

        // 1. 新增复制任务
        $baseData['end_time'] = get_format_date($nextBaseEndTime, 1);
        $baseData['plan_start_time'] = get_format_date($baseData['plan_start_time'], 1);
        $baseData['plan_end_time'] = get_format_date($baseData['plan_end_time'], 1);
        $baseData['status_id'] = $formulaConfigData['no_start_status'];
        $baseModel = new BaseModel();
        $baseAddData = [];
        foreach ($baseData as $key => $baseVal) {
            if (!in_array($key, ['id', 'uuid', 'created'])) {
                if ($key === 'json') {
                    if (!empty($baseVal)) {
                        $baseAddData[$key] = $baseVal;
                    } else {
                        $baseAddData[$key] = [];
                    }
                } else {
                    $baseAddData[$key] = $baseVal;
                }
            }
        }
        $resData = $baseModel->addItem($baseAddData);

        if (!empty($resData)) {
            // 添加任务成功后处理自定义字段
            $variableValueMap = array_column($variableValueData, null, 'variable_id');
            foreach ($variableValueData as $variableValueItem) {

                switch ((int)$variableValueItem['variable_id']) {
                    case (int)$formulaConfigData['actual_time_consuming']:
                        // 实际工时
                        $variableValueItem['value'] = 0;
                        break;
                    case (int)$formulaConfigData['settlement_time_consuming']:
                        // 结算工时等于预估工时
                        $variableValueItem['value'] = $variableValueMap[$formulaConfigData['estimate_working_hours']];
                        break;
                }

                $variableValueItem['link_id'] = $resData['id'];

                $variableValueModel->addItem($variableValueItem);
            }

            // 处理水平关联
            foreach ($horizontalData as $horizontalItem) {
                $horizontalItem['src_link_id'] = $resData['id'];
                $horizontalModel->addItem($horizontalItem);
            }

            //  处理重复配置
            $baseRepeatConfigModel = new BaseRepeatConfigModel();
            $baseRepeatConfig['base_id'] = $resData['id'];
            $baseAddRepeatConfig = [];
            foreach ($baseRepeatConfig as $key => $baseRepeatConfigVal) {
                if (!in_array($key, ['id', 'uuid', 'created'])) {
                    if ($key === 'config') {
                        if (!empty($baseVal)) {
                            $baseAddRepeatConfig[$key] = $baseRepeatConfigVal;
                        } else {
                            $baseAddRepeatConfig[$key] = [];
                        }
                    } else {
                        $baseAddRepeatConfig[$key] = $baseRepeatConfigVal;
                    }
                }
            }
            $baseRepeatConfigModel->addItem($baseAddRepeatConfig);
        }
    }

    /**
     * 处理自动创建下一个重复任务
     * @param $baseId
     */
    public function dealRepeatBase($baseId)
    {
        $baseModel = new BaseModel();
        $baseData = $baseModel->where(['id' => $baseId])->find();

        if ($baseData['repeat'] === 'yes') {
            $baseRepeatConfigModel = new BaseRepeatConfigModel();
            $baseRepeatConfig = $baseRepeatConfigModel->findData([
                'filter' => ['base_id' => $baseId]
            ]);

            if (!empty($baseRepeatConfig)) {

                $currentBaseEndTime = $baseData['end_time'];
                $nextBaseEndTime = 0;
                switch ($baseRepeatConfig['mode']) {
                    case 'daily':
                        // 1.每天重复，复制上个任务，和上个任务重复配置，任务截止时间+1
                        $nextBaseEndTime = $currentBaseEndTime + 86400;
                        break;
                    case 'weekly':
                        // 2.每周重复，复制上个任务，和上个任务重复配置，任务截止时间+7
                        $nextBaseEndTime = $currentBaseEndTime + 604800;
                        break;
                    case 'monthly':
                        // 3.每月重复，复制上个任务，和上个任务重复配置，任务截止时间+31
                        $nextBaseEndTime = $currentBaseEndTime + 2678400;
                        break;
                    case 'annually':
                        // 4.每年重复，复制上个任务，和上个任务重复配置，任务截止时间+365
                        $nextBaseEndTime = $currentBaseEndTime + 31536000;
                        break;
                    case 'working_days':
                        // 5.工作日重复，复制上个任务，和上个任务重复配置，任务截止时间根据工作日配置来计算，跳过休息日
                        $optionsService = new OptionsService();
                        // 当前系统工作日配置
                        $workDayConfig = $optionsService->getOptionsData('schedule_workday');
                        // 当前时间下一个所属星期几
                        for ($i = 1; $i < 7; $i++) {
                            $incrementWeek = get_time_week($currentBaseEndTime, $i);
                            if (in_array($incrementWeek, $workDayConfig['days'])) {
                                $nextBaseEndTime = $currentBaseEndTime + (86400 * $i);
                                break;
                            }
                        }
                        break;
                    case 'custom':
                        // 自定义规则
                        switch ($baseRepeatConfig['config']['mode']) {
                            case 'daily':
                                // 每日重复
                                $nextBaseEndTime = $currentBaseEndTime + (86400 * $baseRepeatConfig['config']['inter']);
                                break;
                            case 'weekly':
                                // 每周重复
                                $dateList = $baseRepeatConfig['config']['date'];
                                $start = get_time_week($currentBaseEndTime, 0, 'number');
                                $nextBaseEndTime = $this->generateCustomWeekRepeatBaseEndTime($currentBaseEndTime, $start, $dateList, $baseRepeatConfig['config']['inter']);
                                break;
                            case 'monthly':
                                // 每月重复
                                $dateList = $baseRepeatConfig['config']['date'];
                                $start = date('d', $currentBaseEndTime);
                                $nextBaseEndTime = $this->generateCustomMonthlyRepeatBaseEndTime($currentBaseEndTime, $start, $dateList, $baseRepeatConfig['config']['inter']);
                                break;
                        }
                        break;
                }

                // 复制添加重复任务
                $this->copyRepeatBaseData($baseData, $baseRepeatConfig, $nextBaseEndTime);
            }
        }
    }

    /**
     * 获取任务水平关联用户ID
     * @param $variableId
     * @param $linkId
     * @return int|mixed
     */
    protected function getBaseHorizontalUserId($variableId, $linkId)
    {
        $horizontalModel = new HorizontalModel();
        $userId = $horizontalModel->where([
            'src_link_id' => $linkId,
            'variable_id' => $variableId,
            'src_module_id' => C('MODULE_ID')['base'],
            'dst_module_id' => C('MODULE_ID')['user']
        ])->getField('dst_link_id');

        return !empty($userId) ? $userId : 0;
    }

    /**
     * 获取任务用户水平关联数据权限范围数据
     * @param $permissionList
     * @param $field
     * @param $roleId
     */
    protected function getBaseHorizontalUserDataRangeData(&$permissionList, $field, $roleId)
    {
        $authAccessModel = new AuthAccessModel();
        $permission = $authAccessModel->where([
            'role_id' => $roleId,
            'page' => 'task_related_user',
            'param' => $field
        ])->getField('permission');

        if (!empty($permission)) {
            switch ($permission) {
                case 'view_related_to_me':
                    // 显示
                    if (!in_array('view', $permissionList)) {
                        $permissionList[] = 'view';
                    }
                    break;
                case 'view_edit_related_to_me':
                    // 显示、编辑
                    if (!in_array('view', $permissionList)) {
                        $permissionList[] = 'view';
                    }
                    if (!in_array('modify', $permissionList)) {
                        $permissionList[] = 'modify';
                    }
                    break;
                case 'view_edit_delete_related_to_me':
                    // 显示、编辑、删除
                    if (!in_array('view', $permissionList)) {
                        $permissionList[] = 'view';
                    }
                    if (!in_array('modify', $permissionList)) {
                        $permissionList[] = 'modify';
                    }
                    if (!in_array('delete', $permissionList)) {
                        $permissionList[] = 'delete';
                    }
                    break;
            }
        }
    }

    /**
     * 获取任务用户水平关联权限配置数据
     * @param int $baseId
     * @return array
     */
    public function getBaseHorizontalUserAuthData($baseId = 0)
    {
        $userId = session('user_id');
        $permissionList = [];
        if ($baseId > 0 && !in_array($userId, [1, 2])) {
            $BaseModel = new BaseModel();
            $baseData = $BaseModel->field('id,project_id,created_by')->where(['id' => $baseId])->find();

            // 当前任务关联的用户自定义字段
            $optionsService = new OptionsService();
            $fieldSettings = $optionsService->getFormulaConfigData();

            $createByUserId = $baseData['created_by'];

            // 获取水平关联用户数据
            $reviewed = $fieldSettings['reviewed_by'];
            $assignee = $fieldSettings['assignee_field'];

            $reviewedUserId = $this->getBaseHorizontalUserId($reviewed, $baseData['id']);
            $assigneeUserId = $this->getBaseHorizontalUserId($assignee, $baseData['id']);

            // 获取权限
            $authService = new AuthService();
            $roleData = $authService->getUserRoleData($userId, $baseData['project_id']);

            // 自定义字段
            $variableModel = new VariableModel();

            if ($createByUserId == $userId) {
                // 创建者
                $this->getBaseHorizontalUserDataRangeData($permissionList, 'created_by', $roleData['id']);
            }

            if ($reviewedUserId == $userId) {
                // 审核者
                $reviewedField = $variableModel->where(['id' => $reviewed])->getField('code');
                $this->getBaseHorizontalUserDataRangeData($permissionList, $reviewedField, $roleData['id']);
            }

            if ($assigneeUserId == $userId) {
                // 执行者
                $assigneeField = $variableModel->where(['id' => $assignee])->getField('code');
                $this->getBaseHorizontalUserDataRangeData($permissionList, $assigneeField, $roleData['id']);
            }
        }

        return $permissionList;
    }
}

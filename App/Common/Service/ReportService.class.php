<?php
// +----------------------------------------------------------------------
// | User 自定义服务
// +----------------------------------------------------------------------
// | 主要服务于User数据处理
// +----------------------------------------------------------------------
// | 错误编码头 229xxx
// +----------------------------------------------------------------------

namespace Common\Service;

use Common\Model\ConfirmHistoryModel;
use Common\Model\HorizontalModel;
use Common\Model\UserModel;
use Common\Model\PlanModel;
use Common\Model\TimelogModel;
use Common\Model\BaseModel;
use Common\Model\ProjectModel;
use Common\Model\VariableValueModel;


class ReportService
{

    /**
     * 获取数据看板用户数据范围权限
     * @return array
     */
    protected function getTeamKanbanDataRangeUserIds()
    {
        $authService = new AuthService();
        return $authService->getRoleDataRangeAuthUserIds(session('user_id'), 'team_kanban');
    }

    /**
     * 获取用户列表
     * @param $param
     * @return mixed
     */
    public function getUserList($param)
    {
        if(!empty($param['user_ids'])){ //筛选了用户
            $filter = [
                'id' => ["IN", $param['user_ids']]
            ];
        }else {
            // 获取当前用户数据看板数据范围权限
            $dataRangeUserIds = $this->getTeamKanbanDataRangeUserIds();

            $filter = [
                'status' => "in_service",
                'id' => ["NOT IN", "1,2"]
            ];

            if (!empty($dataRangeUserIds)) {
                $filter['_complex'] = [
                    'id' => ['IN', join(',', $dataRangeUserIds)]
                ];
            }

            //筛选了部门id
            if(!empty($param['department_id']) && is_array($param['department_id'])){
                $userService = new UserService();
                $departmentList = $userService->getDepartmentChildIds($param['department_id']);
                $filter['_complex'] = [
                    'department_id' => ['IN', join(',', $departmentList)]
                ];
            }
        }

        $userModel = new UserModel();
        $userList = $userModel->field('id,name,department_id')
            ->where($filter)
            ->order("login_name asc")
            ->select();

        return $userList;
    }

    /**
     * 获取实际任务数据
     * @param $param
     * @return mixed
     */
    protected function getTaskTimelogData($param)
    {
        $timeLog = new TimelogModel();
        $actualList = $timeLog->field('link_id,start_time,end_time')->where([
            'module_id' => $param['module_id'],
            'user_id' => $param['user_id'],
            '_complex' => [
                "start_time" => ["BETWEEN", [$param['today_start_time'], $param['today_end_time']]],
                [
                    "end_time" => ["gt", $param['today_start_time']],
                    "start_time" => ["elt", $param['today_end_time']],
                    "_logic" => "AND"
                ],
                "_logic" => "OR"
            ],
            "_logic" => "AND"
        ])->select();

        return $actualList;
    }

    /**
     * 获取任务计划数据
     * @param $param
     * @return mixed
     */
    protected function getTaskPlanData($param)
    {
        $plan = new PlanModel();
        $planList = $plan->field('link_id,start_time,end_time')->where([
            'module_id' => $param['module_id'],
            'user_id' => $param['user_id'],
            '_complex' => [
                "start_time" => ["BETWEEN", [$param['today_start_time'], $param['today_end_time']]],
                [
                    "end_time" => ["gt", $param['today_start_time']],
                    "start_time" => ["elt", $param['today_end_time']],
                    "_logic" => "AND"
                ],
                "_logic" => "OR"
            ],
            "_logic" => "AND"
        ])->select();

        return $planList;
    }

    /**
     * 获取队友计划数据(日期维度）
     * @param $param
     * @return array
     */
    public function getTeammatePlanningData($param)
    {
        //获取用户列表
        $userList = $this->getUserList($param);

        $reportData = [];
        foreach ($userList as $v) {
            $userPlanData = $this->getPlanDataByUid($v['id'],$param);
            $userActualData = $this->getActualDataByUid($v['id'],$param);
            $reportData[] = [
                'user_id' => $v['id'],
                'user_name' => $v['name'],
                'plan_data' => $userPlanData,
                'actual_data' => $userActualData
            ];
        }
        return $reportData;
    }

    /**
     * 获取队友计划数据(小时维度）
     * @param $param
     * @return array
     */
    public function getTeammatePlanningHouseData($param)
    {
        //获取用户列表
        $userList = $this->getUserList($param);

        $reportData = [];
        foreach ($userList as $v) {
            $userPlanData = $this->getPlanHouseDataByUid($v['id'],$param);
            $reportData[] = [
                'user_id' => $v['id'],
                'user_name' => $v['name'],
                'plan_data' => $userPlanData['plan_data'],
                'actual_data' => $userPlanData['actual_data'],
                'estimate_data' => $userPlanData['estimate_data'],
                'settlement_data' => $userPlanData['settlement_data']
            ];
        }
        return $reportData;
    }

    /**
     * 根据用户id获取今日计划图(计划任务）
     * @param $userId
     * @return array
     */
    public function getPlanDataByUid($userId,$param)
    {
        $moduleIds = C('MODULE_ID');
        $moduleId = $moduleIds['base']; //任务模块id

        //计划任务
        $planList = $this->getTaskPlanData([
            'module_id' => $moduleId,
            'user_id' => $userId,
            'today_start_time' => $param['start_date_time'], //开始时间
            'today_end_time' => $param['end_date_time'] //截止时间
        ]);

        $base = new BaseModel();
        $project = new ProjectModel();

        $list = [];
        foreach ($planList as $v) {
            $projectName = '';
            $projectId = 0;

            //查询任务信息
            $baseInfo = $base->findData([
                "filter" => ["id" => $v["link_id"]],
                "fields" => "name,project_id"
            ]);

            if (!empty($baseInfo)) {
                //查询项目信息
                if (!empty($baseInfo["project_id"])) {
                    $projectInfo = $project->findData([
                        "filter" => ["id" => $baseInfo["project_id"]],
                        "fields" => "id,name"
                    ]);

                    $projectName = $projectInfo['name'];
                    $projectId = $projectInfo['id'];
                }

                // 处理时间
                $result = $this->handleTime($v['start_time'], $v['end_time'],$param);

                $list[] = [
                    'id' => $v["link_id"],
                    'name' => $baseInfo['name'],
                    'project_name' => $projectName,
                    'start_time' => $result['start_time'],
                    'end_time' => $result['end_time'],
                    'project_id' => $projectId,
                    'module_id' => $moduleId
                ];
            }
        }

        return $list;
    }

    /**
     * 根据用户id获取今日计划图(实际任务）
     * @param $userId
     * @return array
     */
    public function getActualDataByUid($userId,$param)
    {
        $moduleIds = C('MODULE_ID');
        $moduleId = $moduleIds['base']; //任务模块id

        //实际任务
        $actualList = $this->getTaskTimelogData([
            'module_id' => $moduleId,
            'user_id' => $userId,
            'today_start_time' => $param['start_date_time'], //今日开始时间
            'today_end_time' => $param['end_date_time'] //今日截止时间
        ]);

        $base = new BaseModel();
        $project = new ProjectModel();

        $list = [];
        foreach ($actualList as $v) {

            $projectName = '';
            $projectId = 0;

            //查询任务信息
            $baseInfo = $base->findData([
                "filter" => ["id" => $v["link_id"]],
                "fields" => "name,project_id"
            ]);

            if (empty($baseInfo)) {
                continue;
            }

            //查询项目信息
            if (!empty($baseInfo["project_id"])) {
                $projectInfo = $project->findData([
                    "filter" => ["id" => $baseInfo["project_id"]],
                    "fields" => "id,name"
                ]);
                $projectName = $projectInfo['name'];
                $projectId = $projectInfo['id'];
            }

            $name = $baseInfo['name'];
            $result = $this->handleTime($v['start_time'], $v['end_time'],$param);

            if (empty($result['end_time'])) { //如果任务还未结束
                $result['end_time'] = time();
            }

            $list[] = [
                'id' => $v["link_id"],
                'name' => $name,
                'project_name' => $projectName,
                'start_time' => $result['start_time'],
                'end_time' => $result['end_time'],
                'project_id' => $projectId,
                'module_id' => $moduleId];
        }

        return $list;
    }

    /**
     * 根据用户id获取今日计划图(工时）
     * @param $userId
     * @return array
     */
    public function getPlanHouseDataByUid($userId,$param)
    {
        $moduleIds = C('MODULE_ID');
        $moduleId = $moduleIds['base']; //任务模块id

        //计划任务
        $planList = $this->getTaskPlanData([
            'module_id' => $moduleId,
            'user_id' => $userId,
            'today_start_time' => $param['start_date_time'], //开始时间
            'today_end_time' => $param['end_date_time'] //截止时间
        ]);

        // 获取结算工时和预估工时自定义字段id
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        $estimateWorkingHoursId = $formulaConfigData['estimate_working_hours'];
        $settlementTimeConsumingId = $formulaConfigData['settlement_time_consuming'];
        $endByStatus = $formulaConfigData['end_by_status']; //已完成状态

        $base = new BaseModel();
        $project = new ProjectModel();
        $variableValue = new VariableValueModel();

        $nowTime = time();
        $planData = [];
        $actualData = [];
        $estimateData = [];
        $settlementData = [];

        // 任务计划数据
        foreach ($planList as $v) {

            $projectName = '';
            $projectId = 0;

            //查询任务信息
            $baseInfo = $base->findData([
                "filter" => ["id" => $v["link_id"]],
                "fields" => "name,project_id,plan_duration,status_id"
            ]);

            if (empty($baseInfo)) {
                continue;
            }

            //查询项目信息
            if (!empty($baseInfo["project_id"])) {
                $projectInfo = $project->findData([
                    "filter" => ["id" => $baseInfo["project_id"]],
                    "fields" => "id,name"
                ]);
                $projectName = $projectInfo['name'];
                $projectId = $projectInfo['id'];
            }

            //获取工时
            $workingHouseList = $variableValue->field('variable_id,value')->where([
                'module_id' => $moduleId,
                'link_id' => $v["link_id"],
                "variable_id" => ["in", join(',', [$estimateWorkingHoursId, $settlementTimeConsumingId])]
            ])->select();


            $tempData = [
                'id' => $v["link_id"],
                'name' => $baseInfo['name'],
                'project_name' => $projectName,
                'project_id' => $projectId,
                'module_id' => $moduleId
            ];

            $settlementTemp = $tempData;
            $settlementTemp['settlement'] = '';

            $estimateTemp = $tempData;
            $estimateTemp['estimate'] = '';

            $planTemp = $tempData;

            foreach ($workingHouseList as $value) {
                if ($value['variable_id'] == $settlementTimeConsumingId) {
                    //结算工时
                    $settlementTemp['settlement'] = (string)$value['value'];
                } elseif ($value['variable_id'] == $estimateWorkingHoursId) {
                    //预估工时
                    $estimateTemp['estimate'] = (string)$value['value'];
                }
            }

            $estimateData[] = $estimateTemp;

            if($baseInfo['status_id'] == $endByStatus) { //已结算的任务才显示结算工时
                $settlementData[] = $settlementTemp;
            }

            // 计划工时
            $result = $this->handleTime($v['start_time'], $v['end_time'],$param);
            $planTime = ($result['end_time'] - $result['start_time']) / 60;
            $planTemp['plan'] = (string)$planTime;
            $planData[] = $planTemp;
        }

        //获取实际工时
        $actualList = $this->getTaskTimelogData([
            'module_id' => $moduleId,
            'user_id' => $userId,
            'today_start_time' => $param['start_date_time'], //开始时间
            'today_end_time' => $param['end_date_time'] //截止时间
        ]);

        foreach ($actualList as $v) {
            $projectName = '';
            $projectId = 0;

            //查询任务信息
            $baseInfo = $base->findData([
                "filter" => ["id" => $v["link_id"]],
                "fields" => "name,project_id"
            ]);

            if (empty($baseInfo)) {
                continue;
            }

            //查询项目信息
            if (!empty($baseInfo["project_id"])) {
                $projectInfo = $project->findData([
                    "filter" => ["id" => $baseInfo["project_id"]],
                    "fields" => "id,name"
                ]);
                $projectName = $projectInfo['name'];
                $projectId = $projectInfo['id'];
            }

            $name = $baseInfo['name'];
            $result = $this->handleTime($v['start_time'], $v['end_time'],$param);

            if (empty($result['end_time'])) {
                //如果任务还未结束
                $actual = ($nowTime - $result['start_time']) / 60;
            } else {
                $actual = ($result['end_time'] - $result['start_time']) / 60;
            }

            $actualData[] = [
                'id' => $v["link_id"],
                'name' => $name,
                'project_name' => $projectName,
                'actual' => (string)$actual,
                'project_id' => $projectId,
                'module_id' => $moduleId
            ];
        }

        // 返回数据
        $list = [
            'plan_data' => $planData,
            'actual_data' => $actualData,
            'estimate_data' => $estimateData,
            'settlement_data' => $settlementData
        ];

        return $list;
    }

    /**
     * 时间处理
     * @param $startTime
     * @param $endTime
     * @return array
     */
    public function handleTime($startTime, $endTime,$param)
    {
        $todayStartTime = $param['start_date_time']; //今日开始时间
        //$todayEndTime = strtotime(date('Y-m-d 23:59:59')); //今日截止时间
        $todayEndTime = strtotime('+1 day', $todayStartTime); //今日截止时间

        $timeArr = [];
        if ($startTime >= $todayStartTime && $startTime < $todayEndTime) { //开始时间在当天
            $timeArr['start_time'] = $startTime;
        } elseif ($startTime < $todayStartTime) { //开始时间小于当天
            $timeArr['start_time'] = $todayStartTime;
        } else { //此处理论上不可能有
            $timeArr['start_time'] = $todayEndTime;
        }

        if (empty($endTime)) {
            $timeArr['end_time'] = 0;
        } else {
            if ($endTime >= $todayStartTime && $endTime < $todayEndTime) { //结束时间在当天
                $timeArr['end_time'] = $endTime;
            } elseif ($endTime >= $todayEndTime) { //结束时间大于当天
                $timeArr['end_time'] = $todayEndTime;
            } else { //此处理论上不可能有
                $timeArr['end_time'] = $todayStartTime;
            }
        }

        return $timeArr;
    }


    /**
     * 获取用户超时工时
     * @param $param
     * @return array
     */
    public function getUserOvertimeData($param)
    {
        //获取用户列表
        $userList = $this->getUserList($param);

        $reportData = [];
        foreach ($userList as $v) {
            $overtimeWorkingMinute = $this->getUserOvertimeByUid($v['id'],$param);
            $reportData[] = [
                'user_id' => $v['id'],
                'user_name' => $v['name'],
                'overtime_working_hours' => $overtimeWorkingMinute
            ];
        }
        return $reportData;
    }

    /**
     * 根据用户id获取用户超时工时
     * @param $userId
     * @param $param
     * @return float|int
     */
    public function getUserOvertimeByUid($userId,$param)
    {
        $moduleIds = C('MODULE_ID');
        $moduleId = $moduleIds['base']; //任务模块id
        $overTime = 0;
        $nowtime = time(); //当前时间
        $base = new BaseService();
        $confirmHistory = new ConfirmHistoryModel();
        $overTimeNum = 0; //超时数
        //时间范围内任务列表
        $baseList = $base->getOverTimeTaskListbyUserid($userId,$param['start_date_time'],$param['end_date_time']);
        if(is_array($baseList)) {
            $totalBase = count($baseList); //总任务数
            foreach ($baseList as $v) {
                //获取结算时间
                $info = $confirmHistory->where([
                    "link_id" => $v["id"],
                    'operation' => 'confirm',
                    'module_id' => $moduleId
                ])->field('created')->find();
                //获取结算时间 如果没有，则拿当前时间
                $confirmTime = empty($info['created']) ? $nowtime : $info['created'];
                if ($confirmTime > $v['end_time']) { //超时
                    $overTimeNum++;
                    $overTimeMinute = ($confirmTime - $v['end_time']) / 60; //超时分钟
                    $overTime = bcadd($overTime, $overTimeMinute, 2);
                }
            }
            if (isset($param['showOverTimeRate']) && $param['showOverTimeRate'] == 1) { //返回超时率
                $rate = ($totalBase == 0) ? 0 : round($overTimeNum / $totalBase, 2);
                return $rate;
            }
        }
        return $overTime;
    }

    /**
     * 获取用户负荷率
     * @param $param
     * @return array
     */
    public function getUserLoadRateData($param)
    {
        //获取用户列表
        $userList = $this->getUserList($param);
        $dingTalk = new DingTalkService();
        $reportData = [];
        foreach ($userList as $v) {
            //获取用户结算工时
            $settlementHours = $this->getUserSettlementByUid($v['id'],$param);
            //获取用户日历工时
            $calendarHours = $dingTalk->getUserCalendarHoursByTime($v['id'],$param['start_date_time'],$param['end_date_time']);

            //负荷率
            $loadRate = empty($calendarHours) ? 0 : round($settlementHours/$calendarHours,2);
            $reportData[] = [
                'user_id' => $v['id'],
                'user_name' => $v['name'],
                'loadRate' => $loadRate
            ];
        }
        return $reportData;
    }

    /**
     * 获取用户时间范围内结算工时
     * @param $userId
     * @param $param
     * @return int
     */
    public function getUserSettlementByUid($userId,$param){
        $base = new BaseService();
        $variableValue = new VariableValueModel();
        $moduleIds = C('MODULE_ID');
        $moduleId = $moduleIds['base']; //任务模块id
        // 获取结算工时自定义字段id
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        $settlementTimeConsumingId = $formulaConfigData['settlement_time_consuming'];
        $end_by_status = $formulaConfigData['end_by_status'];
        //时间范围内任务列表
        $baseList = $base->getOverTimeTaskListbyUserid($userId,$param['start_date_time'],$param['end_date_time'],$end_by_status);
        if(empty($baseList)){
            return 0;
        }
        //获取任务id集合
        $baseIds = array_column($baseList,'id');
        $settlementMinute = 0; //总结算分钟
        //结算工时列表
        $settlementValueList = $variableValue->field('value')->where([
            'module_id' => $moduleId,
            'variable_id' => $settlementTimeConsumingId,
            "link_id" => ["in", join(',',$baseIds )]
        ])->select();
        foreach($settlementValueList as $v){
            $settlementMinute += trans_duration($v['value']);
        }
        $settlementHours = round($settlementMinute/60,2);
        return $settlementHours;
    }


    /**
     * 跟据筛选条件获取X轴数据列表
     * @param $param
     * @return array
     */
    public function getDateColumnsData($param){
        $dateData = [];
        if(isset($param['dateType']) && $param['dateType'] == 1){ //如果筛选日期类型为年
           $yearDate = empty($param['yearDate']) ? date('Y') : $param['yearDate']; //不传年份默认为当前年
           for($i=1;$i<=12;$i++){
               $startTime = strtotime($yearDate.'-'.$i.'-1 00:00:00'); //月开始时间
               $endTime = strtotime(date('Y-m-t 23:59:59',$startTime)); //月截止时间
               $dateData[] = [
                   'dateName' => $i.'月',
                   'startTime' => $startTime,
                   'endTime' => $endTime
               ];
           }
        }else{ //如果筛选日期类型不为年
            $endDateTime = strtotime(date('Y-m-d 00:00:00',strtotime('+1 day', $param['end_date_time'])));
            $diffDay = ($endDateTime-$param['start_date_time'])/86400; //相差天数
            if($diffDay>0){
                for($i=0;$i<=$diffDay;$i++){
                    $startTime = strtotime(date('Y-m-d 00:00:00',strtotime('+'.$i.' day', $param['start_date_time']))); //日开始时间
                    $endTime = strtotime(date('Y-m-d 23:59:59',strtotime('+'.$i.' day', $param['start_date_time']))); //日截止时间
                    $day = date('j',$startTime);
                    $dateData[] = [
                        'dateName' => $day.'日',
                        'startTime' => $startTime,
                        'endTime' => $endTime
                    ];
                }
            }
        }
        return $dateData;
    }

    /**
     * 获取用户个人超时率
     * @param $param
     * @return array
     */
    public function getUserOvertimeRate($param){
        $list = [];
        //获取X轴数据
        $dateDataList = $this->getDateColumnsData($param);
        foreach($dateDataList as $value){
            $param['start_date_time'] = $value['startTime'];
            $param['end_date_time'] = $value['endTime'];
            $param['showOverTimeRate'] = 1;
            //获取用户超时率
            $overTimeRate = $this->getUserOvertimeByUid($param['user_id'],$param);
            $list[] = [
                'name' => $value['dateName'],
                'overTimeRate' => $overTimeRate
            ];
        }
        return $list;
    }

    /**
     * 获取用户个人负荷率
     * @param $param
     * @return array
     */
    public function getUserLoadRate($param){
        $list = [];
        //获取X轴数据
        $dateDataList = $this->getDateColumnsData($param);
        foreach($dateDataList as $value){
            $param['start_date_time'] = $value['startTime'];
            $param['end_date_time'] = $value['endTime'];
            //获取用户结算工时
            $settlementHours = $this->getUserSettlementByUid($param['user_id'],$param);
            //获取用户日历工时
            $calendarHours = $this->getUserCalendarHours($param['user_id'],$param);

            //负荷率
            $loadRate = empty($calendarHours) ? 0 : round($settlementHours/$calendarHours,2);
            $list[] = [
                'name' => $value['dateName'],
                'loadRate' => $loadRate
            ];
        }
        return $list;
    }

    /**
     * 任务导出
     * @param $param
     * @return array
     */
    public function getBaseExportData($param){
        $moduleIds = C('MODULE_ID');
        $moduleId = $moduleIds['base']; //任务模块id
        $horizontalModel = new HorizontalModel();
        $base = new BaseModel();
        $variableValue = new VariableValueModel();
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        $assigneeField = $formulaConfigData['assignee_field']; //执行人
        $reviewedBy = $formulaConfigData['reviewed_by']; //分派人
        $estimateWorkingHoursId = $formulaConfigData['estimate_working_hours']; //预估工时
        $actualTimeConsumingId = $formulaConfigData['actual_time_consuming']; //实际工时
        $page = empty($param['page']) ? 1 : $param['page'];
        $limit = empty($param['pagesize']) ? 3000 : $param['pagesize'];
        $offset = ($page - 1) * $limit;

        if(empty($param['start']) || empty($param['end'])){ //默认获取上个月的
            $startTime = strtotime(date('Y-m-01 00:00:00', strtotime('-1 month')));
            $endTime = strtotime(date('Y-m-t 23:59:59', strtotime('-1 month')));
        }else{
            $startTime = strtotime($param['start'].' 00:00:00');
            $endTime = strtotime($param['end'].' 23:59:59');
        }
        $filter['base.end_time'] = ["BETWEEN", [$startTime, $endTime]];
        $baseListData = $base
            ->alias("base")
            ->where($filter)
            ->join('LEFT JOIN strack_status status ON base.status_id = status.id')
            ->join('LEFT JOIN strack_project project ON base.project_id = project.id')
            ->field("
                base.id,
                base.name,
                status.name as status_name,
                base.project_id,
                base.end_time,
                project.name as project_name
            ")
            ->order('base.id')
            ->limit($offset, $limit)
            ->select();
        $title = ['项目名', '任务名', '分配人','执行人','结束时间','预估工时','实际工时','任务状态'];
        $data = [];
        foreach($baseListData as $item){
            $endDate = date('Y-m-d H:i:s',$item['end_time']);
            //获取预估工时跟实际工时
            $workingHouseList = $variableValue->field('variable_id,value')->where([
                'module_id' => $moduleId,
                'link_id' => $item["id"],
                "variable_id" => ["in", join(',', [$estimateWorkingHoursId, $actualTimeConsumingId])]
            ])->select();
            $estimate = $actual = '';
            foreach ($workingHouseList as $value) {
                if ($value['variable_id'] == $actualTimeConsumingId) {
                    //实际工时
                    $actual = (string)$value['value'];
                } elseif ($value['variable_id'] == $estimateWorkingHoursId) {
                    //预估工时
                    $estimate = (string)$value['value'];
                }
            }
            $estimate = duration_format_show($estimate);
            $actual = duration_format_show($actual);
            //获取分配人跟执行人
            $fenpairen = $zhixingren = '';
            $horizontalList = $horizontalModel->alias("horizontal")
                ->join('LEFT JOIN strack_user user ON horizontal.dst_link_id = user.id')
                ->field('horizontal.variable_id,user.name')
                ->where([
                    'horizontal.src_module_id' => $moduleId,
                    'horizontal.variable_id' => ["in", join(',', [$reviewedBy, $assigneeField])],
                    'horizontal.src_link_id' => $item["id"],
                    'horizontal.dst_module_id' => $moduleIds['user']
                ])->select();
            foreach ($horizontalList as $value) {
                if ($value['variable_id'] == $reviewedBy) {
                    //分派人
                    $fenpairen = $value['name'];
                } elseif ($value['variable_id'] == $assigneeField) {
                    //执行人
                    $zhixingren = $value['name'];
                }
            }
            $data[] = [$item['project_name'],$item['name'],$fenpairen,$zhixingren,$endDate,$estimate,$actual,$item['status_name']];
        }
        return ['title'=>$title,'data'=>$data];
    }
}

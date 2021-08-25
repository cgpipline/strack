<?php
// +----------------------------------------------------------------------
// | 日历相关 服务
// +----------------------------------------------------------------------
// | 主要服务于日历 TimeLog 数据处理
// +----------------------------------------------------------------------
// | 错误编码头 228xxx
// +----------------------------------------------------------------------
namespace Common\Service;

use Common\Model\BaseModel;
use Common\Model\PlanLockModel;
use Common\Model\PlanModel;
use Common\Model\TimelogIssueModel;
use Common\Model\CalendarModel;

class ScheduleService
{
    /**
     * 新增时间日志
     * @param $param
     * @return array
     */
    public function addTimelogIssue($param)
    {
        $timelogIssueModel = new TimelogIssueModel();
        $resData = $timelogIssueModel->addItem($param);
        if (!$resData) {
            // 添加时间日志失败错误码 001
            throw_strack_exception($timelogIssueModel->getError(), 228001);
        } else {
            // 返回成功数据
            return success_response($timelogIssueModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 修改时间日志
     * @param $param
     * @return array
     */
    public function modifyTimelogIssue($param)
    {
        $timelogIssueModel = new TimelogIssueModel();
        $resData = $timelogIssueModel->modifyItem($param);
        if (!$resData) {
            // 修改时间日志失败错误码 002
            throw_strack_exception($timelogIssueModel->getError(), 228002);
        } else {
            // 返回成功数据
            return success_response($timelogIssueModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 删除时间日志
     * @param $param
     * @return array
     */
    public function deleteTimelogIssue($param)
    {
        $timelogIssueModel = new TimelogIssueModel();
        $resData = $timelogIssueModel->deleteItem($param);
        if (!$resData) {
            // 删除时间日志失败错误码 003
            throw_strack_exception($timelogIssueModel->getError(), 228003);
        } else {
            // 返回成功数据
            return success_response($timelogIssueModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 时间日志列表数据
     * @param $param
     * @return mixed
     */
    public function getTimelogGridData($param)
    {
        $options = [
            'page' => [$param["page"], $param["rows"]]
        ];
        $timelogIssueModel = new TimelogIssueModel();
        $timelogIssueData = $timelogIssueModel->selectData($options);
        return $timelogIssueData;
    }


    /**
     * 新增日历事项
     * @param $param
     * @return array
     */
    public function addCalendar($param)
    {
        $calendarModel = new CalendarModel();
        $resData = $calendarModel->addItem($param);
        if (!$resData) {
            // 添加日历事项失败错误码 004
            throw_strack_exception($calendarModel->getError(), 228004);
        } else {
            // 返回成功数据
            return success_response($calendarModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 修改日历事项
     * @param $param
     * @return array
     */
    public function modifyCalendar($param)
    {
        $calendarModel = new CalendarModel();
        $resData = $calendarModel->modifyItem($param);
        if (!$resData) {
            // 修改日历事项失败错误码 005
            throw_strack_exception($calendarModel->getError(), 228005);
        } else {
            // 返回成功数据
            return success_response($calendarModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 删除日历事项
     * @param $param
     * @return array
     */
    public function deleteCalendar($param)
    {
        $calendarModel = new CalendarModel();
        $resData = $calendarModel->deleteItem($param);
        if (!$resData) {
            // 删除日历事项失败错误码 006
            throw_strack_exception($calendarModel->getError(), 228006);
        } else {
            // 返回成功数据
            return success_response($calendarModel->getSuccessMassege(), $resData);
        }
    }


    /**
     * 日历事项列表数据
     * @param $param
     * @return mixed
     */
    public function getCalendarGridData($param)
    {
        $options = [
            'page' => [$param["page"], $param["rows"]]
        ];
        $calendarModel = new CalendarModel();
        $calendarData = $calendarModel->selectData($options);
        return $calendarData;
    }

    /**
     * 锁定任务计划通过过滤条件
     * @param $filter
     */
    public function lockTaskPlanByFilter($filter)
    {
        $planModel = new PlanModel();
        $planModel->where($filter)->setField('lock', 'yes');

        // 给当前计划所属人员发送通知
    }

    /**
     * 判断任务是否为审核状态
     * @param $taskId
     * @return int|mixed
     */
    protected function getTaskPlanUserId($taskId)
    {
        $taskData = (new BaseModel())->where(['id' => $taskId])->find();
        $baseService = new BaseService();
        $moduleIds = C('MODULE_ID');

        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        if (!empty($formulaConfigData)) {
            $reviewedByStatus = (int)$formulaConfigData['reviewed_by_status'];
            $assigneeField = (int)$formulaConfigData['assignee_field']; // 执行人
            $reviewedBy = (int)$formulaConfigData['reviewed_by']; // 分派人

            $assigneeFieldUserId = $baseService->getTaskHorizontalUserId($taskData['id'], $assigneeField);
            $reviewedByUserId = $baseService->getTaskHorizontalUserId($taskData['id'], $reviewedBy);

            /**
             * 如果任务为审核状态
             * 1、当前用户为分派人，则创建成分派人的计划
             * 2、其他人，则创建成执行人的计划
             */
            if ($taskData['status_id'] === $reviewedByStatus) {
                if (session('user_id') === (int)$reviewedByUserId) {
                    return $reviewedByUserId;
                } else {
                    // 1、判断是否有分派人创建了审核计划，有则全部锁定并发送提示消息
                    $this->lockTaskPlanByFilter([
                        'module_id' => $moduleIds['base'],
                        'link_id' => $taskData['id'],
                        'user_id' => $reviewedByUserId
                    ]);

                    // 2、当前审核中自动拒绝结算任务
                    $commonService = new CommonService();
                    $commonService->updateBaseConfirmationData([
                        'link_id' => $taskData['id'],
                        'module_id' => $moduleIds['base'],
                        'type' => "reject"
                    ]);
                }
            }

            return $assigneeFieldUserId;
        }

        return 0;
    }

    /**
     * 添加任务计划
     * @param $param
     * @return array
     */
    public function addTaskPlan($param)
    {
        $planModel = new PlanModel();

        if ($param['operation'] === 'add') {
            // 日程新增默认增加10分钟
            $estimateAppend = 600;

            if (!empty($param['estimate_append'])) {
                if ($param['estimate_append'] > 120) {
                    $estimateAppend = 7200;
                } else {
                    $estimateAppend = (int)$param['estimate_append'] * 60;
                }
            }

            $param['end_time'] = get_format_date(strtotime($param['end_time']) + $estimateAppend, 1);
        }

        if (in_array($param['operation'], ['add', 'copy'])
            && strtotime($param['end_time']) <= strtotime($param['start_time'])
        ) {
            throw_strack_exception(L('Planned_EndTime_Less_Than_StartTime'), 228012);
        }

        // 获取指定任务的执行人ID
        $param['user_id'] = $this->getTaskPlanUserId($param['link_id']);
        $resData = $planModel->addItem($param);
        if (!$resData) {
            // 添加任务计划失败错误码 007
            throw_strack_exception($planModel->getError(), 228007);
        } else {
            // 返回成功数据
            return success_response($planModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 修改任务计划
     * @param $param
     * @return array
     */
    public function modifyTaskPlan($param)
    {
        $planModel = new PlanModel();
        $resData = $planModel->modifyItem($param);
        if (!$resData) {
            // 修改任务计划失败错误码
            throw_strack_exception($planModel->getError(), 228011);
        } else {
            // 返回成功数据
            return success_response($planModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 删除任务计划
     * @param $param
     * @return array
     */
    public function deleteTaskPlan($param)
    {
        $planModel = new PlanModel();
        $resData = $planModel->deleteItem($param);
        if (!$resData) {
            // 删除任务计划失败错误码 010
            throw_strack_exception($planModel->getError(), 228010);
        } else {
            // 返回成功数据
            return success_response($planModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 锁定任务计划
     * @param $date
     * @return array
     */
    public function lockTaskPlan($date)
    {
        $planLockModel = new PlanLockModel();
        $resData = $planLockModel->addItem([
            'date' => $date
        ]);
        if (!$resData) {
            // 添加任务计划锁定失败错误码 008
            throw_strack_exception($planLockModel->getError(), 228008);
        } else {
            // 返回成功数据
            $this->resetPlanStatus($date, 'lock');
            return success_response($planLockModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 解锁任务计划
     * @param $date
     * @return array
     */
    public function unLockTaskPlan($date)
    {
        $planLockModel = new PlanLockModel();
        $resData = $planLockModel->deleteItem([
            'date' => strtotime($date)
        ]);
        if (!$resData) {
            // 添加任务计划锁定失败错误码 009
            throw_strack_exception($planLockModel->getError(), 228009);
        } else {
            // 返回成功数据
            $this->resetPlanStatus($date, 'unlock');
            return success_response($planLockModel->getSuccessMassege(), $resData);
        }
    }


    /**
     * 重置指定范围的任务计划状态
     * @param $date
     * @param $type
     */
    public function resetPlanStatus($date, $type)
    {
        // 只要是开始时间或者结束时间在这个范围的都受影响
        $planModel = new PlanModel();
        $startTime = strtotime($date . ' 00:00:00');
        $endTime = strtotime($date . ' 23:59:59');
        $filter = [
            'start_time' => ['BETWEEN', [$startTime, $endTime]],
            'end_time' => ['BETWEEN', [$startTime, $endTime]],
            '_logic' => 'OR'
        ];
        if ($type === 'lock') {
            $planModel->where($filter)->setField('lock', 'yes');
        } else {
            $planModel->where($filter)->setField('lock', 'no');
        }
    }

    /**
     * 获取当前时间范围内已经锁定日期的列表
     * @param $start
     * @param $end
     * @return array
     */
    public function getDateRangeLockDateList($start, $end)
    {
        $planLockModel = new PlanLockModel();
        $dateData = $planLockModel->field('date')
            ->where([
                'date' => ['BETWEEN', [$start, $end]]
            ])
            ->select();

        $dateList = [];
        foreach ($dateData as $dateItem) {
            $dateList[] = get_format_date($dateItem['date'], 0);
        }

        return $dateList;
    }
}

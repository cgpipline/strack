<?php

namespace Home\Controller;

use Common\Controller\VerifyController;
use Common\Service\ScheduleService;

class ScheduleController extends VerifyController
{
    /**
     * 显示我的日程页面
     */
    public function index()
    {
        // 生成页面唯一信息
        $this->generatePageIdentityID('schedule');
        return $this->display();
    }

    /**
     * 添加任务计划
     * @return \Think\Response
     */
    public function addTaskPlan()
    {
        $param = $this->request->param();
        $param['module_id'] = C('MODULE_ID')['base'];
        $scheduleService = new ScheduleService();
        $resData = $scheduleService->addTaskPlan($param);
        return json($resData);
    }

    /**
     * 锁定任务计划
     * @return \Think\Response
     */
    public function lockTaskPlan()
    {
        $param = $this->request->param();
        $scheduleService = new ScheduleService();
        if ($param['type'] === 'lock') {
            // 锁定任务计划
            $resData = $scheduleService->lockTaskPlan($param['date']);
        } else {
            // 解锁任务计划
            $resData = $scheduleService->unLockTaskPlan($param['date']);
        }

        return json($resData);
    }

    /**
     * 删除任务计划
     * @return \Think\Response
     */
    public function deleteTaskPlan()
    {
        $param = $this->request->param();
        $scheduleService = new ScheduleService();
        $param = [
            'id' => ['IN', $param["primary_ids"]]
        ];
        $resData = $scheduleService->deleteTaskPlan($param);
        return json($resData);
    }
}

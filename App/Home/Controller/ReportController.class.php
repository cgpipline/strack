<?php

namespace Home\Controller;

use Common\Controller\VerifyController;
use Common\Service\DingTalkService;
use Common\Service\ReportService;
use Common\Service\UserService;

class ReportController extends VerifyController
{
    /**
     * 显示团队看板页面
     */
    public function index()
    {
        // 生成页面唯一信息
        $this->generatePageIdentityID('report');
        return $this->display();
    }

    /**
     * 获取队友计划数据
     * @return \Think\Response
     */
    public function getUserPlannedData()
    {
        $param = $this->request->param();
        $type = empty($param['type']) ? 1 : $param['type'];

        //筛选日期范围
        if(!empty($param['start_date']) && !empty($param['end_date'])){
            $param['start_date_time'] = strtotime($param['start_date'].' 00:00:00');
            $param['end_date_time'] = strtotime($param['end_date'].' 23:59:59');
        }else{
            $param['start_date_time'] = strtotime(date('Y-m-d 00:00:00'));
            $param['end_date_time'] = strtotime(date('Y-m-d 23:59:59'));
        }

        if(!empty($param['date'])){
            $param['start_date_time'] = strtotime($param['date'].' 00:00:00');
            $param['end_date_time'] = strtotime($param['date'].' 23:59:59');
        }

        $report = new ReportService();
        if($type == 1) { //获取队友计划数据(小时维度）
            $resData = $report->getTeammatePlanningHouseData($param);
        }elseif($type == 3){  //获取权限范围内的用户列表
            $userService = new UserService();
            $resData = $userService->getDepartmentUserTreeList(session('user_id'));
        }elseif($type == 2){ //获取队友计划数据(日期维度）
            $resData = $report->getTeammatePlanningData($param);
        }elseif($type == 4){ //获取团队人员超时时间及员工负荷率
            $overtimeData = $report->getUserOvertimeData($param); //超时时间
            $loadRateData = $report->getUserLoadRateData($param); //员工负荷率

            $resData = ['overtimeData'=>$overtimeData,'loadRateData'=>$loadRateData];
        }elseif($type == 5){ //获取个人超时率及负荷率
            if(empty($param['user_id'])){
                return json(success_response('用户id不能为空',[],404));
            }
            $overtimeRate = $report->getUserOvertimeRate($param); //个人超时率
            $loadRate = $report->getUserLoadRate($param); //个人负荷率
            $resData = ['overtimeRate'=>$overtimeRate,'loadRate'=>$loadRate];
        }
        return json(success_response('获取成功',$resData));
    }
}

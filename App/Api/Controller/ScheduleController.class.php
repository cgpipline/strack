<?php

namespace Api\Controller;


use Common\Model\ModuleModel;
use Common\Service\CommonService;
use Common\Service\ProjectService;
use Common\Service\ScheduleService;
use Common\Service\UserService;

class ScheduleController extends BaseController
{
    /**
     * 获取我的日程数据
     * @return \Think\Response
     */
    public function getMyScheduleData()
    {
        $param = $this->request->param();

        // 切换马甲
        if (!empty($param['filter']['department_members'])) {
            $param['user_id'] = $param['filter']['department_members'];
        } else {
            $param['user_id'] = session("user_id");
        }

        $commonService = new CommonService();

        // 获取页面权限
        $pageRules = $this->authService->getPageAuthRules('home_schedule_index', '');

        $baseModuleId = C('MODULE_ID')['base'];
        $moduleModel = new ModuleModel();
        $baseModuleData = $moduleModel->where(['id' => $baseModuleId])->find();

        $sideRules = [
            'category' => "top_field",
            'grid_page_id' => "",
            'module_code' => $baseModuleData['code'],
            'module_id' => $baseModuleData['id'],
            'module_name' => $baseModuleData['name'],
            'module_type' => $baseModuleData['type'],
            'page' => "my_schedule",
            'position' => "my_schedule",
            'schema_page' => "project_base",
            'rule_side_thumb_clear' => $pageRules['side_bar__top_panel__clear_thumb'],
            'rule_side_thumb_modify' => $pageRules['side_bar__top_panel__modify_thumb'],
            'rule_tab_base' => $pageRules['side_bar__tab_bar__base'],
            'rule_tab_cloud_disk' => $pageRules['side_bar__tab_bar__cloud_disk'],
            'rule_tab_correlation_task' => $pageRules['side_bar__tab_bar__correlation_task'],
            'rule_tab_file' => $pageRules['side_bar__tab_bar__file'],
            'rule_tab_file_commit' => $pageRules['side_bar__tab_bar__commit'],
            'rule_tab_history' => $pageRules['side_bar__tab_bar__history'],
            'rule_tab_horizontal_relationship' => $pageRules['side_bar__tab_bar__horizontal_relationship'],
            'rule_tab_info' => $pageRules['side_bar__tab_bar__info'],
            'rule_tab_notes' => $pageRules['side_bar__tab_bar__note'],
            'rule_tab_onset' => $pageRules['side_bar__tab_bar__onset'],
            'rule_template_fixed_tab' => $pageRules['side_bar__tab_bar__template_fixed_tab']
        ];

        $resData = $commonService->getMyScheduleData($param, $sideRules);

        $formatList = [
            'events_list' => $resData['events_list'],
            'task_remainder_time' => $resData['task_remainder_time'],
            'task_remainder_end_time' => $resData['task_remainder_end_time']
        ];

        return json(success_response('', $formatList));
    }


    /**
     * 获取日历过滤配置
     */
    public function getCalendarFilterConfig()
    {
        // 项目列表
        $projectService = new ProjectService();
        $projectList = $projectService->getProjectListOfMy(session('user_id'));

        // 获取用户列表
        $userList = $projectService->getProjectMemberCombobox(0, true);

        // 获取所有项目任务状态交集
        $statusList = $projectService->getProjectAllTaskStatusCombobox();

        // 获取当前用户所管理的团队成员列表
        $userService = new UserService();
        $departmentMembers = $userService->getMyChargeDepartmentMembers(session('user_id'));

        $resData = [
            'department_members' => $departmentMembers,
            'project_list' => $projectList,
            'user_list' => $userList,
            'status_list' => $statusList
        ];

        return json(success_response('', $resData));
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
     * 修改任务计划
     * @return \Think\Response
     */
    public function modifyTaskPlan()
    {
        $param = $this->request->param();
        $scheduleService = new ScheduleService();
        $resData = $scheduleService->modifyTaskPlan($param);
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
}

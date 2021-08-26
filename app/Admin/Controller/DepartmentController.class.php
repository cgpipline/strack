<?php

namespace Admin\Controller;

use Common\Service\ProjectService;
use Common\Service\SchemaService;
use Common\Service\UserService;

// +----------------------------------------------------------------------
// | 人员部门设置数据控制层
// +----------------------------------------------------------------------

class DepartmentController extends AdminController
{
    /**
     * 显示页面
     */
    public function index()
    {
        $schemaService = new SchemaService();
        $moduleId = 8;
        $moduleData = $schemaService->getModuleFindData(["id" => $moduleId]);

        // 把数据发送到前端页面
        $param = [
            "page" => 'admin_' . $moduleData["code"],
            "module_id" => $moduleId,
            "module_code" => $moduleData["code"],
            "module_name" => $moduleData["name"],
            "module_icon" => $moduleData["icon"]
        ];

        $this->assign($param);

        return $this->display();
    }

    /**
     * 新增部门
     */
    public function addDepartment()
    {
        $param = $this->request->param();
        $userService = new UserService();
        $departmentData = [
            'code' => $param['code'],
            'name' => $param['name'],
            'parent_id' => $param['parent_id']
        ];
        $resData = $userService->addDepartment($departmentData);

        if ($resData['status'] === 200) {
            // 写入部门负责人
            if(!empty($param['user_ids'])){
                $userService->addDepartmentManager($resData['data']['id'], $param['user_ids']);
            }
        }

        return json($resData);
    }

    /**
     * 修改部门
     */
    public function modifyDepartment()
    {
        $param = $this->request->param();

        $departmentData = [
            'id' => $param['id'],
            'code' => $param['code'],
            'name' => $param['name'],
            'parent_id' => $param['parent_id']
        ];

        try {
            $userService = new UserService();
            $resData = $userService->modifyDepartment($departmentData);
        } catch (\Exception $e) {
            $resData = success_response($e->getMessage());
        }

        // 更新部门负责人数据
        $mgResData = $userService->updateDepartmentManager($param['id'], $param['user_ids']);

        if ($mgResData['status'] === 200) {
            return json($mgResData);
        }

        return json($resData);
    }

    /**
     * 删除部门
     */
    public function deleteDepartment()
    {
        $param = $this->request->param();
        $deleteParam = [
            'id' => ['IN', $param["primary_ids"]]
        ];
        $userService = new UserService();
        $resData = $userService->deleteDepartment($deleteParam);

        // 删除部门负责人
        if ($resData['status'] === 200) {
            $userService->deleteDepartmentManager($param["primary_ids"]);
        }

        return json($resData);
    }

    /**
     * 获取所有部门
     */
    public function getDepartmentGridData()
    {
        $param = $this->request->param();
        $userService = new UserService();
        $resData = $userService->getDepartmentTreeGridData($param);
        return json($resData);
    }

    /**
     * 获取部门树列表
     */
    public function getDepartmentTreeList()
    {
        $userService = new UserService();
        $resData = $userService->getDepartmentTreeList();
        return json($resData);
    }

    /**
     * 获取用户列表
     * @return \Think\Response
     */
    public function getProjectMemberCombobox()
    {
        $projectService = new ProjectService();
        $resData = $projectService->getProjectMemberCombobox();
        return json($resData);
    }

    /**
     * 获取部门列表
     */
    public function getDepartmentList()
    {
        $userService = new UserService();
        $resData = $userService->getDepartmentList();
        return json($resData);
    }
}

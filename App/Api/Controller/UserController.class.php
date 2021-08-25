<?php

namespace Api\Controller;


use Common\Model\UserModel;
use Common\Service\UserService;
use Common\Service\BaseService;

class UserController extends BaseController
{

    /**
     * 删除用户方法
     * @return \think\Response
     */
    public function delete()
    {
        $userService = new UserService();
        $userModel = new UserModel();
        $userData = $userModel->field('id')->where($this->param['param']['filter'])->select();
        $userIds = array_column($userData, 'id');
        $param = [
            "user_id" => ['IN', join(',', $userIds)]
        ];
        $resData = $userService->deleteAccount($param);
        return json($resData);
    }

    /**
     * 获取用户中心项目及任务数据
     * @return \Think\Response
     */
    public function getProjectTasksData()
    {
        $param = $this->request->param();
        $base = new BaseService();
        if (empty($param['start']) || empty($param['end'])) {
            $param['start'] = strtotime(date('Y-m-01 00:00:00'));
            $param['end'] = strtotime(date('Y-m-t 23:59:59'));
        }
        $result = $base->getTimeFrameDatabyUserid(session('user_id'), $param['start'], $param['end']);
        return json(success_response('', $result));
    }

    /**
     * 获取用户积分信息
     * @return \Think\Response
     */
    public function getIntegralList()
    {
        $param = $this->request->param();
        $base = new BaseService();
        if (empty($param['start']) || empty($param['end'])) {
            $param['start'] = strtotime(date('Y-m-01 00:00:00'));
            $param['end'] = strtotime(date('Y-m-t 23:59:59'));
        }
        $result = $base->getTimeFrameIntegralList(session('user_id'), $param['start'], $param['end']);
        return json(success_response('', $result));
    }


}

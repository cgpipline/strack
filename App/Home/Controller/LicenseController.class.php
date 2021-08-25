<?php

namespace Home\Controller;

use Common\Controller\VerifyController;
use Common\Service\LicenseService;
use Common\Service\UserService;

class LicenseController extends VerifyController
{
    /**
     * 显示登录页面
     */
    public function index()
    {
        $this->assign("beian_number", '');

        // 获取当前 license 信息
        return $this->display();
    }

    /**
     * 获取License许可文件数据
     */
    public function getLicenseRequestData()
    {
        $licenseService = new LicenseService();
        $requestData = $licenseService->getBaseLicenseData();
        if(!$requestData){
            return json([]);
        }
        return json($requestData);
    }

    /**
     * 获取License许可文件
     */
    public function getRequestFile()
    {
        // 获取许可文件
        $licenseService = new LicenseService();
        $requestFile = $licenseService->generatingRequestFile();
        return download($requestFile["file_path"], $requestFile["file_name"]);
    }

    /**
     * 验证License许可文件
     * @return \Think\Response
     */
    public function validationLicense()
    {
        $param = $this->request->param();
        $licenseService = new LicenseService();
        $resData = $licenseService->validationLicense($param);
        return json($resData);
    }

    /**
     * 获取激活的用户列表
     * @return \Think\Response
     */
    public function getActiveUserGridData()
    {
        $param = $this->request->param();
        $userService = new UserService();
        $resData = $userService->getActiveUserGridData($param);
        return json($resData);
    }

    /**
     * 注销用户
     * @return \Think\Response
     */
    public function cancelAccount()
    {
        $param = $this->request->param();
        $upData = [
            "id" => $param["user_id"],
            "status" => "departing"
        ];
        $userService = new UserService();
        $resData = $userService->cancelAccount($upData);
        return json($resData);
    }
}
<?php

namespace Admin\Controller;

use Common\Service\LicenseService;
use Common\Service\MediaService;
use Common\Service\OptionsService;

// +----------------------------------------------------------------------
// | 关于数据控制层
// +----------------------------------------------------------------------

class AboutController extends AdminController
{
    protected static $test1;

    protected  $test2;

    /**
     * 显示页面
     */
    public function index()
    {
        return $this->display();
    }

    public static function test()
    {

    }

    /**
     * 获得系统参数
     * @return \Think\Response
     */
    public function getSystemAbout()
    {
        // 获取日志服务器状态
        $optionsService = new OptionsService();
        $logServerStatus = $optionsService->getLogServerStatus();

        // 获取媒体服务器状态（存在多台）
        $mediaService = new MediaService();
        $mediaServerStatusList = $mediaService->getMediaServerStatus();

        $serverList = [];
        if (!empty($mediaServerStatusList)) {
            $serverList = $mediaServerStatusList;
        }

        if (!empty($logServerStatus)) {
            array_unshift($serverList, $logServerStatus);
        }

        // 获取当前版本号
        $strackVersionConfig = $optionsService->getOptionsData('system_version');

        // 获取 License 信息
        $licenseService = new LicenseService();
        $getLicenseData = $licenseService->getBaseLicenseData();

        if (!$getLicenseData) {
            $licenseData = [
                "info" => L("License_Null"),
                "request" => '',
                "notice" => '',
            ];
        } else {
            if ($getLicenseData["user_number"] >= 999999) {
                // 不限制使用人数
                $licenseInfo = L("License_Allow_Infinite");
            } else {
                $licenseInfo = L("License_Allow_Numbers_Before") . $getLicenseData["user_number"] . L("License_Allow_Numbers_After");
            }
            $licenseNotice = L("Expires") . " " . $getLicenseData["expiry_date"] . L("License_Notice");
            $licenseData = [
                "info" => $licenseInfo,
                "request" => L("License_Active"),
                "notice" => $licenseNotice,
            ];
        }

        // 获取 License 请求码
        $licenseRequest = $licenseService->generatingRequestCode();

        $resData = [
            'strack_version' => $strackVersionConfig["version"],
            'package_version' => C("STRACK_VERSION"),
            'server_list' => $serverList,
            'license_status' => $licenseData,
            'license_request' => $licenseRequest,
        ];

        return json($resData);
    }

    /**
     * 许可证请求
     * @return array
     */
    public function getLicenseRequest()
    {
        $licenseService = new LicenseService();
        $licenseRequest = $licenseService->generatingRequestCode();
        return json(success_response('', $licenseRequest));
    }

    /**
     * 更新系统许可
     */
    public function updateLicense()
    {
        $param = $this->request->param();
        $licenseService = new LicenseService();
        $resData = $licenseService->updateLicense($param["license"]);
        return json($resData);
    }
}

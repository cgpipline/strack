<?php

namespace Test\Controller;

use Think\Controller;

use Common\Service\LicenseService;

class LicenseController extends Controller
{

    /**
     * 生成许可码
     */
    public function generatingRequestCode()
    {
        $licenseService = new LicenseService();
        $requestCode = $licenseService->generatingRequestCode();
        dump($requestCode);
    }

    /**
     * 验证License
     */
    public function checkLicence(){
        $licenseService = new LicenseService();
        $resData = $licenseService->checkLicence();
        dump($resData);
    }

    /**
     * 生成许可码文件
     */
    public function generatingRequestFile(){
        $licenseService = new LicenseService();
        $resData = $licenseService->generatingRequestFile();
    }
}
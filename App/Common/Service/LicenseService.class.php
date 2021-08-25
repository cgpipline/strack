<?php
// +----------------------------------------------------------------------
// | 许可验证服务层
// +----------------------------------------------------------------------
// | 主要服务于许可验证处理
// +----------------------------------------------------------------------
// | 错误编码头 208xxx
// +----------------------------------------------------------------------
namespace Common\Service;

use Org\Util\Client;
use Common\Model\UserModel;
use Common\Model\SchemaModel;

class LicenseService
{

    // 许可文件信息
    protected $licenseString = '';

    // 许可文件解包后信息
    protected $licenseData = [];

    // 使用人数
    protected $licenseNumber;

    // 公司名称
    protected $company = '';

    // 添加用户
    protected $isUpdateUserMode = false;

    /**
     * License 对象
     * @var Client
     */
    protected $license;

    // 错误信息
    protected $errorMessage = "";

    // 错误码
    protected $errorCode = "";


    public function __construct()
    {
        $this->company = C("COMPANY_NAME");
        $this->license = new Client();
    }

    public function setUpdateUserModeActive()
    {
        $this->isUpdateUserMode = true;
    }

    /**
     * 生成许可请求码
     * @return mixed
     */
    public function generatingRequestCode()
    {
        $moduleData = [
            'fixed' => ['core', 'review'],
            'entity' => []
        ];

        // 获取Module数据
        $schemaModel = new SchemaModel();
        $schemaData = $schemaModel->selectData(["filter" => ["type" => "project"], "fields" => "code"]);
        foreach ($schemaData["rows"] as $item) {
            array_push($moduleData["entity"], $item["code"]);
        }
        // 返回许可码
        return $this->license->generateActivationKey($moduleData);
    }

    /**
     * 生成许可请求码txt文件
     * @return array
     */
    public function generatingRequestFile()
    {
        $content = $this->generatingRequestCode();
        $fileName = 'request_' . date("Ymd", time());
        $directory = ROOT_PATH . "Uploads/download/request/";

        // 不存在目录则创建
        create_directory($directory);

        $filePath = "{$directory}{$fileName}.txt";
        $openFile = fopen($filePath, 'w');
        fwrite($openFile, $content);
        fclose($openFile);
        return ["file_path" => $filePath, "file_name" => $fileName];
    }

    /**
     * 验证许可是否有效
     * @param $param
     * @return array
     */
    public function validationLicense($param)
    {
        $checkResult = $this->license->getLicenseInfosValidated($param["license"]);
        if ($checkResult["localInfoCheckPassed"] === true) {
            // 为空直接更新License激活
            $this->updateLicense($param["license"]);
            return success_response(L("License_Active_SC"));
        } else {
            // 无效许可证
            throw_strack_exception(L("License_Format_Error"), 208003);
        }
    }

    /**
     * 检查Licence
     * @return bool
     */
    public function checkLicense()
    {
        $this->getLicense();
        if ($this->validateLicense()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取许可
     * @return bool
     */
    protected function getLicense()
    {
        $optionsService = new OptionsService();
        $systemLicenseData = $optionsService->getOptionsData("system_license");
        if (!empty($systemLicenseData)) {
            $this->licenseData = $this->license->getLicenseInfosValidated($systemLicenseData["license"]);
            if ($this->licenseData["localInfoCheckPassed"]) {
                $this->licenseString = $systemLicenseData["license"];
                $this->licenseNumber = $this->licenseData["licenseArrayOrErrorArray"]["user_number"];
                return true;
            }
        }

        return false;
    }

    /**
     * 设置许可
     * @param $licenseString
     */
    protected function setLicense($licenseString)
    {
        if (!empty($licenseString)) {
            $this->licenseString = $licenseString;
        }
    }

    /**
     * 获取当前用户数量
     * @return mixed
     */
    protected function getCurrentUserNumber()
    {
        $userModel = new UserModel();
        //排除系统管理员和客户管理员账号，和离职状态账号
        $userNumber = $userModel->where(["id" => ["NOT IN", "1,2"], "status" => 'in_service'])->count();
        if ($this->isUpdateUserMode) {
            $userNumber += 1;
        }
        return $userNumber;
    }

    /**
     * 验证许可
     * @return bool
     */
    protected function validateLicense()
    {
        if (!empty($this->licenseString)) {
            $currentUserNumber = $this->getCurrentUserNumber();
            if ($this->license->validate($this->licenseString, $currentUserNumber)) {
                return true;
            } else {
                $this->errorCode = $this->license->errorCode;
                $this->errorMessage = $this->license->errorMessage;
                return false;
            }
        } else {
            $this->errorCode = 208003;
            $this->errorMessage = L("License_Format_Error");
            return false;
        }
    }

    /**
     * 获取许可授权用户数量
     * @return bool|mixed
     */
    protected function getLicenseUserNumber()
    {
        $this->getLicense();
        if ($this->validateLicense()) {
            return $this->licenseData["licenseArrayOrErrorArray"]["user_number"];
        } else {
            return false;
        }
    }

    /**
     * 更新许可
     * @param $licenseString
     * @return array
     */
    public function updateLicense($licenseString)
    {
        $this->setLicense($licenseString);
        if ($this->validateLicense()) {
            //写入数据库
            $optionsService = new OptionsService();
            return $optionsService->updateOptionsData("system_license", ["license" => $this->licenseString], L("Update_License_SC"));
        } else {
            //无效许可
            throw_strack_exception($this->errorMessage, $this->errorCode, $this->license->licenseInfoData);
        }
    }

    /**
     * 获取许可基本信息，包括过期日期和剩余天数
     * @return array|bool
     */
    public function getBaseLicenseData()
    {
        $this->getLicense();
        if ($this->licenseData["localInfoCheckPassed"]) {
            //过期时间为第二天凌晨 expire_date
            $expireTime = strtotime($this->licenseData["licenseArrayOrErrorArray"]["expire_date"] . ' 00:00:00') + 86400;
            //当前时间戳
            $currentTime = strtotime(date('Y-m-d' . ' 00:00:00', time()));
            //计算剩余天数
            $expiryDays = ($expireTime - $currentTime) / 86400;
            //过期日期
            $expiryDate = date('Y-m-d' . ' 00:00:00', $expireTime);
            return ["user_number" => $this->licenseData["licenseArrayOrErrorArray"]["user_number"], "expiry_date" => $expiryDate, "expiry_days" => $expiryDays];
        } else {
            return false;
        }
    }

    /**
     * 判断是否超过最大用户许可数量
     * @param $licenseUserNumber
     * @return bool
     */
    public function checkMaxUserLicenseNumber()
    {
        if (!$this->licenseNumber) {
            //许可错误
            return $this->errorMessage = L("License_Error");
        } else {
            $currentUserNumber = $this->getCurrentUserNumber();
            if ($this->licenseNumber !== 999999 && $currentUserNumber >= $this->licenseNumber) {
                // 当前用户数量小于许可数量，合法
                return true;
            } else {
                // 当前用户数量大于许可数量，不合法
                return $this->errorMessage = L("The_Number_Of_Licenses_Overflow.");
            }
        }
    }

    /**
     * 注销除了管理员以外账号
     * @return array
     */
    public function disableOverUsers()
    {
        $userModel = new UserModel();
        $resData = $userModel->where(["id" => ["NOT IN", "1,2"]])->setField('status', 'departing');
        if (!$resData) {
            // 注销除了管理员失败错误码 002
            throw_strack_exception($userModel->getError(), 208002);
        } else {
            // 返回成功数据
            return success_response(L("Disable_Over_Users_SC"), $resData);
        }
    }
}
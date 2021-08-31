<?php
// +----------------------------------------------------------------------
// | 邮件服务层
// +----------------------------------------------------------------------
// | 主要服务于邮件发送
// +----------------------------------------------------------------------
// | 错误编码头 204xxx
// +----------------------------------------------------------------------
namespace Common\Service;

use PHPMailer\PHPMailer\PHPMailer;
use Think\QueueClient;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class EmailService
{


    // 错误信息
    protected $errorMessage = '';

    /**
     * PHPMailer对象
     * @var
     */
    protected $phpMailer;

    /**
     * 默认SMTP
     * @var array
     */
    protected $SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    //邮件模板路径
    protected $mailTemplatePath = RUNTIME_PATH . "MailTemplate/";

    //邮件模板
    protected $templateList = ["item", "ping", "progress", "text"];

    /**
     * 邮件html内容
     * @var
     */
    protected $mailContent;
    /**
     * 调试开关
     * @var bool
     */
    public $debug = false;

    public function __construct()
    {
        // 获取当前系统邮箱配置
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->errorMessage;
    }

    /**
     * 设置收件人地址
     * @param $addressee
     */
    protected function setAddressee($addressee)
    {
        if (strstr($addressee, ",")) {
            $emailList = explode(",", $addressee);
        } else {
            $emailList = [$addressee];
        }
        //设置发件人
        foreach ($emailList as $email) {
            $this->phpMailer->AddAddress($email);
        }
    }


    /**
     * 邮件内容处理
     * @param $param
     */
    protected function setMailContent($param)
    {
        if ($param["template"] == "text") {
            $mailContent = $param["content"];
        } else {
            $this->phpMailer->isHTML(true);
            $filePath = $param["template_path"];
            $mailContent = file_get_contents($filePath);
            unlink($filePath);
        }
        //设置邮件正文
        $this->phpMailer->Body = $mailContent;
        $this->mailContent = $mailContent;
    }


    /**
     * 设置邮件配置
     * @param $param
     */
    protected function setConfig($param)
    {
        $mailConfig = $param["config"];
        //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置为 UTF-8
        $this->phpMailer->CharSet = $mailConfig['charset'];
        //设定使用SMTP服务
        $this->phpMailer->IsSMTP();
        // SMTP 服务器
        $this->phpMailer->Host = $mailConfig["server"];
        // SMTP服务器的端口号
        $this->phpMailer->Port = $mailConfig["port"];
        // SMTP服务器用户名
        $this->phpMailer->Username = $mailConfig["username"];
        // SMTP服务器密码
        $this->phpMailer->Password = $mailConfig["password"];
        // 设置发件人地址和名称
        $this->phpMailer->SetFrom($mailConfig["username"], $mailConfig["addresser_name"]);
        $this->phpMailer->SMTPAuth = true;
        /**
         * 判断端口
         * 25：不用加密 SMTPAutoTLS：false
         * 465：ssl加密 SMTPAutoTLS：true
         * 587：tls加密 SMTPAutoTLS：true
         */
        switch ($mailConfig['port']) {
            case 465:
            case 994:
                $this->phpMailer->SMTPSecure = 'ssl';
                $this->phpMailer->SMTPAutoTLS = true;
                $this->phpMailer->SMTPKeepAlive = true;
                $this->phpMailer->SMTPOptions = $this->SMTPOptions;
                break;
            case 587:
                $this->phpMailer->SMTPSecure = 'tls';
                $this->phpMailer->SMTPAutoTLS = true;
                $this->phpMailer->SMTPKeepAlive = true;
                $this->phpMailer->SMTPOptions = $this->SMTPOptions;
                break;
            case 25:
                $this->phpMailer->SMTPAutoTLS = false;
                break;
            default:
                $this->phpMailer->SMTPSecure = '';
                break;
        }
        //根据open_security 设置安全协议
        $this->phpMailer->SMTPSecure = $mailConfig["open_security"] == 1 ? $mailConfig["smtp_secure"] : false;
        //设置邮件标题
        if (!empty($param["param"]["subject"])) {
            $this->phpMailer->Subject = $param["param"]["subject"];
        }
    }

    /**
     * 检查必要字段
     * @param $param
     * @param $requireParam
     * @return bool
     */
    protected function checkRequireParam($param, $requireParam)
    {
        //检查外层参数
        foreach ($requireParam as $val) {
            if (!array_key_exists($val, $param) || empty($param[$val]) && $param[$val] != 0) {
                $this->errorMessag = $val . " " . "require param not exist";
                return false;
            }
        }
        return true;
    }

    /**
     * 检查邮件列表
     * @param $param
     * @return bool
     */
    protected function checkAddressee($param)
    {
        $requireParam = ["addressee"];
        if (!$this->checkRequireParam($param, $requireParam)) {
            return false;
        }
        $addressee = $param["addressee"];
        if (strstr($addressee, ",")) {
            $emailList = explode(",", $addressee);
        } else {
            $emailList = [$addressee];
        }
        foreach ($emailList as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $this->errorMessag = "This $email email address is invalid";
                return false;
            }
            $splitEmail = explode("@", $email);
            if (checkdnsrr(array_pop($splitEmail), "MX") === false) {
                $this->errorMessag = $email . "Mail providers do not exist";
                return false;
            }
        }
        return true;
    }

    /**
     * 检查邮件内容
     * @param $param
     * @return bool
     */
    protected function checkContent($param)
    {
        if (!$this->checkRequireParam($param, ["template", "content"])) {
            return false;
        }
        if (in_array($param["template"], $this->templateList)) {
            if ($param["template"] == "text" && !is_string($param["content"])) {
                $this->errorMessag = "check your mail content";
                return false;
            }
        } else {
            $this->errorMessag = "Mail templates do not exist";
            return false;
        }
        return true;
    }

    /**
     * 检查邮件发送参数
     * @param $param
     * @return bool
     */
    protected function checkEmailParam($param)
    {
        //检查外围参数
        $requirePeripheralParam = ["config", "data", "param"];
        //检查邮件配置
        $requireConfigParam = ["server", "username", "password", "port", "charset", "addresser_name", "smtp_secure", "open_email", "open_security"];
        if (!$this->checkRequireParam($param, $requirePeripheralParam) || !$this->checkRequireParam($param["config"], $requireConfigParam)) {
            return false;
        }
        //检查邮件服务器是否开启
        if ($param["config"]["open_email"] == 0) {
            $this->errorMessag = "Mail service closed";
            return false;
        }
        //检查收件人是否有效
        if (!$this->checkAddressee($param["param"]) || !$this->checkContent($param["data"])) {
            return false;
        }
        return true;
    }

    /**
     * 生成html
     * @param $template
     * @param $content
     * @return bool|string
     */
    protected function generateTemplateFile($template, $content)
    {
        //cache 目录
        $cacheDir = $this->mailTemplatePath . "Cache/";
        create_directory($cacheDir);
        try {
            $loader = new FilesystemLoader($this->mailTemplatePath);
            $twig = new Environment($loader, array());
            //填充模板信息
            $mailContent = $twig->render($template . ".html", $content);
            //保存到缓存目录
            $fileName = "mail" . string_random(6) . ".html";
            $fileName = $cacheDir . $fileName;
            file_put_contents($fileName, $mailContent);
            return $fileName;
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return false;
        }
    }

    /**
     * 初始化邮件配置并检查邮件参数
     * @param $param
     * @return false
     */
    public function initParam($param)
    {
        $optionsService = new OptionsService();
        $emailConfig = $optionsService->getOptionsData("email_settings");
        if (empty($emailConfig)) {
            $this->errorMessag = "Mail Config No Set";
            return false;
        }
        $param["config"] = $emailConfig;
        //检查邮件参数
        if (!$this->checkEmailParam($param)) {
            return false;
        }
        //生成html
        if ($param["data"]["template"] != "text") {
            $templatePath = $this->generateTemplateFile($param["data"]["template"], $param["data"]["content"]);
            if ($templatePath) {
                $param["data"]["template_path"] = $templatePath;
            } else {
                return false;
            }
        }
        return $param;
    }

    /**
     * 发送测试邮件
     * @param $data
     * @return array
     * @throws \Throwable
     */
    public function testSendEmail($data)
    {
        $param = $this->initParam($data);

        if ($param === false) {
            throw_strack_exception($this->getError());
        } else {
            $this->debug = false;
            return $this->send($param);
        }
    }

    /**
     * 直接发送邮件
     * @param $data
     * @return array
     */
    public function directSendEmail($data)
    {
        $param = $this->initParam($data);

        if ($param === false) {
            throw_strack_exception($this->getError());
        } else {

            // 异步处理
            QueueClient::send('email', $param);
            return [];

//            // 加入到email队列
//            $this->debug = false;
//            return $this->send($param);
        }
    }

    /**
     * 使用队列推送邮件
     * @param $data
     * @return array
     */
    public function queueSendEmail($data)
    {
        return $this->send($data);
    }

    /**
     * 发送邮件
     * @param $param
     * @return array
     */
    public function send($param)
    {
        try {
            $this->phpMailer = new PHPMailer();
            //mail debug
            $this->phpMailer->SMTPDebug = $this->debug;
            $this->setConfig($param);
            $this->setAddressee($param["param"]["addressee"]);
            $this->setMailContent($param["data"]);

            if (!$this->phpMailer->send()) {
                $resData = [
                    "status" => 404,
                    "message" => $this->phpMailer->ErrorInfo,
                ];
            } else {
                $resData = [
                    "status" => 200,
                    "message" => "Email successfully sent",
                ];
            }
        } catch (\Exception $e) {
            $resData = [
                "status" => 404,
                "message" => $e->getMessage(),
            ];
        }
        return $resData;
    }
}

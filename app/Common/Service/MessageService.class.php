<?php
// +----------------------------------------------------------------------
// | 事件日志服务层
// +----------------------------------------------------------------------
// | 主要服务于事件日志数据处理
// +----------------------------------------------------------------------
// | 错误编码头 206xxx
// +----------------------------------------------------------------------
namespace Common\Service;

use Common\Model\MessageMemberModel;
use Common\Model\MessageModel;
use Common\Model\SmsModel;
use Common\Model\UserModel;
use Org\Util\Pinyin;
use Overtrue\EasySms\EasySms;
use Think\QueueClient;
use Yurun\Util\HttpRequest;

class MessageService
{

    protected $_headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ];

    //发送消息语言包
    protected $enLang = [
        "update_message_title" => "Modification  Notification!",
        "delete_message_title" => "Delete Notification!",
        "add_message_title" => "Create Notification!",
        "apply_message_title" => "Apply For Settlement Notification!",
        "reject_message_title" => "Reject For Settlement Notification!",
        "confirm_message_title" => "Confirm For Settlement Notification!",
        "reminder_message_title" => "Reminder Notification!",
        "add" => "add",
        "update" => "update",
        "delete" => "delete",
        "reminder" => "reminder",
        "apply" => "Apply For Settlement",
        "reject" => "Reject For Settlement",
        "confirm" => "Confirm For Settlement",
    ];

    //中文包
    protected $zhLang = [
        "update_message_title" => "修改通知",
        "delete_message_title" => "删除通知",
        "add_message_title" => "创建通知",
        "apply_message_title" => "申请结算通知",
        "reject_message_title" => "拒绝结算通知",
        "confirm_message_title" => "确认结算通知",
        "reminder_message_title" => "提醒通知",
        "add" => "添加",
        "update" => "修改",
        "delete" => "删除",
        "reminder" => "提醒",
        "apply" => "申请结算",
        "reject" => "拒绝结算",
        "confirm" => "确认结算",
    ];

    // 错误信息
    protected $errorMsg = '';

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->errorMsg;
    }

    /**
     * 获取事件服务器配置
     * @return array|mixed
     */
    public function getEventServer()
    {
        // 获取事件服务器配置
        $optionsService = new OptionsService();
        $logServerConfig = $optionsService->getOptionsData("log_settings");

        if (!empty($logServerConfig)) {
            // 获取事件服务器状态
            $getServerStatus = check_http_code($logServerConfig["request_url"]);
            $logServerConfig['status'] = $getServerStatus['http_code'];
            $logServerConfig['connect_time'] = $getServerStatus['connect_time'];
            $logServerConfig['token'] = md5($logServerConfig["access_key"] . $logServerConfig["secret_key"]);
            $logServerConfig["add_url"] = $logServerConfig["request_url"] . "/event/add?sign={$logServerConfig['token']}";
            $logServerConfig["find_url"] = $logServerConfig["request_url"] . "/event/find?sign={$logServerConfig['token']}";
            $logServerConfig["select_url"] = $logServerConfig["request_url"] . "/event/select?sign={$logServerConfig['token']}";
            $logServerConfig["fields_url"] = $logServerConfig["request_url"] . "/event/fields?sign={$logServerConfig['token']}";
        } else {
            // 没有配置事件服务器
            $logServerConfig = [
                'status' => 404
            ];
        }
        return $logServerConfig;
    }

    /**
     * 记录到message服务器
     * @param $data
     * @param $controllerMethod
     * @return false|mixed
     */
    protected function postToServer($data, $controllerMethod)
    {
        $logServerConfig = $this->getEventServer();
        if ($logServerConfig["status"] === 200) {
            // 写入到事件服务器

            $http = HttpRequest::newSession();
            $url = $logServerConfig['request_url'] . "/{$controllerMethod}?sign={$logServerConfig['token']}";

            $responseData = $http->headers($this->_headers)
                ->post($url, $data, 'json');

            if ($responseData->httpCode() === 200) {
                $resData = $responseData->json(true);
                if ((int)$resData["status"] === 200) {
                    return $resData["data"];
                } else {
                    $this->errorMsg = $resData["message"];
                    return false;
                }
            } else {
                $this->errorMsg = L('Log_Server_Exception');
                return false;
            }
        } else {
            $this->errorMsg = L('Log_Server_Exception');
            return false;
        }
    }

    /**
     * 生成消息成员保存参数
     * @param $param
     * @return array
     */
    private function generateMemberParam($param)
    {
        return [
            'message_id' => $param['message_id'],
            'status' => 'unread',
            'user_id' => $param['id'],
            'name' => $param['name'],
            'email' => $param['email'],
            'user_uuid' => $param['uuid'],
            'belong_type' => $param['belong_type'],
            'created_by' => $param['created_by'],
            'json' => $param,
        ];
    }

    /**
     * 生成成员保存数据
     * @param $data
     * @param $messageIds
     * @param $primaryIds
     * @param $createdBy
     * @return array
     */
    protected function saveMessageMember($data, $messageIds, $primaryIds, $createdBy)
    {
        $userData = [];
        if (!empty($data)) {
            // 保存成员信息
            $messageMemberModel = new MessageMemberModel();
            $primaryIdData = explode(',', $primaryIds);
            foreach ($primaryIdData as $primaryItem) {
                if (array_key_exists($primaryItem, $data) && !empty($data[$primaryItem])) {
                    foreach ($data[$primaryItem] as &$memberItem) {

                        $memberItem['message_id'] = $messageIds[$primaryItem];
                        $memberItem['created_by'] = $createdBy;

                        // 获取参数并保存数据
                        $saveData = $this->generateMemberParam($memberItem);
                        $messageMemberModel->addItem($saveData);

                        // 将成员信息返回
                        $userData["email"][] = $memberItem['email'];
                        $userData["wechat"][] = $memberItem["login_name"];
                    }
                }
            }
        }
        return $userData;
    }

    /**
     * 生成Item模板信息
     * @param $responseData
     * @param $operationData
     * @param string $language
     * @return array
     */
    protected function generateTemplateItem($responseData, $operationData, $language)
    {
        switch ($language) {
            case 'en-us':
                //标题
                $subject = ucfirst($responseData["message"]["title"]["module_name"]) . "  " . $responseData["message"]["title"]["item_name"] . " " . $this->enLang[$operationData["operate"] . "_message_title"];
                //消息标题
                $messageTitle = "Hello, strack user";
                //消息内容
                $messageContent = "The  " . $responseData["module_data"]["code"] . " information name is " . $operationData["item_name"] . " and was " . $operationData["operate"] . " by " . $operationData["operator"] . "  at  " . $operationData["time"] . " . please pay attention . ";
                break;
            case 'zh-cn';
                //标题
                $subject = $responseData["message"]["title"]["module_name"] . "  " . $operationData["item_name"] . " " . $this->zhLang[$operationData["operate"] . "_message_title"];
                //消息标题
                $messageTitle = "你好，Strack用户！";
                //消息内容
                $messageContent = $responseData["message"]["title"]["module_name"] . " " . $operationData["item_name"] . "  信息在 " . $operationData["time"] . " 被 " . $operationData["operator"] . " " . $this->zhLang[$operationData["operate"]] . "。详情如下";
                break;

        }
        //卡片信息
        $cardData = $responseData["message"]["update_list"];
        $baseData = [];
        //格式化操作参数
        foreach ($cardData as $key => $value) {
            if (is_array($value["value"])) {
                continue;
            }
            $baseData[$key]["title"] = $value["lang"];
            $baseData[$key]["detail"] = $value["value"];
        }
        // 发送邮件
        return [
            "param" => [
                "addressee" => implode(",", $operationData["email_list"]),
                "subject" => $subject
            ],
            "data" => [
                "template" => "item",
                "content" => [
                    "header" => [
                        "title" => $subject
                    ],
                    "body" => [
                        "text" => [
                            "message" => [
                                "title" => $messageTitle,
                                "details" => [
                                    "type" => "text",
                                    "content" => $messageContent
                                ],
                            ]],
                        "card" => [
                            "base" => [
                                [
                                    "name" => $responseData["message"]["title"]["item_name"],
                                    "url" => $responseData["detail_url"],
                                    "item" => $baseData
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * 处理邮件发送内容
     * @param $emailTemplate
     * @param $data
     * @param $userEmail
     * @return array
     */
    protected function dealEmailContent($emailTemplate, $data, $userEmail)
    {
        $responseData = $data["response_data"];
        //语言包
        $language = $responseData["message"]["language"];
        //什么操作
        $operate = $responseData["message"]["operate"];
        //操作时间
        $operationTime = date("Y-m-d H:i:s", $responseData["created"]);
        //操作者
        $operationOf = $responseData["message"]["title"]["created_by"];
        //通用参数
        $operationRelatedData = [
            "operate" => $operate,
            "time" => $operationTime,
            "operator" => $operationOf,
            "email_list" => $userEmail,
            "item_name" => $responseData["message"]["title"]["item_name"]
        ];
        switch ($emailTemplate) {
            case "item":
                $emailData = $this->generateTemplateItem($responseData, $operationRelatedData, $language);
                break;
            case "ping":
            case "progress":
            default:
                $emailData = [];
                break;
        }

        return $emailData;
    }

    /**
     * 新增消息
     * @param $from
     * @param $data
     * @throws \Exception
     */
    public function addMessage($data)
    {
        // 消息数据
        $messageData = $data['message_data']['message'];
        $messageData['identity_id'] = $messageData['identity_id']['identity_id'];
        $primaryIds = $messageData['primary_id'];

        // 存放消息最后添加完成的ID 格式：primary_id=>insert_id
        $lastMessageIds = [];

        // 保存消息
        $messageModel = new MessageModel();

        //var_dump($messageData);
        if (strpos($messageData["primary_id"], ",") === false) {
            $result = $messageModel->addItem($messageData);
            $lastMessageIds[$messageData["primary_id"]] = $result["id"];
        } else {
            $messageIds = explode(",", $messageData["primary_id"]);
            foreach ($messageIds as $messageItem) {
                $messageData["primary_id"] = $messageItem;
                $result = $messageModel->addItem($messageData);
                $lastMessageIds[$messageItem] = $result["id"];
            }
        }

        // 保存成员数据
        $memberData = $data['message_data']['member'];

        $messageMemberData = $this->saveMessageMember($memberData, $lastMessageIds, $primaryIds, $messageData['created_by']);

        // TODO 发送邮件消息
//        if (array_key_exists('response_data', $data)) {
//            $emailService = new EmailService();
//            $template = "item";
//            $emailData = $this->dealEmailContent($template, $data, $messageMemberData["email"]);
//            $emailData = $emailService->initParam($emailData);
//            //wechat参数
//            $emailData["param"]["wechat"] = join(",", $messageMemberData["wechat"]);
//            $emailData["param"]["detail_url"] = $data["response_data"]["detail_url"];
//            $emailService->addToQueue($emailData);
//
//        }
    }


    /**
     * 新增提醒
     * @param $from
     * @param $data
     * @throws \Exception
     */
    public function addReminder($data)
    {
        // 消息记录到Event服务器
        $this->postToServer($data, "message/addTimer");
    }

    /**
     * 获取我的message数据
     * @param $param
     * @return mixed
     */
    public function getMyMessageList($param)
    {
        $userModel = new UserModel();
        $userUuid = $userModel->where(['id' => fill_created_by()])->getField('uuid');

        $filter = [
            'filter' => [
                'message' => [
                    'user_uuid' => ['-eq', $userUuid]
                ]
            ],
            'order' => [
                'message.created' => 'desc'
            ],
            'page' => [
                'page_size' => $param['rows']
            ]
        ];

        // 获取消息记录
        $messageData = $this->postToServer($filter, "message/select");
        $messageData = object_to_array($messageData);

        $userModuleId = C("MODULE_ID")["user"];
        $mediaService = new MediaService();
        $pinyin = new Pinyin();

        foreach ($messageData['rows'] as &$item) {
            $item['sender'] = json_decode($item['sender'], true);
            $item['member'] = json_decode($item['member'], true);

            // 获取用户名称
            $item['user_name'] = $item['sender']['name'];

            // 获取用户头像
            $item['sender']['user_avatar'] = $mediaService->getMediaThumb(['module_id' => $userModuleId, 'link_id' => $item['sender']['id']]);
            $item['sender']['pinyin'] = $pinyin->getAllPY($item['user_name']);

            // 获取媒体数据
            $item['media_data'] = $mediaService->getMediaSelectData(['module_id' => $item['module_id'], 'link_id' => $item['primary_id']]);

            // 获取成员数据
            $item['member_data'] = json_decode($item['member'], true);

            // 格式化 note 时间
            $item["created"] = date_friendly('', $item["created"]);
        }
        return $messageData;
    }

    /**
     * 获取消息盒子数据
     * @param $param
     * @return array|mixed
     */
    public function getSideInboxData($param)
    {
        $userModel = new UserModel();
        $userUuid = $userModel->where(['id' => session('user_id')])->getField('uuid');

        $messageFilter = [];
        if ($param['tab'] == "at_me") {
            $memberFilter["belong_type"] = ['-eq', 'at'];
            $messageFilter["belong_system"] = ['-eq', C('BELONG_SYSTEM')];
        }

        $memberFilter["user_uuid"] = ['-eq', $userUuid];
        $messageFilter["belong_system"] = ['-eq', C('BELONG_SYSTEM')];

        $filter = [
            'filter' => [
                'message' => $messageFilter,
                "message_member" => $memberFilter
            ],
            'page' => [
                'page_size' => $param['page_size'],
                'page_number' => $param['page_number'],
            ]
        ];

        // 获取消息记录
        $messageData = $this->postToServer($filter, "message/select");
        if ($messageData !== false) {
            $messageData = object_to_array($messageData);
            $userModuleId = C("MODULE_ID")["user"];
            $mediaService = new MediaService();
            $pinyin = new Pinyin();
            foreach ($messageData['rows'] as &$item) {
                $item['sender'] = json_decode($item['sender'], true);
                $item['content'] = json_decode($item['content'], true);
                $item['user_name'] = $item['sender']['name'];

                $item['sender']['user_avatar'] = $mediaService->getMediaThumb(['module_id' => $userModuleId, 'link_id' => $item['sender']['id']]);
                $item['sender']['pinyin'] = $pinyin->getAllPY($item['user_name']);

                $mediaLinkId = 0;
                $mediaLinkModuleId = 0;
                switch ($item['operate']) {
                    case 'reject':
                        if (!empty($item['content']['update_data']['note_id']) && !empty($item['content']['update_data']['note_module_id'])) {
                            $mediaLinkId = $item['content']['update_data']['note_id'];
                            $mediaLinkModuleId = $item['content']['update_data']['note_module_id'];
                        }
                        break;
                    default:
                        $mediaLinkId = $item['primary_id'];
                        $mediaLinkModuleId = $item['module_id'];
                        break;
                }

                $item['media_data'] = $mediaService->getMediaSelectData(['module_id' => $mediaLinkModuleId, 'link_id' => $mediaLinkId]);
                $item['created'] = date_friendly('Y', $item['created']);
            }

            return $messageData;
        } else {
            return ["total" => 0, "rows" => []];
        }
    }

    /**
     * 获取指定用户未读消息数据
     * @param $userId
     * @return array|mixed
     */
    public function getUnReadData($userId)
    {
        $userModel = new UserModel();
        $userUuid = $userModel->where(['id' => $userId])->getField('uuid');

        $filter = [
            "filter" => [
                "message_member" => [
                    "user_uuid" => ['-eq', $userUuid],
                    "status" => ['-eq', "unread"],
                    'belong_system' => ['-eq', C('BELONG_SYSTEM')]
                ]
            ]
        ];

        // 获取消息记录
        $messageData = $this->postToServer($filter, "message/getUnReadData");
        if ($messageData !== false) {
            return object_to_array($messageData);
        } else {
            return ["total" => 0, "rows" => []];
        }
    }

    /**
     * 获取指定用户未读消息条数
     * @param $userId
     * @return array|mixed
     */
    public function getUnReadNumber($userId)
    {
        $userModel = new UserModel();
        $userUuid = $userModel->where(['id' => $userId])->getField('uuid');

        $filter = [
            "filter" => [
                "message_member" => [
                    "user_uuid" => ['-eq', $userUuid],
                    "status" => ['-eq', "unread"],
                    'belong_system' => ['-eq', C('BELONG_SYSTEM')]
                ]
            ]
        ];

        // 获取消息记录
        $messageData = $this->postToServer($filter, "message/getUnReadNumber");

        if ($messageData !== false) {
            return object_to_array($messageData);
        } else {
            return ["massage_number" => 0, "last_message_data" => []];
        }
    }

    /**
     * 标记已读消息
     * @param $userId
     * @param $created
     * @return mixed
     */
    public function readMessage($userId, $created)
    {
        $userModel = new UserModel();
        $userUuid = $userModel->where(['id' => $userId])->getField('uuid');

        $filter = [
            "filter" => [
                "message_member" => [
                    "user_uuid" => ['-eq', $userUuid],
                    "status" => ['-eq', "unread"],
                    'belong_system' => ['-eq', C('BELONG_SYSTEM')],
                    "created" => ['-elt', $created]
                ]
            ]
        ];

        // 获取消息记录
        $resData = $this->postToServer($filter, "message/read");

        if ($resData !== false) {
            return object_to_array($resData);
        } else {
            throw_strack_exception("", 404);
        }
    }

    /**
     * 发送注册短信
     * @param $param
     * @return array
     * @throws \Overtrue\EasySms\Exceptions\InvalidArgumentException
     * @throws \Overtrue\EasySms\Exceptions\NoGatewayAvailableException
     */
    public function sendRegisterSMS($param)
    {
        // 判断登录名是否重复
        $checkLoginNameUnique = M("User")->where(["login_name" => $param["login_name"]])->count();
        if ($checkLoginNameUnique > 0) {
            throw_strack_exception(L("User_Login_Name_Repeat"), 404);
        }

        if (!check_tel_number($param["phone"])) {
            // 验证手机号码格式错误
            throw_strack_exception(L("Mobile_Phone_Number_Format_Error"), 404);
        }

        $Verify = new \Think\Verify();
        $checkVerifyCode = $Verify->check($param["system_verify_code"], 'register');
        if (!$checkVerifyCode) {
            // 错误验证码
            throw_strack_exception(L("Verify_Code_Error"), 404);
        }

        // 判断当前手机号码
        $ip = get_client_ip();

        // 判断同一ip一天只能发起注册15次
        if ($this->checkIpRegisterLimit($ip, 15)) {
            throw_strack_exception(L("Register_Too_Frequent"), 404);
        }

        $code = create_sms_code();
        $currentTime = time();
        $deadline = $currentTime + 1800;
        $batch = "{$param["system_verify_code"]}_{$currentTime}";

        $addSMSData = $this->addSMSData([
            'type' => 'register',
            'phone' => $param["phone"],
            'batch' => $batch,
            'ip' => $ip,
            'validate_code' => $code,
            'deadline' => $deadline
        ]);


        // 异步处理
        QueueClient::send('sms', [
            'data' => [
                'phone' => $param["phone"],
                'template' => 'register',
                'code' => $code,
                'content' => "注册码：{$code}",
                'deadline' => 30
            ],
            'gateway' => 'qcloud'
        ]);

        return success_response(L("Send_SMS_SC"), ['batch' => $batch, 'sms_id' => $addSMSData["id"]]);
    }

    /**
     * 发送短信
     * @param $param
     * @param $gateway
     * @throws \Overtrue\EasySms\Exceptions\InvalidArgumentException
     */
    public function sendSMS($param, $gateway)
    {
        $config = C('SMS');
        $easySms = new EasySms($config);
        try {
            $easySms->send($param["phone"], [
                'content' => $param["content"],
                'template' => $config["template"][$param["template"]],
                'data' => [
                    'code' => $param["code"],
                    'type' => '',
                    'deadline' => $param["deadline"]
                ],
            ], [$gateway]);
        } catch (\Overtrue\EasySms\Exceptions\NoGatewayAvailableException $e) {
            switch ($gateway) {
                case "qcloud":
                    throw_strack_exception($e->getExceptions()[$gateway]->raw['errmsg'], 404);
                    break;
                default:
                    throw_strack_exception($e->getMessage(), 404);
                    break;
            }
        }
    }

    /**
     * 清除指定任务的结算按钮
     * @param $linkIds
     */
    public function clearSettlementBnt($linkIds)
    {
        $filter = [
            "filter" => [
                "message" => [
                    'primary_id' => ['-in', join(',', $linkIds)],
                    'belong_system' => ['-eq', C('BELONG_SYSTEM')],
                    "operate" => ['-eq', 'apply']
                ]
            ]
        ];

        $this->postToServer($filter, "message/clearSettlementBnt");
    }

    /**
     * 短信写入到数据库
     * @param $param
     * @return array|bool|mixed
     */
    protected function addSMSData($param)
    {
        $smsModel = new SmsModel();
        $resData = $smsModel->addItem($param);
        if (!$resData) {
            // 写入短信失败
            throw_strack_exception($smsModel->getError(), 404);
        }
        return $resData;
    }

    /**
     * 判断当前IP访问次数是否超过了限制
     * @param $ip
     * @param int $limit
     * @return bool
     */
    protected function checkIpRegisterLimit($ip, $limit = 15)
    {
        $currentDayTime = get_current_day_range(time());
        $filter = [
            'ip' => $ip,
            'created' => ['between', [$currentDayTime["sdate"], $currentDayTime["edate"]]]
        ];
        $smsModel = new SmsModel();
        $countSendNumber = $smsModel->where($filter)->count();
        if ($countSendNumber >= $limit) {
            return true;
        }
        return false;
    }
}

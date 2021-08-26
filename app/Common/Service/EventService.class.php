<?php
// +----------------------------------------------------------------------
// | 事件服务层
// +----------------------------------------------------------------------
// | 主要服务于服务层事件
// +----------------------------------------------------------------------
// | 错误编码头 xxxxxx
// +----------------------------------------------------------------------
namespace Common\Service;

use Think\Request as TPRequest;
use Common\Model\MediaModel;
use Common\Model\ModuleModel;
use Common\Model\StatusModel;
use Common\Model\UserConfigModel;
use Common\Model\UserModel;
use Think\Request;
use Common\Model\BaseModel;
use Common\Model\PlanModel;

class EventService
{

    // 数据
    protected $data;

    // 消息紧急程度
    protected $emergent = "normal";

    // 邮件模板
    protected $emailTemplate = "item";

    // 项目ID
    protected $projectId = 0;

    // 返回信息
    protected $message = '';

    // 消息类型
    protected $messageType = 'message';

    // 发送者信息
    protected $senderData = [];

    // 消息来自类型
    protected $messageFromType = '';

    // 事件操作方法
    protected $messageOperate = '';

    // 传入参数
    protected $requestParam = [];

    // 直接返回数据
    protected $messageResponseData = [];

    // 提醒消息标题
    protected $reminderMessageTitle = '';

    // 消息数据
    protected $messageContent = [
        'message' => [ // 消息主体
            'operate' => '', // 消息操作类型
            'type' => '', // 消息类型
            'module_id' => 0, // 消息所属模块ID
            'project_id' => 0, // 消息所属项目ID（存在即写入）
            'primary_id' => 0, // 消息所属主键
            'emergent' => '', // 消息紧急程度
            'from' => '', // 消息来源
            'identity_id' => [], // 身份ID
            'created_by' => 0, // 创建者
            'created' => 0, // 创建时间
            'content' => [], // 消息内容
            'sender' => [], // 发送者信息
            'email_template' => [], // 邮件模板
            'belong_system' => '' // 归属系统
        ],
        'member' => [] // 消息发送给的成员
    ];

    /**
     * 初始化
     * EventService constructor.
     */
    public function __construct()
    {
        // 初始化消息数据
        $this->setMessageType();
        $this->setMessageEmergent();
        $this->setMessageFrom();
        $this->setMessageIdentityId();
        $this->setMessageCreatedBy();
        $this->setMessageCreated();
        $this->setMessageBelongSystem();
        $this->setMessageEmailTemplate();
        $this->setMessageSenderData();
    }

    /**
     * 获取语言类型
     * @return string
     */
    public function getLanguage()
    {
        $userConfigModel = new UserConfigModel();
        $configData = $userConfigModel->findData([
            "filter" => ["user_id" => session("user_id"), "type" => "system"],
            "fields" => "config"
        ]);
        $lang = "zh-cn";
        if (!empty($configData) && array_key_exists("config", $configData)) {
            $lang = array_key_exists("lang", $configData["config"]) ? $configData["config"]["lang"] : "zh-cn";
        }

        return $lang;
    }

    /**
     * 设置消息操作类型
     * @param string $operate
     */
    protected function setMessageOperate($operate = '')
    {
        $this->messageContent['message']['operate'] = $operate;
    }


    /**
     * 设置消息类型 custom：自定义（默认）， system：系统
     * @param string $type
     */
    protected function setMessageType($type = 'custom')
    {
        $this->messageContent['message']['type'] = $type;
    }

    /**
     * 设置消息所属模块ID
     * @param int $moduleId
     */
    protected function setMessageModuleId($moduleId = 0)
    {
        $this->messageContent['message']['module_id'] = $moduleId;
    }

    /**
     * 设置消息所属项目ID
     * @param int $projectId
     */
    protected function setMessageProjectId($projectId = 0)
    {
        $this->messageContent['message']['project_id'] = $projectId;
    }


    /**
     * 设置消息操作主键ID
     * @param string $primaryId
     */
    protected function setMessagePrimaryId($primaryId = '')
    {
        $this->messageContent['message']['primary_id'] = $primaryId;
    }


    /**
     * 设置消息紧急程度
     * @param string $emergent
     */
    protected function setMessageEmergent($emergent = '')
    {
        $this->messageContent['message']['emergent'] = !empty($emergent) ? $emergent : $this->emergent;
    }

    /**
     * 设置消息来源
     */
    protected function setMessageFrom()
    {
        $this->messageContent['message']['from'] = session("event_from");
    }

    /**
     * 设置消息页面身份ID
     */
    protected function setMessageIdentityId()
    {
        $this->messageContent['message']['identity_id'] = json_decode(session("page_identity"), true);
    }

    /**
     * 设置消息创建者ID
     */
    protected function setMessageCreatedBy()
    {
        $this->messageContent['message']['created_by'] = fill_created_by();
    }

    /**
     * 设置消息创建时间
     */
    protected function setMessageCreated()
    {
        $this->messageContent['message']['created'] = time();
    }

    /**
     * 设置消息所属系统
     */
    protected function setMessageBelongSystem()
    {
        $this->messageContent['message']['belong_system'] = C('BELONG_SYSTEM');
    }

    /**
     * 设置消息使用的邮件模板
     * @param string $emailTemplate
     */
    protected function setMessageEmailTemplate($emailTemplate = '')
    {
        $this->messageContent['message']['email_template'] = !empty($emailTemplate) ? $emailTemplate : $this->emailTemplate;
    }

    /**
     * 设置消息发送者数据
     */
    protected function setMessageSenderData()
    {
        $createdId = session("user_id");
        $userModel = new UserModel();
        $senderData = $userModel->field("id,login_name,name,email,uuid")
            ->where(["id" => $createdId])
            ->find();

        $this->messageContent['message']['sender'] = $senderData;

        $this->senderData = $senderData;
    }


    /**
     * 设置消息内容
     * @param $content
     */
    protected function setMessageContent($content = [])
    {
        $this->messageContent['message']['content'] = $content;
    }

    /**
     * 设置消息接收者数据
     * @param $member
     */
    protected function setMessageMember($member = [])
    {
        $this->messageContent['member'] = $member;
    }


    /**
     * 获取模块数据
     * @param $filter
     * @return mixed
     */
    protected function getModuleData($filter)
    {
        $moduleModel = new ModuleModel();
        $moduleData = $moduleModel->field("id,type,name,code,icon")
            ->where($filter)
            ->find();
        return $moduleData;
    }


    /**
     * 获取指定模块数据
     * @param $code
     * @param $linkId
     * @return mixed
     */
    protected function getModelObjectDataByModuleCode($code, $linkId)
    {
        $class = '\\Common\\Model\\' . string_initial_letter($code) . 'Model';
        $modelObject = new $class();
        return $modelObject->where(['id' => $linkId])->find();
    }


    /**
     * 获取消息接收者用户信息数据
     * @param $userId
     * @return mixed
     */
    protected function getReceiverUserData($userId)
    {
        $userModel = new UserModel();
        $userData = $userModel->field("id,login_name,name,email,uuid")->where(["id" => $userId])->find();
        $userData["belong_type"] = 'cc';
        $userData["belong_system"] = C('BELONG_SYSTEM');

        return $userData;
    }


    /**
     * 获取当前模块水平关联用户数据
     * @param $memberListData
     * @param $userIdList
     * @param $linkIds
     * @param $moduleId
     * @return array
     */
    protected function getHorizontalReceiverData(&$memberListData, &$userIdList, $linkIds, $moduleId)
    {
        $horizontalService = new HorizontalService();
        $relationData = $horizontalService->getHorizontalRelationData([
            "src_link_id" => $linkIds,
            "src_module_id" => $moduleId,
            "dst_module_id" => C("MODULE_ID")["user"]
        ]);

        $horizontalUserData = [];
        foreach ($relationData as $relationItem) {
            $userData = $this->getReceiverUserData($relationItem["dst_link_id"]);

            if (!in_array($userData["id"], $userIdList)) {
                $memberListData[$relationItem["src_link_id"]][] = $userData;

                // web_socket使用用户id数据
                $userIdList[] = $userData["id"];
            }

            // 保存水平关联用户配置
            $horizontalUserData[$relationItem["variable_id"]] = $relationItem;
        }

        return ["member_data" => $memberListData, "user_ids" => $userIdList, "horizontal_user_data" => $horizontalUserData];
    }

    /**
     * 获取当前模糊关联member用户数据
     * @param $memberListData
     * @param $userIdList
     * @param $linkIds
     * @param $moduleId
     */
    protected function getRelationMemberReceiverData(&$memberListData, &$userIdList, $linkIds, $moduleId)
    {
        $memberService = new MemberService();
        $memberList = $memberService->getMemberList(["link_id" => $linkIds, "module_id" => $moduleId], "link_id,user_id,type");
        foreach ($memberList["rows"] as $item) {
            $userData = $this->getReceiverUserData($item["user_id"]);
            if (!in_array($userData["id"], $userIdList)) {
                $memberListData[$item["link_id"]][] = $userData;

                // web_socket使用用户id数据
                $userIdList[] = $userData["id"];
            }
        }
    }

    /**
     * 获取接收者数据
     * @param $linkId
     * @param $moduleData
     * @return array
     */
    protected function getReceiverData($linkId, $moduleData)
    {
        // 成员数据列表按类型存放
        $memberListData = [];
        $userIdList = [];

        // 处理关联主键
        $linkIds = strpos($linkId, ",") === false ? $linkId : ['IN', $linkId];

        // 获取member关联用户
        $this->getRelationMemberReceiverData($memberListData, $userIdList, $linkIds, $moduleData['id']);

        $linkUserMap = [];
        if (!empty($memberListData)) {
            $linkIdsArr = explode(',', $linkId);
            foreach ($linkIdsArr as $id) {
                $linkUserMap[$id] = $id;
            }
        }

        // 判断是否为关联表
        $modelClassName = $moduleData['type'] === 'entity' ? 'Entity' : string_initial_letter($moduleData['code']);

        $modelClass = '\\Common\\Model\\' . $modelClassName . 'Model';
        $modelObject = new $modelClass();
        $modelItemData = $modelObject->where(['id' => $linkIds])->select();
        if (!empty($modelItemData) && array_key_exists('link_id', $modelItemData[0])) {
            $modelItemLinkIds = [];
            $horizontalModuleId = $modelItemData[0]['module_id'];
            foreach ($modelItemData as $modelItem) {
                $modelItemLinkIds[] = $modelItem['link_id'];
                $linkUserMap[$modelItem['link_id']] = $modelItem['id'];
            }
            $linkIds = ['IN', join(',', $modelItemLinkIds)];
        } else {
            $horizontalModuleId = $moduleData['id'];
        }

        // 获取水平关联用户
        $horizontalUserData = $this->getHorizontalReceiverData($memberListData, $userIdList, $linkIds, $horizontalModuleId);

        if (!empty($linkUserMap)) {
            $newMemberListData = [];
            foreach ($linkUserMap as $dstId => $srcId) {
                if (!empty($memberListData[$dstId])) {
                    foreach ($memberListData[$dstId] as $userItem) {
                        $newMemberListData[$srcId][] = $userItem;
                    }
                }
            }
            return ["member_data" => $newMemberListData, "user_ids" => $userIdList, "horizontal_user_data" => $horizontalUserData['horizontal_user_data']];
        }

        return ["member_data" => $memberListData, "user_ids" => $userIdList, "horizontal_user_data" => $horizontalUserData['horizontal_user_data']];
    }

    /**
     * 获取消息内容标题所属项名称
     * @param $updateData
     * @return string
     */
    protected function getMessageContentItemName($updateData)
    {
        $itemName = array_key_exists("name", $updateData) ? $updateData["name"] : "";
        return $itemName;
    }

    /**
     * 获取跳转链接
     * @param $moduleData
     * @param $data
     * @return string
     */
    protected function getJumpUrl($moduleData, $data)
    {
        $url = '';
        switch ($moduleData['code']) {
            case 'base': // 任务详情页面
            case 'entity': // 实体详情页面
                $projectId = !empty($this->projectId) ? $this->projectId : array_key_exists('project_id', $data) ? $data['project_id'] : 0;
                $url = generate_details_page_url($projectId, $moduleData['id'], $data['id']);
                break;
            case 'project':
                // 项目任务列表页面
                $url = rebuild_url(U('project/base'), $data['id']);
                break;
        }

        return $url;
    }

    /**
     * 获取消息所在模块信息
     * @param $belongModuleFilter
     * @param $belongId
     * @return array
     */
    protected function getMessagePositionData($belongModuleFilter, $belongId)
    {
        // 获取当前模块数据
        $moduleData = $this->getModuleData($belongModuleFilter);
        $belongData = $this->getModelObjectDataByModuleCode($moduleData['code'], $belongId);

        return [
            'name' => $belongData['name'],
            'module_name' => L($moduleData['code']),
            'url' => $this->getJumpUrl($moduleData, $belongData)
        ];
    }

    /**
     * 新增操作消息模板
     * @param $moduleData
     * @param $updateData
     * @return array
     */
    protected function addOperationMessageTemplate($moduleData, $updateData)
    {
        $position = [];
        if (array_key_exists('link_id', $updateData) && array_key_exists('module_id', $updateData)) {
            $position = $this->getMessagePositionData(['id' => $updateData['module_id']], $updateData['link_id']);
        } else if (array_key_exists('project_id', $updateData)) {
            $position = $this->getMessagePositionData(['code' => 'project'], $updateData['project_id']);
        }

        $content = [
            "language" => $this->getLanguage(),
            "operate" => $this->messageOperate,
            "title" => [
                "created_by" => $this->senderData['name'],
                "module_name" => L($moduleData['code']),
                "item_name" => $this->getMessageContentItemName($updateData),
                "url" => '' // 删除了不需要跳转了
            ],
            "position" => $position,
            "update_data" => [],
            "update_list" => [],
            "bnt" => []
        ];

        // 填充消息内容数据
        $this->messageContent['message']['content'] = $content;

        return $content;
    }

    /**
     * 更新操作消息模板
     * @param $moduleData
     * @param $updateData
     * @return array
     */
    protected function updateOperationMessageTemplate($moduleData, $updateData)
    {
        $position = [];
        if (array_key_exists('link_id', $updateData) && array_key_exists('module_id', $updateData)) {
            $position = $this->getMessagePositionData(['id' => $updateData['module_id']], $updateData['link_id']);
        } else if (array_key_exists('project_id', $updateData)) {
            $position = $this->getMessagePositionData(['code' => 'project'], $updateData['project_id']);
        }

        $content = [
            "language" => $this->getLanguage(),
            "operate" => $this->messageOperate,
            "title" => [
                "created_by" => $this->senderData['name'],
                "module_name" => L($moduleData['code']),
                "item_name" => $this->getMessageContentItemName($updateData),
                "url" => '' // 删除了不需要跳转了
            ],
            "position" => $position,
            "update_data" => [],
            "update_list" => [],
            "bnt" => []
        ];

        // 填充消息内容数据
        $this->messageContent['message']['content'] = $content;

        return $content;
    }

    /**
     * 删除操作消息模板
     * @param $moduleData
     * @param $updateData
     * @return array
     */
    protected function deleteOperationMessageTemplate($moduleData, $updateData)
    {
        $position = [];
        if (array_key_exists('link_id', $updateData) && array_key_exists('module_id', $updateData)) {
            $position = $this->getMessagePositionData(['id' => $updateData['module_id']], $updateData['link_id']);
        } else if (array_key_exists('project_id', $updateData)) {
            $position = $this->getMessagePositionData(['code' => 'project'], $updateData['project_id']);
        }

        $content = [
            "language" => $this->getLanguage(),
            "operate" => $this->messageOperate,
            "title" => [
                "created_by" => $this->senderData['name'],
                "module_name" => L($moduleData['code']),
                "item_name" => $this->getMessageContentItemName($updateData),
                "url" => '' // 删除了不需要跳转了
            ],
            "position" => $position,
            "update_data" => [],
            "update_list" => [],
            "bnt" => []
        ];

        // 填充消息内容数据
        $this->messageContent['message']['content'] = $content;

        return $content;
    }

    /**
     * 申请结算操作消息模板（只有任务模块有这个操作）
     * 说明：
     * 1、只有甲乙发送对方才能看到执行按钮
     * 2、可以跳转到任务详情页面
     * 3、所属信息为项目信息，可以跳转到项目模块
     * @param $moduleData
     * @param $updateData
     * @param $horizontalUserData
     * @return array
     */
    protected function applyOperationMessageTemplate($moduleData, $updateData, $horizontalUserData)
    {
        // 获取配置
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        $reviewedById = (int)$formulaConfigData['reviewed_by'];
        $assigneeId = (int)$formulaConfigData['assignee_field'];

        // 分派者
        $reviewedBy = $horizontalUserData[$reviewedById];

        // 执行者
        $assignee = $horizontalUserData[$assigneeId];

        $content = [
            "language" => $this->getLanguage(),
            "operate" => $this->messageOperate,
            "title" => [
                "created_by" => $this->senderData['name'],
                "module_name" => L($moduleData['code']),
                "item_name" => $this->getMessageContentItemName($updateData),
                "url" => $this->getJumpUrl($moduleData, $updateData)// 可以跳转到任务详情页面
            ],
            "position" => $this->getMessagePositionData(['code' => 'project'], $updateData['project_id']),
            "update_data" => [],
            "update_list" => [],
            "bnt" => [
                [
                    'lang' => 'Confirmation_For_Settlement',
                    'link_id' => $updateData['id'],
                    'module_id' => $moduleData["id"],
                    'project_id' => $updateData['project_id'],
                    'reviewed_by' => $reviewedBy['dst_link_id'],
                    'assignee' => $assignee['dst_link_id'],
                    'created' => fill_created_by()
                ],
                [
                    'lang' => 'Reject_For_Settlement',
                    'link_id' => $updateData['id'],
                    'module_id' => $moduleData["id"],
                    'project_id' => $updateData['project_id'],
                    'reviewed_by' => $reviewedBy['dst_link_id'],
                    'assignee' => $assignee['dst_link_id'],
                    'created' => fill_created_by()
                ]
            ]
        ];

        // 填充消息内容数据
        $this->messageContent['message']['content'] = $content;

        return $content;
    }

    /**
     * 拒绝结算操作消息模板
     * 说明：
     * 1、当有拒绝理由时候，被拒绝方需要看到拒绝理由
     * 2、可以跳转到任务详情页面
     * 3、所属信息为项目信息，可以跳转到项目模块
     * @param $moduleData
     * @param $updateData
     * @return array
     */
    protected function rejectOperationMessageTemplate($moduleData, $updateData)
    {
        $noteModuleId = C("MODULE_ID")["note"];
        $content = [
            "language" => $this->getLanguage(),
            "operate" => $this->messageOperate,
            "title" => [
                "created_by" => $this->senderData['name'],
                "module_name" => L($moduleData['code']),
                "item_name" => $this->getMessageContentItemName($updateData),
                "url" => $this->getJumpUrl($moduleData, $updateData)// 可以跳转到任务详情页面
            ],
            "position" => $this->getMessagePositionData(['code' => 'project'], $updateData['project_id']),
            "update_data" => [
                'content' => $updateData['reject_text'],
                'note_id' => $updateData['note_id'],
                'note_module_id' => $noteModuleId
            ],
            "update_list" => [],
            "bnt" => []
        ];

        // 填充消息内容数据
        $this->messageContent['message']['content'] = $content;

        return $content;
    }

    /**
     * 确认结算操作消息模板
     * @param $moduleData
     * @param $updateData
     * @return array
     */
    protected function confirmOperationMessageTemplate($moduleData, $updateData)
    {
        $content = [
            "language" => $this->getLanguage(),
            "operate" => $this->messageOperate,
            "title" => [
                "created_by" => $this->senderData['name'],
                "module_name" => L($moduleData['code']),
                "item_name" => $this->getMessageContentItemName($updateData),
                "url" => $this->getJumpUrl($moduleData, $updateData)// 可以跳转到任务详情页面
            ],
            "position" => $this->getMessagePositionData(['code' => 'project'], $updateData['project_id']),
            "update_data" => [],
            "update_list" => [],
            "bnt" => []
        ];

        // 填充消息内容数据
        $this->messageContent['message']['content'] = $content;

        return $content;
    }


    /**
     * 生成提醒操作消息
     * @param $moduleData
     * @param $updateData
     * @return array
     */
    protected function reminderOperationMessageTemplate($moduleData, $updateData)
    {
        $position = [];
        if (array_key_exists('link_id', $updateData) && array_key_exists('module_id', $updateData)) {
            $position = $this->getMessagePositionData(['id' => $updateData['module_id']], $updateData['link_id']);
        } else if (array_key_exists('project_id', $updateData)) {
            $position = $this->getMessagePositionData(['code' => 'project'], $updateData['project_id']);
        }

        $content = [
            "language" => $this->getLanguage(),
            "operate" => $this->messageOperate,
            "title" => [
                "created_by" => $this->senderData['name'],
                "module_name" => L($moduleData['code']),
                "item_name" => $this->reminderMessageTitle,
                "url" => '' // 删除了不需要跳转了
            ],
            "position" => $position,
            "update_data" => [],
            "update_list" => [],
            "bnt" => []
        ];

        // 填充消息内容数据
        $this->messageContent['message']['content'] = $content;

        return $content;
    }

    /**
     * 获取消息内容数据
     * @param $moduleFilter
     * @param $updateData
     * @return array
     */
    protected function getMessageContentData($moduleFilter, $updateData)
    {
        // 获取当前模块数据
        $moduleData = $this->getModuleData($moduleFilter);

        // 设置消息所属模块ID
        $this->setMessageModuleId($moduleData['id']);

        // 获取接收者数据
        if (in_array($this->messageOperate, ['delete'])) {
            $receiverData = $updateData['receive'];
        } else {
            $receiverData = $this->getReceiverData($updateData["id"], $moduleData);
        }

        // 设置消息接收者数据
        $this->setMessageMember($receiverData["member_data"]);

        // 消息操作类型
        $this->setMessageOperate($this->messageOperate);

        // 消息所属项目Id
        $this->setMessageProjectId($this->projectId);

        // 消息所属实体主键Id
        $this->setMessagePrimaryId($updateData['id']);

        $messageContent = [];
        switch ($this->messageOperate) {
            case 'add':
                $messageContent = $this->addOperationMessageTemplate($moduleData, $updateData);
                break;
            case 'update':
                $messageContent = $this->updateOperationMessageTemplate($moduleData, $updateData);
                break;
            case 'delete':
                $messageContent = $this->deleteOperationMessageTemplate($moduleData, $updateData);
                break;
            case 'apply':
                // 申请结算
                $messageContent = $this->applyOperationMessageTemplate($moduleData, $updateData, $receiverData['horizontal_user_data']);
                break;
            case 'reject':
                // 拒绝结算
                $messageContent = $this->rejectOperationMessageTemplate($moduleData, $updateData);
                break;
            case 'confirm':
                // 确认结算
                $messageContent = $this->confirmOperationMessageTemplate($moduleData, $updateData);
                break;
            case 'reminder':
                // 提醒
                $messageContent = $this->reminderOperationMessageTemplate($moduleData, $updateData);
                break;
        }

        // 生成返回消息数据
        $this->messageResponseData = $this->generateResponse($moduleData, $updateData, $messageContent, $receiverData["user_ids"]);

        return ['message_data' => $this->messageContent, 'response_data' => $this->messageResponseData];
    }

    /**
     * 新增后，消息处理
     * @param $moduleFilter
     * @param $data
     * @throws \Exception
     */
    protected function sendMessage($moduleFilter, $data)
    {
        $messageData = $this->getMessageContentData($moduleFilter, $data);

        // 增加从哪里来参数
        $from = session("event_from");

        // 记录消息数据
        $messageService = new MessageService();

        $messageService->addMessage($from, $messageData);
    }

    /**
     * 格式化字段数据
     * @param $updateData
     * @return array|false|string
     */
    protected function formatFieldData($updateData)
    {
        $variableService = new VariableService();
        $variableConfig = $variableService->getVariableConfig($this->requestParam["variable_id"]);
        switch ($this->requestParam["module"]) {
            case "variable":
                return $updateData[$this->requestParam["original_field"]];
            default:
                switch ($this->requestParam["data_source"]) {
                    case "belong_to":
                        $statusModel = new StatusModel();
                        $belongToData = $statusModel->selectData([
                            "filter" => ["id" => $this->requestParam["val"]],
                            "fields" => "id,name,color,icon"
                        ]);
                        return $belongToData;
                    case "horizontal_relationship":
                        // 获取当前水平关联模块信息
                        $dstModuleData = $this->getModuleData([
                            "id" => $updateData["relation_module_id"]
                        ]);

                        $filterData = [
                            "link_data" => explode(",", $this->requestParam["val"]),
                            "project_id" => $this->projectId,
                            "dst_module_id" => $updateData["relation_module_id"],
                        ];

                        if ($dstModuleData["type"] === "entity") {
                            $serviceClass = new EntityService();
                        } else {
                            $serviceClassName = '\\Common\\Service\\' . string_initial_letter($dstModuleData["code"]) . 'Service';
                            $serviceClass = new $serviceClassName();
                        }

                        // 获取当前水平关联数据
                        $horizontalRelationData = $serviceClass->getHRelationSourceData($filterData, [], "");
                        return $horizontalRelationData;
                    default:
                        switch ($this->requestParam["editor"]) {
                            case "combobox":
                                return $variableConfig["combo_list"][$updateData["value"]];
                            case "datebox":
                                return get_format_date($updateData["value"]);
                            case "datetimebox":
                                return get_format_date($updateData["value"], 1);
                            default:
                                return $updateData["value"];
                        }
                }
        }
    }

    /**
     * 生成字段返回数据
     * @param $updateData
     * @return mixed
     */
    protected function generateFieldData($updateData)
    {
        if (array_key_exists("original_field", $this->requestParam)) {
            switch ($this->requestParam["field"]) {
                case "priority":
                    $updateData["priority"] = L(string_initial_letter($updateData["priority"], "_"));
                    $updateData["value_show"] = $updateData["priority"];
                    break;
                case "status":
                    $updateData["status"] = get_user_status($updateData["status"])["name"];
                    $updateData["value_show"] = $updateData["status"];
                    break;
                case "correspond":
                    $updateData["correspond"] = status_corresponds_lang($updateData["correspond"]);
                    $updateData["value_show"] = $updateData["correspond"];
                    break;
                case "public":
                    $updateData["public"] = public_type($updateData["public"])["name"];;
                    $updateData["value_show"] = $updateData["public"];
                    break;
                case "type":
                    switch ($this->requestParam["module"]) {
                        case "action":
                            $updateData["type"] = get_action_type($updateData["type"])["name"];
                            $updateData["value_show"] = $updateData["type"];
                            break;
                        case "note":
                            $updateData["type"] = get_note_type($updateData["type"])["name"];
                            $updateData["value_show"] = $updateData["type"];
                            break;
                        case "tag":
                            $updateData["type"] = tag_type($updateData["type"])["name"];
                            $updateData["value_show"] = $updateData["type"];
                            break;
                    }
                    break;
            }

            // 自定义字段显示value
            if ($this->requestParam["field_type"] === "custom") {
                $updateData["value_show"] = $this->formatFieldData($updateData);
            } else {
                if (strpos($this->requestParam["field"], "_id") !== false) {
                    $moduleField = str_replace("_id", "", $this->requestParam["field"]);
                    if (in_array($moduleField, ["parent", "role"])) {
                        $moduleField = $this->requestParam["module"];
                    }


                    if ($updateData[$this->requestParam["field"]] > 0) {
                        $modelClassName = '\\Common\\Model\\' . string_initial_letter($moduleField) . 'Model';
                        if (class_exists($modelClassName)) {
                            $modelObj = new $modelClassName();
                            $findData = $modelObj->findData(["filter" => ["id" => $updateData[$this->requestParam["field"]]]]);
                            if (!empty($findData)) {
                                $value = $findData[$this->requestParam["original_field"]];
                            } else {
                                $value = '';
                            }
                        } else {
                            $value = $updateData[$this->requestParam["field"]];
                        }
                    } else {
                        $value = "";
                    }

                    $updateData[$this->requestParam["original_field"]] = $value;
                    $updateData["value_show"] = $value;
                } else {
                    $updateData["value_show"] = $updateData[$this->requestParam["original_field"]];
                }
            }
        }

        return $updateData;
    }

    /**
     * 生成返回数据
     * @param $moduleData
     * @param $updateData
     * @param $messageData
     * @param $userIdList
     * @return array
     */
    protected function generateResponse($moduleData, $updateData, $messageData, $userIdList)
    {
        $responseData = [
            'module_data' => $moduleData,
            'param' => $this->requestParam,
            'type' => $this->messageType,
            'from_type' => $this->messageFromType,
            'operate' => $this->messageOperate,
            'data' => $this->generateFieldData($updateData),
            'message' => $messageData,
            'detail_url' => $this->getJumpUrl($moduleData, $updateData),
            'member' => $userIdList,
            'created' => time(),
            'belong_system' => C('BELONG_SYSTEM')
        ];
        return $responseData;
    }

    /**
     * 获取父级接收用户数据
     * @param $memberData
     * @param $exitUserId
     * @param $currentId
     * @param $linkId
     * @param $moduleId
     */
    protected function getParentReceiveUserData(&$memberData, &$exitUserId, $linkId, $moduleId)
    {
        $memberListData = [];
        $userIdList = [];
        $horizontalUserData = $this->getHorizontalReceiverData($memberListData, $userIdList, $linkId, $moduleId);

        foreach ($horizontalUserData['member_data'][$linkId] as $horizontalItem) {
            if (!in_array($horizontalItem['id'], $exitUserId)) {
                $memberData[] = $horizontalItem;
                $exitUserId[] = $horizontalItem['id'];
            }
        }
    }

    /**
     * 生成删除数据按主表分组
     * @param $primaryIds
     * @param $operationData
     * @param array $moduleData
     * @return array
     */
    protected function generateDeleteOperationData($primaryIds, $operationData, $moduleData = [])
    {
        $deleteOperationMapData = [];
        $primaryIdArr = explode(',', $primaryIds);

        // 用户模块module_id
        $userModuleId = C("MODULE_ID")["user"];

        foreach ($primaryIdArr as $primaryId) {
            $deleteOperationMapData[$primaryId] = [
                'master' => [],
                'list' => [],
                'receive' => [
                    "member_data" => [
                        $primaryId => []
                    ],
                    "user_ids" => [],
                    "horizontal_user_data" => []
                ],
            ];
            $exitUserId = [];
            $memberData = [];

            foreach ($operationData as $operationItem) {
                if (
                    array_key_exists('id', $operationItem['record'])
                    && (int)$operationItem['record']['id'] === (int)$primaryId
                ) {
                    $deleteOperationMapData[$primaryId]['master'] = $operationItem;

                    // 主表的创建者也需要收到消息
                    if (
                        array_key_exists('created_by', $operationItem['record'])
                        && !in_array((int)$operationItem['record']['created_by'], $exitUserId)
                    ) {
                        $memberData[] = $this->getReceiverUserData($operationItem['record']['created_by']);
                        $exitUserId[] = (int)$operationItem['record']['created_by'];
                    }

                } else if (array_key_exists('link_id', $operationItem['record'])
                    && (int)$operationItem['record']['link_id'] === (int)$primaryId
                ) {
                    $deleteOperationMapData[$primaryId]['list'][] = $operationItem;
                } else if (array_key_exists('src_link_id', $operationItem['record'])
                    && (int)$operationItem['record']['src_link_id'] === (int)$primaryId
                ) {
                    $deleteOperationMapData[$primaryId]['list'][] = $operationItem;
                } else if (array_key_exists('entity_id', $operationItem['record'])
                    && (int)$operationItem['record']['entity_id'] === (int)$primaryId
                ) {
                    $deleteOperationMapData[$primaryId]['list'][] = $operationItem;
                } else if ($this->messageOperate === 'update'
                    && array_key_exists('link_id', $operationItem)
                    && (int)$operationItem['link_id'] === (int)$primaryId) {
                    $deleteOperationMapData[$primaryId]['list'][] = $operationItem;
                }

                // 判断是否有关联用户
                if (
                    array_key_exists('dst_module_id', $operationItem['record'])
                    && (int)$operationItem['record']['dst_module_id'] === (int)$userModuleId
                    && !in_array((int)$operationItem['record']['dst_link_id'], $exitUserId)
                ) {
                    $memberData[] = $this->getReceiverUserData((int)$operationItem['record']['dst_link_id']);
                    $exitUserId[] = (int)$operationItem['record']['dst_link_id'];
                }
            }

            // 判断主表是否存在爸爸
            if (
                !empty($deleteOperationMapData[$primaryId]['master']['record']['link_id'])
                && !empty($deleteOperationMapData[$primaryId]['master']['record']['module_id'])
            ) {
                $this->getParentReceiveUserData($memberData, $exitUserId, $deleteOperationMapData[$primaryId]['master']['record']['link_id'], $deleteOperationMapData[$primaryId]['master']['record']['module_id']);
            }

            $deleteOperationMapData[$primaryId]['receive']['member_data'][$primaryId] = $memberData;
            $deleteOperationMapData[$primaryId]['receive']['user_ids'][] = $exitUserId;

            // 判断是否有master数据没有重新取下
            if ($this->messageOperate === 'update'
                && empty($deleteOperationMapData[$primaryId]['master'])) {
                $deleteOperationMapData[$primaryId]['master'] = $this->getModelObjectDataByModuleCode($moduleData['code'], $primaryId);
            }

            // TODO 更新项说明 update_data
        }

        return $deleteOperationMapData;
    }

    /**
     * 销毁批次事件缓存
     * @param $batchNumber
     */
    protected function destroyBatchEventCache($batchNumber = '')
    {
        $batchNumberData = !empty($batchNumber) ? $batchNumber : Request::$batchNumber;
        S($batchNumberData, null);
    }


    /**
     * 生成消息数据，传入参数（controller， action，config，request_param， operation_data）
     * @param $param
     * @throws \Ws\Http\Exception
     * @throws \Exception
     */
    public function generateMessageData($param)
    {
        /**
         * controller: 当前操作的控制器
         * action: 当前操作的方法
         * config: action_end hook传参
         * request_param: 用户请求参数
         * operation_data: 当前控制器生命周期内所有触发数据库操作产生的记录
         */

        /**
         * 代码思路
         * 1、判断当前操作是 add update delete apply reject confirm
         * 2、消息要发送给谁 是否有水平关联用户字段或 者有 at member字段
         * 3、消息主体是否有归属父级 position
         * 4、数据是否需要生成跳转链接
         * 5、消息是否有执行按钮
         */

        // 获取发送者数据
        switch ($param['action']) {
            case 'saveMediaData':
                // 添加、修改媒体消息组装
                $mediaService = new MediaService();
                $mediaModel = new MediaModel();
                $this->messageFromType = 'thumb';

                $this->requestParam = TPRequest::$serviceOperationParam;

                foreach ($param['operation_data'] as $operationItem) {
                    if ($operationItem['table'] === 'Media') {
                        if ($operationItem['operate'] === 'create') {
                            // 创建媒体
                            $this->messageOperate = 'add';
                            $moduleFilter = ['id' => $operationItem['module_id']];
                            $mediaData = $mediaService->getMediaData(["link_id" => $operationItem['record']["link_id"], "module_id" => $operationItem['record']["module_id"], "relation_type" => $operationItem['record']["relation_type"], 'type' => $operationItem['record']["type"], "variable_id" => $operationItem['record']["variable_id"]]);
                            $mediaData["id"] = $operationItem["link_id"];
                            $mediaData["value_show"] = $operationItem['record']["thumb"];

                            // 发送处理消息
                            $this->sendMessage($moduleFilter, $mediaData);
                        } else if ($operationItem['operate'] === 'update') {
                            // 修改媒体
                            $this->messageOperate = 'update';
                            $moduleFilter = ['id' => $operationItem['module_id']];
                            $mediaParam = $mediaModel->field('link_id,module_id,relation_type,type,variable_id,thumb')->where(['id' => $operationItem["link_id"]])->find();
                            $mediaData = $mediaService->getMediaData(["link_id" => $mediaParam['link_id'], "module_id" => $mediaParam['module_id'], "relation_type" => $mediaParam["relation_type"], "type" => $mediaParam["type"], "variable_id" => $mediaParam["variable_id"]]);
                            $mediaData["id"] = $operationItem["link_id"];
                            $mediaData["value_show"] = $mediaParam["thumb"];

                            // 发送处理消息
                            $this->sendMessage($moduleFilter, $mediaData);
                        }
                    }
                }
                break;
            case 'addNote':
                $this->requestParam = $param['request_param'];
                $moduleFilter = ['code' => 'note'];
                $this->messageFromType = 'note';
                $this->messageOperate = 'add';
                $noteData = TPRequest::$serviceOperationResData;;
                $this->projectId = $noteData['project_id'];
                $this->sendMessage($moduleFilter, $noteData);
                break;
            case 'modifyNote':
                $this->requestParam = $param['request_param'];
                //$moduleFilter = ['id' => $this->requestParam['module_id']];
                $moduleFilter = ['code' => 'note'];
                $this->projectId = $this->requestParam['project_id'];
                $this->messageFromType = 'note';
                $this->messageOperate = 'update';
                $noteData = TPRequest::$serviceOperationResData;
                $this->sendMessage($moduleFilter, $noteData);
                break;
            case 'deleteNote':
                // 删除Note
                $this->messageOperate = 'delete';
                $this->messageFromType = 'note';
                $moduleFilter = ['code' => 'note'];
                $this->requestParam = $param['request_param'];
                $deleteOperationData = $this->generateDeleteOperationData($param['request_param']['primary_ids'], $param['operation_data']);
                foreach ($deleteOperationData as $deleteOperationItem) {
                    $this->projectId = array_key_exists('project_id', $deleteOperationItem['master']) ? $deleteOperationItem['master']['project_id'] : 0;
                    $deleteData = $deleteOperationItem['master']['record'];
                    $deleteData['receive'] = $deleteOperationItem['receive'];
                    $this->sendMessage($moduleFilter, $deleteData);
                }
                break;
            case 'deleteGridData':
                // 数据表格通用删除
                $this->messageOperate = 'delete';
                $this->requestParam = $param['request_param'];
                $moduleFilter = TPRequest::$serviceOperationModuleFilter;
                $deleteOperationData = $this->generateDeleteOperationData($param['request_param']['primary_ids'], $param['operation_data']);

                foreach ($deleteOperationData as $deleteOperationItem) {
                    $this->projectId = array_key_exists('project_id', $deleteOperationItem['master']) ? $deleteOperationItem['master']['project_id'] : 0;
                    if (!empty($deleteOperationItem['master']['record'])) {
                        $deleteData = $deleteOperationItem['master']['record'];
                        $deleteData['receive'] = $deleteOperationItem['receive'];
                        $this->sendMessage($moduleFilter, $deleteData);
                    }
                }
                break;
            case 'updateItemDialog':
                // 通用新增、修改批量操作消息处理
                $this->requestParam = TPRequest::$serviceOperationParam;
                switch ($this->requestParam["mode"]) {
                    case "create":
                        $this->messageOperate = 'add';
                        break;
                    case "modify":
                        $this->messageOperate = 'update';
                        break;
                }

                // 获取消息返回数据
                $moduleFilter = ['id' => $this->requestParam['module_id']];
                $moduleData = $this->getModuleData($moduleFilter);
                $this->projectId = $this->requestParam['project_id'];
                $this->messageFromType = 'widget_grid';
                $masterData = TPRequest::$serviceOperationResData;
                $deleteOperationData = $this->generateDeleteOperationData((string)$masterData['id'], $param['operation_data'], $moduleData);

                foreach ($deleteOperationData as $deleteOperationItem) {
                    if ($this->messageOperate === 'update') {
                        if (!empty($deleteOperationItem['list'])) {
                            $this->sendMessage($moduleFilter, $masterData);
                        }
                    } else {
                        $this->sendMessage($moduleFilter, $masterData);
                    }
                }
                break;
            case 'updateWidget':
                // 通用单个控件更新
                $this->requestParam = TPRequest::$serviceOperationParam;

                $this->projectId = array_key_exists('project_id', $this->requestParam) ? $this->requestParam['project_id'] : 0;
                $this->messageFromType = 'widget_common';
                $this->messageOperate = 'update';

                $updateData = TPRequest::$serviceOperationResData;
                $updateData['project_id'] = $this->projectId;
                $moduleFilter = TPRequest::$serviceOperationModuleFilter;

                $this->sendMessage($moduleFilter, $updateData);
                break;
            case 'updateBaseConfirmationData':
                $this->requestParam = $param['request_param'];
                // 返回成功数据
                $moduleFilter = ['id' => $this->requestParam['module_id']];
                $masterData = TPRequest::$serviceOperationResData;

                $this->projectId = $masterData['project_id'];
                $this->messageOperate = $masterData['operation'];
                $this->messageFromType = 'base_confirmation';

                $this->sendMessage($moduleFilter, $masterData);
                break;
            case 'startOrStopTimelog':
                // 处理时间日志消息通知
                $moduleFilter = ['code' => 'plan'];
                $this->messageOperate = 'update';
                $this->messageFromType = "timelog_bnt_{$param['request_param']['from']}";
                $masterData = TPRequest::$serviceOperationResData;
                $masterData['data']['from'] = $param['request_param']['from'];
                $this->sendMessage($moduleFilter, $masterData['data']);
                break;
        }

        $this->destroyBatchEventCache();
    }


    /**
     * 添加指定任务的任务提醒
     * @param $planId
     * @param $baseId
     * @throws \Exception
     */
    public function addTaskReminder($planId, $baseId)
    {
        // 获取自动设置任务计划消息提醒配置
        $taskReminderConfig = (new OptionsService())->getOptionsConfigItemData('message_settings', 'task_reminder_before_five_min');

        if (!empty($taskReminderConfig) && $taskReminderConfig > 0) {
            // 自动设置任务计划提前五分钟消息
            $planModel = new PlanModel();
            $masterData = $planModel->where(['id' => $planId])->find();

            $baseModel = new BaseModel();
            $baseData = $baseModel->where(['id' => $baseId])->find();

            // 生成消息数据
            $this->projectId = $baseData['project_id'];
            $this->messageOperate = 'reminder';
            $this->messageFromType = 'plan reminder';
            $this->reminderMessageTitle = $baseData['name'] . " : " . L("Task_Reminder_Before_Five_Min");

            $masterData['project_id'] = $baseData['project_id'];

            $moduleModel = new ModuleModel();
            $moduleData = $moduleModel->where(['code' => 'plan'])->find();
            $moduleFilter = ['id' => $moduleData['id']];

            $messageData = $this->getMessageContentData($moduleFilter, $masterData);

            $afterStartTime = $masterData['start_time'] - 300;
            $currentTime = time();

            if ($afterStartTime > $currentTime) {

                $afterTime = $afterStartTime - $currentTime;

                $reminderMessageData = [
                    "data" => [
                        "name" => $this->reminderMessageTitle,
                        "config" => [
                            "message_data" => $messageData['message_data']
                        ],
                        "belong_system" => C('BELONG_SYSTEM'),
                        "execute_time" => $masterData['start_time'],
                        "after_time" => $afterTime
                    ],
                    "param" => [
                        "module_data" => $moduleData,
                        "detail_url" => $messageData['response_data']['detail_url']
                    ]
                ];


                // 记录消息数据
                $messageService = new MessageService();
                $messageService->addReminder(session("event_from"), $reminderMessageData);
            }

        }
    }
}

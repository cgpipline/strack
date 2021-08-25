<?php
// +----------------------------------------------------------------------
// | 动作服务层
// +----------------------------------------------------------------------
// | 主要服务于钉钉数据对接
// +----------------------------------------------------------------------
// | 错误编码头 236xxx
// +----------------------------------------------------------------------
namespace Common\Service;

use Common\Model\DingtalkPunchCardRecordModel;
use Common\Model\DingtalkPunchCardResultModel;
use Common\Model\TimelogModel;
use Common\Model\UserModel;
use EasyDingTalk\Application;

class DingTalkService
{
    // 钉钉配置
    protected $config = [];

    /**
     * EasyDingTalk对象
     * @var \EasyDingTalk\Application
     */
    protected $dingTalkApp = null;

    /**
     * 缓存Key
     * @var string
     */
    protected $cacheKey = 'dingTalk_user_list';

    /**
     * 初始化对象
     * @param int $userId
     */
    public function init($userId = 0)
    {
        if (empty($this->config)) {
            $this->getConfig($userId);
        }

        if (!empty($this->config)) {
            $this->dingTalkApp = new Application($this->config);
        }
    }

    /**
     * 设置钉钉配置
     * @param $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
        $this->cacheKey = 'dingTalk_user_list_' . $config['corp_id'];
    }

    /**
     * 获取当前用户所在部门绑定的钉钉配置
     * @param $userId
     */
    public function getConfig($userId)
    {
        // 1.获取
        $userModel = new UserModel();
        $departmentId = $userModel->where(['id' => $userId])->getField('department_id');

        // 获取钉钉配置
        $optionsService = new OptionsService();
        $dingTalkSettings = $optionsService->getOptionsData("dingtalk_settings");

        if (!empty($dingTalkSettings)) {
            foreach ($dingTalkSettings as $dingTalkItem) {
                if (in_array($departmentId, $dingTalkItem['control_department'])) {
                    $this->config = $dingTalkItem;
                }
            }
        }
    }

    /**
     * 获取部门用户列表
     * @param $userList
     * @param $departmentId
     * @param $offset
     * @param $size
     * @return array
     */
    protected function getDepartmentUserList(&$userList, $departmentId, $offset, $size)
    {
        $userData = $this->dingTalkApp->user->getDetailedUsers($departmentId, $offset, $size);

        if (!empty($userData['userlist'])) {
            foreach ($userData['userlist'] as $userItem) {
                $userList[] = $userItem;
            }
        }

        if ($userData['hasMore']) {
            $offset = $offset + $size;
            return $this->getDepartmentUserList($userList, $departmentId, $offset, $size);
        }

        return $userList;
    }

    /**
     * 生成钉钉缓存
     * @return array
     */
    public function generateDingTalkCache()
    {
        $userMapByMobile = [];
        // 读取缓存
        $oldDingTalkUserCache = S($this->cacheKey);

        if (!empty($oldDingTalkUserCache)) {
            // 有缓存，判断缓存时间是否是今天
            $todayDate = get_format_date(time(), 0);
            $cacheDate = get_format_date($oldDingTalkUserCache['cache_time'], 0);
            if ($todayDate === $cacheDate) {
                // 今天缓存直接返回
                return $oldDingTalkUserCache['user_map'];
            }
        }

        // 缓存时间戳
        if (!empty($this->dingTalkApp)) {
            $departmentList = $this->dingTalkApp->department->list(null, true); // 获取子部门
            if ($departmentList['errcode'] === 0) {
                $userList = [];
                foreach ($departmentList['department'] as $departmentItem) {
                    // 获取每个部门下面用户
                    $offset = 0; // 偏移量
                    $size = 100; // 分页数量
                    $this->getDepartmentUserList($userList, $departmentItem['id'], $offset, $size);
                }

                /**
                 * 缓存用户列表
                 * 1、以phone为key值
                 * 2、每天缓存一次
                 */
                $userMapByMobile = array_column($userList, null, 'mobile');

                $dingTalkUserCache = [
                    'user_map' => $userMapByMobile,
                    'cache_time' => time()
                ];

                S($this->cacheKey, $dingTalkUserCache);
            }
        }

        return $userMapByMobile;
    }

    /**
     * 获取公司排班时间
     * @param $date
     * @return mixed
     */
    public function getSchedulingTime($date)
    {
        return $this->dingTalkApp->attendance->schedules($date);
    }

    /**
     * 获取指定人的考勤记录最后打卡时间
     * @param $userIds
     * @param $time
     * @return array
     */
    public function getWorkAttendanceByUserId($userIds, $time)
    {
        $userDingTalkIds = [];
        $userMapByMobile = $this->generateDingTalkCache();

        // 获取当前用户id
        $userModel = new UserModel();
        $userData = $userModel->field('id,phone')->where(['id' => ['IN', join(',', unique_arr($userIds))]])->select();
        foreach ($userData as &$userItem) {
            if (array_key_exists($userItem['phone'], $userMapByMobile)) {
                $userItem['dingTalk_user_id'] = $userMapByMobile[$userItem['phone']]['userid'];
                $userDingTalkIds[] = $userMapByMobile[$userItem['phone']]['userid'];
            } else {
                $userItem['dingTalk_user_id'] = 0;
            }
        }

        if (empty($userDingTalkIds)) {
            return [];
        }

        // 当前日期往前推一天
        $beforeTime = $time - 86400;

        $params = [
            'userIds' => $userDingTalkIds,
            'checkDateFrom' => get_format_date($beforeTime, 1),
            'checkDateTo' => get_format_date($time, 1),
            'isI18n' => false
        ];

        $recordsData = $this->dingTalkApp->attendance->records($params);

        // 查找当前人员最后下班时间
        $userAttendanceMapData = [];
        foreach ($recordsData['recordresult'] as $recordsItem) {
            if (array_key_exists('checkType', $recordsItem) && $recordsItem['checkType'] === 'OffDuty') {
                $offDutyTime = $recordsItem['gmtCreate'] / 1000;
                if (array_key_exists($recordsItem['userId'], $userAttendanceMapData)) {
                    if ($offDutyTime > $userAttendanceMapData[$recordsItem['userId']]) {
                        $userAttendanceMapData[$recordsItem['userId']] = $offDutyTime;
                    }
                } else {
                    $userAttendanceMapData[$recordsItem['userId']] = $offDutyTime;
                }
            }

        }

        // 填充回用户数据字典
        $userMapData = [];
        foreach ($userData as $userItem) {
            if (array_key_exists($userItem['dingTalk_user_id'], $userAttendanceMapData)) {
                $offDutyTime = $userAttendanceMapData[$userItem['dingTalk_user_id']];
            } else {
                $offDutyTime = 0;
            }

            //  获取排班情况
            $offDutyCheckTime = 0;
            if (!empty($userItem['dingTalk_user_id'])) {
                $userDingTalkGroup = $this->dingTalkApp->attendance->userGroup($userItem['dingTalk_user_id']);

                if ($userDingTalkGroup['errcode'] === 0 && !empty($userDingTalkGroup['result'])) {
                    $offDutyCheckTime = date('H:i:s', strtotime($userDingTalkGroup['result']['classes'][0]['sections'][0]['times'][1]['check_time']));
                }
            }

            $userMapData[$userItem['id']] = [
                'off_duty_time' => $offDutyTime,
                'off_duty_check_time' => $offDutyCheckTime,
                'dingTalk_user_id' => $userItem['dingTalk_user_id']
            ];
        }

        return $userMapData;
    }


    /**
     * 更新时间日志
     * @param $id
     * @param $endTime
     * @throws \Think\Exception
     */
    public function updateTimelog($id, $endTime)
    {
        $timelogModel = new TimelogModel();
        $timelogModel->where(['id' => $id])
            ->save([
                'end_time' => $endTime,
                'complete' => 'yes'
            ]);
    }

    /**
     * 修正时间日志
     * @param $time
     * @param $unCompleteTimelogData
     * @throws \Think\Exception
     */
    public function reviseTimeLogRecords($time, $unCompleteTimelogData)
    {
        // 2、获取指定人的考勤记录最后打卡时间
        if (!empty($unCompleteTimelogData)) {

            $timelogModel = new TimelogModel();

            $userIds = array_column($unCompleteTimelogData, 'user_id');
            $userMapData = $this->getWorkAttendanceByUserId($userIds, $time);


            /**
             * 自动停止timelog
             */
            foreach ($unCompleteTimelogData as $timelogItme) {
                $timelogStartDate = get_format_date($timelogItme['start_time'], 0);
                if (array_key_exists($timelogItme['user_id'], $userMapData)) {
                    if ($userMapData[$timelogItme['user_id']]['off_duty_time'] > 0) {
                        // 有打卡记录的按打卡记录走，跨天的自动复制
                        $offDutyDate = get_format_date($userMapData[$timelogItme['user_id']]['off_duty_time'], 0);
                        $timelogAtZoreTime = strtotime("{$timelogStartDate} 00:00:00");
                        $offDutyAtZoreTime = strtotime("{$offDutyDate} 00:00:00");
                        $intervalTime = ($offDutyAtZoreTime - $timelogAtZoreTime) / 86400;
                        if ($intervalTime === 0) {
                            // 下班时间
                            $this->updateTimelog($timelogItme['id'], $userMapData[$timelogItme['user_id']]['off_duty_time']);
                        } else if ($intervalTime > 0 && $intervalTime <= 1) {
                            // 跨1天打卡，自动截断
                            $yesterdayEndTime = strtotime("{$timelogStartDate} 23:59:59");
                            $this->updateTimelog($timelogItme['id'], $yesterdayEndTime);

                            // 新建时间日志从零点开始到打卡时间
                            $todayTimelogStartTime = strtotime("{$offDutyDate} 00:00:00");
                            $todayTimelogEndTime = $userMapData[$timelogItme['user_id']]['off_duty_time'];
                            $todayCopyTimelogData = [
                                'complete' => 'yes',
                                'module_id' => $timelogItme['module_id'],
                                'project_id' => $timelogItme['project_id'],
                                'status_id' => $timelogItme['status_id'],
                                'link_id' => $timelogItme['link_id'],
                                'user_id' => $timelogItme['user_id'],
                                'description' => $timelogItme['description'],
                                'start_time' => get_format_date($todayTimelogStartTime, 1),
                                'end_time' => get_format_date($todayTimelogEndTime, 1)
                            ];

                            $timelogModel->addItem($todayCopyTimelogData);
                        } else if ($intervalTime > 1) {
                            // 一直没有暂停的
                            $endTime = strtotime("{$timelogStartDate} {$userMapData[$timelogItme['user_id']]['off_duty_check_time']}");
                            $this->updateTimelog($timelogItme['id'], $endTime);
                        }
                    } else {
                        // 有绑定钉钉账户的按排班下班时间走
                        if ($userMapData[$timelogItme['user_id']]['off_duty_check_time'] > 0) {
                            $endTime = strtotime("{$timelogStartDate} {$userMapData[$timelogItme['user_id']]['off_duty_check_time']}");
                        } else {
                            $endTime = strtotime("{$timelogStartDate} 18:30:00");
                        }

                        $this->updateTimelog($timelogItme['id'], $endTime);
                    }
                } else {
                    // 1、没有打卡记录或者打卡记录为0的自动暂停时间为18:30:00
                    $endTime = strtotime("{$timelogStartDate} 18:30:00");
                    $this->updateTimelog($timelogItme['id'], $endTime);
                }
            }

        }

    }

    /**
     * 获取钉钉user_id
     * @param $userId
     */
    public function getDingTalkUserId($userId)
    {
        $this->init($userId);
        $userMapByMobile = $this->generateDingTalkCache();
        // 获取当前用户id
        $userModel = new UserModel();
        $phone = $userModel->where("id=$userId")->getField('phone');

        if (array_key_exists($phone, $userMapByMobile)) {
            $dingTalkUserId = $userMapByMobile[$phone]['userid'];
        } else {
            $dingTalkUserId = 0;
        }
        return $dingTalkUserId;
    }

    /**
     * 获取用户打卡详情
     * @param $userDingTalkIds
     * @param int $startTime
     * @param int $endTime
     * @return mixed
     */
    public function getUserAttendancelistRecord($userId, $startTime = 0, $endTime = 0)
    {
        //获取钉钉id
        $userDingTalkId = $this->getDingTalkUserId($userId);
        if (empty($userDingTalkId)) {
            return [];
        }
        $params = [
            'userIdList' => [$userDingTalkId],
            'workDateFrom' => get_format_date($startTime, 1),
            'workDateTo' => get_format_date($endTime, 1),
            'offset' => 0,
            'limit' => 10
        ];
//        $params = [
//            'userIds' => [$userDingTalkId],
//            'checkDateFrom' => get_format_date($startTime, 1),
//            'checkDateTo' => get_format_date($endTime, 1)
//        ];
        $recordsData = $this->dingTalkApp->attendance->results($params);
        return $recordsData;
    }

    /**
     * 定时插入前一天的所有打卡记录
     */
    public function synchronousPunchRecord()
    {
        $userModel = new UserModel();
        $userList = $userModel
            ->field('id,phone,department_id')
            ->where([
                'department_id' => ['gt', 0],
                'phone' => ['neq', '']
            ])
            ->select();

        // 获取钉钉配置
        $optionsService = new OptionsService();
        $dingTalkSettings = $optionsService->getOptionsData("dingtalk_settings");


        // 按相同钉钉配置来分组用户
        foreach ($userList as $userItem) {
            foreach ($dingTalkSettings as &$dingTalkSettingItem) {
                if (in_array($userItem['department_id'], $dingTalkSettingItem['control_department'])) {
                    if (array_key_exists('user_list', $dingTalkSettingItem)) {
                        $dingTalkSettingItem['user_list'][] = $userItem;
                    } else {
                        $dingTalkSettingItem['user_list'] = [$userItem];
                    }
                    break;
                }
            }
        }
        //获取昨天时间范围
        $startTime = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
        $endTime = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));
//        $startTime = strtotime('2019-12-03 00:00:00');
//        $endTime = strtotime('2019-12-03 23:59:59');
        try {
            $list = [];
            foreach ($dingTalkSettings as $dingTalkSetting) {
                if (array_key_exists('user_list', $dingTalkSetting)) {
                    $this->setConfig([
                        "app_key" => $dingTalkSetting['app_key'],
                        "corp_id" => $dingTalkSetting['corp_id'],
                        "node_name" => $dingTalkSetting['node_name'],
                        "app_secret" => $dingTalkSetting['app_secret']
                    ]);

                    $this->init();
                    //获取时间范围内打卡记录
                    $dingTalkList = $this->getDingTalkUserResults($dingTalkSetting['user_list'], $startTime, $endTime);
                    if (!empty($dingTalkList)) {
                        $list = array_merge($list, $dingTalkList);
                    }
                }
            }
            if (!empty($list)) { //存在打卡记录
                //插入数据库
                $this->insertDingtalkPunchCardResult($list);
            }

        } catch (\Exception $e) {
            // 报错信息
            throw_strack_exception($e->getMessage());
        }

    }

    /**
     * 获取指定用户的考勤记录
     * @param $userList
     * @param $startTime
     * @param $endTime
     * @return array
     */
    public function getDingTalkUserRecords($userList, $startTime, $endTime)
    {
        $userDingTalkIds = [];
        $userMapByMobile = $this->generateDingTalkCache();

        foreach ($userList as $userItem) {
            if (array_key_exists($userItem['phone'], $userMapByMobile)) {
                $userDingTalkIds[] = $userMapByMobile[$userItem['phone']]['userid'];
            }
        }

        if (empty($userDingTalkIds)) {
            return [];
        }


        $resultList = [];

        //分页 考虑到企业内的员工id列表，最多不能超过50个
        $page = ceil(count($userDingTalkIds) / 50);
        for ($i = 1; $i <= $page; $i++) {
            $start = ($i - 1) * 50;
            $dingUserIds = array_slice($userDingTalkIds, $start, 50);
            $params = [
                'userIds' => $dingUserIds,
                'checkDateFrom' => get_format_date($startTime, 1),
                'checkDateTo' => get_format_date($endTime, 1),
                'isI18n' => false
            ];
            $recordsData = $this->dingTalkApp->attendance->records($params);
            if (!empty($recordsData['recordresult'])) {
                $resultList = array_merge($resultList, $recordsData['recordresult']);
            }
        }
        return $resultList;
    }

    /**
     * 获取指定用户的考勤结果
     * @param $userList
     * @param $startTime
     * @param $endTime
     * @return array
     */
    public function getDingTalkUserResults($userList, $startTime, $endTime)
    {
        $userDingTalkIds = [];
        $userMapByMobile = $this->generateDingTalkCache();

        foreach ($userList as $userItem) {
            if (array_key_exists($userItem['phone'], $userMapByMobile)) {
                $userDingTalkIds[] = $userMapByMobile[$userItem['phone']]['userid'];
            }
        }

        if (empty($userDingTalkIds)) {
            return [];
        }


        $resultList = [];

        //分页 考虑到企业内的员工id列表，最多不能超过50个
        $page = ceil(count($userDingTalkIds) / 50);
        for ($i = 1; $i <= $page; $i++) {
            $start = ($i - 1) * 50;
            $dingUserIds = array_slice($userDingTalkIds, $start, 50);
            $j = 1;
            do {
                $offset = ($j - 1) * 50;
                $params = [
                    'userIdList' => $dingUserIds,
                    'workDateFrom' => get_format_date($startTime, 1),
                    'workDateTo' => get_format_date($endTime, 1),
                    'offset' => $offset,
                    'limit' => 50 //最大值为50
                ];
                $recordsData = $this->dingTalkApp->attendance->results($params);
                if (!empty($recordsData['recordresult'])) {
                    $resultList = array_merge($resultList, $recordsData['recordresult']);
                }
                $j++;
            } while (isset($recordsData['hasMore']) && $recordsData['hasMore'] == true);
        }
        return $resultList;
    }

    /**
     * 批量插入打卡记录
     * @param $data
     * @return bool|string
     * @throws \Think\Exception
     */
    public function insertDingtalkPunchCardRecord($data)
    {
        $dingtalkPunchCardRecord = new DingtalkPunchCardRecordModel();
        $insertArr = [];
        foreach ($data as $item) {
            $dingtalkId = $item['id'];
            $count = $dingtalkPunchCardRecord->where("dingtalk_id=$dingtalkId")->count('id');
            if ($count == 0) { //不存在，则新增
                $newItem['dingtalk_id'] = $dingtalkId;
                $newItem['groupId'] = empty($item['groupId']) ? 0 : $item['groupId'];
                $newItem['planId'] = empty($item['planId']) ? 0 : $item['planId'];
                $newItem['workDate'] = empty($item['workDate']) ? 0 : $item['workDate'];
                $newItem['corpId'] = empty($item['corpId']) ? '' : $item['corpId'];
                $newItem['userId'] = empty($item['userId']) ? '' : $item['userId'];
                $newItem['checkType'] = empty($item['checkType']) ? 'OnDuty' : $item['checkType'];
                $newItem['sourceType'] = empty($item['sourceType']) ? '' : $item['sourceType'];
                $newItem['timeResult'] = empty($item['timeResult']) ? '' : $item['timeResult'];
                $newItem['locationResult'] = empty($item['locationResult']) ? '' : $item['locationResult'];
                $newItem['approveId'] = empty($item['approveId']) ? '' : $item['approveId'];
                $newItem['procInstId'] = empty($item['procInstId']) ? '' : $item['procInstId'];
                $newItem['baseCheckTime'] = empty($item['baseCheckTime']) ? 0 : $item['baseCheckTime'];
                $newItem['userCheckTime'] = empty($item['userCheckTime']) ? 0 : $item['userCheckTime'];
                $newItem['classId'] = empty($item['classId']) ? '' : $item['classId'];
                $newItem['isLegal'] = empty($item['isLegal']) ? 'Y' : $item['isLegal'];
                $newItem['locationMethod'] = empty($item['locationMethod']) ? '' : $item['locationMethod'];
                $newItem['deviceId'] = empty($item['deviceId']) ? '' : $item['deviceId'];
                $newItem['userAddress'] = empty($item['userAddress']) ? '' : $item['userAddress'];
                $newItem['userLongitude'] = empty($item['userLongitude']) ? '' : $item['userLongitude'];
                $newItem['userLatitude'] = empty($item['userLatitude']) ? '' : $item['userLatitude'];
                $newItem['userAccuracy'] = empty($item['userAccuracy']) ? 0 : $item['userAccuracy'];
                $newItem['userSsid'] = empty($item['userSsid']) ? '' : $item['userSsid'];
                $newItem['userMacAddr'] = empty($item['userMacAddr']) ? '' : $item['userMacAddr'];
                $newItem['planCheckTime'] = empty($item['planCheckTime']) ? 0 : $item['planCheckTime'];
                $newItem['baseAddress'] = empty($item['baseAddress']) ? '' : $item['baseAddress'];
                $newItem['baseLongitude'] = empty($item['baseLongitude']) ? '' : $item['baseLongitude'];
                $newItem['baseLatitude'] = empty($item['baseLatitude']) ? '' : $item['baseLatitude'];
                $newItem['baseAccuracy'] = empty($item['baseAccuracy']) ? 0 : $item['baseAccuracy'];
                $newItem['baseSsid'] = empty($item['baseSsid']) ? '' : $item['baseSsid'];
                $newItem['baseMacAddr'] = empty($item['baseMacAddr']) ? '' : $item['baseMacAddr'];
                $newItem['outsideRemark'] = empty($item['outsideRemark']) ? '' : $item['outsideRemark'];
                $newItem['created'] = time();
                $insertArr[] = $newItem;
            }
        }
        $add = $dingtalkPunchCardRecord->addAll($insertArr);
        return $add;
    }

    /**
     * 批量插入打卡结果
     * @param $data
     * @return bool|string
     * @throws \Think\Exception
     */
    public function insertDingtalkPunchCardResult($data)
    {
        $dingtalkPunchCardResult = new DingtalkPunchCardResultModel();
        $insertArr = [];
        foreach ($data as $item) {
            $dingtalkId = $item['id'];
            $count = $dingtalkPunchCardResult->where("dingtalk_id=$dingtalkId")->count('id');
            if ($count == 0) { //不存在，则新增
                $newItem['dingtalk_id'] = $dingtalkId;
                $newItem['groupId'] = empty($item['groupId']) ? 0 : $item['groupId'];
                $newItem['planId'] = empty($item['planId']) ? 0 : $item['planId'];
                $newItem['recordId'] = empty($item['recordId']) ? 0 : $item['recordId'];
                $newItem['workDate'] = empty($item['workDate']) ? 0 : $item['workDate'];
                $newItem['userId'] = empty($item['userId']) ? '' : $item['userId'];
                $newItem['checkType'] = empty($item['checkType']) ? 'OnDuty' : $item['checkType'];
                $newItem['sourceType'] = empty($item['sourceType']) ? '' : $item['sourceType'];
                $newItem['timeResult'] = empty($item['timeResult']) ? '' : $item['timeResult'];
                $newItem['locationResult'] = empty($item['locationResult']) ? '' : $item['locationResult'];
                $newItem['approveId'] = empty($item['approveId']) ? '' : $item['approveId'];
                $newItem['procInstId'] = empty($item['procInstId']) ? '' : $item['procInstId'];
                $newItem['baseCheckTime'] = empty($item['baseCheckTime']) ? 0 : $item['baseCheckTime'];
                $newItem['userCheckTime'] = empty($item['userCheckTime']) ? 0 : $item['userCheckTime'];
                $newItem['created'] = time();
                $insertArr[] = $newItem;
            }
        }
        $add = $dingtalkPunchCardResult->addAll($insertArr);
        return $add;
    }


    /**
     * 获取用户时间范围内的钉钉打卡工时
     * @param $userId
     * @param $startTime
     * @param $endTime
     * @return int
     */
    public function getUserCalendarHoursByTime($userId, $startTime, $endTime)
    {
        $startTime = strtotime(date('Y-m-d 00:00:00', $startTime));
        $endTime = strtotime(date('Y-m-d 00:00:00', strtotime('+1 day', $endTime)));
        $diffDay = ($endTime - $startTime) / 86400; //相差天数
        $workHouse = 0;
        //获取钉钉id
        $dingtalk_id = $this->getDingTalkUserId($userId);
        if (empty($dingtalk_id)) { //如果不存在
            return 0;
        }
        $dingtalkPunchCardRecord = new DingtalkPunchCardResultModel();
        for ($i = 0; $i < $diffDay; $i++) {
            $workDate = strtotime("+" . $i . " day", $startTime);
            $workDate = $workDate * 1000; //毫秒
            //获取上班卡时间
            $workList = $dingtalkPunchCardRecord->where([
                'workDate' => $workDate,
                'userId' => $dingtalk_id,
                'timeResult' => ['neq', 'NotSigned']
            ])->select();
            $goToWorkInfo = $goOffWorkInfo = []; //上下班打卡数据
            foreach ($workList as $item) {
                if (array_key_exists('checkType', $item) && $item['checktype'] == 'OnDuty') { //为上班卡
                    $goToWorkInfo = $item;
                } else {
                    $goOffWorkInfo = $item;
                }
            }

            if (!empty($goToWorkInfo) && !empty($goOffWorkInfo)) {
                //计算上班加班时间
                $goTodiffTime = round(($goToWorkInfo['basechecktime'] - $goToWorkInfo['userchecktime']) / 1000 / 3600, 2); //相差小时
                //计算下班加班时间
                $goOffdiffTime = round(($goOffWorkInfo['userchecktime'] - $goOffWorkInfo['basechecktime']) / 1000 / 3600, 2); //相差小时
                $diffHouse = $goTodiffTime + $goOffdiffTime + 8;
                $workHouse += $diffHouse;
            }

        }
        return $workHouse;
    }

}

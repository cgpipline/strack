<?php
// +----------------------------------------------------------------------
// | User 自定义服务
// +----------------------------------------------------------------------
// | 主要服务于User数据处理
// +----------------------------------------------------------------------
// | 错误编码头 229xxx
// +----------------------------------------------------------------------

namespace Common\Service;

use Common\Model\DepartmentManageModel;
use Common\Model\DepartmentModel;
use Common\Model\PasswordHistoryModel;
use Common\Model\ProjectMemberModel;
use Common\Model\RoleUserModel;
use Common\Model\SmsModel;
use Common\Model\UserConfigModel;
use Common\Model\UserModel;
use Common\Model\ViewModel;
use Common\Model\ViewUseModel;
use Overtrue\Pinyin\Pinyin;

class UserService
{
    // Pinyin 对象
    protected $pinyinClass;

    public function __construct()
    {
        $this->pinyinClass = new Pinyin();
    }

    /**
     * 添加部门
     * @param $param
     * @return array
     */
    public function addDepartment($param)
    {
        $departmentModel = new DepartmentModel();
        $resData = $departmentModel->addItem($param);
        if (!$resData) {
            // 添加部门失败错误码 - 001
            throw_strack_exception($departmentModel->getError(), 229001);
        } else {
            return success_response($departmentModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 修改部门
     * @param $param
     * @return array
     */
    public function modifyDepartment($param)
    {
        $departmentModel = new DepartmentModel();
        $resData = $departmentModel->modifyItem($param);
        if (!$resData) {
            // 修改部门失败错误码 - 002
            throw_strack_exception($departmentModel->getError(), 229002);
        } else {
            return success_response($departmentModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 删除部门
     * @param $param
     * @return array
     */
    public function deleteDepartment($param)
    {
        $departmentModel = new DepartmentModel();
        $resData = $departmentModel->deleteItem($param);
        if (!$resData) {
            // 删除部门失败错误码 - 003
            throw_strack_exception($departmentModel->getError(), 229003);
        } else {
            return success_response($departmentModel->getSuccessMassege());
        }
    }

    /**
     * 获取部门树列表
     * @return array
     */
    public function getDepartmentTreeList()
    {
        $departmentModel = new DepartmentModel();
        $departmentData = $departmentModel->field('id,name as text,parent_id')->select();
        $defaultNode = array(
            'id' => 0,
            'text' => 'Root',
            'parent_id' => 0
        );

        array_unshift($departmentData, $defaultNode);

        //生成树
        $tree = new \Org\Util\Tree("id", "parent_id", "children");
        $tree->load($departmentData);
        $departmentTreeData = $tree->DeepTree();
        return $departmentTreeData;
    }

    /**
     * 获取部门列表
     * @return mixed
     */
    public function getDepartmentList()
    {
        $departmentModel = new DepartmentModel();
        $departmentData = $departmentModel->field('id,name')->select();
        return $departmentData;
    }

    /**
     * 获取部门表格数据
     * @param $param
     * @return mixed
     */
    public function getDepartmentGridData($param)
    {
        $options = [
            'page' => [$param["page"], $param["rows"]]
        ];
        $departmentModel = new DepartmentModel();
        $departmentData = $departmentModel->selectData($options);

        // 获取部门负责人列表
        $departmentIds = array_column($departmentData, 'id');
        $departmentManageModel = new DepartmentManageModel();

        $departmentUserData = $departmentManageModel->alias("dept_m")
            ->join("LEFT JOIN strack_user user ON user.id = dept_m.user_id")
            ->where(["dept_m.department_id" => ['IN', join(',', $departmentIds)]])
            ->field("
                dept_m.department_id as id,
                user.id as user_id,
                user.name as user_name
            ")->select();

        $departmentUserMapData = [];
        foreach ($departmentUserData as $departmentUserItem) {
            if (array_key_exists($departmentUserItem['id'], $departmentUserMapData)) {
                $departmentUserMapData[$departmentUserItem['id']]['name'][] = $departmentUserItem['user_name'];
                $departmentUserMapData[$departmentUserItem['id']]['ids'][] = $departmentUserItem['user_id'];
            } else {
                $departmentUserMapData[$departmentUserItem['id']] = [
                    'name' => [$departmentUserItem['user_name']],
                    'ids' => [$departmentUserItem['user_id']]
                ];
            }
        }

        foreach ($departmentData['rows'] as &$departmentItem) {
            if (array_key_exists($departmentItem['id'], $departmentUserMapData)) {
                $departmentItem['manager_ids'] = join(',', $departmentUserMapData[$departmentItem['id']]['ids']);
                $departmentItem['manager_name'] = join(',', $departmentUserMapData[$departmentItem['id']]['name']);
            } else {
                $departmentItem['manager_ids'] = '';
                $departmentItem['manager_name'] = '';
            }
        }

        return $departmentData;
    }

    /**
     * 获取部门负责人数据字典
     * @param $departmentRowsData
     * @return array
     */
    public function getDepartmentChargeMap($departmentRowsData)
    {
        $departmentIds = array_column($departmentRowsData, 'id');
        $departmentManageModel = new DepartmentManageModel();

        $departmentUserData = $departmentManageModel->alias("dept_m")
            ->join("LEFT JOIN strack_user user ON user.id = dept_m.user_id")
            ->where(["dept_m.department_id" => ['IN', join(',', $departmentIds)]])
            ->field("
                dept_m.department_id as id,
                user.id as user_id,
                user.name as user_name
            ")->select();

        $departmentUserMapData = [];
        foreach ($departmentUserData as $departmentUserItem) {
            if (array_key_exists($departmentUserItem['id'], $departmentUserMapData)) {
                $departmentUserMapData[$departmentUserItem['id']]['name'][] = $departmentUserItem['user_name'];
                $departmentUserMapData[$departmentUserItem['id']]['ids'][] = $departmentUserItem['user_id'];
            } else {
                $departmentUserMapData[$departmentUserItem['id']] = [
                    'name' => [$departmentUserItem['user_name']],
                    'ids' => [$departmentUserItem['user_id']]
                ];
            }
        }

        return $departmentUserMapData;
    }

    /**
     * 获取部门表格树数据
     * @param $param
     * @return mixed
     */
    public function getDepartmentTreeGridData($param)
    {
        $options = [
            'fields' => 'id,name,code,parent_id',
            'page' => [$param["page"], $param["rows"]]
        ];
        $departmentModel = new DepartmentModel();
        $departmentData = $departmentModel->selectData($options);

        // 获取部门负责人列表
        $departmentUserMapData = $this->getDepartmentChargeMap($departmentData['rows']);

        foreach ($departmentData['rows'] as &$departmentItem) {
            if (array_key_exists($departmentItem['id'], $departmentUserMapData)) {
                $departmentItem['manager_ids'] = join(',', $departmentUserMapData[$departmentItem['id']]['ids']);
                $departmentItem['manager_name'] = join(',', $departmentUserMapData[$departmentItem['id']]['name']);
            } else {
                $departmentItem['manager_ids'] = '';
                $departmentItem['manager_name'] = '';
            }
        }

        // 生成tree
        $tree = new \Org\Util\Tree("id", "parent_id", "children");
        $tree->load($departmentData['rows']);
        $departmentTreeData = $tree->DeepTree();

        $departmentData['rows'] = $departmentTreeData;

        return $departmentData;
    }


    /**
     * 添加部门负责人
     * @param $departmentId
     * @param $userIds
     */
    public function addDepartmentManager($departmentId, $userIds)
    {
        $departmentManageModel = new DepartmentManageModel();
        foreach ($userIds as $userId) {
            $departmentManageModel->addItem([
                'user_id' => $userId,
                'department_id' => $departmentId
            ]);
        }
    }

    /**
     * 更新部门负责人
     * @param $departmentId
     * @param $userIdsStr
     * @return array
     */
    public function updateDepartmentManager($departmentId, $userIdsStr)
    {
        // 获取当前存在的部门负责人
        $departmentManageModel = new DepartmentManageModel();
        $exitManager = $departmentManageModel->field('id,user_id')->where(['department_id' => $departmentId])->select();

        $exitManagerMap = array_column($exitManager, null, 'user_id');

        $userIds = explode(',', $userIdsStr);

        $updateStatus = 404;

        // 处理删除
        foreach ($exitManager as $exitManagerItem) {
            if (!in_array($exitManagerItem['user_id'], $userIds)) {
                $updateStatus = 200;
                $departmentManageModel->where(['id' => $exitManagerMap[$exitManagerItem['user_id']]['id']])->delete();
            }
        }

        // 处理新增
        foreach ($userIds as $userId) {
            if (!array_key_exists($userId, $exitManagerMap)) {
                $updateStatus = 200;
                $departmentManageModel->addItem([
                    'department_id' => $departmentId,
                    'user_id' => $userId,
                ]);
            }
        }

        return success_response(L('Modify_Department_Sc'), [], $updateStatus);
    }

    /**
     * 删除部门负责人
     * @param $departmentIds
     */
    public function deleteDepartmentManager($departmentIds)
    {
        // 获取当前存在的部门负责人
        $departmentManageModel = new DepartmentManageModel();
        $departmentManageModel->where(['department_id' => ['IN', $departmentIds]])->delete();
    }

    /**
     * 递归取部门下面的儿子
     * @param $newDepartmentIds
     * @param $departmentId
     * @param $allDepartmentMapData
     * @return mixed
     */
    protected function recurrenceDepartmentChildIds(&$newDepartmentIds, $departmentId, $allDepartmentMapData)
    {
        if (array_key_exists($departmentId, $allDepartmentMapData)) {
            foreach ($allDepartmentMapData[$departmentId] as $childDepartmentId) {
                if (!in_array($childDepartmentId, $newDepartmentIds)) {
                    $newDepartmentIds[] = $childDepartmentId;
                }
                $this->recurrenceDepartmentChildIds($newDepartmentIds, $childDepartmentId, $allDepartmentMapData);
            }
        }

        return $newDepartmentIds;
    }

    /**
     * 获取部门子部门IDs
     * @param $departmentIds
     * @return array
     */
    public function getDepartmentChildIds($departmentIds)
    {
        // 获取所有部门
        $departmentModel = new DepartmentModel();
        $allDepartmentData = $departmentModel->field('id,parent_id')->select();
        $allDepartmentMapData = [];
        foreach ($allDepartmentData as $allDepartmentItem) {
            if (array_key_exists($allDepartmentItem['parent_id'], $allDepartmentMapData)) {
                $allDepartmentMapData[$allDepartmentItem['parent_id']][] = $allDepartmentItem['id'];
            } else {
                $allDepartmentMapData[$allDepartmentItem['parent_id']] = [$allDepartmentItem['id']];
            }
        }
        $newDepartmentIds = [];

        foreach ($departmentIds as $departmentId) {
            if (!in_array($departmentId, $newDepartmentIds)) {
                $newDepartmentIds[] = $departmentId;
            }
            $this->recurrenceDepartmentChildIds($newDepartmentIds, $departmentId, $allDepartmentMapData);
        }

        return $newDepartmentIds;
    }

    /**
     * 获取我负责部门所属用户IDs，多个团队用户ID需要去重
     * @param $userId
     * @return array
     */
    public function getMyChargeTeamUserIds($userId)
    {
        $departmentManageModel = new DepartmentManageModel();
        $userDepartmentManageData = $departmentManageModel->where(['user_id' => $userId])->select();

        if (!empty($userDepartmentManageData)) {
            $departmentIds = array_column($userDepartmentManageData, 'department_id');

            // 找到这些部门的儿子
            $newDepartmentIds = $this->getDepartmentChildIds($departmentIds);

            $userModel = new UserModel();
            $userData = $userModel->field('id')
                ->where(['department_id' => ['IN', join(',', $newDepartmentIds)]])
                ->select();

            if (!empty($userData)) {
                $userIds = array_column($userData, 'id');
                if (!in_array($userId, $userIds)) {
                    $userIds[] = $userId;
                }

                return $userIds;
            }

        }

        return [$userId];
    }

    /**
     * 获取我管理部门成员列表
     * @param $userId
     * @return array|mixed
     */
    public function getMyChargeDepartmentMembers($userId)
    {
        $userIds = $this->getMyChargeTeamUserIds($userId);

        if (empty($userIds) || (count($userIds) === 1 && $userIds[0] === $userId)) {
            return [];
        }

        $userModel = new UserModel();
        $userData = $userModel->field('id,name')
            ->where([
                'id' => ['IN', join(',', $userIds)],
                "status" => "in_service"
            ])
            ->select();

        return $userData;
    }


    /**
     * 新增账户
     * @param $param
     * @param string $moduleCode
     * @return array
     */
    public function addAccount($param, $moduleCode = "user")
    {
        $masterData = [];
        $message = '';

        //新增用户操作首先要判断是否超过了许可数量

        // 实例化主模块Model
        $createModel = D("Common/" . camelize($moduleCode), "Model");
        //开启事务
        $createModel->startTrans();
        try {
            foreach ($param as $key => $item) {
                // 如果当前的key等于当前模块，直接调用添加，否则，将当前模块添加完成的主键ID保存到数据里面
                if ($key == $moduleCode) {
                    $masterData = $createModel->addItem($item);
                    if (!$masterData) {
                        throw new \Exception($createModel->getError());
                    }
                } else {
                    $otherModel = D("Common/" . camelize($key));
                    if ($item['field_type'] == "built_in") {
                        $item['user_id'] = $masterData['id'];
                    } else { // 如果为自定义字段表时，关联条件为link_id
                        $item['link_id'] = $masterData['id'];
                    }
                    // 执行添加数据
                    $resData = $otherModel->addItem($item);
                    if (!$resData) {
                        throw new \Exception($createModel->getError());
                    }
                }
            }

            // 保存用户当前密码
            $passwordHistoryModel = new PasswordHistoryModel();
            $passwordHistoryData = $passwordHistoryModel->addItem(['user_id' => $masterData['id'], 'password' => $masterData['password']]);

            // 保存用户当前密码报错
            if (!$passwordHistoryData) {
                throw new \Exception($passwordHistoryModel->getError());
            }

            // 提交事物
            $createModel->commit();
            $message = $createModel->getSuccessMassege();
        } catch (\Exception $e) {
            // 事物回滚
            $createModel->rollback();
            // 添加用户失败错误码 - 004
            throw_strack_exception($createModel->getError(), 229004);
        }

        // 返回成功数据
        return success_response($message, $masterData);
    }

    /**
     * 同步创建第三方登录账户，并绑定openid
     * @param $loginUserData
     * @param $idField
     */
    public function syncCreateUserByTheThirdLogin($loginUserData, $idField)
    {
        $userModel = new UserModel();

        // 1. 先查找是否存在当前手机号码用户
        $exitUserData = $userModel->findData([
            'fields' => 'id,' . $idField,
            'filter' => [
                'phone' => $loginUserData['phone']
            ]
        ]);

        if (!empty($exitUserData)) {
            // 2. 存在用户判断是否绑定了账户
            if (empty($exitUserData[$idField])) {
                // 没有绑定账户
                $userModel->where(['id' => $exitUserData['id']])->setField($idField, $loginUserData['id']);
            }
        } else {
            // 2. 不存在用户，创建账户
            $addUserData = [
                'login_name' => $loginUserData['phone'],
                'email' => $loginUserData['phone'] . C('Default_EmailExt'),
                'name' => $loginUserData['name'],
                'nickname' => $this->pinyinClass->permalink($loginUserData['name'], '', PINYIN_KEEP_ENGLISH),
                'phone' => $loginUserData['phone'],
                'password' => $this->getUserDefaultPassword(),
                $idField => $loginUserData['id']
            ];

            $userModel->addItem($addUserData);
        }
    }

    /**
     * 获取用户默认密码
     * @return mixed
     */
    public function getUserDefaultPassword()
    {
        $optionsService = new OptionsService();
        $defaultOptionData = $optionsService->getOptionsData("default_settings");
        if (isset($defaultOptionData["default_password"]) && !empty($defaultOptionData["default_password"])) {
            $defaultPassword = $defaultOptionData["default_password"];
        } else {
            $defaultPassword = C('DEFAULT_PASSWORD');
        }
        return $defaultPassword;
    }

    /**
     * 更新当前用户系统默认配置
     * @param $userId
     * @param $param
     * @return array
     */
    public function updateUserSystemConfig($userId, $param)
    {
        $userConfigModel = new UserConfigModel();
        // 获取用户配置ID
        $userConfigFindData = $userConfigModel->findData([
            "filter" => ['type' => 'system', 'user_id' => $userId],
            "fields" => "id"
        ]);

        if (isset($userConfigFindData["id"])) {
            // 已经存在用户系统配置则修改操作
            $resData = $userConfigModel->modifyItem([
                'id' => $userConfigFindData["id"],
                "config" => $param
            ]);
        } else {
            // 不存在新增操作
            $resData = $userConfigModel->addItem([
                'type' => 'system',
                'user_id' => $userId,
                "config" => $param
            ]);
        }
        if (!$resData) {
            // 更新用户系统配置失败错误码 - 004
            throw_strack_exception($userConfigModel->getError(), 229006);
        } else {
            // 更新用户语言包cookie
            cookie('think_language', $param["lang"]);
            return success_response($userConfigModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 获取用户自定义配置
     * @param $filter
     * @return array|mixed
     */
    public function getUserCustomConfig($filter)
    {
        $userConfigModel = new UserConfigModel();
        $userConfigData = $userConfigModel->findData([
            'fields' => 'config',
            'filter' => $filter
        ]);
        return $userConfigData;
    }

    /**
     * 获取当前用户系统默认配置
     * @param $userId
     * @return array
     */
    public function getUserSystemConfig($userId)
    {
        $userConfigModel = new UserConfigModel();
        $userSystemConfigData = $userConfigModel->findData([
            'fields' => 'config',
            'filter' => [
                'type' => 'system',
                'user_id' => $userId,
            ]
        ]);

        if (!$userSystemConfigData) {
            $optionsService = new OptionsService();
            $resData = $optionsService->getOptionsData("default_settings");
            return ["lang" => $resData["default_lang"], "timezone" => $resData["default_timezone"], "mfa" => 'no'];
        } else {
            return $userSystemConfigData["config"];
        }
    }

    /**
     * 获取用户二次验证配置
     * @param $userId
     * @return bool
     */
    public function getUserMfaVerifyConfig($userId)
    {
        if ($userId > 1) {
            $optionsService = new OptionsService();
            $defaultSettings = $optionsService->getOptionsData("default_settings");
            if (array_key_exists("open_mfa_verify", $defaultSettings) && $defaultSettings["open_mfa_verify"] > 0) {
                $userSystemConfig = $this->getUserSystemConfig($userId);
                if (array_key_exists("mfa", $userSystemConfig) && $userSystemConfig["mfa"] === "yes") {
                    return true;
                }
            }
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取指定用户语言包配置
     * @param $userId
     * @return string
     */
    public function getUserDefaultLang($userId)
    {
        $userSystemConfig = $this->getUserSystemConfig($userId);
        if (empty($userSystemConfig)) {
            return 'en-us';
        } else {
            return $userSystemConfig["lang"];
        }
    }

    /**
     * 修改账户信息
     * @param $param
     * @return array
     */
    public function modifyAccount($param)
    {
        //判断是否存在修改用户状态操作，存在则需要判断当前激活账号是否已经超过许可用户数量
        if (array_key_exists("status", $param)) {
            if ($param["status"] === "in_service") {
                // 更新用户系统配置失败错误码 - 005
                throw_strack_exception(L("User_Over_Allow_Number"), 229007);
            }
        }

        $userModel = new UserModel();
        $resData = $userModel->modifyItem($param);
        if (!$resData) {
            // 更新账户信息失败错误码 - 006
            throw_strack_exception($userModel->getError(), 229008);
        } else {
            // 如果修改了密码记录到密码历史记录
            $passwordHistoryModel = new PasswordHistoryModel();
            $passwordHistoryModel->addItem(['user_id' => $resData['id'], 'password' => $resData['password']]);
            return success_response($userModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 重置用户默认密码
     * @param $ids
     * @return array
     */
    public function resetUserDefaultPassword($ids)
    {
        $data = [
            'id' => ["IN", $ids],
            'password' => $this->getUserDefaultPassword(),
        ];
        $userModel = new UserModel();
        $resData = $userModel->modifyItem($data);
        if (!$resData) {
            // 重置用户默认密码失败错误码 - 009
            throw_strack_exception($userModel->getError(), 229009);
        } else {
            return success_response($userModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 删除账户
     * @param $param
     * @return array
     */
    public function deleteAccount($param)
    {
        $userModel = new UserModel();
        // 开启事务
        $userModel->startTrans();
        try {
            $deleteParam = ["user_id" => $param["user_id"]];

            // 删除用户密码记录
            $passwordHistoryModel = new PasswordHistoryModel();
            $passwordHistoryModel->deleteItem($deleteParam);

            // 删除用户配置
            $userConfigModel = new UserConfigModel();
            $userConfigModel->deleteItem($deleteParam);

            // 删除用户视图
            $viewModel = new ViewModel();
            $viewModel->deleteItem($deleteParam);

            // 删除用户当前使用视图
            $viewUseModel = new ViewUseModel();
            $viewUseModel->deleteItem($deleteParam);

            // 删除用户权限组
            $roleUserModel = new RoleUserModel();
            $roleUserModel->deleteItem($deleteParam);

            // 删除项目团队
            $projectMemberModel = new ProjectMemberModel();
            $projectMemberModel->deleteItem($deleteParam);

            try {
                // 删除媒体数据
                $mediaService = new MediaService();
                $mediaService->batchClearMediaThumbnail([
                    'link_id' => $param["primary_ids"],
                    'module_id' => C("MODULE_ID")["user"]
                ]);
            } catch (\Exception $e) {

            }

            // 删除用户
            $userParam["id"] = $param["user_id"];
            $userDeleteStatus = $userModel->deleteItem($userParam);

            // 删除用户错误
            if (!$userDeleteStatus) {
                throw new \Exception($userModel->getError());
            }

            // 删除用户成功
            $userModel->commit();

            return success_response($userModel->getSuccessMassege(), []);
        } catch (\Exception $e) {
            // 删除账户失败错误码 - 010
            throw_strack_exception($e->getMessage(), 229010);
            $userModel->rollback();
        }
    }

    /**
     * 注销用户
     * @param $param
     * @return array
     */
    public function cancelAccount($param)
    {
        $userModel = new UserModel();
        $resData = $userModel->modifyItem($param);
        if (!$resData) {
            // 注销用户失败错误码 - 011
            throw_strack_exception($userModel->getError(), 229011);
        } else {
            return success_response($userModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 判断API请求Token有效性
     * @param $token
     */
    public function checkApiToken($token)
    {
        $decryptData = _decrypt($token);
        if (!empty($decryptData)) {
            $tokenData = json_decode($decryptData, true);
            $userModel = new UserModel();
            $param = [
                "filter" => ["id" => $tokenData["user_id"]],
                "fields" => ["id", "login_session", "token_time"]
            ];

            session('user_id', $tokenData["user_id"]);

            $userData = $userModel->findData($param);
            if (empty($userData)) {
                throw_strack_exception(L("Incorrect_Token"), 229013);
            }
            if ($userData["login_session"] == $token) {
                $checkToken = $this->checkTokenExpireTime($userData);
                if ($checkToken === "yes") {
                    // token过期错误码 - 012
                    throw_strack_exception(L("Token_Expiration"), 229012);
                } else {
                    if (!session('?api_login_session')) {
                        // api登录session不存在
                        session('api_login_session', $userData["id"]);
                    }
                }
            } else {
                throw_strack_exception(L("Incorrect_Token"), 229013);
            }
        } else {
            throw_strack_exception(L("Incorrect_Token"), 229013);
        }
    }

    /**
     * 判断Token是否过期
     * @param $userData
     * @param bool $isRebuild
     * @return String
     */
    public function checkTokenExpireTime($userData, $isRebuild = false)
    {
        $userModel = new UserModel();

        $checkTokenTime = check_token_time($userData["token_time"]);
        if ($checkTokenTime && $isRebuild) {
            $currentTime = time();
            $token = generate_api_token($userData["id"], $currentTime);
            $param = [
                "id" => $userData["id"],
                "login_session" => $token,
                "token_time" => $currentTime
            ];
            $userModel->modifyItem($param);
            return $token;
        } else if ($checkTokenTime && !$isRebuild) {
            return 'yes';
        } else {
            return $userData["login_session"];
        }
    }

    /**
     * 令牌延长时间
     * @param $param
     * @return mixed
     */
    public function renewToken($param)
    {
        if (array_key_exists("token", $param) && !empty($param["token"])) {
            $token = $param["token"];
        } else {
            // token不存在
            throw_strack_exception(L("Token_Does_Not_Exist"));
        }
        if (!session('?user_id')) {
            throw_strack_exception(L("Illegal_Operation"));
        }
        $userId = session("user_id");
        $tokenData = json_decode(_decrypt($token), true);
        if (is_array($tokenData)) {
            if (array_key_exists("user_id", $tokenData) && !empty($tokenData["user_id"])) {
                if ($userId !== $tokenData["user_id"]) {
                    throw_strack_exception(L("Incorrect_Token"), 229013);
                }
            } else {
                throw_strack_exception(L("Incorrect_Token"), 229013);
            }
            $userModel = new UserModel();
            $countUser = $userModel->where(["id" => $userId, "login_session" => $token])->count();
            if (empty($countUser)) {
                throw_strack_exception(L("Incorrect_Token"), 229013);
            } else {
                $param = [
                    "id" => $userId,
                    "token_time" => time()
                ];
                $userModel->modifyItem($param);
                $resData = [
                    "token" => $token,
                ];
                return success_response(L("Token_Refresh_Success"), $resData);
            }
        } else {
            throw_strack_exception(L("Incorrect_Token"), 229013);
        }
    }

    /**
     * 注销Token登录
     * @throws \Think\Exception
     */
    public function cancelToken()
    {
        if (!session('?user_id')) {
            throw_strack_exception(L("Illegal_Operation"));
        }

        $userId = session("user_id");

        // 清空user表对应字段 login_session、token_time
        $userModel = new UserModel();

        $userModel->where(['id' => $userId])->save([
            'login_session' => '',
            'token_time' => 0
        ]);

        // session
        session(null);
    }

    /**
     * 获取我的账户数据（登录名、姓名、昵称、邮箱、手机、头像）
     * @param $userId
     * @return array|mixed
     */
    public function getMyAccountData($userId)
    {
        $userModel = new UserModel();
        $userData = $userModel->findData([
            "fields" => "id,login_name,name,nickname,email,phone",
            "filter" => [
                "id" => $userId
            ]
        ]);

        // 获取用户头像
        $mediaService = new MediaService();
        $userData['avatar'] = $mediaService->getSpecifySizeThumbPath(['link_id' => $userData['id'], 'module_id' => 34], 'origin');
        $userData['pinyin'] = $this->pinyinClass->permalink($userData['name'], '', PINYIN_KEEP_ENGLISH);

        return $userData;
    }

    /**
     * 通过用户uuid获取用户头像
     * @param $userUUID
     * @return array|mixed|string[]
     */
    public function getUserAvatarByUUID($userUUID)
    {
        $userModel = new UserModel();
        $userData = $userModel->findData([
            "fields" => "id,name",
            "filter" => [
                "uuid" => $userUUID
            ]
        ]);

        if (!empty($userData)) {
            // 获取用户头像
            $mediaService = new MediaService();
            $userData['avatar'] = $mediaService->getSpecifySizeThumbPath(['link_id' => $userData['id'], 'module_id' => 34], 'origin');
            $userData['pinyin'] = $this->pinyinClass->permalink($userData['name'], '', PINYIN_KEEP_ENGLISH);
            return $userData;
        } else {
            return ['avatar' => '', 'pinyin' => ''];
        }
    }

    /**
     * 修改我的账户数据
     * @param $userId
     * @param $param
     * @return array
     */
    public function modifyMyAccount($userId, $param)
    {
        // 判断是否有密码修改操作
        $userModel = new UserModel();
        $allowFields = ["email", "name", "nickname", "phone"];
        $updateData = [
            "id" => $userId,
        ];
        foreach ($allowFields as $field) {
            if (array_key_exists($field, $param) && !empty($param[$field])) {
                $updateData[$field] = $param[$field];
            }
        }

        if (!empty($param["old_password"]) && !empty($param["new_password"]) && !empty($param["new_password_repeat"])) {
            if ($param["new_password"] !== $param["new_password_repeat"]) {

                // 两次密码输入不匹配错误码 - 014
                throw_strack_exception(L("Reset_User_Password_Confirm"), 229014);
            } else {
                // 判断旧密码是否输入正确
                $getOldPassword = $userModel->where(["id" => $userId])->getField("password");
                if (check_pass($param["old_password"], $getOldPassword)) {
                    $updateData["password"] = $param["new_password"];
                } else {
                    // 用户旧密码错误 - 015
                    throw_strack_exception(L("User_Old_Password_Error"), 229015);
                }
            }
        }

        // 修改用户数据
        $resData = $userModel->modifyItem($updateData);
        if (!$resData) {
            // 修改密码失败错误码 - 016
            throw_strack_exception($userModel->getError(), 229016);
        } else {
            // 返回成功数据
            return success_response($userModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 获取能被At用户列表
     * @param $projectId
     * @return array
     */
    public function getAtUserList($projectId)
    {
        $userIds = [];
        // 获取项目成员里面的用户
        $projectMemberModel = new ProjectMemberModel();
        $projectMemberData = $projectMemberModel->selectData(["filter" => ["project_id" => $projectId]]);

        if ($projectMemberData["total"] > 0) {
            foreach ($projectMemberData["rows"] as $item) {
                // 排除用户1,2
                if (!in_array($item["user_id"], ["1", "2"])) {
                    array_push($userIds, $item["user_id"]);
                }
            }
            // 组装条件
            $options = [
                "filter" => ["id" => ["IN", join(",", $userIds)]]
            ];
        } else {
            // 排除用户1,2
            $options = [
                "filter" => ["id" => ["NOT IN", "1,2"]]
            ];
        }

        // 查询用户表信息
        $userModel = new UserModel();
        $userData = $userModel->selectData($options);
        $index = 1;
        foreach ($userData["rows"] as &$item) {
            $item["abbr"] = $item["id"];
            $item["id"] = $index++;
            $item["pinyin"] = $this->pinyinClass->permalink($item['name'], '', PINYIN_KEEP_ENGLISH);
        }

        return $userData["rows"];
    }

    /**
     * 获取用户表格数据
     * @param $param
     * @return mixed
     */
    public function getUserGridData($param)
    {
        // 获取schema配置
        $viewService = new ViewService();
        $schemaFields = $viewService->getGridQuerySchemaConfig($param);

        $filter = [
            "field" => "id",
            "field_type" => 'built_in',
            "editor" => "combobox",
            "value" => "1,2",
            "condition" => "NOT IN",
            "module_code" => "user",
            "table" => string_initial_letter("user")
        ];
        array_push($schemaFields["relation_structure"]["filter"]["request"], $filter);

        // 查询关联模型数据
        $userModel = new UserModel();
        $resData = $userModel->getRelationData($schemaFields);

        return $resData;
    }

    /**
     * 获取已经激活的用户列表
     * @param $param
     * @return array
     */
    public function getActiveUserGridData($param)
    {
        $userModel = new UserModel();
        $userData = $userModel->selectData([
            "filter" => [
                'status' => "in_service",
                "id" => ["NOT IN", "1,2"]
            ],
            "fields" => "id,login_name,name,nickname"
        ]);
        return $userData;
    }

    /**
     * 获取个人信息
     * @param $userId
     * @return array
     */
    public function getUserInfo($userId)
    {
        // 获取用户信息
        $userModel = new UserModel();
        $userData = $userModel->findData([
            "filter" => ['id' => $userId],
            "fields" => "id as user_id,login_name,name,email,department_id"
        ]);

        // 组装数据
        $loginData = [
            'user_id' => $userData['user_id'],
            'login_name' => $userData['login_name'],
            'name' => $userData['name'],
            'pinyin' => $this->pinyinClass->permalink($userData['name'], '', PINYIN_KEEP_ENGLISH),
            'department' => $this->getDepartmentName($userData['department_id']),
            'ip' => get_client_ip()
        ];

        // 返回数据
        return $loginData;
    }

    /**
     * 获取部门名称
     * @param $department_id
     * @return string
     */
    public function getDepartmentName($department_id)
    {
        $departmentModel = new DepartmentModel();
        $resData = $departmentModel->findData(["filter" => ["id" => $department_id], "fields" => "name"]);
        return empty($resData) ? "" : $resData["name"];
    }

    /**
     * 获取用户单个字段
     * @param $filter
     * @param $field
     * @return array|mixed
     */
    public function getUserFindField($filter, $field)
    {
        $userModel = new UserModel();
        $resData = $userModel->findData(["filter" => $filter, "fields" => $field]);
        return empty($resData) ? [] : $resData;
    }


    /**
     * 获取找回密码请求地址
     * @param $param
     * @return array
     */
    public function getForgetLoginRequest($param)
    {
        // 判断是不是标准邮箱格式
        if (check_login_is_email($param["email"])) {
            // 查询数据库匹配当前邮箱地址的用户
            $userModel = new UserModel();
            $userData = $userModel->findData([
                "filter" => [
                    "email" => $param["email"]
                ]
            ]);
            if (!empty($userData)) {
                // 匹配成功
                $isToday = is_today($userData["last_forget"]);

                if ($isToday && $userData["forget_count"] == 3) {
                    // 每天最多找回密码三次密码错误码 - 017
                    throw_strack_exception(L("Login_Forget_Frequency_Limit"), 229017);
                } else if (time() < $userData["last_forget"] + 600) {
                    // 10分钟后才能继续查找 600秒 - 018
                    throw_strack_exception(L("Login_Forget_Frequency_Limit10"), 229018);
                } else {
                    //判断是否需要重置request_count
                    $requestCount = !$isToday ? 1 : $userData["forget_count"] + 1;
                    //url token验证码
                    $token = random_hash_keys($userData["login_name"], time());
                    //更新数据库
                    $updateData = [
                        'id' => $userData["id"],
                        'forget_count' => $requestCount,
                        'last_forget' => time(),
                        'forget_token' => $token,
                    ];

                    $modifyResult = $userModel->modifyItem($updateData);

                    if (!$modifyResult) {
                        // 修改用户找回密码token信息失败错误
                        throw_strack_exception($userModel->getError(), 229021);
                    } else {
                        // 发送找回密码邮件
                        $emailService = new EmailService();
                        $forgetEmailParam = [
                            "param" => [
                                "addressee" => $userData["email"],
                                "subject" => "找回密码"
                            ],
                            "data" => [
                                "template" => "ping",
                                "content" => [
                                    "header" => [
                                        "title" => "找回密码"
                                    ],
                                    "body" => [
                                        "text" => [
                                            "message" => [
                                                "username" => $userData["email"],
                                                "title" => "找回密码操作（有效期10分钟）",
                                                "details" => [
                                                    "type" => "text",
                                                    "content" => ""
                                                ]
                                            ],
                                            "bottom" => []
                                        ],
                                        "button" => [
                                            [
                                                "content" => generate_forget_page_url($updateData),
                                                "name" => "点击重置密码",
                                                "type" => "url"
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $emailService->directSendEmail($forgetEmailParam);
                        return success_response(L("Login_Forget_Send_Notice"));
                    }
                }
            } else {
                // 不存在的用户
                throw_strack_exception(L("Login_Forget_Email_Error"), 229019);
            }
        } else {
            // 邮件格式错误
            throw_strack_exception(L("Email_Format_Error"), 229020);
        }
    }

    /**
     * 1、验证找回密码操作是否合法
     * 2、并返回修改密码剩余时间 当时间为0时锁定
     * @param $token
     * @return array
     */
    public function verifyPassRequest($token)
    {
        $keyArr = explode(',', _decrypt($token));
        $allowReset = 'no';
        $expirationDate = 0;
        $userId = 0;
        if (count($keyArr) === 3) {
            $userId = $keyArr[0];
            $forgetTime = $keyArr[1];
            $forgetToken = $keyArr[2];
            $userModel = new UserModel();
            //首先验证时间是否大于当前时间
            if (time() < $forgetTime + 600) {
                //验证token是否合法
                $dbForgetToken = $userModel->where(['id' => $userId])->getField('forget_token');
                if ($dbForgetToken == $forgetToken) {
                    $allowReset = 'yes';
                    $expirationDate = 600 - (time() - $forgetTime);
                }
            }
        }
        return [
            'allow_reset' => $allowReset,
            'user_id' => $userId,
            'expiration_date' => $expirationDate,
        ];
    }

    /**
     * 找回密码操作 -- 提交修改密码请求
     * @param $param
     * @return array
     */
    public function modifyUserPassword($param)
    {
        //判断两次密码是否相等
        if ($param["new_password"] == $param["confirm_password"]) {
            $data = [
                'id' => $param["user_id"],
                'password' => $param["new_password"],
                'forget_token' => ""
            ];

            $userModel = new UserModel();
            $resData = $userModel->modifyItem($data);
            if (!$resData) {
                // 修改用户密码错误码 002
                throw_strack_exception($userModel->getError(), 229021);
            } else {
                // 返回成功数据
                return success_response(L("Login_Reset_User_Password_SC"), $resData);
            }
        } else {
            //两次密码不想等
            throw_strack_exception(L("Two_Password_Mismatches"), 229022);
        }
    }

    /**
     * 保存更新面板设置
     * @param $param
     * @return array
     */
    public function saveDialogSetting($param)
    {
        return $this->modifyUserConfig($param);
    }

    /**
     * 更新用户个人偏好设置信息
     * @param $param
     * @param $message
     * @param bool $isAppend
     * @return array
     */
    public function savePreference($param, $message, $isAppend = false)
    {
        $userConfigModel = new UserConfigModel();
        $userConfigData = $userConfigModel->findData([
            'filter' => ['user_id' => $param['user_id'], 'page' => $param['page'], 'type' => $param['type']]
        ]);

        if (!empty($userConfigData)) {
            // 存在更新操作
            if ($isAppend) {
                $oldJsonData = $userConfigData['config'];
                $oldJsonData[$param['page']] = $param;
                $config = $oldJsonData;
            } else {
                $config = $param;
            }
            $modifyData = [
                'id' => $userConfigData['id'],
                'config' => $config
            ];
            $resData = $userConfigModel->modifyItem($modifyData);
        } else {
            // 不存在新增操作
            if ($isAppend) {
                $config = [$param['page'] => $param];
            } else {
                $config = $param;
            }
            $data = [
                'user_id' => $param['user_id'],
                'type' => $param['type'],
                'page' => $param['page'],
                'config' => $config,
            ];
            $resData = $userConfigModel->addItem($data);
        }
        if ($param['type'] == "system") {
            //如果为个人系统设置则更新
            $lang = get_lang_type($param['lang']);
            cookie('think_language', $lang);
        }
        if (!$resData) {
            // 更新用户个人偏好设置失败错误码 - 021
            throw_strack_exception($userConfigModel->getError(), 229021);
        } else {
            // 返回成功数据
            return success_response($message, $resData);
        }
    }

    /**
     * 获取用户条件过滤配置
     * @param $page
     * @return string
     */
    public function getUserFilterBarConfig($page)
    {
        $userId = session("user_id");
        $userConfigModel = new UserConfigModel();
        $userConfigData = $userConfigModel->findData([
                'filter' => [
                    'user_id' => $userId,
                    'page' => $page,
                    'type' => 'filter_stick'
                ],
                'fields' => 'config']
        );
        if (!empty($userConfigData) && array_key_exists("stick", $userConfigData["config"])) {
            $filterBarAllowShow = $userConfigData["config"]["stick"];
        } else {
            $filterBarAllowShow = "no";
        }

        // 返回数据
        return $filterBarAllowShow;
    }

    /**
     * 修改用户配置
     * @param $param
     * @return array
     */
    public function modifyUserConfig($param)
    {
        $templateId = array_key_exists('template_id', $param) ? $param['template_id'] : 0;
        $userConfigModel = new UserConfigModel();
        $userConfigData = $userConfigModel->findData(['filter' => [
            'page' => $param['page'],
            'type' => $param['type'],
            'template_id' => $templateId,
            'user_id' => session('user_id')
        ]]);
        if (!empty($userConfigData)) {
            $updateData = [
                'id' => $userConfigData['id'],
                'config' => $param['config']
            ];
            $resData = $userConfigModel->modifyItem($updateData);
        } else {
            $param["template_id"] = $templateId;
            $resData = $userConfigModel->addItem($param);
        }
        if (!$resData) {
            // 更新面板设置失败错误码 - 018
            throw_strack_exception($userConfigModel->getError(), 229020);
        } else {
            // 返回成功数据
            return success_response($userConfigModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 获取指定项目元的用户列表数据
     * @param $filter
     * @return array
     */
    public function getProjectMemberData($filter)
    {
        return []; // TODO 后台配置控制是否启用用户列表

        $projectMemberModel = new ProjectMemberModel();
        $projectMemberData = $projectMemberModel->selectData(["filter" => $filter, "fields" => "user_id"]);
        $userIds = [];
        if (!empty($projectMemberData)) {
            $userIds = array_column($projectMemberData["rows"], "user_id");
        }
        return $userIds;
    }

    /**
     * 获取水平关联源数据
     * @param $param
     * @param $searchValue
     * @param $mode
     * @return mixed
     */
    public function getHRelationSourceData($param, $searchValue, $mode)
    {

        $linkData = $param["link_data"];
        if ($mode === "all") {

            $userIds = $this->getProjectMemberData(["project_id" => $param["project_id"]]);

            if (!empty($userIds)) {
                $horizontalService = new HorizontalService();
                $dstLinkIds = $horizontalService->getModuleRelationIds([
                    "src_link_id" => $param["src_link_id"],
                    "src_module_id" => $param["src_module_id"],
                    "dst_module_id" => $param["dst_module_id"],
                ], "dst_link_id");

                $queryIds = [];
                if (!empty($dstLinkIds)) {
                    foreach ($userIds as $userItem) {
                        if (!in_array($userItem, $dstLinkIds)) {
                            array_push($queryIds, $userItem);
                        }
                    }
                } else {
                    $queryIds = $userIds;
                }
                $filter = [
                    "id" => ["IN", join(",", $queryIds)],
                    "status" => "in_service"
                ];

            } else {
                array_push($linkData, "1", "2");
                $filter = [
                    "id" => ["NOT IN", join(",", $linkData)],
                    "status" => "in_service"
                ];
            }
        } else {
            $filter = [
                "id" => ["IN", join(",", $linkData)],
                "status" => "in_service"
            ];
        }

        // 有额外过滤条件
        if (!empty($searchValue)) {
            $filter = [
                $filter,
                [
                    "name" => ["LIKE", "%{$searchValue}%"],
                    "nickname" => ["LIKE", "%{$searchValue}%"],
                    "_logic" => "OR"
                ],
                "_logic" => "AND"
            ];
        }

        $option = [
            "filter" => $filter,
            "fields" => "id,name,nickname as code",
        ];

        if (array_key_exists("pagination", $param)) {
            $option["page"] = [$param["pagination"]["page_number"], $param["pagination"]["page_size"]];
        }

        $userModel = new UserModel();
        $horizontalRelationData = $userModel->selectData($option);

        return $horizontalRelationData;
    }

    /**
     * 注册用户
     * @param $param
     * @return array
     */
    public function registerUser($param)
    {
        // 1.验证短信验证码
        $smsModel = new SmsModel();
        $smsData = $smsModel->field("validate_code,created,active,deadline")->where(["id" => $param["sms_id"]])->find();

        if (!empty($smsData) && ($smsData["active"] === "no" || $smsData["validate_code"] !== $param["sms_verify_code"])) {
            // 无效短信验证码
            throw_strack_exception(L("Invalid_SMS_Verification_Code"), 404);
        }

        if ($smsData["deadline"] < time()) {
            // 短信验证码已经过期
            throw_strack_exception(L("SMS_Verification_Code_Expiration"), 404);
        }

        // 获取当前系统模式是否需要同时克隆项目
        $optionsService = new OptionsService();
        $defaultModeConfig = $optionsService->getSystemModeConfig(false);

        // 2.写入用户信息，默认角色 role_id $defaultModeConfig["default_role"]
        $param["nickname"] = $param["login_name"];
        $param["name"] = $param["login_name"];
        $addUserParam = [
            "user" => $param,
            "role_user" => [
                "role_id" => $defaultModeConfig["default_role"],
                "field_type" => "built_in"
            ]
        ];
        $userStatus = $this->addAccount($addUserParam);

        if ($userStatus["status"] === 200) {
            // 写入session，保存项目时使用
            $userId = $userStatus["data"]["id"];
            session("user_id", $userId);
            // 3.是否需要指定的拷贝项目
            if ($defaultModeConfig["open_clone_project"]) {
                $projectService = new ProjectService();
                $templateService = new TemplateService();
                $templateId = $templateService->getProjectTemplateID($defaultModeConfig["default_clone_project"]);
                $addProjectParam = [
                    "template_id" => $templateId,
                    "info" => [
                        "name" => $param["login_name"],
                        "code" => $param["login_name"],
                        "status_id" => 0,
                        "public" => $defaultModeConfig["default_project_public"],
                        "group_open" => 0
                    ],
                    "disk" => [],
                    "has_media" => "",
                    "media" => []
                ];
                $projectStatus = $projectService->addProject($addProjectParam);
                // 清空session
                session("user_id", null);
                if ($projectStatus["status"] === 200) {
                    return success_response(L("Register_Success"), $userStatus["data"]);
                } else {
                    // 项目拷贝失败，删除用户，方便下次注册
                    $userModel = new UserModel();
                    $userModel->deleteItem(["id" => $userId]);
                    $roleUserModel = new RoleUserModel();
                    $roleUserModel->deleteItem(["user_id" => $userId]);
                    throw_strack_exception(L("Register_Failed"));
                }
            }
        } else {
            return $userStatus;
        }
    }

    /**
     * 用户绑定第三方登录信息
     * @param $userId
     * @param $idField
     * @param $openId
     * @return array|bool|mixed
     */
    public function loginBind($userId, $idField, $openId)
    {
        if (empty($userId) || empty($openId)) {
            return false;
        }

        $userModel = new UserModel();
        $userData = $userModel->findData([
            "filter" => [
                "id" => $userId
            ]
        ]);

        if (empty($userData) || !empty($userData[$idField])) {
            return false;
        }

        // 更新用户信息
        $updateData = [
            'id' => $userId,
            $idField => $openId,
        ];

        $resData = $userModel->modifyItem($updateData);
        return $resData;
    }

    /**
     * 解除第三方登录绑定
     * @param $param
     * @return array
     */
    public function cancelBind($param)
    {
        // 验证数据参数
        $login_type = array_key_exists("login_type", $param) ? $param["login_type"] : '';
        $userModel = new UserModel();
        switch ($login_type) {
            case 'qq':
                // 解除QQ 登录方式
                $updateData = ['id' => session('user_id'), 'qq_openid' => ''];
                $resData = $userModel->modifyItem($updateData);
                break;
            case 'strack':
                // 解除strack union 登录方式
                $updateData = ['id' => session('user_id'), 'strack_union_id' => 0];
                $resData = $userModel->modifyItem($updateData);
                break;
            default:
                // 默认系统用户验证登录
                $resData = false;
                break;
        }

        if (!$resData) {
            // 解除绑定失败错误码 - 018
            throw_strack_exception($userModel->getError(), 229220);
        } else {
            // 返回成功数据
            return success_response(L("unBind_Login_Sc"), $resData);
        }
    }

    /**
     * 获取我负责部门与人员树列表
     * @param $userId
     * @return array
     */
    public function getDepartmentUserTreeList($userId)
    {
        /**
         * 获取所有部门
         */
        $options = [
            'fields' => 'id,name as text,code,parent_id'
        ];
        $departmentModel = new DepartmentModel();
        $departmentData = $departmentModel->selectData($options);

        //获取用户列表,排除离职员工
        $reportService = new ReportService();
        $userData = $reportService->getUserList(['user_ids' => '']);

        foreach ($userData as $userItem) {
            $departmentData['rows'][] = [
                'id' => "u_{$userItem['id']}",
                'text' => $userItem['name'],
                'parent_id' => $userItem['department_id']
            ];
        }

        //生成树
        $tree = new \Org\Util\Tree("id", "parent_id", "children");
        $tree->load($departmentData['rows']);
        $departmentTreeData = $tree->DeepTree();
        return $departmentTreeData;
    }

    /**
     * 根据部门ids 获取用户按部门分组列表
     * @param $departmentIdArr
     * @return array
     */
    public function userGroupByDepartment($departmentIdArr)
    {
        //获取所有部门下面所有用户
        $userModel = new UserModel();
        $userData = $userModel->field('id,name,department_id')
            ->where(['department_id' => ['IN', join(',', $departmentIdArr)]])
            ->select();
        //给用户按部门分组
        $departmentUserList = [];
        foreach ($userData as $item) {
            $departmentId = $item['department_id'];
            $departmentUserList[$departmentId][] = [
                'id' => $item['id'],
                'name' => $item['name']
            ];
        }
        return $departmentUserList;
    }

}

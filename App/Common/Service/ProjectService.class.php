<?php
// +----------------------------------------------------------------------
// | Project 项目服务
// +----------------------------------------------------------------------
// | 主要服务于Project数据处理
// +----------------------------------------------------------------------
// | 错误编码头 221xxx
// +----------------------------------------------------------------------

namespace Common\Service;

use Common\Model\BaseModel;
use Common\Model\DiskModel;
use Common\Model\EntityModel;
use Common\Model\FieldModel;
use Common\Model\HorizontalModel;
use Common\Model\ModuleModel;
use Common\Model\ProjectDiskModel;
use Common\Model\ProjectMemberModel;
use Common\Model\ProjectModel;
use Common\Model\ProjectTemplateModel;
use Common\Model\RoleUserModel;
use Common\Model\StatusModel;
use Common\Model\UserModel;
use Common\Model\VariableModel;

class ProjectService
{

    /**
     * 添加项目
     * @param $param
     * @return array
     */
    public function addProject($param)
    {
        $projectModel = new ProjectModel();
        $projectModel->startTrans();
        try {

            // 保存项目信息
            if (empty($param['info']["start_time"])) {
                unset($param['info']["start_time"]);
            }
            if (empty($param['info']["end_time"])) {
                unset($param['info']["end_time"]);
            }
            $projectData = $projectModel->addItem($param['info']);
            if (!$projectData) {
                throw new \Exception($projectModel->getError());
            }

            // 上传缩略图
            if ($param["has_media"] > 0) {
                $param["media"]["link_id"] = $projectData["id"];
                $mediaService = new MediaService();
                $mediaService->saveMediaData($param["media"]);
            }

            // 选择模板
            $projectTemplateModel = new ProjectTemplateModel();
            $templateData = $projectTemplateModel->findData([
                'filter' => ['id' => $param['template_id']],
                'fields' => 'name,code,schema_id,project_id,config'
            ]);

            $templateSaveData['project_id'] = $projectData['id'];
            $templateSaveData['name'] = $projectData['name'];
            $templateSaveData['code'] = $projectData['code'];
            $templateSaveData['schema_id'] = $templateData['schema_id'];
            $templateSaveData['config'] = $templateData['config'];

            $projectTemplateData = $projectTemplateModel->addItem($templateSaveData);
            if (!$projectTemplateData) {
                throw new \Exception($projectTemplateModel->getError());
            }

            // 如果选择的模版为项目模版
            if ($templateData["project_id"] > 0) {
                // 拷贝当前项目下的自定义字段
                $variableService = new VariableService();
                $variableList = $variableService->getProjectCustomFields($templateData["project_id"]);
                if (!empty($variableList)) {
                    $variableModel = new VariableModel();
                    foreach ($variableList as &$item) {
                        $item["project_id"] = $projectData['id'];
                        $item["config"]["project_id"] = $projectData['id'];
                        $variableData = $variableModel->addItem($item);
                        if (!$variableData) {
                            throw new \Exception($variableModel->getError());
                        }
                    }
                }

                // 拷贝当前项目下的默认视图
                $viewService = new ViewService();
                $viewListData = $viewService->getViewListData([
                    "module_code" => "view_default",
                    "filter" => [
                        "filter" => ["project_id" => $templateData["project_id"]],
                        "fields" => "name,code,page,config"
                    ]]);
                if ($viewListData["total"] > 0) {
                    foreach ($viewListData["rows"] as $item) {
                        $item["project_id"] = $projectData['id'];
                        $viewService->saveViewDefault($item);
                    }
                }
            }

            // 拷贝团队
            if ($param["info"]["group_open"] > 0) {
                $projectMemberModel = new ProjectMemberModel();
                $projectMemberData = $projectMemberModel->selectData(["filter" => ["project_id" => $templateData["project_id"]]]);
                if ($projectMemberData["total"] > 0) {
                    foreach ($projectMemberData["rows"] as $item) {
                        $saveProjectMember = [
                            'project_id' => $projectData["id"],
                            'role_id' => $item["role_id"],
                            'user_id' => $item["user_id"],
                        ];
                        $memberData = $projectMemberModel->addItem($saveProjectMember);
                        if (!$memberData) {
                            throw new \Exception($projectMemberModel->getError());
                        }
                    }
                }
            }

            // 项目磁盘设置
            $projectDiskModel = new ProjectDiskModel();
            $projectDiskData = $projectDiskModel->addItem([
                'config' => $param['disk'],
                'project_id' => $projectData['id']
            ]);

            if (!$projectDiskData) {
                throw new \Exception($projectDiskModel->getError());
            }

            // 把当前项目创建者默认写入系统团队
            $this->appendProjectMember($projectData['id'], session('user_id'));

            $projectModel->commit();
            // 返回成功数据
            return success_response($projectModel->getSuccessMassege());
        } catch (\Exception $e) {
            $projectModel->rollback();
            // 添加项目失败错误码 - 001
            throw_strack_exception($e->getMessage(), 221001);
        }
    }

    /**
     * 修改项目
     * @param $param
     * @return array
     */
    public function modifyProject($param)
    {
        $projectModel = new ProjectModel();
        $resData = $projectModel->modifyItem($param);
        if (!$resData) {
            // 修改项目失败错误码 - 002
            throw_strack_exception($projectModel->getError(), 221002);
        } else {
            return success_response($projectModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 删除项目
     * @param $param
     * @return array
     */
    public function deleteProject($param)
    {
        $projectModel = new ProjectModel();
        $resData = $projectModel->deleteItem($param);
        if (!$resData) {
            // 删除项目失败错误码 - 003
            throw_strack_exception($projectModel->getError(), 221003);
        } else {
            $relationFilter = ["project_id" => $param["id"]];
            // 查询出当前数据库中的所有表
            $fieldModel = new FieldModel();
            $tables = $fieldModel->getTables();

            $tableList = [];
            foreach ($tables as $tableItem) {
                if ($tableItem !== "phinxlog") {
                    // 将存在project_id的表放到列表中
                    $fields = $fieldModel->checkTableField($tableItem, "project_id");
                    if ($fields) {
                        $table = str_replace("strack_", "", $tableItem);
                        array_push($tableList, $table);
                    }
                }
            }

            // 删除相应的数据
            foreach ($tableList as $table) {
                $modelName = '\\Common\\Model\\' . string_initial_letter($table) . 'Model';
                $modelObj = new $modelName();
                $modelObj->deleteItem($relationFilter);
            }
            // 返回成功数据
            return success_response($projectModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 获取当前模块自身基础字段
     * @param $param
     * @param bool $isGroup
     * @return array
     */
    public function getModuleBaseColumns($param, $isGroup = false)
    {
        $fieldShowList = [];

        // 根据 module_id 获取 module_code
        $moduleModel = new ModuleModel();
        $moduleData = $moduleModel->findData(['filter' => ['id' => $param["module_id"]], 'fields' => 'id as module_id,code,type']);


        // 判断当前template 是否有项目id
        $projectTemplateModel = new ProjectTemplateModel();
        $projectId = $projectTemplateModel->where(["id" => $param['template_id']])->getField("project_id");
        $projectId = !empty($projectId) ? $projectId : 0;

        $schemaService = new SchemaService();
        $fieldConfigList = $schemaService->getTableFieldConfig($moduleData, $projectId);

        // 固定字段：循环判断 - 如果当前字段为显示状态，则放到新数组
        foreach ($fieldConfigList['built_in'] as $builtInKey => $builtInFieldItem) {
            if ($builtInFieldItem['show'] === "yes") {
                $builtInFieldItem['belong_table'] = L(string_initial_letter($builtInFieldItem['module']));
                // 处理语言包
                $builtInFieldItem['lang'] = L($builtInFieldItem['lang']);
                array_push($fieldShowList, $builtInFieldItem);
            }
        }

        // 自定义字段
        foreach ($fieldConfigList['custom'] as $customKey => $customFieldItem) {
            // 处理语言包
            $customFieldItem['belong_table'] = L("Custom");
            array_push($fieldShowList, $customFieldItem);
        }

        // 分组重新赋值id
        if ($isGroup) {
            foreach ($fieldShowList as &$fieldItem) {
                $fieldItem["field_group_id"] = "{$fieldItem["module_code"]}_{$fieldItem["id"]}";
            }
        }

        return $fieldShowList;
    }

    /**
     * 获取模块分组字段
     * @param $param
     * @param bool $isGroup
     * @return array
     */
    public function getModuleBaseGroupColumns($param, $isGroup = false)
    {

        // 根据 module_id 获取 module_code
        $moduleModel = new ModuleModel();
        $moduleData = $moduleModel->findData(['filter' => ['id' => $param["module_id"]], 'fields' => 'id as module_id,code,type']);

        // 判断当前template 是否有项目id
        $projectTemplateModel = new ProjectTemplateModel();
        $projectId = $projectTemplateModel->where(["id" => $param['template_id']])->getField("project_id");
        $projectId = !empty($projectId) ? $projectId : 0;

        $schemaService = new SchemaService();
        $fieldConfigList = $schemaService->getTableFieldConfig($moduleData, $projectId);

        $fieldShowList = [];
        $moduleTableList = [];
        foreach ($fieldConfigList['built_in'] as $builtInFieldItem) {
            if (array_key_exists("is_foreign_key", $builtInFieldItem) && $builtInFieldItem["is_foreign_key"] == "yes") {
                if (array_key_exists("foreign_key_map", $builtInFieldItem) && !empty($builtInFieldItem["foreign_key_map"])) {
                    $moduleTable = str_replace("_id", "", $builtInFieldItem["foreign_key_map"]);
                } else {
                    $moduleTable = str_replace("_id", "", $builtInFieldItem["fields"]);
                }
                if (($moduleTable === "entity_module" && $moduleData["code"] != "base") || !in_array($moduleTable, ["module", "step"])) {
                    array_push($moduleTableList, $moduleTable);
                }
            } else {
                if ($builtInFieldItem["show"] === "yes") {
                    // 处理语言包
                    $builtInFieldItem['lang'] = L($builtInFieldItem['lang']);
                    $builtInFieldItem['belong_table'] = L(string_initial_letter($builtInFieldItem['module']));
                    array_push($fieldShowList, $builtInFieldItem);
                }
            }
        }

        // 自定义字段
        foreach ($fieldConfigList['custom'] as $customKey => $customFieldItem) {
            // 处理语言包
            $customFieldItem['belong_table'] = L("Custom");
            array_push($fieldShowList, $customFieldItem);
        }

        // 如果为工序字段，显示缩略图
        if ($param["category"] === "step_fields") {
            $moduleMediaData = $moduleModel->findData(['filter' => ['code' => 'media'], 'fields' => 'id as module_id,code,type']);
            $mediaFieldsConfig = $schemaService->getTableFieldConfig($moduleMediaData, $projectId);
            foreach ($mediaFieldsConfig['built_in'] as $mediaFieldsItem) {
                if ($mediaFieldsItem["show"] === "yes") {
                    $mediaFieldsItem['lang'] = L($mediaFieldsItem['lang']);
                    $mediaFieldsItem['belong_table'] = L(string_initial_letter($mediaFieldsItem['module']));
                    array_push($fieldShowList, $mediaFieldsItem);
                }
            }
        }

        $belongFieldShowList = $this->getBelongToFieldsConfig($moduleTableList, $projectId);

        $fieldConfig = array_merge($fieldShowList, $belongFieldShowList);

        // 分组重新赋值id
        if ($isGroup) {
            foreach ($fieldConfig as &$fieldItem) {
                $fieldItem["field_group_id"] = "{$fieldItem["module_code"]}_{$fieldItem["id"]}";
            }
        }

        return $fieldConfig;
    }

    /**
     * 获取belong 字段配置
     * @param $moduleTableList
     * @param $projectId
     * @return array
     */
    public function getBelongToFieldsConfig($moduleTableList, $projectId)
    {
        // 获取module code 字典数据
        $schemaService = new SchemaService();
        $moduleMapData = $schemaService->getModuleMapData("code");

        $belongFieldShowList = [];

        foreach ($moduleTableList as $item) {
            if (!in_array($item, ["entity", "parent", "entity_module"])) {
                $moduleData = $moduleMapData[$item];
                $moduleData["module_id"] = $moduleData["id"];
                $fieldConfigList = $schemaService->getTableFieldConfig($moduleData, $projectId);
                // 固定字段
                foreach ($fieldConfigList["built_in"] as $builtInFieldItem) {
                    if ($builtInFieldItem["show"] === "yes") {
                        // 处理语言包
                        $builtInFieldItem['lang'] = L($builtInFieldItem['lang']);
                        $builtInFieldItem['belong_table'] = L(string_initial_letter($builtInFieldItem['module']));
                        array_push($belongFieldShowList, $builtInFieldItem);
                    }
                }

                // 自定义字段
                foreach ($fieldConfigList['custom'] as $customKey => $customFieldItem) {
                    // 处理语言包
                    $customFieldItem['belong_table'] = L("Custom");
                    array_push($belongFieldShowList, $customFieldItem);
                }
            }
        }

        return $belongFieldShowList;

    }

    /**
     * 获取模板数据列表
     * @param $param
     * @return array
     */
    public function getTemplateDataList($param)
    {
        // 获取schema_id
        $projectTemplateModel = new ProjectTemplateModel();
        $projectTemplateData = $projectTemplateModel->findData(['filter' => ['id' => $param['template_id']], 'fields' => 'schema_id']);

        // 获取当前关联模型数据字段
        $viewService = new ViewService();
        $schemaFieldConfig = $viewService->getSchemaConfig($param, $projectTemplateData['schema_id'], "view");

        $fieldDataList = [];

        foreach ($schemaFieldConfig["field_clean_data"]["field_auth_config"]['sort_list'] as $key => $item) {
            // 固定字段
            foreach ($item['built_in']['fields'] as $builtInFieldItem) {
                $builtInFieldItem['belong_table'] = $key;
                $builtInFieldItem['lang'] = L($builtInFieldItem['lang']);
                array_push($fieldDataList, $builtInFieldItem);
            }

            // 自定义字段
            foreach ($item['custom']['fields'] as $customFieldItem) {
                $customFieldItem['belong_table'] = $key;
                $customFieldItem['lang'] = L($customFieldItem['lang'] . "_Custom");
                array_push($fieldDataList, $customFieldItem);
            }
        }

        return $fieldDataList;
    }

    /**
     * 获取当前关联模型的字段
     * @param $param
     * @return array
     */
    public function getModuleRelationColumns($param)
    {
        // 获取schema_id
        $projectTemplateModel = new ProjectTemplateModel();
        $projectTemplateData = $projectTemplateModel->findData(['filter' => ['id' => $param['template_id']], 'fields' => 'schema_id']);

        $viewService = new ViewService();
        $schemaFieldConfig = $viewService->getSchemaConfig($param, $projectTemplateData['schema_id'], "view");

        $fieldList = [];
        foreach ($schemaFieldConfig["field_clean_data"]["field_auth_config"]['show_list'] as $key => $item) {
            // 固定字段
            foreach ($item['built_in']['fields'] as $builtInFieldItem) {
                $builtInFieldItem['belong_table'] = $key;
                array_push($fieldList, $builtInFieldItem);
            }

            // 自定义字段
            foreach ($item['custom']['fields'] as $customFieldItem) {
                $customFieldItem['belong_table'] = $key;
                array_push($fieldList, $customFieldItem);
            }
        }

        return $fieldList;
    }

    /**
     * 获取项目内置模板
     * 条件：内置模板为项目id = 0
     * @return array|bool
     */
    public function getProjectBuiltinTemplateList()
    {
        $projectTemplateModel = new ProjectTemplateModel();
        $options = [
            'filter' => ['project_id' => 0],
            'fields' => 'id as project_template_id,name,code,project_id'
        ];
        $resData = $projectTemplateModel->selectData($options);
        return $resData['rows'];
    }

    /**
     * 获取项目模板数据
     * @param $projectId
     * @return array|mixed
     */
    public function getTemplateData($projectId)
    {
        $projectTemplateModel = new ProjectTemplateModel();
        $options = [
            'filter' => ['project_id' => $projectId],
            'fields' => 'id,name,code,project_id'
        ];
        $resData = $projectTemplateModel->findData($options);
        return $resData;
    }

    /**
     * 验证项目ID并返回子菜单设置
     * @param $projectId
     */
    public function verifyProject($projectId)
    {

    }


    /**
     * 获取项目列表
     * @param $filter
     * @return array
     * @throws \Ws\Http\Exception
     */
    public function getAdminProjectList($filter)
    {
        $projectModel = new ProjectModel();
        $options = [
            'filter' => $filter,
            'fields' => 'id,name,code',
            'order' => 'created DESC'
        ];
        // 获取项目图片
        $moduleId = C("MODULE_ID")["project"];
        $mediaService = new MediaService();
        $resData = $projectModel->selectData($options);
        foreach ($resData["rows"] as &$item) {
            $item['thumb'] = $mediaService->getSpecifySizeThumbPath(['link_id' => $item["id"], 'module_id' => $moduleId], '250x140');
        }

        return $resData;
    }

    /**
     * 获取获取
     * @param $userId
     * @return mixed
     */
    public function getProjectTeamDataByUser($userId)
    {
        $projectMember = new ProjectMemberModel();
        $projectMemberData = $projectMember->field("project_id,role_id")->where(["user_id" => $userId])->select();
        return $projectMemberData;
    }

    /**
     * 获取项目id对应的项目名称
     * @param $idArr
     * @return mixed
     */
    public function getProjectIdList($idArr)
    {
        $projectModel = new ProjectModel();
        $projectData = $projectModel->field("id,name")
            ->where(["id" => ["in", join(',', $idArr)]])
            ->select();
        $proArr = [];
        foreach ($projectData as $item) {
            $proArr[$item['id']] = $item['name'];
        }
        return $proArr;
    }

    /**
     * 获取跟我相关项目IDS
     * @param $userId
     * @return array
     */
    protected function getRelevantToMeProjectIds($userId)
    {
        // 查询水平关联
        $moduleIds = C('MODULE_ID');
        $horizontalModel = new HorizontalModel();
        $filter = [
            'dst_link_id' => $userId,
            'dst_module_id' => $moduleIds['user'],
        ];

        $relationData = $horizontalModel->field('src_link_id,src_module_id')
            ->where($filter)
            ->select();

        $entityIds = [];
        $baseIds = [];
        foreach ($relationData as $item) {
            switch ($item['src_module_id']) {
                case $moduleIds['base']:
                    if (!in_array($item['src_link_id'], $baseIds)) {
                        $baseIds[] = $item['src_link_id'];
                    }
                    break;
                case $moduleIds['entity']:
                    if (!in_array($item['src_link_id'], $entityIds)) {
                        $entityIds[] = $item['src_link_id'];
                    }
                    break;
            }
        }

        $projectIds = [];

        // 获取任务project id列表
        $baseModel = new BaseModel();
        $baseProjectIdData = $baseModel->distinct(true)
            ->field('project_id')
            ->where(['id' => ['IN', join(',', $baseIds)]])
            ->select();

        foreach ($baseProjectIdData as $baseProjectIdItem) {
            if (!in_array($baseProjectIdItem['project_id'], $projectIds)) {
                $projectIds[] = $baseProjectIdItem['project_id'];
            }
        }

        // 获取实体project id列表
        $entityModel = new EntityModel();
        $entityProjectIdData = $entityModel->distinct(true)
            ->field('project_id')
            ->where(['id' => ['IN', join(',', $entityIds)]])
            ->select();

        foreach ($entityProjectIdData as $entityProjectIdItem) {
            if (!in_array($entityProjectIdItem['project_id'], $projectIds)) {
                $projectIds[] = $entityProjectIdItem['project_id'];
            }
        }

        // 创建者project id列表
        $projectModel = new ProjectModel();
        $createdByProjectId = $projectModel->field('id')->where(['created_by' => $userId])->select();

        foreach ($createdByProjectId as $item) {
            if (!in_array($item['id'], $projectIds)) {
                $projectIds[] = $item['id'];
            }
        }

        return $projectIds;
    }

    /**
     * 获取项目允许访问必须过滤条件
     * @param $userId
     * @return array
     */
    protected function getProjectAccessRequestFilter($userId)
    {
        $projectMemberData = $this->getProjectTeamDataByUser($userId);
        $filter = [
            [
                'project.public' => 'yes'
            ],
            [
                'project.public' => 'no',
                'project.created_by' => $userId
            ]
        ];
        if (!empty($projectMemberData)) {
            $projectIds = array_column($projectMemberData, "project_id");
            array_push($filter, [
                'project.public' => 'no',
                'project.id' => ["IN", join(",", $projectIds)]
            ]);
        }
        $filter["_logic"] = "or";
        return $filter;
    }

    /**
     * 获取项目列表（过滤参数，时间过滤、状态）
     * @param $param
     * @return array
     */
    public function getProjectList($param)
    {
        $filter = [];
        // 处理状态查询
        if (!empty($param["project_status"])) {
            $statusModel = new StatusModel();
            $statusData = $statusModel->selectData([
                "filter" => ["correspond" => ["IN", $param["project_status"]]],
                "fields" => "id",
            ]);

            $statusIds = [];
            foreach ($statusData["rows"] as $item) {
                array_push($statusIds, $item["id"]);
            }
            $filter["project.status_id"] = ["IN", join(",", $statusIds)];
        }

        // 处理名称查询
        if (!empty($param["name"])) {
            $userModel = new UserModel();
            $userData = $userModel->selectData(["filter" => ["name" => ["LIKE", "%" . $param["name"] . "%"]], "fields" => "id"]);
            $userIds = [];
            if ($userData["total"] > 0) {
                foreach ($userData["rows"] as $item) {
                    array_push($userIds, $item["id"]);
                }
                $filter[] = [
                    "project.created_by" => ["IN", join(",", $userIds)],
                    "project.name" => ["LIKE", "%" . $param["name"] . "%"],
                    "_logic" => "OR"
                ];
            } else {
                $filter = ["project.name" => ["LIKE", "%" . $param["name"] . "%"]];
            }
        }

        // 处理时间查询
        if (!empty($param["project_time"])) {
            $dateOptions = transfer_time_data($param["project_time"]);
            $filter["project.created"] = $dateOptions;
        }

        // 处理跟我相关项目查询
        if (!empty($param["project_belong"]) && $param["project_belong"] === "my") {
            $projectIds = $this->getRelevantToMeProjectIds(session('user_id'));
            $filter["project.id"] = ["IN", join(",", $projectIds)];
        }

        // 添加项目团队过滤条件
        $currentUserId = fill_created_by();
        if (!in_array($currentUserId, [1, 2])) {
            $filter["_complex"] = $this->getProjectAccessRequestFilter($currentUserId);
        }

        // 项目信息
        $projectModel = new ProjectModel();

        $total = $projectModel->alias("project")
            ->where($filter)
            ->count();


        $projectListData = $projectModel
            ->alias("project")
            ->join('LEFT JOIN strack_status status ON status.id = project.status_id')
            ->join('LEFT JOIN strack_user user ON user.id = project.created_by')
            ->join('LEFT JOIN strack_media media ON media.link_id = project.id AND media.module_id = 20')
            ->where($filter)
            ->field("
                project.id,
                project.name,
                status.name as status_name,
                status.color as status_color,
                user.name as created_by,
                media.thumb as thumb
            ")
            ->select();

        // 获取完成状态的IDS
        $statusService = new StatusService();
        $doneStatusIds = $statusService->getCorrespondStatusIds("done");

        // 项目完成进度，进度分类为 done的状态为完成
        $entityModel = new EntityModel();
        $baseModel = new BaseModel();
        foreach ($projectListData as &$projectItem) {
            // 查询entity
            $entityTotal = $entityModel->where(['project_id' => $projectItem['id']])->count();
            $entityDoneTotal = $entityModel->where(['project_id' => $projectItem['id'], 'status_id' => ['IN', join(',', $doneStatusIds)]])->count();
            // 查询task
            $baseTotal = $baseModel->where(['project_id' => $projectItem['id']])->count();
            $baseDoneTotal = $baseModel->where(['project_id' => $projectItem['id'], 'status_id' => ['IN', join(',', $doneStatusIds)]])->count();

            $totalNumber = $entityTotal + $baseTotal;
            $totalDoneNumber = $entityDoneTotal + $baseDoneTotal;

            $projectItem['progress'] = $totalNumber > 0 ? number_format(100 * ($totalDoneNumber / $totalNumber), 2) : 0;
        }

        return ["total" => $total, "rows" => $projectListData];
    }

    /**
     * 获取跟当前指定用户相关的项目列表
     * @param $userId
     * @return mixed
     */
    public function getProjectListOfMy($userId)
    {

        // 跟我相关的项目或者我创建的
        $projectIds = $this->getRelevantToMeProjectIds($userId);
        if (!empty($projectIds)) {
            $filter = ['id' => ["IN", join(",", $projectIds)]];
        } else {
            $filter = ['id' => 0];
        }

        $filter['created_by'] = $userId;
        $filter['_logic'] = 'or';

        $projectModel = new ProjectModel();
        $projectData = $projectModel->field('id,name')->where($filter)->select();
        return $projectData;
    }

    /**
     * 获取项目状态Combobox下拉列表
     * @param $param
     * @return array
     */
    public function getProjectStatusCombobox($param)
    {
        $param["module_code"] = "project";
        $param["category"] = "status";
        $templateService = new TemplateService();
        $templateStatusConfig = $templateService->getTemplateConfig($param);

        $statusModel = new StatusModel();
        $statusList = [];
        foreach ($templateStatusConfig as $templateStatusItem) {
            $statusData = $statusModel->field("id,name")->where(["id" => $templateStatusItem["id"]])->find();
            array_push($statusList, $statusData);
        }
        return $statusList;
    }

    /**
     * 获取项目详情数据
     * @param $param
     * @return mixed
     */
    public function getProjectDetails($param)
    {
        $projectModel = new ProjectModel();
        $options = [
            'filter' => ['id' => $param['project_id']],
        ];
        $projectData = $projectModel->findData($options);

        // 获取 template id
        $projectTemplateModel = new ProjectTemplateModel();
        $projectData["template_id"] = $projectTemplateModel->where(['project_id' => $param['project_id']])->getField("id");

        // 获取缩略图
        $mediaService = new MediaService();
        try {
            $projectData["media_data"] = $mediaService->getMediaData(['link_id' => $param["project_id"], 'module_id' => $param["module_id"], 'relation_type' => 'direct', 'type' => 'thumb']);
        } catch (\Exception $e) {
            $projectData["media_data"] = ['has_media' => 'no', 'param' => []];
        }

        return $projectData;
    }

    /**
     * 获取当前激活项目列表（状态没有隐藏和完成的项目，不包括当前打开的项目）
     * @param $projectId
     * @return mixed
     */
    public function getActiveProjectList($projectId)
    {
        $statusModel = new StatusModel();
        $activeStatusData = $statusModel->field("id")->where(["correspond" => ["IN", "blocked,not_started,in_progress,daily"]])->select();
        $activeStatusList = [];
        foreach ($activeStatusData as $activeStatusItem) {
            array_push($activeStatusList, $activeStatusItem["id"]);
        }
        // 获取项目列表
        $projectModel = new ProjectModel();

        $filter = [
            "project.id" => ["NEQ", $projectId],
            "project.status_id" => ["IN", join(",", $activeStatusList)]
        ];

        // 添加项目团队过滤条件
        $currentUserId = fill_created_by();
        if (!in_array($currentUserId, [1, 2])) {
            $filter["_complex"] = $this->getProjectAccessRequestFilter($currentUserId);
        }

        $activeProjectData = $projectModel
            ->alias("project")
            ->join('LEFT JOIN strack_status status ON status.id = project.status_id')
            ->where($filter)
            ->field("project.id as project_id,project.name,status.name as status_name, status.color as status_color")
            ->select();
        return $activeProjectData;
    }

    /**
     * 获取项目磁盘ID
     * @param $projectId
     * @return mixed
     */
    public function getProjectDiskId($projectId)
    {
        $projectDiskModel = new ProjectDiskModel();
        $diskId = $projectDiskModel->where(["project_id" => $projectId])->getField("id");
        return $diskId;
    }

    /**
     * 查找指定项目磁盘配置
     * @param $filter
     * @return array|mixed
     */
    protected function findProjectDiskConfig($filter)
    {
        $projectDiskModel = new ProjectDiskModel();
        $options = [
            "filter" => $filter,
            "fields" => "config"
        ];
        $resData = $projectDiskModel->findData($options);
        return $resData;
    }

    /**
     * 获取项目磁盘配置
     * @param $projectId
     * @return array|mixed
     */
    public function getProjectDiskConfig($projectId)
    {
        $resData = $this->findProjectDiskConfig(["project_id" => $projectId]);
        return $resData;
    }

    /**
     * 获取项目成员表格数据
     * @param $param
     * @return mixed
     */
    public function getProjectMemberGridData($param)
    {
        // 获取schema配置
        $viewService = new ViewService();
        $schemaFields = $viewService->getGridQuerySchemaConfig($param);

        // 查询关联模型数据
        $projectMemberModel = new ProjectMemberModel();
        $resData = $projectMemberModel->getRelationData($schemaFields);

        return $resData;
    }

    /**
     * 验证项目ID传参是否合法
     * @param $projectId
     * @return bool
     */
    public function checkProjectExist($projectId)
    {
        $projectModel = new ProjectModel();
        $count = $projectModel->where(["id" => $projectId])->count();
        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * 判断项目团队权限，可以访问要求 1、项目创建者 2、在项目团队里面
     * @param $templateId
     * @param $systemRoleId
     * @return array
     */
    public function checkProjectMemberAuth($templateId, $systemRoleId)
    {
        $userId = session("user_id");
        // 获取project id
        $projectTemplateModel = new ProjectTemplateModel();
        $projectId = $projectTemplateModel->where(["id" => $templateId])->getField("project_id");

        // 判断用户是否在项目团队里面
        $projectMemberModel = new ProjectMemberModel();
        $roleId = $projectMemberModel->where(["project_id" => $projectId, "user_id" => $userId])->getField("role_id");

        if ($roleId > 0) {
            return ["permission" => true, "role_id" => $roleId];
        } else {
            // 判断是否是创建者
            $projectModel = new ProjectModel();
            $projectCreatedBy = $projectModel->where(["id" => $projectId])->getField("created_by");
            if ($projectCreatedBy == $userId) {
                return ["permission" => true, "role_id" => $systemRoleId];
            } else {
                return ["permission" => false, "role_id" => 0];
            }
        }
    }

    /**
     * 判断是否是示例项目
     * @param $projectId
     * @return array|mixed
     */
    public function checkIsDemoProject($projectId)
    {
        $projectModel = new ProjectModel();
        $projectData = $projectModel->field("is_demo,name")->where(["id" => $projectId])->find();
        if ($projectData["is_demo"] === "yes") {
            return $projectData;
        } else {
            return [];
        }
    }

    /**
     * 添加磁盘
     * @param $param
     * @return array
     */
    public function addProjectDisk($param)
    {
        $diskModel = new DiskModel();
        $saveData = [
            "name" => $param["name"],
            "code" => $param["code"],
            "type" => "local",
            "config" => [
                "win_path" => $param["win_path"],
                "mac_path" => $param["mac_path"],
                "linux_path" => $param["linux_path"],
            ]
        ];
        $resData = $diskModel->addItem($saveData);
        if (!$resData) {
            // 添加磁盘失败错误码 005
            throw_strack_exception($diskModel->getError(), 221005);
        } else {
            return success_response($diskModel->getSuccessMassege());
        }
    }

    /**
     * 添加更多项目磁盘
     * @param $param
     * @return array
     */
    public function addProjectMoreDisk($param)
    {
        $diskConfig = $this->findProjectDiskConfig(["id" => $param["disk_id"]]);
        $configData = $diskConfig["config"];

        if (!is_array($configData)) {
            $configData = [];
        }

        $diskItemConfig = [
            "id" => $param["id"],
            "name" => $param["name"],
            "code" => $param["code"],
        ];

        if (!array_key_exists($param["code"], $configData)) {
            $projectDiskModel = new ProjectDiskModel();
            $configData[$param["code"]] = $diskItemConfig;
            $resData = $projectDiskModel->modifyItem([
                "id" => $param["disk_id"],
                "config" => $configData,
            ]);
            if (!$resData) {
                // 修改磁盘失败错误码 006
                throw_strack_exception($projectDiskModel->getError(), 221006);
            } else {
                return success_response(L("Project_Disks_Add_SC"), $diskItemConfig);
            }
        } else {
            // 项目磁盘Code存在 新增失败错误码 007
            throw_strack_exception(L("Project_Disks_Code_Exist"), 221007);
        }
    }

    /**
     * 删除项目更多磁盘设置
     * @param $param
     * @return array
     */
    public function deleteProjectMoreDisk($param)
    {
        $diskConfig = $this->findProjectDiskConfig(["id" => $param["disk_id"]]);
        $configData = $diskConfig["config"];

        $newConfigData = [];
        foreach ($configData as $key => $value) {
            if ($param["code"] !== $key) {
                $newConfigData[$key] = $value;
            }
        }

        $projectDiskModel = new ProjectDiskModel();
        $resData = $projectDiskModel->modifyItem([
            "id" => $param["disk_id"],
            "config" => $newConfigData,
        ]);

        if (!$resData) {
            // 删除磁盘失败错误码 008
            throw_strack_exception($projectDiskModel->getError(), 221008);
        } else {
            return success_response($projectDiskModel->getSuccessMassege());
        }
    }

    /**
     * 修改项目磁盘
     * @param $param
     * @return array
     */
    public function modifyProjectDisk($param)
    {
        $code = $param["param"]["code"];
        $projectDiskModel = new ProjectDiskModel();

        $diskConfig = $this->findProjectDiskConfig(["id" => $param["param"]["disk_id"]]);
        $configData = $diskConfig["config"];

        if (!is_array($configData)) {
            $configData = [];
        }

        $configData[$code] = [
            "id" => $param["data"]["id"],
            "name" => $param["param"]["name"],
            "code" => $param["param"]["code"],
        ];

        $resData = $projectDiskModel->modifyItem([
            "id" => $param["param"]["disk_id"],
            "config" => $configData,
        ]);

        if (!$resData) {
            // 修改磁盘失败错误码 009
            throw_strack_exception($projectDiskModel->getError(), 221009);
        } else {
            return success_response($projectDiskModel->getSuccessMassege());
        }

    }

    /**
     * 获取成员combobox
     * @param $projectId
     * @param bool $isExclude
     * @return array
     */
    public function getProjectMemberCombobox($projectId = 0, $isExclude = false)
    {
        if ($projectId > 0) {
            // 获取当前项目下已存在的用户
            $userService = new UserService();
            $userIds = $userService->getProjectMemberData(["project_id" => $projectId]);

            if ($isExclude) {
                array_push($userIds, 1, 2);
                $filter = [
                    "filter" => [
                        "id" => ["NOT IN", join(",", $userIds)],
                        "status" => "in_service"
                    ],
                    "fields" => "id,name"
                ];
            } else {
                if (!empty($userIds)) {
                    $filter = [
                        "filter" => [
                            "id" => ["IN", join(",", $userIds)],
                            "status" => "in_service"
                        ],
                        "fields" => "id,name"
                    ];
                } else {
                    $filter = [
                        "filter" => [
                            "id" => ["NOT IN", "1,2"],
                            "status" => "in_service"
                        ],
                        "fields" => "id,name"
                    ];
                }
            }
        } else {
            $filter = [
                "filter" => [
                    "id" => ["NOT IN", "1,2"],
                    "status" => "in_service"
                ],
                "fields" => "id,name"
            ];
        }

        // 排除已存在的用户
        $userModel = new UserModel();
        $userList = $userModel->selectData($filter);

        // 处理数据并返回
        $list = [];
        foreach ($userList["rows"] as $item) {
            $tempItem = ['id' => $item["id"],
                'name' => $item["name"]];
            array_push($list, $tempItem);
        }

        return $list;
    }

    /**
     * 增加项目团队成员
     * @param $projectId
     * @param $userId
     */
    public function appendProjectMember($projectId, $userId)
    {
        $projectMemberModel = new ProjectMemberModel();
        $addData = [
            'project_id' => $projectId,
            'user_id' => $userId
        ];
        $count = $projectMemberModel->where($addData)->count();
        if ($count === 0) {
            try {
                // 获取当前用户的系统角色
                $roleUserModel = new RoleUserModel();
                $roleId = $roleUserModel->where(['user_id' => $userId])->getField('role_id');

                // 没有设置系统角色不设置项目成员
                if ($roleId > 0) {
                    $addData['role_id'] = $roleId;
                    $projectMemberModel->addItem($addData);
                }
            } catch (\Exception $e) {

            }
        }
    }

    /**
     * 把添加完任务中用户数据写入项目团队
     * @param $baseData
     */
    public function afterAddTaskInsertUserToProjectMember($baseData)
    {
        $optionsService = new OptionsService();

        // 字段配置
        $formulaConfigData = $optionsService->getFormulaConfigData();

        if (!empty($baseData['data']) && !empty($formulaConfigData['reviewed_by']) && !empty($formulaConfigData['assignee_field'])) {
            $addData = $baseData['data'];

            if (!empty($addData['project_id'])) {

                $variableService = new VariableService();
                // 分派者字段配置
                $reviewerConfig = $variableService->getVariableConfig($formulaConfigData['reviewed_by']);

                // 执行者字段配置
                $assigneeConfig = $variableService->getVariableConfig($formulaConfigData['assignee_field']);

                // 默认角色
//                $defaultModeConfig = $optionsService->getSystemModeConfig(false);
//                $defaultRoleId = $defaultModeConfig["default_role"];

                // 获取当前用户系统角色id


                if (!empty($addData[$reviewerConfig['code']])) {
                    // 存在分派者字段
                    $reviewerData = $addData[$reviewerConfig['code']];
                    if (!empty($reviewerData[0]['id']) && $reviewerData[0]['id'] > 0) {
                        $this->appendProjectMember($addData['project_id'], $reviewerData[0]['id']);
                    }
                }


                if (!empty($addData[$assigneeConfig['code']])) {
                    // 存在执行者字段
                    $assigneeData = $addData[$assigneeConfig['code']];
                    if (!empty($assigneeData[0]['id']) && $assigneeData[0]['id'] > 0) {
                        $this->appendProjectMember($addData['project_id'], $assigneeData[0]['id']);
                    }
                }
            }
        }
    }

    /**
     * 通过任务id查找项目绑定的公海任务id
     * @param $baseId
     * @return int
     */
    public function getXzProjectIdByBaseId($baseId)
    {
        $baseModel = new BaseModel();
        $projectData = $baseModel->alias("base")
            ->join("LEFT JOIN strack_project project ON project.id = base.project_id")
            ->where([
                "base.id" => $baseId
            ])
            ->field("
                project.xz_project_id
            ")
            ->find();

        if (!empty($projectData['xz_project_id'])) {
            return $projectData['xz_project_id'];
        }

        return 0;
    }

    /**
     * 获取所有项目任务状态交集列表
     * @return array|mixed
     */
    public function getProjectAllTaskStatusCombobox()
    {
        $projectTemplateModel = new ProjectTemplateModel();
        $templateConfigData = $projectTemplateModel->selectData([
            'fields' => 'config'
        ]);

        $statusIds = [];

        $formulaConfigData = (new OptionsService())->getFormulaConfigData();

        foreach ($templateConfigData['rows'] as $templateItem) {
            if (array_key_exists('base', $templateItem['config']) && array_key_exists('status', $templateItem['config']['base'])) {
                foreach ($templateItem['config']['base']['status'] as $statusItem) {
//                    if (!in_array($statusItem['id'], $statusIds) && $statusItem['id'] != $formulaConfigData['end_by_status']) {
//                        $statusIds[] = $statusItem['id'];
//                    }

                    if (!in_array($statusItem['id'], $statusIds)) {
                        $statusIds[] = $statusItem['id'];
                    }
                }
            }
        }

        // 查询状态列表
        $statusModel = new StatusModel();
        $statusList = $statusModel->field('id,name')->where(['id' => ['IN', join(',', $statusIds)]])->select();
        if (!empty($statusList)) {
            return $statusList;
        }

        return [];
    }

    /**
     * 获取文件属性数据
     * @param $type
     * @param $linkId
     * @return array
     * @throws \Ws\Http\Exception
     */
    public function getFileAttributeData($type, $linkId)
    {
        $resData = [
            // 项目属性数据
            'project_uuid' => '', //项目编号
            'project_name' => '', //项目名称
            'project_status' => '', //项目状态
            'project_start_time' => 0, //项目开始时间
            'project_end_time' => 0, //项目结束时间
            'project_created' => 0, //项目创建时间
            'project_created_by' => 0, //项目创建者
            // 任务属性数据
            'building_name' => '', //楼盘名称
            'building_area' => '', //楼盘片区
            'building_number' => '', //楼盘号码
            // 任务属性
            'task_uuid' => '', //任务编号
            'task_name' => '', //任务名称
            'task_assignor' => '', //任务分派人
            'task_executor' => '', //任务执行人
            'task_created_by' => '', //任务创建者
            'task_status' => '', //任务状态
            'task_end_time' => 0, //任务截止时间
            'task_priority' => '', //任务优先级
            'task_stage' => '', //任务阶段
        ];
        switch ($type) {
            case 'project':
                // 获取项目属性数据
                $this->getFileProjectData($resData, $linkId);
                break;
            case 'task':
                // 获取任务属性数据
                $this->getFileTaskData($resData, $linkId);
                break;
        }

        return success_response('', $resData);
    }

    /**
     * 获取文件项目数据
     * @param $data
     * @param $projectUUID
     * @return mixed
     * @throws \Ws\Http\Exception
     */
    protected function getFileProjectData(&$data, $projectUUID)
    {
        $projectModel = new ProjectModel();
        $projectData = $projectModel->where([
            'uuid' => $projectUUID
        ])
            ->find();

        if (!empty($projectData)) {
            $statusModel = new StatusModel();
            $projectStatusName = $statusModel->where(['id' => $projectData['status_id']])->getField('name');

            $userModel = new UserModel();
            $createdByName = $userModel->where(['id' => $projectData['created_by']])->getField('name');

            // 项目固有属性
            $data['project_uuid'] = $projectData['uuid'];
            $data['project_name'] = $projectData['name'];
            $data['project_status'] = $projectStatusName;
            $data['project_start_time'] = $projectData['start_time'];
            $data['project_end_time'] = $projectData['end_time'];
            $data['project_created'] = $projectData['created'];
            $data['project_created_by'] = $createdByName;

            // 远程获取楼栋信息
            $xzService = new XzService();
            $xzData = $xzService->getOwnerInfo($projectData['xz_project_id']);

            if (!empty($xzData)) {
                $data['building_name'] = $xzData['building_name'];
                $data['building_area'] = $xzData['building_area'];
                $data['building_number'] = $xzData['building_number'];
            }
        }


        return $data;
    }

    /**
     * 获取任务文件数据
     * @param $data
     * @param $taskUUID
     * @return mixed
     * @throws \Ws\Http\Exception
     */
    public function getFileTaskData(&$data, $taskUUID)
    {
        $baseModel = new BaseModel();
        $baseData = $baseModel->where([
            'uuid' => $taskUUID
        ])
            ->find();

        if (!empty($baseData)) {
            // 获取当前任务项目的UUID
            $projectModel = new ProjectModel();
            $projectUUID = $projectModel->where(['id' => $baseData['project_id']])->getField('uuid');

            $this->getFileProjectData($data, $projectUUID);

            // 任务数据
            $statusModel = new StatusModel();
            $taskStatusName = $statusModel->where(['id' => $baseData['status_id']])->getField('name');

            $userModel = new UserModel();
            $createdByName = $userModel->where(['id' => $baseData['created_by']])->getField('name');

            $data['task_uuid'] = $baseData['uuid'];
            $data['task_name'] = $baseData['name'];
            $data['task_status'] = $taskStatusName;
            $data['task_start_time'] = $baseData['start_time'];
            $data['task_end_time'] = $baseData['end_time'];
            $data['task_created'] = $baseData['created'];
            $data['task_priority'] = $baseData['priority'];
            $data['task_created_by'] = $createdByName;

            $formulaConfigData = (new OptionsService())->getFormulaConfigData();

            // 分配人
            $moduleIds = C('MODULE_ID');
            $HorizontalService = new HorizontalService();
            $reviewedById = $HorizontalService->getHorizontalServiceData($moduleIds['base'], $moduleIds['user'], $formulaConfigData['reviewed_by'], $baseData['id']);
            $data['task_assignor'] = $userModel->where(['id' => $reviewedById])->getField('name');

            // 执行人
            $assigneeFieldId = $HorizontalService->getHorizontalServiceData($moduleIds['base'], $moduleIds['user'], $formulaConfigData['assignee_field'], $baseData['id']);
            $data['task_executor'] = $userModel->where(['id' => $assigneeFieldId])->getField('name');

            // 设计阶段
            $variableService = new VariableService();
            $data['task_stage'] = $variableService->getCustomVariableValue($baseData['id'], $moduleIds['base'], $formulaConfigData['grouping_of_stage']);
        }

        return $data;
    }

    /**
     * 根据项目id集合获取项目信息
     * @param $projectIdArr
     * @return array
     */
    public function getProjectDataByids($projectIdArr)
    {
        $projectModel = new ProjectModel();
        $formulaConfigData = (new OptionsService())->getFormulaConfigData();
        $filter = [
            "id" => ["IN", join(",", $projectIdArr)],
        ];
        $list = $projectModel->where($filter)
            ->field("id,name,status_id,end_time")
            ->select();
        $completeCount = $incompleteCount = $overtimeCount = 0; //已完成、未完成、已超时
        $nowtime = time();
        $i = 0;
        foreach ($list as $item) {
            switch ($item['status_id']) {
                case $formulaConfigData['end_by_status']: //已完成
                    $completeCount++;
                    break;
                default: //未完成
                    if ($item['end_time'] < $nowtime) { //如果已超时
                        $overtimeCount++;
                    } else {
                        $incompleteCount++;
                    }
            }
            $i++;
        }
        return [
            'total' => $i,
            'completeCount' => $completeCount,
            'incompleteCount' => $incompleteCount,
            'overtimeCount' => $overtimeCount,
        ];
    }
}

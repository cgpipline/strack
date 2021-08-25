<?php

namespace Api\Controller;


use Common\Service\SchemaService;

class SchemaController extends BaseController
{
    protected $schemaService;

    // 验证器
    protected $commonVerify = 'Schema';

    // 验证场景 （key名小写）
    protected $commonVerifyScene = [
        'createSchemaStructure' => 'CreateSchemaStructure',
        'updateSchemaStructure' => 'UpdateSchemaStructure',
        'getSchemaStructure' => 'GetSchemaStructure',
        'deleteSchemaStructure' => 'DeleteSchemaStructure',
        'createEntityModule' => 'CreateEntityModule',
        'getTableConfig' => 'GetTableConfig',
        'updateTableConfig' => 'UpdateTableConfig',

    ];

    public function __construct()
    {
        parent::__construct();
        $this->schemaService = new SchemaService();
    }

    /**
     * 创建Schema关联结构
     * @return \Think\Response
     * @throws \Exception
     */
    public function createSchemaStructure()
    {
        $resData = $this->schemaService->saveSchemaModuleRelation($this->param);
        return json($resData);
    }

    /**
     * 修改Schema结构
     * @return \Think\Response
     * @throws \Exception
     */
    public function updateSchemaStructure()
    {
        $resData = $this->schemaService->modifySchemaModuleRelation($this->param);
        return json($resData);
    }

    /**
     * 删除Schema
     * @return \Think\Response
     */
    public function deleteSchemaStructure()
    {
        $resData = $this->schemaService->deleteSchema($this->param);
        return json($resData);
    }

    /**
     * 获得Schema
     * @return \Think\Response
     */
    public function getSchemaStructure()
    {
        $resData = $this->schemaService->getModuleRelationData($this->param);
        return json(success_response('', $resData['rows']));
    }

    /**
     * 获取Schema数据
     * @return \Think\Response
     */
    public function getAllSchema()
    {
        $resData = $this->schemaService->getSchemaList([]);
        return json(success_response('', $resData));
    }

    /**
     * 创建Entity模块
     * @return \Think\Response
     * @throws \Exception
     */
    public function createEntityModule()
    {
        $resData = $this->schemaService->saveEntityModuleData($this->param);
        $resData["data"] = [];
        return json(success_response('', $resData));
    }

    /**
     * 获取单个表配置
     * @return array|\Think\Response
     */
    public function getTableConfig()
    {
        $resData = $this->schemaService->getTableFieldData($this->param);
        return json(success_response('', $resData));
    }

    /**
     * 获取所有表配置
     * @return array|\Think\Response
     */
    public function getAllTableName()
    {
        $resData = $this->schemaService->getAllTableName();
        return json(success_response('', $resData));
    }

    /**
     * 更新字段表配置
     * @return \Think\Response
     */
    public function updateTableConfig()
    {
        $resData = $this->schemaService->modifyFieldConfig($this->param);
        return json(success_response('', $resData));
    }

}

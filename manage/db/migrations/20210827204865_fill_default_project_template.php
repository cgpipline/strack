<?php


use Phinx\Migration\AbstractMigration;

class FillDefaultProjectTemplate extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */

    /**
     * 获取所有模块数据
     * @return array
     */
    public function getAllModuleData()
    {
        $sql = 'select * from strack_module';
        $result = $this->query($sql);
        $data = array();
        foreach ($result as $val) {
            $data[$val['code']] = [
                'code' => $val['code'],
                'icon' => $val['icon'],
                'name' => $val['name'],
                'type' => $val['type'],
                'module_id' => $val['id'],
                "module_name" => $val['type'] === 'fixed' ? "固定模块" : "动态模块"
            ];
        }

        // overview
        $data['overview'] = [
            'code' => 'overview',
            'icon' => $data['project']['icon'],
            'name' => $data['project']['name'],
            'type' => $data['project']['type'],
            'module_id' =>  $data['project']['module_id'],
            "module_name" => $data['project']['module_name']
        ];

        return $data;
    }

    /**
     * 获取所有状态数据
     * @return array
     */
    public function getAllStatusData()
    {
        $sql = 'select * from strack_status';
        $result = $this->query($sql);
        $data = array();
        foreach ($result as $val) {
            $data[$val['code']] = $val;
        }
        return $data;
    }

    /**
     * 获取所有项目数据结构数据
     * @return array
     */
    public function getAllProjectSchemaData()
    {
        $sql = "select * from strack_schema where type='project'";
        $result = $this->query($sql);
        $data = array();
        foreach ($result as $val) {
            $data[$val['code']] = $val;
        }
        return $data;
    }

    /**
     * 获取项目分类配置
     * @param $moduleDict
     * @param $statusDict
     * @param $statusCodeList
     * @param $navigationModuleCode
     * @return array|array[]
     */
    public function getProjectConfig($moduleDict, $statusDict, $statusCodeList, $navigationModuleCode)
    {
        $config = [
            'status' => [],
            'navigation' => []
        ];

        foreach ($statusCodeList as $statusCodeItem) {
            $config['status'][] = [
                'id' => $statusDict[$statusCodeItem]['id']
            ];
        }

        foreach ($navigationModuleCode as $navigationModuleItem) {
            $config['navigation'][] = $moduleDict[$navigationModuleItem];
        }

        return $config;
    }

    /**
     * 获取任务分类配置
     */
    public function getTaskConfig()
    {

    }

    /**
     * 生成默认项目模板
     * @param $name
     * @param $schemaCode
     * @return array
     */
    public function generateProjectTemaplate($name, $schemaCode, $settings)
    {
        $moduleDict = $this->getAllModuleData();
        $statusDict = $this->getAllStatusData();
        $schemaDict = $this->getAllProjectSchemaData();
        // 项目模板配置
        $config = [];

        foreach ($settings as $key => $val){
            switch ($key){
                case 'project':
                    $config['project'] = $this->getProjectConfig($moduleDict, $statusDict, $val['status'], $val['module']);
                    break;
            }
        }

        // 项目模板信息
        $data = [
            'name' => $name,
            'code' => $schemaCode,
            'project_id' => 0,
            'schema_id' => $schemaDict[$schemaCode]['id'],
            'config' => json_encode($config),
            'uuid' => Webpatser\Uuid\Uuid::generate()->string,
        ];

        return $data;
    }

    public function executeRawSql($sqlName)
    {
        $path = dirname(dirname(__FILE__))."/sql/{$sqlName}.sql";
        $sql = file_get_contents($path);

        $this->execute($sql);
    }

    /**
     * Migrate Up.
     */
    public function up()
    {
        // 并执行sql文件
        $this->executeRawSql("strack_project_template_data");
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->execute('DELETE FROM strack_project_template');
    }
}

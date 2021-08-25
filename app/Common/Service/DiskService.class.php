<?php
// +----------------------------------------------------------------------
// | 磁盘服务层
// +----------------------------------------------------------------------
// | 主要服务于磁盘路径配置
// +----------------------------------------------------------------------
// | 错误编码头 203xxx
// +----------------------------------------------------------------------
namespace Common\Service;

use Common\Model\BaseModel;
use Common\Model\DiskModel;
use Common\Model\ProjectModel;
use Common\Model\UserModel;
use Ws\Http\Request;
use Ws\Http\Request\Body;

class DiskService
{

    // 缓存对象
    protected $redisObj;

    protected $_headers = [
        'Accept' => 'application/json',
        'content-type' => 'application/json'
    ];

    // 错误信息
    protected $errorMsg = '';

    public function __construct()
    {
        $this->redisObj = new RedisService();
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->errorMsg;
    }


    /**
     * 远程请求数据
     * @param $data
     * @param $url
     * @return bool
     * @throws \Ws\Http\Exception
     */
    protected function postData($data, $url)
    {
        $http = Request::create();
        $body = Body::json($data);

        $responseData = $http->post($url, $this->_headers, $body);

        if ($responseData->code === 200) {
            switch ($responseData->body->status) {
                case 0:
                case 200:
                    return $responseData->body->data;
                    break;
                case -41002:
                case -41003:
                    // token过期重新获取，再次发起请求
                    $this->errorMsg = 'token_expire';
                    return false;
                    break;
                default:
                    $this->errorMsg = $responseData->body->message;
                    return false;
                    break;
            }

            if (in_array($responseData->body->status, [0, 200])) {
                return $responseData->body->data;
            } else {
                $this->errorMsg = $responseData->body->message;
                return false;
            }
        } else {
            $this->errorMsg = '文件管理系统异常';
            return false;
        }
    }

    /**
     * 获取访问令牌
     * @param $param
     * @param string $mode
     * @return mixed|string
     * @throws \Ws\Http\Exception
     */
    protected function getToken($param, $mode = '')
    {
        $tokenKey = 'cloud_sys_token_' . session('user_id');
        $tokenCache = S($tokenKey);
        if ($mode !== 'new' && !empty($tokenCache)) {
            return $tokenCache;
        }

        // 从新获取
        $url = $param['url'] . '/login/get_token';
        $postResult = $this->postData([
            "login_name" => $param['login_name'],
            "password" => $param['password']
        ], $url);

        $token = '';
        if ($postResult !== false) {
            $token = $postResult->token;
            //  写入缓存
            S($tokenKey, $token);
        }

        return $token;
    }

    /**
     * 通过用户手机号或者UCid创建用户
     * @param $endpointParam
     * @return array
     * @throws \Ws\Http\Exception
     */
    protected function createUserByPhoneOrUcId($endpointParam)
    {
        // 获取当前用信息
        $userModel = new UserModel();
        $userData = $userModel->field('id,login_name,name,phone,status,qq_openid,created,uuid')
            ->where([
                'id' => session('user_id')
            ])
            ->find();

        $endpointUrlParam = [
            'url' => $endpointParam['check_url'],
            "login_name" => $endpointParam['login_name'],
            "password" => $endpointParam['password']
        ];

        $token = $this->getToken($endpointUrlParam);


        $this->_headers['token'] = $token;
        $url = "{$endpointParam['check_url']}/user/create_user_by_phone_or_ucid";

        $postResult = $this->postData($userData, $url);


        $resData = [];
        if ($postResult !== false) {
            $resData = object_to_array($postResult);
        } else {
            if ($this->errorMsg === 'token_expire') {
                $this->_headers['token'] = $this->getToken($endpointUrlParam, 'new');
                $postResult = $this->postData($userData, $url);

                if ($postResult !== false) {
                    $resData = object_to_array($postResult);
                }
            }
        }

        return $resData;
    }

    /**
     * 添加磁盘
     * @param $param
     * @return array
     */
    public function addDisks($param)
    {
        $diskModel = new DiskModel();
        $resData = $diskModel->addItem($param);
        if (!$resData) {
            // 磁盘创建失败错误码 001
            throw_strack_exception($diskModel->getError(), 203001);
        } else {
            // 返回成功数据
            return success_response($diskModel->getSuccessMassege(), $resData);
        }
    }

    /**、
     * 修改磁盘
     * @param $param
     * @return array
     */
    public function modifyDisks($param)
    {
        $diskModel = new DiskModel();
        $resData = $diskModel->modifyItem($param);
        if (!$resData) {
            // 磁盘修改失败错误码 002
            throw_strack_exception($diskModel->getError(), 203002);
        } else {
            // 返回成功数据
            return success_response($diskModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 删除磁盘
     * @param $param
     * @return array
     */
    public function deleteDisks($param)
    {
        $diskModel = new DiskModel();
        $resData = $diskModel->deleteItem($param);
        if (!$resData) {
            // 磁盘删除失败错误码 003
            throw_strack_exception($diskModel->getError(), 203003);
        } else {
            // 返回成功数据
            return success_response($diskModel->getSuccessMassege(), $resData);
        }
    }

    /**
     * 获取磁盘Combobox列表
     * @return mixed
     */
    public function getDiskCombobox()
    {
        $diskModel = new DiskModel();
        $diskList = $diskModel->field("id,name")->select();
        return $diskList;
    }

    /**
     * 加载项目存储路径列表
     * @param $param
     * @return array
     */
    public function getDisksGridData($param)
    {

        $options = [
            'page' => [$param["page"], $param["rows"]]
        ];

        $diskModel = new DiskModel();
        $diskData = $diskModel->selectData($options);
        return $diskData;
    }

    /**
     * 通过code返回disk信息
     * @param $code
     * @return array|mixed
     */
    public function getDiskByCode($code)
    {
        $diskModel = new DiskModel();
        $ret = $diskModel->findData(["filter" => ["code" => $code]]);
        return $ret;
    }

    /**
     * 获取云盘配置数据
     * @return array|mixed
     */
    public function getCloudDiskConfig()
    {
        $optionsService = new OptionsService();
        $couldDiskSettings = $optionsService->getOptionsData("cloud_disk_settings");
        if (!empty($couldDiskSettings) && $couldDiskSettings["open_cloud_disk"] == 1) {
            return $couldDiskSettings;
        }
        return ["open_cloud_disk" => 0];
    }

    /**
     * 获取最近最快的网盘节点
     * @param $endpoints
     * @return array|mixed
     * @throws \Ws\Http\Exception
     */
    protected function getTheFastEndpoints($endpoints)
    {
        $connectTime = 999999999999999;
        $endpointParam = [];
        if (!empty($endpoints) && is_array($endpoints)) {
            foreach ($endpoints as $endpoint) {
                try {
                    $postResult = $this->postData([], "{$endpoint['check_url']}/login/check_status");
                    if ($postResult !== false) {
                        $getServerStatus = check_http_code($endpoint['check_url']);
                        if ($getServerStatus['http_code'] === 200 && $getServerStatus['connect_time'] < $connectTime) {
                            $connectTime = $getServerStatus['connect_time'];
                            $endpointParam = $endpoint;
                        }
                    }
                } catch (\Exception $e) {

                }
            }
        }

        return $endpointParam;
    }

    /**
     * @return string
     */
    public function generateCloudDiskUrlKey()
    {
        $userId = session('user_id');
        $key = "strack_cloud_disk_{$userId}";
        $urlMd5 = md5($key);

        return $urlMd5;
    }

    /**
     * 保存网盘url缓存
     * @param $urlData
     */
    public function saveCloudDiskUrlCache($urlData)
    {
        $urlMd5 = $this->generateCloudDiskUrlKey();
        S($urlMd5, $urlData);
    }

    /**
     * 获取网盘url缓存
     * @param $urlMd5
     * @return array
     */
    public function getCloudDiskUrlCache($urlMd5)
    {
        $cache = S($urlMd5);
        if (!empty($cache)) {
            $resData = $cache;
        } else {
            $resData = [];
        }

        return success_response('', $resData);
    }

    /**
     * 获取配置的网盘节点
     * @return array
     */
    public function getCloudDiskEndpoints()
    {
        $resData = [];
        $couldDiskSettings = $this->getCloudDiskConfig();
        $resData['endpoints'] = $couldDiskSettings["endpoints"];

        // 获取当前用信息
        $userModel = new UserModel();
        $resData['user_data'] = $userModel->field('id,login_name,name,phone,status,qq_openid,created,uuid')
            ->where([
                'id' => session('user_id')
            ])
            ->find();

        return $resData;
    }

    /**
     * 获取云盘访问路径
     * @param string $type
     * @param int $linkId
     * @param int $uuid
     * @param string $folderName
     * @return array
     * @throws \Ws\Http\Exception
     */
    public function getCloudDiskUrl($type = 'project', $linkId = 0, $uuid = 0, $folderName = '')
    {
        $couldDiskSettings = $this->getCloudDiskConfig();
        $url = '';
        $requestUrl = '';
        $cloudDiskToken = '';
        $cloudDiskUserId = 0;
        $linkUUID = 0;
        $urlMd5 = $this->generateCloudDiskUrlKey();

        if ($couldDiskSettings["open_cloud_disk"] > 0) {
            $endpointParam = $this->getTheFastEndpoints($couldDiskSettings["endpoints"]);

            if (!empty($endpointParam)) {

                $requestUrl = $endpointParam['check_url'];
                $url = $endpointParam['base_url'];

                // 创建网盘用户同步
                $userData = $this->createUserByPhoneOrUcId($endpointParam);

                if (!empty($userData)) {
                    $cloudDiskToken = $userData['token'];
                    $cloudDiskUserId = $userData['user_data']['id'];

                    switch ($type) {
                        case 'project':
                            $projectModel = new ProjectModel();
                            $linkUUID = $projectModel->where(['id' => $linkId])->getField('uuid');
                            break;
                        case 'task':
                            if (empty($uuid)) {
                                $baseModel = new BaseModel();
                                $linkUUID = $baseModel->where(['id' => $linkId])->getField('uuid');
                            } else {
                                $linkUUID = $uuid;
                            }
                            break;
                        default:
                            break;
                    }

                    $url .= '/' . $urlMd5;

                    if (!empty($folderName)) {
                        $url .= '/' . $folderName;
                    }

                    $this->saveCloudDiskUrlCache([
                        'token' => $cloudDiskToken,
                        'type' => $type,
                        'uc_id' => $userData['user_data']['uc_id'],
                        'user_id' => $cloudDiskUserId,
                        'link_id' => $linkUUID
                    ]);

                } else {
                    $url = '';
                }
            }
        }


        return [
            "cloud_disk_url" => $url,
            "cloud_disk_token" => $cloudDiskToken,
            "cloud_disk_user_id" => $cloudDiskUserId,
            "cloud_disk_type" => $type,
            "cloud_disk_link_uuid" => $linkUUID,
            "cloud_disk_request_url" => $requestUrl
        ];
    }

    /**
     * 通过前端传参返回网盘访问地址
     * @param $userData
     * @param $fastEndpoints
     * @param $param
     * @return string
     */
    public function getCloudDiskUrlByFrontend($userData, $fastEndpoints, $param)
    {
        $url = '';
        if (!empty($userData)) {
            $cloudDiskToken = $userData['token'];
            $cloudDiskUserId = $userData['user_data']['id'];

            $urlMd5 = $this->generateCloudDiskUrlKey();
            $url = $fastEndpoints['base_url'];

            $linkUUID = 0;
            switch ($param['type']) {
                case 'project':
                    $projectModel = new ProjectModel();
                    $linkUUID = $projectModel->where(['id' => $param['link_id']])->getField('uuid');
                    break;
                case 'task':
                    if (empty($uuid)) {
                        $baseModel = new BaseModel();
                        $linkUUID = $baseModel->where(['id' => $param['link_id']])->getField('uuid');
                    } else {
                        $linkUUID = $uuid;
                    }
                    break;
                default:
                    break;
            }

            $url .= '/' . $urlMd5;

            if (!empty($folderName)) {
                $url .= '/' . $folderName;
            }

            $this->saveCloudDiskUrlCache([
                'token' => $cloudDiskToken,
                'type' => $param['type'],
                'uc_id' => $userData['user_data']['uc_id'],
                'user_id' => $cloudDiskUserId,
                'link_id' => $linkUUID
            ]);

        } else {
            $url = '';
        }

        return success_response('', ['url' => $url]);
    }

    /**
     * 获取边侧栏云盘访问路径
     * @param $param
     * @return array
     * @throws \Ws\Http\Exception
     */
    public function getDataGridSliderOtherPageData($param)
    {

        $tableName = $param["module_type"] === "entity" ? "Entity" : string_initial_letter($param["module_code"]);
        $itemUUID = M($tableName)->where(["id" => $param["item_id"]])->getField("uuid");

        $cloudDiskConfig = $this->getCloudDiskUrl($type = 'task', $param["item_id"], $itemUUID);

        if (!empty($cloudDiskConfig)) {
            $cloudDiskConfig['host'] = get_http_type() . $_SERVER['HTTP_HOST'];
            return success_response('', $cloudDiskConfig);
        }

        throw_strack_exception('', 203004);
    }
}

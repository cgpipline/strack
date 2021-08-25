<?php
// +----------------------------------------------------------------------
// | XZ 1.0 数据接口服务层
// +----------------------------------------------------------------------
// | 主要服务于 XZ 1.0 数据处理
// +----------------------------------------------------------------------
// | 错误编码头 233xxx
// +----------------------------------------------------------------------
namespace Common\Service;

use Common\Model\BaseModel;
use Common\Model\UserModel;
use Ws\Http\Request;
use Ws\Http\Request\Body;

class XzService
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
            if (in_array($responseData->body->status, [0, 200])) {
                return $responseData->body->data;
            } else {
                $this->errorMsg = $responseData->body->msg;
                return false;
            }
        } else {
            $this->errorMsg = 'xz 1.0 平台异常';
            return false;
        }
    }

    /**
     * 获取strack union id通过User ID
     * @param $userId
     * @return mixed
     */
    protected function getStrackUnionIdByUserId($userId)
    {
        $userModel = new UserModel();
        $strackUnionId = $userModel->where(['id' => $userId])->getField('strack_union_id');
        return $strackUnionId;
    }

    /**
     * 获取 xz1.0 平台所有项目 TODO：按项目总过滤
     * @param int $userId
     * @return array|mixed
     * @throws \Ws\Http\Exception
     */
    public function getXzProjectList($userId = 0)
    {
        // 缓存项目信息
        $cacheName = 'xz_project_list';
        $cache = $this->redisObj->getCache($cacheName);

        if (!empty($cache)) {
            return $cache;
        } else {
            $url = C('jgy_xz_url') . '/business/storeSea';
            $strackOpenId = 0;
            if ($userId > 0) {
                // 获取当前用户绑定的ucenter id
                $userModel = new UserModel();
                $strackOpenId = $userModel->where(['id' => $userId])->getField('strack_union_id');
            }

            $postResult = $this->postData(['strack_open_id' => $strackOpenId], $url);
            if (!$postResult) {
                return [];
            } else {
                $arrayData = json_decode($postResult, true);

                // 缓存数据 1个小时更新一次
                $expireTime = time() + 3600;
                $cacheData = [
                    'data' => $arrayData['rows'],
                    'expire' => $expireTime
                ];
                $this->redisObj->saveCache($cacheName, $cacheData);

                return $arrayData['rows'];
            }
        }
    }

    /**
     * 从1.0 协作平台取团队列表
     * @return array|string
     * @throws \Ws\Http\Exception
     */
    public function getXzGroupList()
    {
        // 缓存团队列表信息
        $cacheName = 'xz_group_list';
        $cache = $this->redisObj->getCache($cacheName);

        if (!empty($cache)) {
            return $cache;
        } else {
            $url = C('jgy_xz_url') . '/getTeamList';

            $postResult = $this->postData([], $url);

            if (!$postResult) {
                return [];
            } else {
                $arrayData = object_to_array($postResult);

                // 缓存数据 1个小时更新一次
                $expireTime = time() + 3600;
                $cacheData = [
                    'data' => $arrayData,
                    'expire' => $expireTime
                ];
                $this->redisObj->saveCache($cacheName, $cacheData);

                return $arrayData;
            }
        }
    }

    /**
     * 写入任务积分记录
     * @param $reviewedId
     * @param $assigneeId
     * @param $baseId
     * @param $points
     * @return array
     * @throws \Ws\Http\Exception
     */
    public function writeTaskPointsLog($reviewedId, $assigneeId, $baseId, $points)
    {
        // 判断当前项目绑定的 sea_id
        $projectService = new ProjectService();
        $xzProjectId = $projectService->getXzProjectIdByBaseId($baseId);

        if ($xzProjectId > 0) {
            $url = C('jgy_xz_url') . '/taskManageSystem';

            $taskModel = new BaseModel();
            $basUUId = $taskModel->where(['id' => $baseId])->getField('uuid');

            // 获取付款人是否绑定了团队id
            $userModel = new UserModel();
            $xzGroupId = $userModel->where(['id' => $reviewedId])->getField('xz_group_id');

            $param = [
                'receive_id' => $this->getStrackUnionIdByUserId($assigneeId),
                'pay_id' => $this->getStrackUnionIdByUserId($reviewedId),
                'task_id' => $basUUId, // 任务的uuid
                'sea_id' => $xzProjectId,
                'group_id' => $xzGroupId,
                'source_related' => C('jgy_xz_source_related'),
                'source_item' => C('jgy_xz_source_item'),
                'money' => $points
            ];

            /**
             * 'receive_id' => 'required', 收款人，任务执行人uc_id
             * 'pay_id' => 'required', 付款人，任务分配人uc_id
             * 'task_id' => 'required', 任务id，属于任务管理系统
             * 'sea_id' => 'required', 项目绑定的xz 1.0 的公海任务id
             * 'money' => 'required', 金额，任务积分
             */
            $postResult = $this->postData($param, $url);

            if (!$postResult) {
                return [];
            } else {
                $arrayData = object_to_array($postResult);
                return $arrayData;
            }
        }
    }

    /**
     * 通过过滤条件查询楼栋信息
     * @param $storeSeaId
     * @return array
     * @throws \Ws\Http\Exception
     */
    public function getOwnerInfo($storeSeaId)
    {
        if ($storeSeaId > 0) {
            $url = C('jgy_xz_url') . "/taskManageSystem/getOwnerInfo?store_sea_id={$storeSeaId}";
            $postResult = $this->postData([], $url);
            if (!$postResult) {
                return [];
            } else {
                return object_to_array($postResult[0]);
            }
        }
    }
}

<?php
/**
 *
 * code  -206
 */

namespace common\service;

use common\model\UserModel;
use phpcent\Client;
use support\ErrorCode;
use think\Exception;

class CentrifugalService
{

    // 配置
    protected $config;

    /**
     * @var Client
     */
    protected $client;


    /**
     * CentrifugalService constructor.
     */
    public function __construct()
    {
        // 获取 Centrifugo 配置
        $this->config = C("centrifugo");

        // 初始化客户端连接
        $this->client = new Client($this->config['api_url']);

        // 设置api_key
        $this->client->setApiKey($this->config['api_key']);

        // 设置secret
        $this->client->setSecret($this->config['secret']);
    }

    /**
     * 生成连接token
     * @param $userData
     * @param $expires
     * @return string
     */
    public function generateConnectionToken($userData, $expires)
    {
        // 过期时间比 http token 延长10分钟
        $deviceUniqueCode = $userData['device_unique_code'] ?? "";
        $uuid = $userData['uuid'] . $deviceUniqueCode;
        $token = $this->client->generateConnectionToken($uuid, (time() + $expires + 600), [], [$userData['channel']]);
        return $token;
    }

    /**
     * 生成Centrifugo token
     * @param $userId
     * @param $channel
     * @return string
     */
    public function generateGlobalCentrifugoToken($userId, $channel)
    {
        $centrifugoConfig = C('centrifugo');
        $centrifugo =  new Client($centrifugoConfig["api_url"], $centrifugoConfig["api_key"], $centrifugoConfig["secret"]);

        return $centrifugo->generateConnectionToken($userId, 0, [], [$channel]);
    }

    /**
     * 推送消息
     * @param string $channel
     * @param array $message
     */
    public function pushMassage($channel = '', $message = [])
    {
        $response = null;
        if (!empty($channel) && !empty($message)) {
            $response = $this->client->publish($channel, $message);
        }
        return $response;
    }

    /**
     * 推送消息
     * @param array $channels
     * @param array $message
     * @return mixed
     */
    public function broadcast(array $channels, $message)
    {
        return $this->client->broadcast($channels, $message);
    }

    /**
     * 发送消息到用户频道
     * @param $data
     * @param int $userId
     * @return mixed|null
     * @throws Exception
     */
    public function sendDataToMineChannel($data, int $userId)
    {
        $user = model(UserModel::class)->field("channel")->find($userId);
        $channel = $user['channel'];
        if (empty($user['channel'])) {
            throw new Exception("channel is empty", ErrorCode::CHANNEL_IS_EMPTY);
        }
        return $this->pushMassage($channel, $data);
    }
}

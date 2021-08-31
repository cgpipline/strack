<?php

namespace Api\Controller;

use Common\Service\MediaService;

class MediaController extends BaseController
{
    protected $mediaServer;

    // 验证器
    protected $commonVerify = 'Media';

    // 验证场景 （key名小写）
    protected $commonVerifyScene = [
        'createMedia' => 'CreateMedia',
        'updateMedia' => 'UpdateMedia',
        'getMediaData' => 'GetMediaData',
        'getMediaServerItem' => 'GetMediaServerItem',
        'getSpecifySizeThumbPath' => 'GetSpecifySizeThumbPath',
        'selectMediaData' => 'SelectMediaData',
    ];


    public function __construct()
    {
        parent::__construct();
        $this->mediaServer = new MediaService();
    }

    /**
     * 添加media
     * @return \Think\Response
     */
    public function createMedia()
    {
        $resData = $this->mediaServer->saveMediaData($this->param);
        return json(success_response('', $resData));
    }

    /**
     * 修改media
     * @return \Think\Response
     */
    public function updateMedia()
    {
        $resData = $this->mediaServer->saveMediaData($this->param);
        return json(success_response('', $resData));
    }


    /**
     * 获取指定媒体信息
     * @return \Think\Response
     */
    public function getMediaData()
    {
        $resData = $this->mediaServer->getMediaData($this->param['param']['filter']);
        return json(success_response('', $resData));
    }

    /**
     * 获取媒体指定上传服务器配置信息
     * @return \Think\Response
     */
    public function getMediaUploadServer()
    {
        $resData = $this->mediaServer->getMediaUploadServer();
        return json(success_response('', $resData));
    }

    /**
     * 获取指定服务器信息
     * @return \Think\Response
     */
    public function getMediaServerItem()
    {
        $resData = $this->mediaServer->getMediaServerItem($this->param['param']['filter']);
        return json(success_response('', $resData));
    }


    /**
     * 获取所有媒体服务器状态
     * @return \Think\Response
     */
    public function getMediaServerStatus()
    {
        $resData = $this->mediaServer->getMediaServerStatus();
        return json(success_response('', $resData));
    }

    /**
     * 获取指定尺寸的媒体缩略图路径
     * @return \Think\Response
     */
    public function getSpecifySizeThumbPath()
    {
        $resData = $this->mediaServer->getSpecifySizeThumbPath($this->param['param']["filter"], $this->param["size"]);
        return json(success_response('', ['src' => $resData]));
    }

    /**
     * 获取多个媒体信息
     * @return \Think\Response
     */
    public function selectMediaData()
    {
        $resData = $this->mediaServer->getMediaSelectData($this->param['param']["filter"]);
        return json(success_response('', $resData));
    }
}

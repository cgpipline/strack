<?php

namespace Api\Controller;


use Common\Service\DiskService;
use Common\Service\ProjectService;

class ProjectController extends BaseController
{

    /**
     * 获取文件属性数据
     * type  类型（project、task）
     * linkId  关联id
     */
    public function getFileAttributeData()
    {
        if (array_key_exists('type', $this->param) && array_key_exists('link_id', $this->param)) {
            $type = $this->param['type'];
            $linkId = $this->param['link_id'];
            $projectService = new ProjectService();
            $resData = $projectService->getFileAttributeData($type, $linkId);
            return json($resData);
        } else {
            throw_strack_exception('Parameter error');
        }
    }

    /**
     * 获取网盘url缓存
     * @return \Think\Response
     */
    public function getCloudDiskUrlCache()
    {
        if (array_key_exists('url_md5', $this->param)) {
            $diskService = new DiskService();
            $resData = $diskService->getCloudDiskUrlCache($this->param['url_md5']);
            return json($resData);
        } else {
            throw_strack_exception('Parameter error');
        }
    }
}

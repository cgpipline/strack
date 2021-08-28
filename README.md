# Strack 4.0 影视动画游戏流程管理系统

[![Php Version](https://img.shields.io/badge/php-%3E=7.4-brightgreen.svg)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/workerman-%3E=4.0.19-brightgreen.svg)](https://github.com/walkor/Workerman)
[![imi License](https://img.shields.io/badge/license-Apache%202.0-brightgreen.svg)](https://github.com/cgpipline/strack/blob/master/LICENSE)

# 缘起

当前strack版本基于开源版本 strack3.0 继续维护，基于 Apache-2.0 License 开源。

本人从2016年-2019年作为strack核心开发人员，因为公司财务问题导致strack3.0实际上没有得到完全商业就夭折甚是可惜，故重启此维护版本回馈行业。

# 维护人员

 姓名 | 简介 | 联系方式
---|---|---
weijer | PMC | 微信 weijer（备注：github strack）
mychenjun | committer

# 更新计划

- [x] 整理优化代码
- [x] event和消息异步处理
- [x] 底层框架漏洞修复
- [ ] PHP版本兼容升级 7.4
- [x] 重写media服务
- [x] 重写log服务
- [ ] 编写docker-compose文件
- [ ] 编写使用文档
- [ ] 整理 python api sdk 代码
- [ ] 整理 python event 代码
- [ ] 重写 PYQT client 客户端
- [ ] 对接 Rocket.Chat
- [ ] 支持workerman高性能运行（改动太大看情况）

# 架构图

![image](doc/strack_structure.png)

# 安装

当前还是测试阶段，mysql，redis 请自行安装配置

```shell
docker run --network jgy --network-alias strack_3.0 \
    --name strack -d  \
    -p 18094:80 \
    -v /mnt/hgfs/dev/weijer/strack:/usr/local/apache2/htdocs/app/public \
    weijer/sd_docker:strack
```





<?php
return [
    // HTTP 请求的超时时间（秒）
    'timeout' => 5.0,

    // 默认发送配置
    'default' => [
        // 网关调用策略，默认：顺序调用
        'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

        // 默认可用的发送网关
        'gateways' => ['qcloud'],
    ],
    // 可用的网关配置
    'gateways' => [
        'errorlog' => [
            'file' => RUNTIME_PATH . 'easy_sms/error.log',
        ],
        'qcloud' => [
            'sdk_app_id' => '', // SDK APP ID
            'app_key' => '', // APP KEY
            'sign_name' => '', // 短信签名，如果使用默认签名，该字段可缺省（对应官方文档中的sign）
        ]
    ],
    'template' => [
        'register' => 280851, // 注册短信
        'login' => 280802, // 登陆短信
    ]
];

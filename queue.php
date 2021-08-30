<?php
namespace Think;

// 检测PHP环境
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    die('require PHP > 7.0.0 !');
}

// 定义应用目录
define('APP_PATH', __DIR__ . '/app/');

// 定义缓存目录
define('RUNTIME_PATH', __DIR__ . '/runtime/');

define('APP_MODE', 'cli');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG', false);

// 加载基础文件
require __DIR__ . '/core/Base.php';

$env = parse_ini_file('.env', true);
define('STRACK_ENV', $env);

use Workerman\Worker;
use Workerman\RedisQueue\Client;

App::init();

// redis 配置
$redisUrl = "redis://{$env['redis_host']}:{$env['redis_port']}";
$redisOptions = [
    'auth' => $env['redis_password'],
    'db' => $env['redis_select']
];

// 队列消费者目录
$consumerDir = __DIR__.'/app/Common/Queue';

$worker = new Worker();
$worker->count = 2;
$worker->onWorkerStart = function () use ($redisUrl, $redisOptions, $consumerDir) {

    // 实例化客户端
    $client = new Client($redisUrl, $redisOptions);

    // 实例化消费者
    $dir_iterator = new \RecursiveDirectoryIterator($consumerDir);
    $iterator = new \RecursiveIteratorIterator($dir_iterator);

    foreach ($iterator as $file) {
        if (is_dir($file)) {
            continue;
        }
        $fileinfo = new \SplFileInfo($file);
        $ext = $fileinfo->getExtension();
        if ($ext === 'php') {
            $class = str_replace('/', "\\", substr(substr($file, strlen(__DIR__.'\\app\\')), 0, -4));
            if(class_exists($class)){
                $consumer = new $class();
                $queue = $consumer->queue;
                $client->subscribe($queue, [$consumer, 'consume']);
            }
        }
    }
};

Worker::runAll();

<?php
require_once '../vendor/autoload.php';

use Workerman\Worker;

$worker = new Worker('tcp://0.0.0.0:9501');
$worker->count = 1;

$worker->uidConnections = array();

$worker->onMessage = function ($connection, $data) {
    global $worker;
    // 判断当前客户端是否已经验证,即是否设置了uid
    if (!isset($connection->uid)) {
        $connection->uid = $data;
        /* 保存uid到connection的映射，这样可以方便的通过uid查找connection，
         * 实现针对特定uid推送数据
         */
        $worker->uidConnections[$connection->uid] = $connection;
        sendMessageByUid(1111, 'to uid= 1 send message');
        //return $connection->send('login success, your uid is ' . $connection->uid);
    }
};
// 当有客户端连接断开时
$worker->onClose = function ($connection) {
    global $worker;
    if (isset($connection->uid)) {
        // 连接断开时删除映射
        unset($worker->uidConnections[$connection->uid]);
    }
};

// 向所有验证的用户推送数据
function broadcast($message)
{
    global $worker;
    foreach ($worker->uidConnections as $connection) {
        $connection->send($message);
    }
}

// 针对uid推送数据
function sendMessageByUid($uid, $message)
{
    global $worker;
    if (isset($worker->uidConnections[$uid])) {
        $connection = $worker->uidConnections[$uid];
        $connection->send($message);
    }
}

// 运行所有的worker（其实当前只定义了一个）
Worker::runAll();
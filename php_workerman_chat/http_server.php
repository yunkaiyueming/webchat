<?php
require_once '../../../../www3/workerman-for-win/Autoloader.php';
use Workerman\Worker;

$http_worker = new Worker("http://0.0.0.0:8000");
$http_worker->onMessage = function($connection, $data){
	$data = file_get_contents("./chat.html");
    $connection->send($data);
};
Worker::runAll();
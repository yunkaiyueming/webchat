<?php
use Workerman\Worker;
require_once '../../../../www3/workerman-for-win/Autoloader.php';
require './chat_strategy.php';

$ws_worker = new Worker("websocket://0.0.0.0:12345");
$ws_worker->count = 4;

$ws_worker->onConnect = function($connection){
    echo "New connection \n";
};

$ws_worker->onMessage = function($connection, $data){
	echo "C: ".$data."\n";
	ChatStrategy($connection, $data);
};

$ws_worker->onClose = function($connection){
    echo "Connection closed\n";
};
Worker::runAll();
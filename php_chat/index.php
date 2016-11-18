<?php
set_time_limit(0);
require 'websocket.php';
require 'send_strategy.php';
require 'service.php';

$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
$Server->wsStartServer('127.0.0.1', 12345);
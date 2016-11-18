<?php
function wsOnMessage($clientID, $message, $messageLength, $binary) {
	global $Server;
	if($messageLength == 0) {
		return $Server->wsClose($clientID);
	}

	$receiveData = json_decode($message, TRUE);
	switch ($receiveData['type']){
		case 1 :
			UserJoin($receiveData['name'], $clientID);
			break;
		case 2:
			BroadCastMsg($receiveData['name'], $receiveData['msg']);
			break;
		case 3:
			LeaveChat($receiveData['name']);
			break;
		case 4:
			ToOneUser($receiveData['name'], $receiveData['to_name'], $receiveData['msg']);
	}
}

function wsOnOpen($clientID){
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );
	$Server->log( "$ip ($clientID) has connected." );
}

function wsOnClose($clientID, $status) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );
	$Server->log( "$ip ($clientID) has disconnected." );
}
?>
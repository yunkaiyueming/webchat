<?php
$connMap = array();
function UserJoin($from_name, $clientID){
	global $Server;
	global $connMap;
	if(isset($connMap[$from_name])){
		$responseMsg = array('action' => 1, 'name' => $from_name, 'resp_code'=>1);
		foreach($Server->wsClients as $id => $client){
			if($id==$clientID){
				$Server->wsSend($id, json_encode($responseMsg));
				return $Server->wsClose($id);
			}
		}
	}
	
	$connMap[$from_name] = $clientID;
	$responseMsg = array(
		'action' => 1,
		'name' => $from_name,
		'resp_code'=>1,
		'users'=>getUserList(),
	);
	foreach($Server->wsClients as $id => $client){
		$Server->wsSend($id, json_encode($responseMsg));
	}
}

function BroadCastMsg($from_name, $msg){
	global $Server;
	global $connMap;
	$responseMsg = array(
		'action' => 2,
		'name' => $from_name,
		'resp_code'=>1,
		'msg' => $msg,
		'users'=>getUserList(),
	);
	foreach($Server->wsClients as $id => $client){
		$Server->wsSend($id, json_encode($responseMsg));
	}
}

function LeaveChat($from_name){
	global $Server;
	global $connMap;
	unset($connMap[$from_name]);
	$responseMsg = array(
		'action' => 3,
		'name' => $from_name,
		'users'=>getUserList(),
	);
	
	foreach($Server->wsClients as $id => $client){
		$Server->wsSend($id, json_encode($responseMsg));
	}
}

function ToOneUser($from_user, $to_user, $msg){
	global $Server;
	global $connMap;
	$responseMsg = array(
		'action' => 4,
		'name' => $from_user,
		'msg' => $msg,
		'to_name' => $to_user,
		'users'=>getUserList(),
	);
	
	$Server->wsSend($connMap[$from_user], json_encode($responseMsg));
	$Server->wsSend($connMap[$to_user], json_encode($responseMsg));	
}

function getUserList(){
	global $connMap;
	$users = array_keys($connMap);
	$user_names = array();
	foreach($users as $name){
		$user_names[] = array('name'=>$name);
	}
	return $user_names;
}
<?php
$connMap = array();
function UserJoin($from_name, $conn){
	global $connMap;
	if(AddConnPool($conn, $from_name)==FALSE){
		$responseMsg = array('action' =>1, 'name' => $from_name, 'resp_code'=>0);
		$conn->send(json_encode($responseMsg));
		return $conn->close();
	}
	
	$connMap[$from_name] = $conn;
	$responseMsg = array(
		'action' => 1,
		'name' => $from_name,
		'resp_code'=>1,
		'users'=>getUserList(),
	);
	foreach($connMap as $conn){
		$conn->send(json_encode($responseMsg));
	}
}

function BroadCastMsg($from_name, $msg){
	global $connMap;
	$responseMsg = array(
		'action' => 2,
		'name' => $from_name,
		'resp_code'=>1,
		'msg' => $msg,
		'users'=>getUserList(),
	);
	foreach($connMap as $name => $conn){
		$conn->send(json_encode($responseMsg));
	}
}

function LeaveChat($from_name){
	global $connMap;
	unset($connMap[$from_name]);
	$responseMsg = array(
		'action' => 3,
		'name' => $from_name,
		'users'=>getUserList(),
	);
	
	foreach($connMap as $name => $conn){
		$conn->send(json_encode($responseMsg));
	}
}

function ToOneUser($from_user, $to_user, $msg){
	global $connMap;
	$responseMsg = array(
		'action' => 4,
		'name' => $from_user,
		'msg' => $msg,
		'to_name' => $to_user,
		'users'=>getUserList(),
	);
	
	$connMap[$from_user]->send(json_encode($responseMsg));
	$connMap[$to_user]->send(json_encode($responseMsg));	
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

function AddConnPool($conn, $name){
	global $connMap;
	if(empty($name)){
		return FALSE;
	}
	$users = array_keys($connMap);
	if(!in_array($name, $users)){
		$connMap[$name] = $conn;
		return TRUE;
	}
	return FALSE;
}

function ChatStrategy($conn, $data){
	$receiveData = json_decode($data, TRUE);
	switch ($receiveData['type']){
		case 1 :
			UserJoin($receiveData['name'], $conn);
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
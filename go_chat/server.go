package main

import (
	"encoding/json"
	"fmt"
	"html/template"
	"net/http"

	"golang.org/x/net/websocket"
)

const AddChatType = 1
const SendMsgType = 2
const LeaveChatType = 3
const ToOneUser = 4

type User struct {
	Name string `json:"name"`
}

type ReceiveMsg struct {
	Type   int    `json:"type"`
	Name   string `json:"name"`
	Msg    string `json:"msg"`
	ToName string `json:"to_name"`
}

type ResponseMsg struct {
	RespCode int    `json:"resp_code"`
	Action   int    `json:"action"`
	Users    []User `json:"users"`
	Uname    string `json:"name"`
	Msg      string `json:"msg"`
	ToName   string `json:"to_name"`
}

type WsCon struct {
	Name string
	Con  *websocket.Conn
}

var UserList = make([]User, 0, 50)
var WsPool = make([]WsCon, 0, 50)
var ConnCounter = 0

func Router() {
	http.HandleFunc("/", RenderTemplate)
	http.Handle("/chat", websocket.Handler(EchoServer))
}

func ChatHandler() {
	Router()
	err := http.ListenAndServe(":12345", nil)
	if err != nil {
		panic("ListenAndServe: " + err.Error())
	}
}

func EchoServer(ws *websocket.Conn) {
	var err error
	for {
		var receiveMsg string
		if err = websocket.Message.Receive(ws, &receiveMsg); err != nil {
			fmt.Println("Can't receive, reason:", err)
			ws.Close()
			break
		}

		fmt.Println("C: ", receiveMsg)
		if receiveMsg != "" {
			go SendMsg(ws, MsgReturn(receiveMsg, ws))
		}
	}
}

func RenderTemplate(w http.ResponseWriter, r *http.Request) {
	t, err := template.ParseFiles("./chat.html")
	if err != nil {
		fmt.Println(err)
	}
	t.Execute(w, nil)
}

func MsgReturn(msgStr string, ws *websocket.Conn) ResponseMsg {
	receiveMsg := &ReceiveMsg{}
	json.Unmarshal([]byte(msgStr), receiveMsg)

	respMsg := ResponseMsg{}
	switch receiveMsg.Type {
	case AddChatType:
		respMsg = HandleAddUser(receiveMsg.Name, ws)

	case SendMsgType:
		respMsg = HandleBroadcast(receiveMsg.Name, receiveMsg.Msg)

	case LeaveChatType:
		respMsg = HandleLeaveMsg(receiveMsg.Name, ws)

	case ToOneUser:
		fmt.Println(receiveMsg)
		respMsg = HandleToOneUser(receiveMsg.Name, receiveMsg.ToName, receiveMsg.Msg)
	}

	return respMsg
}

func HandleAddUser(name string, ws *websocket.Conn) ResponseMsg {
	Resp := ResponseMsg{}
	var code int
	var msg string
	for _, user := range UserList {
		if user.Name == name {
			Resp.RespCode = 0
			Resp.Action = 1
			Resp.Uname = name
			Resp.Msg = "该名字已被占用"
			return Resp
		}
	}

	UserList = append(UserList, User{Name: name})
	if !InPool(WsCon{Name: name}) {
		ConnCounter++
		WsPool = append(WsPool, WsCon{name, ws})
		code = 1
		msg = "加入成功"
	} else {
		code = 0
		msg = "加入失败，该用户已加入"
	}

	return ResponseMsg{
		RespCode: code,
		Action:   AddChatType,
		Users:    UserList,
		Uname:    name,
		Msg:      msg,
	}
}

func HandleBroadcast(name string, msg string) ResponseMsg {
	return ResponseMsg{
		RespCode: 1,
		Action:   SendMsgType,
		Users:    UserList,
		Uname:    name,
		Msg:      msg,
	}
}

func HandleLeaveMsg(name string, ws *websocket.Conn) ResponseMsg {
	DeleteUser(name)
	ws.Close()
	ConnCounter--
	RemovePool(WsCon{Name: name})
	return ResponseMsg{
		RespCode: 1,
		Action:   LeaveChatType,
		Users:    UserList,
		Uname:    name,
	}
}

func HandleToOneUser(fromUserName, toUserName, receiveMsg string) ResponseMsg {
	return ResponseMsg{
		RespCode: 1,
		Action:   ToOneUser,
		Users:    UserList,
		Uname:    fromUserName,
		Msg:      receiveMsg,
		ToName:   toUserName,
	}
}

func SendMsg(ws *websocket.Conn, MsgResp ResponseMsg) {
	respJson, _ := json.Marshal(MsgResp)
	//如果加入失败，只给该连接发送失败消息
	if MsgResp.RespCode == 0 {
		websocket.Message.Send(ws, string(respJson))
		return
	}

	fmt.Println("S:", string(respJson))
	if MsgResp.Action == ToOneUser { //单播
		SendToOne(MsgResp.Uname, MsgResp.ToName, string(respJson))
	} else {
		BroadcastMsg(string(respJson)) //广播
	}
}

func BroadcastMsg(respJson string) {
	for _, WsCon := range WsPool {
		if err := websocket.Message.Send(WsCon.Con, string(respJson)); err != nil {
			fmt.Println("Can't send:", err)
			RemovePool(WsCon)
			WsCon.Con.Close()
		}
	}
}

func SendToOne(fromUname, toUname, respStr string) {
	for _, WsCon := range WsPool {
		if WsCon.Name == fromUname || WsCon.Name == toUname {
			if err := websocket.Message.Send(WsCon.Con, respStr); err != nil {
				fmt.Println("Can't send:", err)
				RemovePool(WsCon)
				WsCon.Con.Close()
			}
		}
	}
}

func DeleteUser(name string) {
	var key int
	for i, u := range UserList {
		if u.Name == name {
			key = i
			break
		}
	}
	UserList = append(UserList[:key], UserList[key+1:]...)
}

func InPool(wsCon WsCon) bool {
	for _, con := range WsPool {
		if con.Name == wsCon.Name {
			return true
		}
	}
	return false
}

func RemovePool(leaveCon WsCon) {
	var key int
	for i, con := range WsPool {
		if con.Name == leaveCon.Name {
			key = i
		}
	}
	WsPool = append(WsPool[:key], WsPool[key+1:]...)
}

func main() {
	ChatHandler()
}

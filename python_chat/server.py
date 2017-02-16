# coding=utf8

from SimpleWebSocketServer import SimpleWebSocketServer, WebSocket
import json

clients = []
userNames = []
connMap = {}


class SimpleChat(WebSocket):
        def handleMessage(self):
                print self.data
                msg = json.loads(self.data, encoding="utf8")
                print msg['type'],type(msg['type'])

                if msg['type'] == 1:
                        self.userJoin(msg['name'])

                elif msg['type'] == 2:
                        self.broadCastMsg(msg['name'], msg['msg'])

                elif msg['type'] == 3:
                        self.leaveChat(msg['name'])

                elif msg['type'] == 4:
                        self.toOneUser(msg['name'], msg['to_name'], msg['msg'])

                else:
                        print "no define msg"

        def handleConnected(self):
                print self.address, 'connected'
                clients.append(self)

        def handleClose(self):
                clients.remove(self)
                print self.address, 'closed'


        def userJoin(self, from_name):
                print "handle join user";
                userNames.append({"name":from_name})
                connMap[from_name] = self
                print connMap

                for client in clients:
                        if client == self:
                                print 'send self msg'
                                msg = {'action': 1, 'name': from_name, 'resp_code': 1}
                                print json.dumps(msg, encoding="utf8", ensure_ascii=False)
                                client.sendMessage(json.dumps(msg, encoding="utf8", ensure_ascii=False))
                        else:
                                print 'send other msg'
                                msg = {'action':1, 'name': from_name, 'resp_code':1, 'users':userNames}
                                print json.dumps(msg, encoding="utf8", ensure_ascii=False)
                                client.sendMessage(json.dumps(msg, encoding="utf8", ensure_ascii=False))

        def broadCastMsg(self, from_name, msg):
                response_msg = {"action": 2, "name": from_name, "resp_code": 1, "msg": msg, "users": userNames}
                for client in clients:
                        client.sendMessage(json.dumps(response_msg, encoding="utf8", ensure_ascii=False))

        def leaveChat(self, from_name):
                for nameInfo in userNames:
                        if nameInfo["name"]==from_name:
                                userNames.remove(nameInfo)

                del connMap[from_name]

                for client in clients:
                        msg = {"action": 3, "name": from_name, "users": userNames}
                        client.sendMessage()

        def toOneUser(self, from_name, to_name, msg):
                response_msg = {"action": 4, "name": from_name, "to_name": to_name, "msg": msg, "users": userNames}
                connMap[from_name].sendMessage(json.dumps(response_msg, encoding="utf8", ensure_ascii=False))
                connMap[to_name].sendMessage(json.dumps(response_msg, encoding="utf8", ensure_ascii=False))


server = SimpleWebSocketServer('', 12345, SimpleChat)
server.serveforever()

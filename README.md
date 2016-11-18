# webchat
概述： web聊天，支持多人同时聊天(广播)， 两人私聊（单播）  
实现：后端用PHP和Go都实现了，实际运行，任选其一  
协议：websocket  
数据格式：json  

#两种实现：
1. PHP实现  
cd webchat_path/php_chat  
php -S 127.0.0.1:8000 chat.html  
php index.php  
浏览器访问：http://127.0.0.1:8000  

2. Go实现  
cd webchat_path/go_chat  
go run server.go  
浏览器访问：http://127.0.0.1:12345  

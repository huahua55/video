#!/bin/sh
PATH=/usr/local/php/bin:/opt/someApp/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# 1.执行 php 命令不需要到thinkphp项目的目录下 2.index.php为入口文件 3.第三个参数为需要执行方法的路由
# 采集所有
cd /data/www/video/

##ok迅雷下载所有天
#php think Cj name=okcyxlday#force=1
##ok所有天
#sleep 60
#php think Cj name=okzycjday#force=1
##135所有天
#sleep 60
#php think Cj name=day123cj#force=1
##135迅雷下载所有天
#sleep 60
#php think Cj name=xl135day#force=1
##卧龙所有天
#sleep 60
#php think Cj name=wlzyxsday#force=1
#1866采集所有天
sleep 60
php think Cj name=1866cjday#force=1
#1866迅雷下载采集所有天
sleep 60
php think Cj name=1866cjxlday#force=1
#秒播采集所有天
sleep 60
php think Cj name=mbzycjday#force=1
#秒播迅雷下载采集所有天
sleep 60
php think Cj name=mbzycjxlday#force=1


## 强制采集 #force=1
##001 更新ok资源站 级别当天 默认后台设置请求时间 小时级别
#php think Cj name=okzycj
#sleep 30
###OK迅雷下载链接
#php think Cj name=cjokzyxlxs
#sleep 30
###卧龙下载
#php think Cj name=wlzyxs

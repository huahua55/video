#!/bin/sh
PATH=/usr/local/php/bin:/opt/someApp/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# 1.执行 php 命令不需要到thinkphp项目的目录下 2.index.php为入口文件 3.第三个参数为需要执行方法的路由
# 当天
cd /data/www/video/
## 强制采集 #force=1

#001 更新ok资源站 级别当天 默认后台设置请求时间 小时级别
#php think Cj name=cjokzyxs
#sleep 30
###OK迅雷下载链接
#php think Cj name=cjokzyxlxs
#sleep 30
###卧龙采集
#php think Cj name=wlzyxs
#sleep 30
###135迅雷下载
#php think Cj name=135xlcj
#sleep 30
###135采集
#php think Cj name=135cj
#sleep 30
###1886采集
#php think Cj name=1886zycj
#sleep 30
###1886迅雷下载
#php think Cj name=1886zyxlcj
#sleep 30
###秒播下载
#php think Cj name=mbzycj
#sleep 30
###秒播迅雷下载
#php think Cj name=mbzyxlcj
#sleep 30
##最大下载
php think Cj name=zuidacj
#sleep 30
###最大迅雷下载
#php think Cj name=zuidaicjxl
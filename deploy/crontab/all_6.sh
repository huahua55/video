#!/bin/sh
PATH=/usr/local/php/bin:/opt/someApp/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# 1.执行 php 命令不需要到thinkphp项目的目录下 2.index.php为入口文件 3.第三个参数为需要执行方法的路由
# 采集所有
cd /data/www/video/

#最大采集所有天
# php think Cj name=zuidacjday#force=1
# sleep 360
# php think Cj name=zuidaicjxlday#force=1
## 强制采集 #force=1
##001 更新ok资源站 级别当天 默认后台设置请求时间 小时级别

php think Cj name=zuidacjday#force=1#custom_page=862
sleep 60
php think Cj name=zuidacjday#force=1#custom_page=1049
sleep 60
php think Cj name=zuidacjday#force=1#custom_page=1303


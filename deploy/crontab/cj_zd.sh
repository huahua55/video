#!/bin/sh
PATH=/usr/local/php/bin:/opt/someApp/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# 1.执行 php 命令不需要到thinkphp项目的目录下 2.index.php为入口文件 3.第三个参数为需要执行方法的路由
# 当天

sleep 5
cd /data/www/video/

chown -R www:www ../video


php think Cj name=zuidacj
#time1=$(date "+%M")
#len_time=${#time1}
#if ((len_time==2));then
#  if [ "$time1" = 31 ] || [ "$time1" = 01 ];then
#    php think Cj name=zuidacj
#  fi
#fi
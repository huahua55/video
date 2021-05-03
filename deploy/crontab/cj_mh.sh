#!/bin/sh
PATH=/usr/local/php/bin:/opt/someApp/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# 1.执行 php 命令不需要到thinkphp项目的目录下 2.index.php为入口文件 3.第三个参数为需要执行方法的路由
# 当天
cd /data/www/video/

chown -R www:www ../video


time1=$(date "+%M")
echo $time1
if [ "$time1" = 00 ] || [ "$time1" = 10 ] || [ "$time1" = 20 ] || [ "$time1" = 30 ] || [ "$time1" = 40 ] || [ "$time1" = 50 ];then
  sleep 3
#  php think Cj name=mhysday
fi
php think Cj name=kbzyw
sleep 2
#php think Cj name=mhysday
#php think Cj name=mhysday

#time1=$(date "+%M")
#len_time=${#time1}
#if ((len_time==2));then
#  time1_str=${time1:1}
#else
#  time1_str=${time1:0:1}
#fi
#if (($time1_str!=5));then
#  # 不存在 5 的时候跑麻花
#  sleep 8
#  php think Cj name=mhysday
#else
#  # 暂不处理
#  echo $time1_str
#fi
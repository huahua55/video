#!/bin/sh
PATH=/usr/local/php/bin:/opt/someApp/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# 1.执行 php 命令不需要到thinkphp项目的目录下 2.index.php为入口文件 3.第三个参数为需要执行方法的路由
# 当天


function rand(){
    min=$1
    max=$(($2-$min+1))
    num=$(($RANDOM+1000000000)) #增加一个10位的数再求余
    echo $(($num%$max+$min))
}

rnd=$(rand 1 2)


cd /data/www/video/
a=`ps -ef | grep pushData |grep name=up | grep -v grep | awk '{print $2}'`
if [ ! -n "$a" ]; then
  #空的
#  php think pushData name=up
   echo 1
else
  if (($rnd==2));then
     echo 1
  else
#    echo 1
     ps -ef | grep pushData |grep name=up | grep -v grep | awk '{print $2}' | xargs kill -9
  fi
  # 不是空的 先不杀死
fi
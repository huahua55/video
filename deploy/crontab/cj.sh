#!/bin/sh
PATH=/usr/local/php/bin:/opt/someApp/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# 1.执行 php 命令不需要到thinkphp项目的目录下 2.index.php为入口文件 3.第三个参数为需要执行方法的路由
# 当天
cd /data/www/video/

chown -R www:www ../video

## 强制采集 #force=1
ps -ef | grep Cj | grep -v grep | awk '{print $2}' | xargs kill -9
#001 更新ok资源站 级别当天 默认后台设置请求时间 小时级别
php think Cj name=cjokzyxs
sleep 3

php think Cj name=zuidacj
sleep 1

# 任务

a=`ps -ef | grep pushData |grep name= | grep -v grep | awk '{print $2}'`
if [ ! -n "$a" ]; then
#   echo 1
  #空的
  php think pushData name=i
else
  # 不是空的 先不杀死
  ps -ef | grep pushData |grep name=i | grep -v grep | awk '{print $2}' | xargs kill -9
  sleep 1
  php think pushData name=i
fi

sleep 1

function rand(){
    min=$1
    max=$(($2-$min+1))
    num=$(($RANDOM+1000000000)) #增加一个10位的数再求余
    echo $(($num%$max+$min))
}

rnd=$(rand 1 2)

a=`ps -ef | grep pushData |grep name=up | grep -v grep | awk '{print $2}'`
if [ ! -n "$a" ]; then
  #空的
  php think pushData name=up
#   echo 1
else
  if (($rnd==2));then
     echo 1
  else
#    echo 1
     ps -ef | grep pushData |grep name=up | grep -v grep | awk '{print $2}' | xargs kill -9
     sleep 1
     php think pushData name=up
  fi
  # 不是空的 先不杀死
fi





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
##OK迅雷下载链接
#php think Cj name=cjokzyxlxs
#sleep 30
###最大迅雷下载
#php think Cj name=zuidaicjxl
# 开始 采集
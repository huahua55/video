#!/bin/sh
PATH=/usr/local/php/bin:/opt/someApp/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# 1.执行 php 命令不需要到thinkphp项目的目录下 2.index.php为入口文件 3.第三个参数为需要执行方法的路由
# 采集所有
cd /data/www/video/


#ok所有天

# php think Cj name=okzycjday#force=1
# sleep 360
#ok迅雷下载所有天
# php think Cj name=okcyxlday#force=1

## 强制采集 #force=1
##001 更新ok资源站 级别当天 默认后台设置请求时间 小时级别



page=('501' '801' '1101' '1401' '1701')

for i in ${page[@]}
do
	{
		if [ $i -ne 1101 -a $i -ne 1401 -a $i -ne 1701 ]
		then
			php think Cj name=okzycjday#force=1#custom_page=$i
			sleep 360
		fi
	}&
done
# wait关键字确保每一个子进程都执行完成
wait
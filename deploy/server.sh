#!/bin/bash

fpm=php-fpm
fpm_config=/data/www/video/deploy/php-fpm.conf
fpm_pidfile=/var/run/php-fpm.pid
nginx=nginx

mkdir -p /var/log/php-fpm

start_fpm(){
	printf "重启 php-fpm..."
	$fpm -y $fpm_config -g $fpm_pidfile
	if [ -f "$fpm_pidfile" ]; then
		echo ""
		echo "  php-fpm started"
	else
		echo ""
		echo "  php-fpm failed!"
	fi
}

stop_fpm(){
	printf "stopping php-fpm..."
	echo ""
    if [ -f "$fpm_pidfile" ]; then
        kill `cat $fpm_pidfile`
    else
        echo ""
        echo "  stopped"
        return
    fi
    echo ""
    echo "  php-fpm stop"
}

ask_restart_fpm(){
	echo ""
	/bin/echo -n "重启 php-fpm?(y/n)[n] "
	read yn
	if [ "$yn" = "y" ] ; then
		:
	else
		echo "skip php-fpm"
		return
	fi
	stop_fpm
	start_fpm
}

case "$1" in 
	'start')
		stop_fpm
		start_fpm
		;;
	'stop') 
		stop_fpm
		;;
	'restart')
		ask_restart_fpm
		$nginx -s reload
		;;
	*)
		echo "Usage: $0 {start|stop|restart}"
		exit 1  
		;;
esac

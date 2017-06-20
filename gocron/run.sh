#!/bin/sh

case $1 in 
	start)
		chmod +x gocron
		nohup ./gocron 2>&1 >> gocroninfo.log 2>&1 /dev/null &
		echo "服务已启动..."
		sleep 1
	;;
	stop)
		killall gocron
		echo "服务已停止..."
		sleep 1
	;;
	restart)
		chmod +x gocron
		killall gocron
		sleep 1
		nohup ./gocron 2>&1 >> gocroninfo.log 2>&1 /dev/null &
		echo "服务已重启..."
		sleep 1
	;;
	*) 
		echo "$0 {start|stop|restart}"
		exit 4
	;;
esac
#!/bin/sh

case $1 in 
	start)
		chmod +x goapi
		nohup ./goapi 2>&1 >> goapiinfo.log 2>&1 /dev/null &
		echo "服务已启动..."
		sleep 1
	;;
	stop)
		killall goapi
		echo "服务已停止..."
		sleep 1
	;;
	restart)
		chmod +x goapi
		pid=$(ps x | grep "goapi" | grep -v grep | awk '{print $1}')
		if [ ! $pid ];then
		chmod +x goapi
		nohup ./goapi 2>&1 >> goapiinfo.log 2>&1 /dev/null &
		echo "服务已启动..."
		sleep 1
		else
		kill -USR2 $pid
		echo "服务已重启..."
	 	fi
		#echo $pid
		#sleep 1
		#nohup ./goapi 2>&1 >> goapiinfo.log 2>&1 /dev/null &
		#echo "服务已重启..."
		sleep 1
	;;
	*) 
		echo "$0 {start|stop|restart}"
		exit 4
	;;
esac

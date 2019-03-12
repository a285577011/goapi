#!/bin/sh
gobin='yx-go-api'
case $1 in 
	start)
		nohup ./"$gobin" 2>&1 >> "$gobin"info.log 2>&1 /dev/null &
		echo "服务已启动..."
		sleep 1
	;;
	stop)
		killall "$gobin"
		echo "服务已停止..."
		sleep 1
	;;
	restart)
		killall "$gobin"
		sleep 1
		nohup ./"$gobin" 2>&1 >> "$gobin"info.log 2>&1 /dev/null &
		echo "服务已重启..."
		sleep 1
	;;
	*) 
		echo "$0 {start|stop|restart}"
		exit 4
	;;
esac

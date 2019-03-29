#!/bin/sh
basepath=$(cd `dirname $0`; pwd)
fileName=$basepath'/consumerTopics'
yiiConsole='/home/wwwroot/default/lottery/yii'
command='kafka-consumer/consumer'
case $1 in 
	list)
		lists=$(ps x | grep -w "$command" | grep -v grep | awk '{print $8}')
		echo -e $lists
	;;
	add)
		if [ ! -n "$2" ] ;then
			echo "请输入主题";
		else
    	    echo $2 >> $fileName
			nohup $yiiConsole $command $2 > $basepath'/'$2".out" 2>&1 &
			echo "nohup $yiiConsole $command $2 & 2>&1 >>$basepath/$2.out"
			#nohup $yiiConsole" "$command" "$2 &
			echo "add kafka consumer..."
		fi
	;;
	stop)
		if [ ! -n "$2" ] ;then
			echo "请输入主题";
		else
		cat $fileName | while read line
		do
		   if [ "$2" = "$line" ];then
				echo $2
				sed -i "/$2/d" $fileName
				pid=$(ps x | grep -w "$yiiConsole $command $2" | grep -v grep | awk '{print $1}')
				if [ "$pid" ];then
				echo $pid
				kill -2 $pid
				fi
		   fi
		done
		    echo "stop kafka consumer..."
		fi
	;;
        kill)
                if [ ! -n "$2" ] ;then
                        echo "请输入主题";
                else
                cat $fileName | while read line
                do
                   if [ "$2" = "$line" ];then
                                echo $2
                                sed -i "/$2/d" $fileName
                                pid=$(ps x | grep -w "$yiiConsole $command $2" | grep -v grep | awk '{print $1}')
                                if [ "$pid" ];then
                                echo $pid
                                kill -9 $pid
                                fi
                   fi
                done
                    echo "kill kafka consumer..."
                fi
        ;;
	addAll)
		cat $fileName | while read line
		do
		nohup $yiiConsole $command $line > $basepath'/'$line".out" 2>&1 &
		done
	   echo "addAll kafka consumer..."
	;;
	*) 
		echo "$0 {list|add|stop|addAll|kill}"
		exit 4
	;;
esac

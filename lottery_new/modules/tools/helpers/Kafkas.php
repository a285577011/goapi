<?php

/*
 * 普通工具类
 */

namespace app\modules\tools\helpers;

/**
 * 说明 ：工具类
 * @author  kevi
 * @date 2017年7月6日 下午1:41:34
 */
class Kafkas {

public static $topic = 'test';    //消息主题
		public static $broker_list = '122.114.160.165:9092';   //kafka服务器地址端口
		public static $partition = 0;   //topic物理上的分组
		public $logFile = './kafka.log';   //日志文件

		private static $producer = null;    //生产者
 		protected $customer = null;	//消费者

 		private function __construct(){
 			//可以做一些参数配置
 		}

 		public static function getInstance(){

	 		if(!isset(self::$producer)){
	 			try {
	 				if(empty(self::$broker_list)){
	 					throw new \Exception("broker_list is null", 1);
	 				}

	 				$rk = new \RdKafka\Producer();  //创建生产者
	 				if(!isset($rk)){
	 					throw new \Exception("create producer error", 1);
	 				}

	 				$rk->setLogLevel(LOG_DEBUG);
	 				if(!$rk->addBrokers(self::$broker_list)){  //设置kafka服务器
	 					throw new \Exception("add producer error", 1);
	 				}
	 				self::$producer = $rk;

	 			} catch (Exception $e) {
	 				echo 'Message: ' . $e->getMessage();
	 			}
	 			return self::$producer;
	 		} else {
	 			return self::$producer;
	 		}

 				
 		}


 		//生产者生产消息
 		public static function send($message = []) {

 			$producer = self::getInstance();
 			$topic = $producer->newTopic(self::$topic);   //创建主题topic
 			//向指定的topic物理地址发消息
 			return $topic->produce(RD_KAFKA_PARTITION_UA, self::$partition, json_encode($message));
 		
 		}

 		//消费者消费消息
 		public function consumer(){

 			//设置消费者conf配置
 			$conf = new \RdKafka\Conf();
 			$conf->set('group.id', 0);   //设置groupid
 			$conf->set('metadata.broker.list', $this->broker_list);   //设置brokerlist

			//设置和topic相关参数
 			$topicConf = new \RdKafka\TopicConf();   
 			$topicConf->set('auto.offset.reset', 'smallest');   //从开头消费最新消息,类似设置from-beginning
 			$conf->setDefaultTopicConf($topicConf);   

 			//实例化消费者
 			$consumer = new \RdKafka\KafkaConsumer($conf);

 			//消费者订阅topic(可订阅多个)
 			$consumer->subscribe([$this->topic]);

 			echo "wait message...\n";

 			while (true) {      //阻塞等待获取消息队列中的消息

 				$message = $consumer->consume(120 * 1000);   //获取队列并往下执行消息,设置timeout
 				
 				switch ($message->err) {

 					case RD_KAFKA_RESP_NO_ERROR:   //当获取消息没有错误时执行处理消息操作
 						echo "message payload...";
 						//do anything you want （这里测试的话我就把消息写入文件了）
 						$msg = $message->payload;
 						file_put_contents(__DIR__ . '/kafka.log', $msg ."\n", FILE_APPEND);
 						break;

 				}
 				sleep(1);    //休眠一秒防止服务器压力过大崩溃
 			}
 		}
}

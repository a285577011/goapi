<?php
namespace app\modules\common\services;

use app\modules\common\helpers\Commonfun;

class KafkaService
{

	private static $queData = [];

	public static $rdTopicInstance = null;

	const LOG_PREFIX = 'Log';

	const LOG_NAME = 'SysBusinessLog';

	public static $partitionNum = 16;

	public static $partitionNumDev = 8;

	private static $producer = null;

	public static function getInstance()
	{
		if (! isset(self::$producer))
		{
			try
			{
				$conf = new \RdKafka\Conf();
				$conf->set('log.connection.close', 'false'); // 防止断开连接
				$conf->set('api.version.request', 'true'); // api请求版本
				$conf->set('socket.blocking.max.ms', 50); // broker 在 socket 操作时最大阻塞时间(提高发送速度，否则出现延迟1秒情况)
				//$conf->set('request.timeout.ms', 600001);
				$redis = \yii::$app->redis;
				$conf->setDrMsgCb(function ($kafka, $message) use ($redis) {
					if ($message->err)
					{
						$redis->executeCommand('lpush', ['kafka-fail-que',var_export($message, true)]);
					}else
					{
						// success
					}
				});
				$rk = new \RdKafka\Producer($conf);
				// $rk->setLogLevel(LOG_DEBUG);
				$rk->addBrokers(\Yii::$app->params['kafka_borker']);
				self::$producer = $rk;
			}
			catch (\Exception $e)
			{
				echo 'Message: ' . $e->getMessage();
			}
			return self::$producer;
		}else
		{
			return self::$producer;
		}
	}

	/**
	 * 异步发送队列
	 * @param unknown $queName
	 * @param unknown $params
	 */
	public static function addQue($queName, array $params, $log = true)
	{
		if ($log)
		{
			$queId = Commonfun::addQueue('Kafka-' . $queName, $queName, $params);
			$params['queueId'] = $queId;
		}
		self::addKfQueue($queName, $params);
		return ['code' => 600,'msg' => '发送成功'];
	}

	/**
	 * rdkafka
	 * @param unknown $queName
	 * @param unknown $params
	 */
	private static function addKfQueue($queName, $params)
	{
		$queName = self::getSubTopic($queName);
		$producer = self::getInstance();
		$topicConf = new \RdKafka\TopicConf();
		// 开启提交失败重复提交
		$topicConf->set("request.required.acks", 1); // 1 节点下线时候保证不会重复发送(可能丢失) -1要副本都确认 节点异常有可能节点会重复发(不会丢失)
		$topic = $producer->newTopic($queName, $topicConf); // 创建主题topic
		$params=json_encode($params);
		$res = $topic->produce(RD_KAFKA_PARTITION_UA, 0, $params);
		$producer->poll(0);
		return $res;
	}

	/**
	 * rdkafa(添加日志)
	 * @param string $logName 日志名称(主题名)
	 * @param unknown $data(数据)
	 */
	public static function addLog($logName, $data, $now = false)
	{
		if ($now)
		{
			$tableName = 'business_log';
			if (YII_ENV_DEV)
			{
				$tableName .= '_dev';
			}
			\Yii::$app->db2->createCommand()->insert($tableName, ['type' => $logName,'data' => json_encode($data),'log_ctime' => time(),'c_time' => time()])->execute();
			return;
		}
		$queName = self::getSubTopic(self::LOG_NAME);
		$producer = self::getInstance();
		$topicConf = new \RdKafka\TopicConf();
		// 开启提交失败重复提交
		$topicConf->set("request.required.acks", 0); //
		$topic = $producer->newTopic($queName, $topicConf);
		$res = $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(['topic'=>$logName,'type' => $logName,'data' => json_encode($data),'log_ctime' => time(),'c_time' => time()]));
		$producer->poll(0);
		return $res;
	}

	/**
	 * 获取订阅的主题名称
	 */
	public static function getSubTopic($topic)
	{
		$subTopic = 'Gula-' . $topic;
		return $subTopic;
	}

	/**
	 * 异步发送队列
	 * @param unknown $queName
	 * @param unknown $params
	 */
	public static function addProdQue($queName, array $params, $log = true)
	{
		if ($log)
		{
			$queId = Commonfun::addQueue('Kafka-' . $queName, $queName, $params);
			$params['queueId'] = $queId;
		}
		self::addProdKfQueue($queName, $params);
		return ['code' => 600,'msg' => '发送成功'];
	}

	/**
	 * rdkafka
	 * @param unknown $queName
	 * @param unknown $params
	 */
	private static function addProdKfQueue($queName, $params)
	{
		static $topic = null;
		static $rk = null;
		static $topicConf = null;
		$queName = self::getSubTopic($queName);
		if ($rk == null || $topicConf == null)
		{
			list ($rk,$topicConf) = self::setConfig2();
		}
		if (! isset($topic[$queName]))
		{
			$topic[$queName] = $rk->newTopic($queName, $topicConf);
		}
		$partitionNum = YII_ENV_DEV ? self::$partitionNumDev : self::$partitionNum;
		$nowP = rand(0, $partitionNum - 1);
		$res = $topic[$queName]->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($params));
		$rk->poll(0);
		return $res;
	}
}

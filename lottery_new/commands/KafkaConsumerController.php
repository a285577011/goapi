<?php
namespace app\commands;

use app\modules\common\services\KafkaService;
use yii\console\Controller;
use app\modules\common\models\Queue;

class KafkaConsumerController extends Controller
{

	protected $consumer;

	private $maxMessage = 32;
	// 最大消费数据
	/**
	 * kafka消费者(手动提交)
	 */
	public function actionConsumer($topic)
	{
		ini_set('memory_limit', '-1');
		pcntl_signal(SIGHUP, [$this,"sigHandler"]);
		pcntl_signal(SIGINT, [$this,"sigHandler"]);
		pcntl_signal(SIGQUIT, [$this,"sigHandler"]);
		pcntl_signal(SIGTERM, [$this,"sigHandler"]);
		$conf = new \RdKafka\Conf();
		
		// Set a rebalance callback to log partition assignments (optional)
		$conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
			switch ($err) {
				case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
					echo "Assign: ";
					// var_dump($partitions);
					$kafka->assign($partitions);
					break;
				case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
					
					echo "Revoke: ";
					// var_dump($partitions);
					$kafka->assign(NULL);
					break;
				
				default:
					\yii::$app->redis->executeCommand('lpush', ['kafka-setRebalanceCb-fail',var_export($err, true)]);
				// send error
				// throw new \Exception($err);
			}
		});
		
		// Configure the group.id. All consumer with the same group.id will consume
		// different partitions.
		$subTopic = KafkaService::getSubTopic($topic);
		// Initial list of Kafka brokers
		$conf->set('metadata.broker.list', \Yii::$app->params['kafka_borker']);
		$conf->set('group.id', $subTopic);
		$conf->set('log.connection.close', 'false');
		// $conf->set('enable.auto.commit', 'false');
		$conf->set('api.version.request', 'true');
		$conf->set('session.timeout.ms', '60000');
		$conf->set('queued.max.messages.kbytes', 1024);
		$topicConf = new \RdKafka\TopicConf();
		
		// Set where to start consuming messages when there is no initial offset in
		// offset store or the desired offset is out of range.
		// 'smallest': start from the beginning
		$topicConf->set('auto.offset.reset', 'earliest');
		// $topicConf->set('offset.store.method', 'broker');
		$topicConf->set('auto.commit.interval.ms', 1000);
		// Set the configuration to use for subscribed/assigned topics
		$conf->setDefaultTopicConf($topicConf);
		
		$this->consumer = new \RdKafka\KafkaConsumer($conf);
		
		// Subscribe to topic 'test'
		$this->consumer->subscribe([$subTopic]);
		
		echo "Waiting for partition assignment... (make take some time when\n";
		echo "quickly re-joining the group after leaving it.)\n";
		$maxMessage = 32;
		while (true)
		{
			$cnt = 0;
			while ($cnt ++ < $this->maxMessage)
			{
				$message = $this->consumer->consume(120 * 1000);
				switch ($message->err) {
					case RD_KAFKA_RESP_ERR_NO_ERROR:
						$className = '\app\modules\tools\kafka\\' . ucfirst($topic);
						$class = new $className();
						// var_dump(call_user_func_array(array($class,'run'), ['data' => json_decode($message->payload, true)]));
						$data = json_decode($message->payload, true);
						if (isset($data['queueId']) && $data['queueId'])
						{
							$q = Queue::findOne(['queue_id' => $data['queueId']]);
							if ($q && $q['status'] != 1)
							{
								KafkaService::addLog('kafka-repconsumer-' . $topic, $message->payload);
								echo $data['queueId'] . '-' . ':' . '重复消费';
								\yii::$app->db->close();
								\yii::$app->db2->close();
								$class = null;
								unset($class);
								break;
							}
						}
						call_user_func_array(array($class,'run'), ['data' => $data]);
						\yii::$app->db->close();
						\yii::$app->db2->close();
						$class = null;
						unset($class);
						/*
						 * try
						 * { // 如果失败尝试提交两次（防节点崩溃）
						 * $this->consumer->commit();
						 * }
						 * catch (\Exception $e)
						 * {
						 * try
						 * {
						 * \yii::$app->redis->executeCommand('lpush', ['kafka-commit-fail',$e->getMessage() . ';code:' . $e->getCode().';data:'.$message->payload]);
						 * // usleep(500000);
						 * // $this->consumer->commit();
						 * }
						 * catch (\Exception $e)
						 * {
						 * // \yii::$app->redis->executeCommand('lpush', ['kafka-commit-fail',$e->getMessage().';code:'.$e->getCode()]);
						 *
						 * // $this->consumer->commit();
						 * }
						 * }
						 */
						pcntl_signal_dispatch();
						break;
					case RD_KAFKA_RESP_ERR__PARTITION_EOF:
						echo "will wait for more..\n";
						pcntl_signal_dispatch();
						break;
					case RD_KAFKA_RESP_ERR__TIMED_OUT:
						echo "Timed out\n";
						echo memory_get_usage() . "\n";
						pcntl_signal_dispatch();
						break;
					default:
						\yii::$app->redis->executeCommand('lpush', ['kafka-consume-fail',$message->errstr() . ';code:' . $message->err]);
						// send
						// throw new \Exception($message->errstr(), $message->err);
						break;
				}
			}
		}
	}

	private function sigHandler($signo)
	{
		switch ($signo) {
			case SIGHUP:
			case SIGQUIT:
			case SIGTERM:
			case SIGINT:
				$this->consumer->unsubscribe();
				echo 'shutdown';
				exit();
				break;
			default:
		}
	}

	/**
	 * kafka消费者(自动提交)日志消费
	 */
	public function actionAutoConsumer($topic)
	{
		pcntl_signal(SIGHUP, [$this,"sigHandler"]);
		pcntl_signal(SIGINT, [$this,"sigHandler"]);
		pcntl_signal(SIGQUIT, [$this,"sigHandler"]);
		pcntl_signal(SIGTERM, [$this,"sigHandler"]);
		$conf = new \RdKafka\Conf();
		
		// Set a rebalance callback to log partition assignments (optional)
		$conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
			switch ($err) {
				case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
					echo "Assign: ";
					// var_dump($partitions);
					$kafka->assign($partitions);
					break;
				
				case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
					echo "Revoke: ";
					// var_dump($partitions);
					$kafka->assign(NULL);
					break;
				
				default:
					\yii::$app->redis->executeCommand('lpush', ['kafka-setRebalanceCb-fail',var_export($err, true)]);
				// send error
				// throw new \Exception($err);
			}
		});
		
		// Configure the group.id. All consumer with the same group.id will consume
		// different partitions.
		
		// Initial list of Kafka brokers
		$conf->set('metadata.broker.list', \Yii::$app->params['kafka_borker']);
		$conf->set('group.id', $topic);
		$conf->set('log.connection.close', 'false');
		$conf->set('enable.auto.commit', 'true');
		$conf->set('api.version.request', 'true');
		$topicConf = new \RdKafka\TopicConf();
		
		// Set where to start consuming messages when there is no initial offset in
		// offset store or the desired offset is out of range.
		// 'smallest': start from the beginning
		$topicConf->set('auto.offset.reset', 'earliest');
		$topicConf->set('offset.store.method', 'broker');
		// $topicConf->set('auto.commit.interval.ms', 100);
		// Set the configuration to use for subscribed/assigned topics
		$conf->setDefaultTopicConf($topicConf);
		
		$this->consumer = new \RdKafka\KafkaConsumer($conf);
		
		// Subscribe to topic 'test'
		$this->consumer->subscribe([$topic]);
		
		echo "Waiting for partition assignment... (make take some time when\n";
		echo "quickly re-joining the group after leaving it.)\n";
		while (true)
		{
			$message = $this->consumer->consume(120 * 1000);
			switch ($message->err) {
				case RD_KAFKA_RESP_ERR_NO_ERROR:
					list ($prefix,$logType) = explode('-', $topic);
					$className = '\app\modules\tools\kafka\\' . ucfirst($logType);
					$class = new $className();
					// var_dump(call_user_func_array(array($class,'run'), ['data' => json_decode($message->payload, true)]));
					call_user_func_array(array($class,'run'), ['data' => json_decode($message->payload, true)]);
					\yii::$app->db->close();
					pcntl_signal_dispatch();
					break;
				case RD_KAFKA_RESP_ERR__PARTITION_EOF:
					echo "will wait for more..\n";
					pcntl_signal_dispatch();
					break;
				case RD_KAFKA_RESP_ERR__TIMED_OUT:
					echo "Timed out\n";
					pcntl_signal_dispatch();
					break;
				default:
					\yii::$app->redis->executeCommand('lpush', ['kafka-consume-fail',$message->errstr() . ';code:' . $message->err]);
					// send
					// throw new \Exception($message->errstr(), $message->err);
					break;
			}
		}
	}

	/**
	 * kafka消费者(golang)
	 */
	public function actionGolangConsumer($topic,$data)
	{
		\Yii::$app->db->enableSlaves=false;
		$className = '\app\modules\tools\kafka\\' . ucfirst($topic);
		$class = new $className();
		// var_dump(call_user_func_array(array($class,'run'), ['data' => json_decode($message->payload, true)]));
		$data = json_decode($data,true);
		if (isset($data['queueId']) && $data['queueId'])
		{
			$q = Queue::findOne(['queue_id' => $data['queueId']]);
			if ($q && $q['status'] != 1)
			{
				KafkaService::addLog('kafka-repconsumer-' . $topic, $data);
				exit;
			}
		}
		var_dump(call_user_func_array(array($class,'run'), ['data' => $data]));
		exit;
	}
}

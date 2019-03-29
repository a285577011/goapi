<?php
namespace app\modules\tools\kafka;

use app\modules\common\models\ApiOrder;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\KafkaService;

class SyncThird implements Kafka
{

	public function run($params)
	{
		Commonfun::updateQueue($params['queueId'], 2);
		sleep(3);
		$curl_ret = \Yii::sendCurlPost($params['url'], $params['params']);
		if (!$curl_ret||$curl_ret['code'] != 600)
		{
			KafkaService::addLog('queue-sync-error:' . $params['url'], $params['params']);
			KafkaService::addLog('queue-error-reason:' . $params['url'], var_export($curl_ret,true).';from:queue-SyncThird');
		}
		Commonfun::updateQueue($params['queueId'], 3);
	}
}
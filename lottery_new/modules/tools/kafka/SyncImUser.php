<?php

namespace app\modules\tools\kafka;

use app\modules\common\services\CommonService;
use app\modules\common\services\KafkaService;
use app\modules\common\helpers\Commonfun;

class SyncImUser implements Kafka
{

	public function run($params)
	{
		//Commonfun::updateQueue($params['queueId'], 2);
		$res = CommonService::syncToIm($params);
		if (!$res)
		{
			KafkaService::addLog('SyncImUser', 'code:' . $res['code'] . ';msg:' . $res['msg'].';data:'.var_export($params,true));
		}
		//Commonfun::updateQueue($params['queueId'], 3);
	}

}
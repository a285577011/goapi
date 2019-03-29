<?php

namespace app\modules\tools\kafka;

use app\modules\common\services\CommonService;
use app\modules\common\services\KafkaService;
use app\modules\common\helpers\Commonfun;

class BackupOrder implements Kafka
{

	public function run($params)
	{
		Commonfun::updateQueue($params['queueId'], 2);
		$res = CommonService::backupOrderToSqlServer($params);
		if (!$res)
		{
			//KafkaService::addLog('BackupOrder-fali', 'code:' . $res['code'] . ';msg:' . $res['msg'].';data:'.var_export($params,true));
		}
		Commonfun::updateQueue($params['queueId'], 3);
	}

}
<?php

namespace app\modules\tools\kafka;

use app\modules\common\services\CommonService;
use app\modules\common\services\KafkaService;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\SyncService;

class SyncBackup implements Kafka
{

	public function run($params)
	{
		Commonfun::updateQueue($params['queueId'], 2);
		$res = SyncService::doSync($params['url'], $params['data']);
		if (!$res)
		{
			//KafkaService::addLog('BackupOrder-fali', 'code:' . $res['code'] . ';msg:' . $res['msg'].';data:'.var_export($params,true));
		}
		Commonfun::updateQueue($params['queueId'], 3);
	}

}
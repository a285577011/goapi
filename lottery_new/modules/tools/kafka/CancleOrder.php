<?php

namespace app\modules\tools\kafka;

use app\modules\common\services\OrderService;
use app\modules\common\helpers\Commonfun;

class CancleOrder implements Kafka
{
	public function run($params){
		Commonfun::updateQueue($params['queueId'], 2);
		$res=OrderService::cancleOrder($params);
		Commonfun::updateQueue($params['queueId'], 3);
	}

}
<?php

namespace app\modules\tools\kafka;

use app\modules\common\services\OrderService;
use app\modules\common\helpers\Commonfun;
/**
 * 失败重跑队列(加入队列失败情况)
 * @author Administrator
 *
 */
class RetryQue implements Kafka
{
	public function run($params){
		Commonfun::updateQueue($params['queueId'], 2);
		$res=OrderService::cancleOrder($params);
		Commonfun::updateQueue($params['queueId'], 3);
	}

}
<?php
namespace app\modules\tools\kafka;

use app\modules\common\services\AdditionalService;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\SyncService;

class OrderTrace implements Kafka
{

	public $args;

	public function run($params)
	{
		$this->args = $params;
		Commonfun::updateQueue($this->args['queueId'], 2);
		try
		{
			$trace = new AdditionalService();
			$ret = $trace->doTrace($this->args['traceInfo'], $this->args['periods'], $this->args['endTime']);
			SyncService::syncFromQueue('OrderTrace');
			Commonfun::updateQueue($this->args['queueId'], 3);
			return $ret;
		}
		catch (\yii\db\Exception $e)
		{
			Commonfun::updateQueue($this->args['queueId'], 4);
			return json_encode($e);
		}
	}
}
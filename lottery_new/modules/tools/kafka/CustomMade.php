<?php
namespace app\modules\tools\kafka;

use app\modules\common\helpers\Made;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\SyncService;

class CustomMade implements Kafka
{

	public $args;

	public function run($params)
	{
		$this->args = $params;
		Commonfun::updateQueue($this->args['queueId'], 2);
		try
		{
			$made = new Made();
			$result = $made->CustomMade($this->args['expert_no'], $this->args['lottery_code'], $this->args['bet_nums'], $this->args['programme_id'], $this->args['programme_price']);
			SyncService::syncFromQueue('CustomMade');
			Commonfun::updateQueue($this->args['queueId'], 3);
			return $result;
		}
		catch (\yii\db\Exception $ex)
		{
			Commonfun::updateQueue($this->args['queueId'], 4);
			return json_encode($ex);
		}
	}
}
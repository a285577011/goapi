<?php
namespace app\modules\tools\kafka;


use app\modules\common\helpers\Commonfun;
use app\modules\tools\helpers\Zmf;

class ZmfOrderCheck implements Kafka
{

	public $args;

	public function run($params)
	{
		$this->args = $params;
		Commonfun::updateQueue($this->args['queueId'], 2);
		try
		{
			$zmfObj = new Zmf();
			$ret = $zmfObj->to1019($params);
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
<?php
namespace app\modules\tools\kafka;

use app\modules\common\services\ProgrammeService;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\SyncService;

class Programme implements Kafka
{

	public $args;

	public function run($params)
	{
		$this->args = $params;
		Commonfun::updateQueue($this->args['queueId'], 2);
		try
		{
			$proSer = new ProgrammeService();
			$ret = $proSer->playProgramme($this->args['programmeId']);
			SyncService::syncFromQueue('Programme');
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
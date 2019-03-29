<?php

use app\modules\common\helpers\Commonfun;
use app\modules\common\services\CommonService;
require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';
/**
 * 说明: 
 * @author  
 * @date 2017年11月13日 
 * @param
 * @return 
 */
class syncImUser_job {

	public function perform()
	{
		Commonfun::updateQueue($this->args['queueId'], 2);
		try
		{
			CommonService::syncToIm($this->args);
			Commonfun::updateQueue($this->args['queueId'], 3);
		}
		catch (\yii\db\Exception $e)
		{
			Commonfun::updateQueue($this->args['queueId'], 4);
			return json_encode($e);
		}
	}

}

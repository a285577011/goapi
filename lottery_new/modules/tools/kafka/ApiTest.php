<?php
namespace app\modules\tools\kafka;

use app\modules\common\models\ApiOrder;
use app\modules\common\helpers\Commonfun;

class ApiTest implements Kafka
{

	public function run($params)
	{
		try
		{
			sleep(10);
			return 'success';
		}
		catch (\yii\db\Exception $e)
		{
			return json_encode($e);
		}
	}
}
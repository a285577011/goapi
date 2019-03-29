<?php
namespace app\modules\tools\kafka;

use app\modules\common\models\ApiOrder;
use app\modules\common\helpers\Commonfun;

class ApiTest1 implements Kafka
{

	public function run($params)
	{
		//Commonfun::updateQueue($params['queueId'], 2);
		try
		{
			var_dump($params);
			 $data = ApiOrder::findOne(['third_order_code' => 'xxx','user_id' => 2]);
			if (! $data)
			{
				$apiOrder = new ApiOrder();
				$apiOrder->api_order_code = Commonfun::getCode('API', "");
				$apiOrder->third_order_code = 'xxx';
				$apiOrder->user_id = 2;
				$apiOrder->lottery_code = '1';
				$apiOrder->periods = '1';
				$apiOrder->play_code = '1';
				$apiOrder->bet_val = '1';
				$apiOrder->bet_money = 1;
				$apiOrder->multiple = 1;
				$apiOrder->is_add = 1;
				$apiOrder->end_time = date('Y-m-d H:i:s');
				$apiOrder->create_time = date('Y-m-d H:i:s');
				$apiOrder->save();
			}
			//Commonfun::updateQueue($params['queueId'], 3);
		}
		catch (\yii\db\Exception $e)
		{
			//Commonfun::updateQueue($params['queueId'], 4);
			return json_encode($e);
		}
	}
}
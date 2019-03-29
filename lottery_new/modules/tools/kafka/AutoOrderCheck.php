<?php
namespace app\modules\tools\kafka;


use app\modules\common\helpers\Commonfun;
use app\modules\tools\helpers\Zmf;
use app\modules\tools\helpers\Nm;
use app\modules\tools\helpers\Jw;

class AutoOrderCheck implements Kafka
{

	public $args;

	public function run($params)
	{
		$this->args = $params;
		Commonfun::updateQueue($this->args['queueId'], 2);
		try
		{
			switch ($params['source']){
				case 'NM':
					$obj = new Nm();
					$data = [
						[
							'orderid'=>$params['order_code'],
						]
					];
					$datas['orderlist'] =$data;
					$ret=$obj->to802res($datas);
				case 'JW':
					$obj=new Jw();
					$ret=$obj->to200009($params['order_code']);
					break;
			}
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
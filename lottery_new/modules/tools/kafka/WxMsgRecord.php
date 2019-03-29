<?php
namespace app\modules\tools\kafka;

use app\modules\common\helpers\Commonfun;

class WxMsgRecord implements Kafka
{

	public $args;

	public function run($params)
	{
		$this->args = $params;
		//Commonfun::updateQueue($this->args['queueId'], 2);
		
		$data = $this->args['data'];
		$type = $this->args['type'];
		$status = $this->args['status'];
		$user_open_id = $this->args['user_open_id'];
		$order_code = $this->args['order_code'];
		$res = \Yii::$app->db->createCommand()->insert("wx_msg_record", ['msg_data' => $data,'type' => $type,'status' => $status,'user_open_id' => $user_open_id,'order_code' => $order_code,'create_time' => date('Y-m-d H:i:s')])->execute();
		
		if (! $res)
		{
			return false;
		}
		//Commonfun::updateQueue($this->args['queueId'], 3);
	}
}
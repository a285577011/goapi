<?php
namespace app\modules\tools\kafka;

use app\modules\common\models\LotteryOrder;
use app\modules\common\services\OrderService;
use app\modules\common\models\BettingDetail;
use app\modules\common\helpers\Commonfun;
use app\modules\orders\services\MajorService;
use app\modules\user\models\User;
use app\modules\common\helpers\OrderNews;
use yii\db\Query;
use app\modules\orders\helpers\OrderDeal;
use app\modules\common\services\KafkaService;
use app\modules\common\services\SyncService;
use app\modules\orders\models\AutoOutOrder;
use app\modules\tools\helpers\Nm;
use app\modules\tools\helpers\Jw;

class AutoWinMoney implements Kafka
{

	public $args;

	public function run($params)
	{
		$this->args = $params;
		Commonfun::updateQueue($this->args['queueId'], 2);
		$lotteryOrderCode = $params['order_code'];
		$tran = \Yii::$app->db->beginTransaction();
		try
		{
			$db = \Yii::$app->db;
			$orderData = AutoOutOrder::find()->where([ 
				'order_code' => $lotteryOrderCode 
			])->asArray()->all();
			if (! $orderData)
			{
				KafkaService::addLog('AutoWinMoney-nodata', $lotteryOrderCode);
				throw new \Exception('查无订单', 109);
			}
			$totalMoney=0;
			$error=0;
			foreach ($orderData as $v){
				switch ($v['source']){
					case 'ZMF':
						break;
					case 'NM':
						$data = [
							[
								'orderid'=>$v['out_order_code'],
							],
						];
						$datas['orderlist'] =$data;
						$nmObj = new Nm();
						$ret = $nmObj->to803($datas);
						KafkaService::addLog('AutoWinMoney-thirtdata', $ret);
						if($ret['head']['errorcode']==0){
							if($ret['body']['orderlist']['order']['errorcode']==4){//已中奖
								$totalMoney+=$ret['body']['orderlist']['order']['amount'];
							}else{
								continue;
								//throw new \Exception('第三方订单状态错误', 109);
								$error=1;
								return;
							}
						}
						else{
							continue;
							//throw new \Exception('第三方订单查询错误', 109);
							$error=1;
							return;
						}
						break;
					case 'JW';
					$obj=new Jw();
					$data=$obj->to200009Data($v['out_order_code']);
					KafkaService::addLog('AutoWinMoney-thirtdata', $data);
					if($data){
						foreach ($data as $v){
							if(in_array($v['awardStatus'], [3,2])){//中奖状态
								$totalMoney+=$v['totalPrize'];
							}
							else{
								continue;
								//throw new \Exception('第三方订单状态错误', 109);
							}
						}
					}
					else{
						continue;
						//throw new \Exception('第三方订单查询错误', 109);
					}
					break;
						
				}
			}
			if($error){
				//throw new \Exception('订单金额查询错误', 109);
			}
			$totalMoney = $totalMoney;
			$db->createCommand("update lottery_order set zmf_award_money = {$totalMoney} where lottery_order_code = '{$lotteryOrderCode}'")->execute();
			Commonfun::updateQueue($this->args['queueId'], 3);
			$tran->commit();
		}
		catch (\Exception $e)
		{
			$tran->rollBack();
			Commonfun::updateQueue($this->args['queueId'], 3);
			return ['code'=>$e->getCode(),'msg'=>$e->getMessage()];
		}
		return ['code'=>'600','msg'=>'成功'];
	}
}

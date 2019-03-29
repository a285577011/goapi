<?php
namespace app\modules\common\services;

class SyncService
{

	const SYNC_MODELS = [ // 同步到SQLSERVER的模型
							'\app\modules\common\models\PayRecord',
							'\app\modules\common\models\LotteryOrder',
							// '\app\modules\common\models\UserFunds',
							'\app\modules\common\models\BettingDetail',
							'\app\modules\common\models\IceRecord'
							];

	const SYNC_API_MAP = [  'publicinterface/interface/play-order' => 'order/main',//下单
							'publicinterface/interface/funds-pay' => 'order/pay',//余额支付
							'pay/qb-pay/qb-callback' => 'order/pay',//钱包支付回调
							'store/store/out-ticket' => ['order/update_odds','order/pay_record'],//门店出票
							'cron/cron/sub-order' => ['order/detail'],//手工出票
							'store/store/play-awards' => 'order/pay_record',//手动派奖
							'store/store/out-ticket-false'=>['order/pay_record','order/ice_record'],//出票失败 不一定有
							'cron/cron/sub-order-false' => ['order/detail','order/pay_record','order/ice_record'],//手工出票失败 ps(cron/cron/sub-order失败情况)不管
							'cron/time/programme-limit-play' => ['order/main','order/pay_record','order/ice_record'],//定时任务（合买截止时间30分钟内的出单）不一定有
							'publicinterface/interface/append-guarantee'=>'order/ice_record',//追加保底金额
							'cron/time/get-to-account'=>['order/pay_record','order/ice_record'],//查询到账（提现主动回调）一定有
							'publicinterface/interface/cancel-trace'=>'order/ice_record',//取消追期
							'pay/qb-pay/auth-call-back'=>'order/pay',//钱包二维码支付状态主动查询
							'pay/qb-pay/auth-call-back-false'=>'order/pay_record',//钱包二维码支付状态主动查询(失败)
							'openapi/thirdapi/transfer' => 'order/pay',//第三方订单
							'user/user/withdraw' => 'order/ice_record',//提现
                            'user/user/activity-login'=> ['order/main', 'order/pay_record']//赠送
		];

	const QUE_SYNC_API_MAP = ['LotteryJob' => 'order/detail',//生成子单
								'Programme' => ['order/main','order/pay_record','order/ice_record'],//合买队列
								'OrderTrace' => ['order/main','order/pay_record','order/ice_record'],//追期
								'CashArticle' => 'order/pay_record',//专家收款/会员购文未中退款 线程调方法
								'CustomMade' => 'order/pay_record',//定制用户进行跟单
								'ConfirmOutTicket' => ['order/update_odds','order/pay_record'],//自动出票确认 
								'LotteryJobFalse'=>['order/pay_record','order/ice_record'],//生成子单失败
								'AutoOrderCreate'=>['order/pay_record','order/ice_record'],//自动出票
								'ThirdOrderCreate' => ['order/main','order/pay_record'],//第三方订单队列
                                                                'OrderPollingStore' => ['order/update_odds','order/pay_record'],//旗舰店门店轮循
		];

	const ASYNC = 0;
	// 是否异步 1全部走异步
	/**
	 * 单页面执行需要同步的表写入队列
	 */
	public static function syncFromHttp($uri='',$now = true)
	{
		if (! \Yii::$app->params['sync_order'])
		{
			return;
		}
		!$uri&&$uri = \Yii::$app->request->getPathInfo();
		if (! isset(self::SYNC_API_MAP[$uri]))
		{
			return;
		}
		// $modulesName=\Yii::$app->controller->module->id;
		// $controllerName=\Yii::$app->controller->id;
		// $actionName=\Yii::$app->controller->action->id;
		if (is_array(self::SYNC_API_MAP[$uri]))
		{
			foreach (self::SYNC_API_MAP[$uri] as $uric)
			{
				$syncApi[] = \Yii::$app->params['backup_sqlserver'] . $uric;
			}
		}else
		{
			$syncApi = \Yii::$app->params['backup_sqlserver'] . self::SYNC_API_MAP[$uri];
		}
		self::ASYNC && $now = false;
		$data = self::getSyncData();
		self::doSync($syncApi, $data, $now,$uri);
		return;
	}

	/**
	 * 同步
	 * @param unknown $argv
	 */
	public static function doSync($api, $argv, $now = true,$from='')
	{
		if (! $argv)
		{
			return true;
		}
		if (! $now)
		{ // 异步
			KafkaService::addQue('SyncBackup', ['url' => $api,'data' => $argv], true);
			return true;
		}
		$data = json_encode($argv);
		$api = (array) $api;
		foreach ($api as $apic)
		{
			$continue=0;
			switch ($apic){
				case \Yii::$app->params['backup_sqlserver'] .'order/pay_record':
					if(!array_key_exists('pay_record', $argv)){
						KafkaService::addLog('sync-nodata:' . $apic, $argv);
						$continue=1;
					}
					break;
				case \Yii::$app->params['backup_sqlserver'] .'order/ice_record':
					if(!array_key_exists('ice_record', $argv)){
						KafkaService::addLog('sync-nodata:' . $apic, $argv);
						$continue=1;
					}
					break;
				case \Yii::$app->params['backup_sqlserver'] .'order/main':
					if(!array_key_exists('lottery_order', $argv)){
						KafkaService::addLog('sync-nodata:' . $apic, $argv);
						$continue=1;
					}
					break;
					
			}
			if($continue){
				continue;
			}
			$curl_ret = \Yii::sendCurlPost($apic, $data);
			if (!$curl_ret||$curl_ret['code'] != 600)
			{
				KafkaService::addQue('SyncThird', ['url'=>$apic,'params'=>$data]);
				KafkaService::addLog('sync-error:' . $apic, $argv);
				KafkaService::addLog('sync-error-reason:' . $apic, var_export($curl_ret,true).';from:'.$from);
			}
		}
		return true;
	}

	/**
	 * 异步队列执行需要同步的表
	 */
	public static function syncFromQueue($queueName, $now = true)
	{
		if (! \Yii::$app->params['sync_order'])
		{
			return;
		}
		if (! isset(self::QUE_SYNC_API_MAP[$queueName]))
		{
			return;
		}
		// $modulesName=\Yii::$app->controller->module->id;
		// $controllerName=\Yii::$app->controller->id;
		// $actionName=\Yii::$app->controller->action->id;
		if (is_array(self::QUE_SYNC_API_MAP[$queueName]))
		{
			foreach (self::QUE_SYNC_API_MAP[$queueName] as $uri)
			{
				$syncApi[] = \Yii::$app->params['backup_sqlserver'] . $uri;
			}
		}else
		{
			$syncApi = \Yii::$app->params['backup_sqlserver'] . self::QUE_SYNC_API_MAP[$queueName];
		}
		$data = self::getSyncData();
		self::doSync($syncApi, $data, $now,$queueName);
		return;
	}

	public static function getSyncData()
	{
		$argv = [];
		$tmp = [];
		$unqArgv = [];
		foreach (self::SYNC_MODELS as $model)
		{
			if ($model::$syncData)
			{ // 需要同步
				foreach ($model::$syncData as $v)
				{
					$pk = $model::getPk();
					$tmp[$model::tableName()] = $pk;
					if(isset($v['set'])&&$v['set']){
						foreach ($v['where'] as $kk=>$vv){
							if(isset($v['set'][$kk])){
								$v['where'][$kk]=$v['set'][$kk];
							}
						}
					}
					if (isset($argv[$model::tableName()]))
					{
						$argv[$model::tableName()] = array_merge($argv[$model::tableName()], $model::find()->where($v['where'])->asArray()->all());
					}else
					{
						$argv[$model::tableName()] = $model::find()->where($v['where'])->asArray()->all();
					}
				}
				foreach ($argv as $k => $v)
				{
					foreach ($v as $vc)
					{
						$unqArgv[$k][$vc[$tmp[$k]]] = $vc;
					}
				}
			}
		}
		$data = [];
		foreach ($unqArgv as $k => $v)
		{
			$data[$k] = array_values($v);
		}
		return $data;
	}
}

<?php
namespace app\modules\tools\kafka;

use app\modules\openapi\models\CallbackLog;
use app\modules\openapi\models\CallbackDetail;
use app\modules\openapi\models\CallbackBase;
use app\modules\common\services\KafkaService;
use yii\db\Expression;
use app\modules\common\helpers\Commonfun;

/**
 *类说明：回调第三方队列
 */
class CallbackThird implements Kafka
{

	public $args;

	public function run($params)
	{
		$this->args=$params;
//		Commonfun::updateQueue($this->args['queueId'], 2);
		try
		{
			$baseData = CallbackBase::findOne(['code' => $params['code']]);
			if (!$baseData)
			{
				KafkaService::addLog('CallbackThird_error4', var_export($params, true));
				throw new \Exception('110');
			}
			$callbackDetail = new CallbackDetail();
			$callbackDetail->callback_base_id = $baseData->id;
			$callbackDetail->exec_status = 0;
			$callbackDetail->callback_status = 0;
			$callbackDetail->exec_times = 0;
			$callbackDetail->params = json_encode($params['params'], JSON_FORCE_OBJECT);
			$callbackDetail->c_time = time();
			$callbackDetail->u_time = time();
			$callbackDetail->url = $baseData->url;
			$count = 0;
			$success=false;
			while (!$success && $count < $baseData->times)
			{//回调
				$success=true;
				$res = \yii::curlPost($callbackDetail->url, $params['params']);
                                KafkaService::addLog('CallbackThird_error4', var_export($res, true));
				if($res['error']||$res['data']!=1){
					$success=false;
				}
				$CallbackLog = new CallbackLog();
				$CallbackLog->callback_base_id = $callbackDetail->callback_base_id;
				$CallbackLog->url = $callbackDetail->url;
				$CallbackLog->params = json_encode($params['params'], JSON_UNESCAPED_UNICODE);
				$CallbackLog->return = json_encode($res, JSON_UNESCAPED_UNICODE);
				$CallbackLog->c_time = time();
				if (! $CallbackLog->save())
				{
					KafkaService::addLog('CallbackThird_error6', var_export($callbackDetail->getFirstErrors(), true));
					throw new \Exception('2222');
				}
				$count ++;
				if(!$success){
					sleep(1);
				}
			}
			$update = [];
			$callbackDetail->exec_status = 2;
			$callbackDetail->u_time = time();
			$callbackDetail->exec_times = $count;
			if ($success)
			{//回调成功
				$callbackDetail->callback_status = 1;
			}else
			{//回调失败
				$callbackDetail->callback_status = 2;
			}
			if (! $callbackDetail->save())
			{//保存detail
				KafkaService::addLog('CallbackThird_error5', var_export($callbackDetail->getFirstErrors(), true));
				throw new \Exception('111');
			}
		}
		catch (\Exception $e)
		{
//			Commonfun::updateQueue($this->args['queueId'], 3);
		}
//		Commonfun::updateQueue($this->args['queueId'], 3);
	}
}

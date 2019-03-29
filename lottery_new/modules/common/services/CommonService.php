<?php

namespace app\modules\common\services;

class CommonService {
	const SYNC_SQLSERVER_TYPE = [//同步到sqlserver类型
			'1' => '下单',
			'2' => '扫码支付',
			'3' => '出票',
			'4' => '追期',
			'5' => '合买成单',
			'6' => '退款',
			'7' => '派奖',
			'8' => '发起合买',
			'9' => '余额支付',
			'10' => '充值',
	];
	/**
	 * 备份订单相关数据到SQLSERVER
	 * @param unknown $argv
	 */
	public static function backupOrderToSqlServer($argv){
		$token = \Yii::$app->params['sqlserver_token'];
		$data['tablename'] = $argv['tablename'];
		$fields=$argv['field'];
		switch ($argv['type']){
			case 'update'://更新
					$updateData= (new \yii\db\Query())->select($fields)->from($data['tablename'])->where($argv['data']['where'])->all();
					$data['keyfield'] = $argv['pkField'];
					$data['signdata'] = md5($data['tablename'] . $data['keyfield'] . $token);
					$data['data']=$updateData;
				break;
			case 'insert'://插入
				$data['keyfield'] = $argv['pkField'];
				$data['signdata'] = md5($data['tablename'] . $argv['pkField'] . $token);
				$data['data']= (new \yii\db\Query())->select($fields)->from($data['tablename'])->where($argv['data']['where'])->all();//接口只支持二维数组
				break;
		}
		$surl = \Yii::$app->params['backup_sqlserver'];
		$data=  json_encode($data);
		$curl_ret = \Yii::sendCurlPost($surl, $data);
		if($curl_ret['code']==1){
			return true;
		}
		KafkaService::addLog('backupOrderToSqlServer', ['data'=>$argv,'requestResult'=>$curl_ret]);
		return false;
	}
	/**
	 * 同步数据给群聊数据库
	 */
	public static function syncToIm($argv){
		$token = \Yii::$app->params['sync_im_token'];
		$data['tablename'] = $argv['tablename'];
		switch ($argv['type']){
			case 'update'://更新
				if(isset($argv['pk'])){//有主键的更新
					$data['keyfield'] = array_keys($argv['pk'])[0];
					$fields="*";
					$updateData= (new \yii\db\Query())->select($fields)->from($data['tablename'])->where($argv['data']['where'])->one();
					$data['keyfield'] = array_keys($argv['pk'])[0];
					$data['signdata'] = md5($data['tablename'] . $data['keyfield'] . $token);
					$data['data'][0]=$updateData;//接口只支持二维数组
				}
				else{
					$fields="*";
					$updateData= (new \yii\db\Query())->select($fields)->from($data['tablename'])->where($argv['data']['where'])->all();
					$data['keyfield'] = $argv['pkField'];
					$data['signdata'] = md5($data['tablename'] . $data['keyfield'] . $token);
					$data['data']=$updateData;
				}
				break;
			case 'insert'://插入
				$data['keyfield'] = $argv['pkField'];
				$data['signdata'] = md5($data['tablename'] . $argv['pkField'] . $token);
				$data['data'][0]=$argv['data'];//接口只支持二维数组
				break;
		}
		$surl = \Yii::$app->params['sync_im_api'];
		$data=  json_encode($data);
		$curl_ret = \Yii::sendCurlPost($surl, $data);
		if($curl_ret['code']==1){
			return true;
		}
		KafkaService::addLog('syncToIm', ['data'=>$argv,'requestResult'=>$curl_ret]);
		return false;
	}
	/**
	 * 程序执行完成后执行
	 */
	public static function scriptComplete(){
	}
}

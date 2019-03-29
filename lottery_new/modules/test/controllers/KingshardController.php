<?php

namespace app\modules\test\controllers;

use yii\web\Controller;
use app\modules\common\models\PayRecord;
use app\modules\common\models\LotteryOrder;

class KingshardController extends Controller {
	public function actionShard(){
		try {
			$p=PayRecord::updateAll(['order_code'=>1],['cust_no'=>'gl00030117']);
		} catch (\Exception $e) {
			print_r($e->getMessage());die;
		}
		//$p=PayRecord::find()->where(['cust_no'=>'gl00030117'])->one(\yii::$app->db3);
		//$p=LotteryOrder::find()->one(\yii::$app->db3);
		print_r($p);die;
	}


}

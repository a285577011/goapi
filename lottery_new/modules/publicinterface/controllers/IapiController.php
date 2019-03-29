<?php
namespace app\modules\publicinterface\controllers;
use yii\web\Controller;
use app\modules\common\helpers\Commonfun;
use app\modules\common\models\PayRecord;
/**
 * 内部接口
 * @author Administrator
 *
 */
class IapiController extends Controller {
    public function actionAddOrder(){
    	$request = \Yii::$app->request;
    	$type = $request->post('type', '');
    	$orderCode = $request->post('order_code', '');
    	$money = $request->post('money', '');
    	switch ($type){
    		case 'zhonchou':
    			$insert = [
    				"body" => '众筹下单',
    				"order_code" => $orderCode,
    				"pay_no" => Commonfun::getCode("PAY", "L"),
    				"pay_type" => 100,
    				"pay_type_name" => '众筹',
    				"pay_pre_money" => $money,
    				"cust_no" => \yii::$custNo,
    				"user_id" => '',
    				"cust_type" => 1,
    				"modify_time" => date("Y-m-d H:i:s"),
    				"create_time" => date("Y-m-d H:i:s"),
    				"status" => 0,
    			];
    			$res=PayRecord::addData($insert);
    			if($res){
    				return \yii::jsonResult(600, '', $insert);
    			}
    			return \yii::jsonError(110, '下单失败');
    			break;    			
    		case 'shop':
    			break;
    	}
    }
}


<?php
namespace app\modules\test\controllers;



use app\modules\common\services\SyncService;
use app\modules\tools\kafka\LotteryJob;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\BettingDetail;
use yii\web\Controller;
use app\modules\common\models\PayRecord;
use app\modules\common\models\IceRecord;
class SyncController extends Controller
{

   public function actionSyncOrder(){
   	$get=\Yii::$app->request->get();
   	$orderCode=$get['orderCode'];
   	LotteryOrder::addQueSync(LotteryOrder::$syncInsertType, '*', ['lottery_order_code'=>$orderCode]);
   	SyncService::syncFromHttp('publicinterface/interface/play-order');
   	echo 'success';
   }
   public function actionSyncBett(){
   	$get=\Yii::$app->request->get();
   	$orderCode=$get['orderCode'];
   	BettingDetail::addQueSync(BettingDetail::$syncInsertType, '*', ['lottery_order_code'=>$orderCode]);
   	SyncService::syncFromQueue('LotteryJob');
   	echo 'success';
   }
   public function actionSyncPayre(){
   	$get=\Yii::$app->request->get();
   	$payRecordId=$get['id'];
   	PayRecord::addQueSync(PayRecord::$syncInsertType, '*', ['pay_record_id'=>$payRecordId]);
   	SyncService::syncFromQueue('CustomMade');
   	echo 'success';
   }
   public function actionSyncIce(){
   	$get=\Yii::$app->request->get();
   	$id=$get['id'];
   	IceRecord::addQueSync(IceRecord::$syncInsertType, '*', ['ice_record_id'=>$id]);
   	SyncService::syncFromHttp('user/user/withdraw');
   	echo 'success';
   }
   public function actionSyncUpdateodd(){
   	$get=\Yii::$app->request->get();
   	$orderCode=$get['orderCode'];
   	LotteryOrder::addQueSync(LotteryOrder::$syncInsertType, '*', ['lottery_order_code'=>$orderCode]);
   	BettingDetail::addQueSync(BettingDetail::$syncInsertType, '*', ['lottery_order_code'=>$orderCode]);
   	PayRecord::addQueSync(PayRecord::$syncInsertType, '*', ['order_code'=>$orderCode,'pay_type'=>9]);
   	SyncService::syncFromHttp('store/store/out-ticket');
   	echo 'success';
   }
   public function actionT(){
   	echo '测试sss';
   	exit;
   }
}

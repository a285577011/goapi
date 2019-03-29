<?php

namespace app\modules\orders\controllers;

use Yii;
use yii\web\Controller;
use app\modules\orders\helpers\DealOrder;
use app\modules\common\services\OrderService;
use app\modules\user\models\CouponsDetail;
use app\modules\common\models\PayRecord;


class OrderController extends Controller {
    
    /**
     * 说明: 处理推迟或者取消的足球场次订单明细
     * @author  kevi
     * @date 2017年11月7日 上午9:28:36
     * @param mid  需要处理的场次mid
     * @return 
     */
    public function actionDelaySchedule(){
        $request = \Yii::$app->request;
        $scheduleMid = $request->post('mid');
        if(!$scheduleMid){
            return $this->jsonError(109, '参数错误');
        }
        $DealOrder = new DealOrder();
        $helpRet = $DealOrder->dealDelaySchedule($scheduleMid);
        if($helpRet['code']==1){
            return $this->jsonError(100, $helpRet['msg']);
        }
        return $this->jsonResult(600, '推迟场次处理完成', $helpRet['data']);
    }

	/**
	 * 获取订单金额所有可用的折扣列表
	 */
	public function actionDiscountList()
	{
		\Yii::$app->db->enableSlaves=false;
		$request = \Yii::$app->request;
		$case =$request->post('case');
		$money = $request->post('money');
		! $money && $this->jsonError(109, '金额错误');
		$orderType = $request->post('order_type', 1);
		! $orderType && $this->jsonError(109, '订单类型错误');
		$LotteryCode = $request->post('lottery_code');
		! $LotteryCode && $this->jsonError(109, '彩种类型参数错误');
		// 获取用户优惠信息
		$data = OrderService::getDiscountList($this->custNo, $money,$LotteryCode, $orderType,$case);
		$this->jsonResult(600, '', $data);
	}
	/**
	 * 获取下单前可用的优惠券列表
	 */
	public function actionCouponsList(){
		$request = \Yii::$app->request;
		$case =$request->post('case');
		if($case){//追期不能用优惠
			$this->jsonResult(600, '', []);
		}
		$money = $request->post('money');
		! $money && $this->jsonError(109, '金额错误');
		$orderType = $request->post('order_type', 1);
		! $orderType && $this->jsonError(109, '订单类型错误');
//		if($orderType!=1){
//			$this->jsonResult(600, '', []);
//		}
		$LotteryCode = $request->post('lottery_code');
		! $LotteryCode && $this->jsonError(109, '彩种类型参数错误');
		$type = $request->post('type',1);
		$page=$request->post('page',1);
		$pageSize=$request->post('size',10);
		$m=new CouponsDetail();
		$total=$m->couponsNum($this->custNo, $LotteryCode, $money);
		$data=$m->orderUseCoupon($this->custNo, $LotteryCode, $money,$type,$page,$pageSize);
		$data['data']['total']=$total['data'];
		//$this->jsonResult(600, '', $data);
		if($data['code']==600){
			$this->jsonResult($data['code'], '', $data['data']);
		}
		$this->jsonError($data['code'],$data['msg']);
	}

	/**
	 * 取消订单
	 */
	public function actionCancleOrder()
	{
		$request = \Yii::$app->request;
		$orderCode = $request->post('order_code');
		! $orderCode && $this->jsonError(109, '订单编号不能为空');
		$payData = PayRecord::findOne(["order_code" => $orderCode,'cust_no' => $this->custNo]);
		! $payData && $this->jsonError(110, '订单错误');
		if($payData['status']!=0){
			$this->jsonError(111, '订单状态错误');
		}
		$ret = OrderService::cancleOrder($payData);
		if ($ret['code'] == 600)
		{
			$this->jsonResult($ret['code'], $ret['msg'], '');
		}
		$this->jsonError($ret['code'], $ret['msg']);
	}
	/**
	 * 检查优惠券的可用性
	 */
	public function actionCheckCoupon(){
		$request = \Yii::$app->request;
		$cdModel=new CouponsDetail();
		$coupons = $request->post('coupons');
		!$coupons&& $this->jsonError(110, '优惠券错误');
		$LotteryCode = $request->post('lottery_code');
		! $LotteryCode && $this->jsonError(109, '彩种类型参数错误');
		$money = $request->post('money');
		! $money && $this->jsonError(109, '金额错误');
		$data=$cdModel->checkCoupon($this->custNo, $coupons, $LotteryCode, $money);
		if($data['code']==600){
			$this->jsonResult($data['code'], '', $data['data']);
		}
		$this->jsonError($data['code'],$data['msg']);
	}

}


<?php

namespace app\modules\pay\controllers;

use yii\web\Controller;
use app\modules\pay\helpers\PayTool;
use app\modules\common\models\PayRecord;
use app\modules\common\models\LotteryOrder;
use app\modules\common\services\PayService;
use app\modules\common\models\LotteryAdditional;
use app\modules\common\models\Programme;
use app\modules\common\services\SyncService;

/**
 * Default controller for the `tools` module
 */
class QbPayController extends Controller {

    /**
     * 说明: 咕啦钱包回调
     * @author  kevi
     * @date 2017年8月18日 上午10:26:32
     * @param
     * @return 
     */
    public function actionQbCallback() {
        $request = \Yii::$app->request;
        $returnCode = $request->post('return_code'); //返回编码
        $returnMsg = $request->post('return_msg'); //返回说明
        $money = $request->post('money'); //实际支付金额
        $payStatus = $request->post('pay_status'); //支付状态success、close、finish、unkown
        $payTime = $request->post('pay_time'); //支付时间戳
        $tradeNo = $request->post('trade_no'); //订单编号(钱包的)
        $attach = $request->post('attach'); //商户订单号(我们的订单id)
        $cust_no = $request->post('cust_no'); //商户编号
        $pay_channel = $request->post('pay_channel'); //01支付宝，02微信
        $order_id = $request->post('order_id'); //支付订单id(钱包的)

        if ($payStatus == "success") {
            $paySer = new \app\modules\common\services\PayService();
            $paySer->notify($attach, $order_id, $money, $payTime);
        }
        SyncService::syncFromHttp();
        return 'success';
    }

    /**
     * 说明: 生成钱包二维码
     * @author  kevi
     * @date 2017年8月18日 下午5:39:40
     * @param string not nul orderCode    订单Code
     * @return 
     */
    public function actionCreateQbQrcode() {
        $request = \Yii::$app->request;
        $orderCode = $request->post('order_code');
        $order = LotteryOrder::find()->select(['source', 'source_id'])->where(['lottery_order_code' => $orderCode])->asArray()->one();
        if (!empty($order) && $order['source'] == 2) {
            $total = LotteryAdditional::find()->select(['user_id', 'cust_no', 'total_money'])->where(['lottery_additional_id' => $order['source_id']])->asArray()->one();
            $service = new PayService();
            $service->way_type = 'GLC';
            $service->pay_way = 4;
            $service->cust_no = $total['cust_no'];
            $service->payPreMoney = $total['total_money'];
//            $service->betMoney = $betMoney;
            $service->body = "充值";
            $service->custType = 1;
            $service->user_id = $total['user_id'];
            $service->order_code = $orderCode;
            $ret = $service->recharge();
        } else {
            $payRecord = \app\modules\common\models\PayRecord::findOne(["order_code" => $orderCode]);
            if ($payRecord == null) {
                $ProgrammeOrder = Programme::findOne(['programme_code' => $orderCode]);
                if ($ProgrammeOrder) {//如果是发起合买单 走充值流程
                    $service = new PayService();
                    $service->way_type = 'GLC';
                    $service->pay_way = 4;
                    $service->cust_no = $ProgrammeOrder->expert_no;
                    $payAmount = ($ProgrammeOrder->minimum_guarantee + $ProgrammeOrder->owner_buy_number) * $ProgrammeOrder->programme_univalent;
                    $service->payPreMoney = $payAmount;
                    $service->body = "充值";
                    $service->custType = 1;
                    $service->user_id = $ProgrammeOrder->user_id;
                    $service->order_code = $orderCode; //记录发起合买单子
                    $ret = $service->recharge();
                } else {
                    return $this->jsonResult(109, "未找到该订单", "");
                }
            }
            if ($payRecord->status != 0) {
                return $this->jsonResult(109, "请勿重复支付", "");
            }
            $payRecord->modify_time = date("Y-m-d H:i:s");
            $payRecord->pay_way = 4;
            $payRecord->pay_name = "咕啦钱包";
            $payRecord->way_type = "GLC";
            $payRecord->way_name = "钱包二维码";
//         $payRecord->save();
            $money = $payRecord->pay_pre_money;
            $paytool = new PayTool();
            $ret = $paytool->createQbQrcode($money, $orderCode);
            if ($ret["code"] == 1) {
                $payRecord->outer_no = $ret['orderId']; //钱包支付二维码返回的唯一交易单号
                $payRecord->save();
                return $this->jsonResult(600, '下单成功', ["create_time" => $payRecord->create_time, "order_code" => $orderCode, "bet_money" => $money, "pay_url" => $ret["pay_url"]]);
            } else {
                return $this->jsonResult(109, '下单失败', $ret);
            }
        }
    }

    /**
     * 说明: 钱包二维码支付状态主动查询
     * @author  kevi
     * @date 2017年11月13日 下午2:02:40
     * @param
     * @return 
     */
    public function actionAuthCallBack() {
        $qb_url = 'http://open.goodluckchina.net/open/pay/getOrderByOrderId';
        $qbAppId = \Yii::$app->params["withdraw_AppId"];
        $qbCustNo = \Yii::$app->params["withdraw_custNo"];

        $payRecords = PayRecord::find()->select(['pay_record_id', 'order_code', 'outer_no'])->where(['pay_way' => 4, 'status' => 0])->asArray()->limit(50)->all();
        if (empty($payRecords)) {
            return $this->jsonError(460, '暂无需要查询的支付订单');
        }

        $payTime = date('Ymdhis');
        $post_data = [
            'appid' => $qbAppId,
            'custNo' => $qbCustNo,
        ];
        foreach ($payRecords as $payRecord) {
            $post_data['orderId'] = $payRecord['outer_no'];
            $qbret = \Yii::sendCurlPost($qb_url, $post_data);
            if ($qbret['code'] == 1 && $qbret['data']['payStatus'] == '01') {
                $paySer = new \app\modules\common\services\PayService();
                $return = $paySer->notify($payRecord['order_code'], $payRecord['outer_no'], $qbret['data']['money'], $payTime);
                SyncService::syncFromHttp();
            } elseif ($qbret['code'] == 1 && $qbret['data']['payStatus'] == '00') {
                continue;
            } else {
                $saveRecord = PayRecord::find()->where(['pay_record_id' => $payRecord['pay_record_id']])->one();
                $saveRecord->status = 2;
                $saveRecord->save();
                SyncService::syncFromHttp('pay/qb-pay/auth-call-back-false');
            }
        }
        return $this->jsonResult(600, '查询成功', true);
    }

    /**
     * 钱包H5支付
     * @return type
     */
    public function actionCreateQbH5() {
        $request = \Yii::$app->request;
        $payType = $request->post('payType', '');
        $orderCode = $request->post('orderCode', '');
        $order = LotteryOrder::find()->select(['source', 'source_id'])->where(['lottery_order_code' => $orderCode])->asArray()->one();
        if (!empty($order) && $order['source'] == 2) {
            $total = LotteryAdditional::find()->select(['user_id', 'cust_no', 'total_money'])->where(['lottery_additional_id' => $order['source_id']])->asArray()->one();
            $service = new PayService();
            $service->way_type = 'GLCH5';
            $service->pay_way = 5;
            $service->cust_no = $total['cust_no'];
            $service->payPreMoney = $total['total_money'];
            $service->body = "充值";
            $service->custType = 1;
            $service->user_id = $total['user_id'];
            $service->order_code = $orderCode;
            $service->qbH5PayType = $payType;
            $ret = $service->recharge();
        } else {
            $payRecord = \app\modules\common\models\PayRecord::findOne(["order_code" => $orderCode]);
            if ($payRecord == null) {
                $ProgrammeOrder = Programme::findOne(['programme_code' => $orderCode]);
                if ($ProgrammeOrder) {//如果是发起合买单 走充值流程
                    $service = new PayService();
                    $service->way_type = 'GLCH5';
                    $service->pay_way = 5;
                    $service->cust_no = $ProgrammeOrder->expert_no;
                    $payAmount = ($ProgrammeOrder->minimum_guarantee + $ProgrammeOrder->owner_buy_number) * $ProgrammeOrder->programme_univalent;
                    $service->payPreMoney = $payAmount;
                    $service->body = "充值";
                    $service->custType = 1;
                    $service->user_id = $ProgrammeOrder->user_id;
                    $service->order_code = $orderCode; //记录发起合买单子
                    $service->qbH5PayType = $payType;
                    $ret = $service->recharge();
                } else {
                    return $this->jsonError(109, "未找到该订单");
                }
            }
            if ($payRecord->status != 0) {
                return $this->jsonError(109, "请勿重复支付");
            }
            $payRecord->modify_time = date("Y-m-d H:i:s");
            $payRecord->pay_way = 5;
            $payRecord->pay_name = "咕啦钱包H5";
            $payRecord->way_type = "GLCH5";
            $payRecord->way_name = "钱包H5";
            $money = $payRecord->pay_pre_money;
            $paytool = new PayTool();
            $ret = $paytool->createH5Pay($payType, $money, $orderCode);
            if ($ret["code"] == 1) {
                $payRecord->outer_no = $ret['orderId']; //钱包支付二维码返回的唯一交易单号
                $payRecord->save();
                return $this->jsonResult(600, '下单成功', ["create_time" => $payRecord->create_time, "order_code" => $orderCode, "bet_money" => $money, "pay_url" => $ret["pay_url"], "qr_code" => $ret['qr_code']]);
            } else {
                return $this->jsonResult(109, '下单失败', $ret);
            }
        }
    }

}

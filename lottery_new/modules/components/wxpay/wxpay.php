<?php

namespace app\modules\components\wxpay;

require_once \Yii::$app->basePath . "/modules/components/wxpay/lib/Notify.php";
require_once \Yii::$app->basePath . "/modules/components/wxpay/lib/WxPay.Api.php";
//require_once \Yii::$app->basePath . "/modules/components/wxpay/lib/WxPay.NativePay.php";
require_once \Yii::$app->basePath . "/modules/components/wxpay/lib/WxPay.Notify.php";
require_once \Yii::$app->basePath . "/modules/components/wxpay/lib/WxPay.JsApiPay.php";
require_once \Yii::$app->basePath . "/modules/components/wxpay/lib/WxPay.Config.php";

class wxpay {

    public $body;
    public $orderCode;
    public $totalFee = 1;
    public $attach = "";
    public $tag = "";
    public $type = "NATIVE";
    public $notifyUrl = "";
    public $productId = "";
    public $openid = "";

    public function __construct() {
        $this->notifyUrl = \Yii::$app->params["userDomain"] . "/api/publicinterface/interface/notify_wxpay";
    }

    /**
     * 异步返回调用
     */
    public function Notify() {
        $notify = new \Notify();
        $notify->Handle(false);
    }

    /**
     * 预生成支付订单统一下单
     * @return boolean
     */
    public function productPrePay() {
        $status = \Yii::redisGet("pay:weixin");
        if ($status == null) {
            \app\modules\common\helpers\Commonfun::updatePayLimit();
            $status = \Yii::redisGet("pay:weixin");
        }
        if ($status == 1) {
            return \Yii::jsonError(109, "停用该支付");
        }
        $limitPay = \Yii::redisGet("pay:weixin_limit_pay");
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($this->body);
        $input->SetAttach($this->attach);
        $input->SetOut_trade_no($this->orderCode);
        $input->SetTotal_fee($this->totalFee);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($this->tag);
        $input->SetNotify_url($this->notifyUrl);
        $input->SetTrade_type($this->type);
        $input->SetLimit_pay($limitPay);
        if ($this->type == "NATIVE") {
            $input->SetProduct_id($this->productId);
            $order = \WxPayApi::unifiedOrder($input);
        } elseif ($this->type == "JSAPI") {
            $tools = new \JsApiPay();
            if (empty($this->openid)) {
                $openId = $tools->GetOpenid();
            } else {
                $openId = $this->openid;
            }
            $input->SetOpenid($openId);
            $orderInput = \WxPayApi::unifiedOrder($input);
            $order = [];
            $order['jsApiParameters'] = $tools->GetJsApiParameters($orderInput);
            $order['editAddress'] = $tools->GetEditAddressParameters();
            $order['total_money'] = $this->totalFee / 100.00;
            $order['order_code'] = $this->orderCode;
        } elseif ($this->type == "APP") {
            $order = \WxPayApi::unifiedOrder($input);
        } else {
            return false;
        }
        return $order;
    }

    /**
     * 退款
     * @param string $out_trade_no
     * @param integer $refund_fee
     * @param integer $total_fee
     * @param string $out_request_no
     * @return boolean
     */
    public function refund($out_trade_no, $refund_fee, $total_fee, &$out_request_no) {
        if (isset($out_trade_no) && $out_trade_no != "") {
            $input = new \WxPayRefund();
            $input->SetOut_trade_no($out_trade_no);
            $input->SetTotal_fee($total_fee);
            $input->SetRefund_fee($refund_fee);
            $input->SetOut_refund_no(\WxPayConfig::MCHID . date("YmdHis"));
            $input->SetOp_user_id(\WxPayConfig::MCHID);
            $response = \WxPayApi::refund($input);
            if ($response["return_msg"] == "OK" && $response["result_code"] == 'SUCCESS') {
                $out_request_no = $response["out_refund_no"];
                return true;
            } else {
                return false;
            }
            return $response;
        } else {
            return false;
        }
    }

}

<?php

namespace app\modules\components\wxpay;

require_once \Yii::$app->basePath . "/modules/components/wxpay/applib/Notify.php";
require_once \Yii::$app->basePath . "/modules/components/wxpay/applib/WxPay.Api.php";
//require_once \Yii::$app->basePath . "/modules/components/wxpay/lib/WxPay.NativePay.php";
require_once \Yii::$app->basePath . "/modules/components/wxpay/applib/WxPay.Notify.php";
require_once \Yii::$app->basePath . "/modules/components/wxpay/applib/WxPay.JsApiPay.php";

require_once \Yii::$app->basePath . "/modules/components/wxpay/applib/WxPay.Config.php";

class wxpayapp {

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
        $this->notifyUrl = \Yii::$app->params["userDomain"] . "/api/publicinterface/interface/notify_wxpayapp";
    }

    /**
     * 异步返回调用
     */
    public function Notify() {
        $notify = new \AppNotify();
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
            }else{
                $openId =  $this->openid;
            }
            $input->SetOpenid($openId);
            $orderInput = \WxPayApi::unifiedOrder($input);
            $order = [];
            $order['jsApiParameters'] = $tools->GetJsApiParameters($orderInput);
            $order['editAddress'] = $tools->GetEditAddressParameters();
            $order['total_money'] = $this->totalFee / 100.00;
            $order['order_code'] = $this->orderCode;
        } elseif ($this->type == "APP") {
            $data = \WxPayApi::unifiedOrder($input);
            $order = [];
            $order["appid"] = $data["appid"];
            $order["partnerid"] = $data["mch_id"];
            $order["prepayid"] = $data["prepay_id"];
            $order["package"] = "Sign=WXPay";
            $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
            $str = "";
            for ($i = 0; $i < 32; $i++) {
                $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            }
            $order["noncestr"] = $str;
            $order["timestamp"] = (string)time();
            ksort($order);
            $string = "";
            foreach ($order as $k => $v) {
                if ($k != "sign" && $v != "" && !is_array($v)) {
                    $string .= $k . "=" . $v . "&";
                }
            }
            $string = trim($string, "&");
            $string = $string . "&key=" . \WxPayConfig::KEY;
            $string = md5($string);
            $sign = strtoupper($string);
            $order["sign"] = $sign;
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
                $out_request_no = $response["refund_id"];
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

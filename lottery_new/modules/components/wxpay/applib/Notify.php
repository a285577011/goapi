<?php

//namespace app\modules\components\wxpay\lib;

require_once \Yii::$app->basePath . "/modules/components/wxpay/applib/WxPay.Api.php";
require_once \Yii::$app->basePath . "/modules/components/wxpay/applib/WxPay.Notify.php";

class AppNotify extends \WxPayNotify {

    //查询订单
    public function Queryorder($transaction_id) {
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = \WxPayApi::orderQuery($input);
        if (array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS") {
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg) {
        $notfiyOutput = array();

        if (!array_key_exists("transaction_id", $data)) {
            $msg = "输入参数不正确";
            return false;
        }
        //查询订单，判断订单真实性
        if (!$this->Queryorder($data["transaction_id"])) {
            $msg = "订单查询失败";
            return false;
        }
        $paySer = new \app\modules\common\services\PayService();
        $ret = $paySer->notify($data["out_trade_no"], $data["transaction_id"], ($data["total_fee"] / 100.00));
        if ($ret["code" == 0]) {
            return true;
        }
//        $info = \app\modules\common\services\OrderService::getPayRecord($data["out_trade_no"]);
//        if ($info["pay_type"] == "1") {
//            $ret = \app\modules\common\services\OrderService::orderNotify($data["out_trade_no"], $data["transaction_id"], ($data["total_fee"] / 100.00));
//        }
//        if ($info["pay_type"] == "3") {
//            $ret = \app\modules\common\services\OrderService::rechargeNotify($data["out_trade_no"], $data["transaction_id"], ($data["total_fee"] / 100.00));
//        }
//        if ($info["pay_type"] == "5") {
//            $proSer = new \app\modules\common\services\ProgrammeService();
//            $ret = $proSer->programmeNotify($data["out_trade_no"], $data["transaction_id"], ($data["total_fee"] / 100.00));
//        }
//        if ($info["pay_type"] == "7") {
//            $ret = \app\modules\common\services\PlanService::planNotify($data["out_trade_no"], $data["transaction_id"], ($data["total_fee"] / 100.00));
//        }
//        $ret = \app\modules\common\services\OrderService::orderNotify($data["out_trade_no"], $data["transaction_id"], ($data["total_fee"] / 100));
        return false;
    }

}

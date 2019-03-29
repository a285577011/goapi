<?php

namespace app\modules\components\alipay;

require_once \Yii::$app->basePath . "/modules/components/alipay/service/AlipayTradeService.php";
require_once \Yii::$app->basePath . "/modules/components/alipay/lib/AlipayTradeWapPayContentBuilder.php";
require_once \Yii::$app->basePath . "/modules/components/alipay/lib/AlipayTradePagePayContentBuilder.php";
require_once \Yii::$app->basePath . "/modules/components/alipay/lib/AlipayTradeAppPayContentBuilder.php";
require \Yii::$app->basePath . "/modules/components/alipay/lib/config.php";

require_once \Yii::$app->basePath . "/modules/components/alipay/lib/AlipayTradeQueryContentBuilder.php";
require_once \Yii::$app->basePath . "/modules/components/alipay/lib/AlipayTradeRefundContentBuilder.php";

class alipay {

    //订单名称，必填
    //付款金额，必填
    //商品描述，可空
    //超时时间
    public $body = "彩票购买, 所需0.01元";
    public $subject = "彩票购买";
    public $total_amount = "0.01";
    public $timeout_express = "30m";
    public $out_trade_no = "GLCDLT170621T0000010";
    public $type = "PAGE";
    public $notify_url;
    public $return_url;

    public function __construct() {
        $this->notify_url = 'http://' . $_SERVER['HTTP_HOST'] . '/api/publicinterface/interface/notify_ali';
        $this->return_url = 'http://' . $_SERVER['HTTP_HOST'] . '/api/publicinterface/interface/return_ali';
    }

    /**
     * 支付跳转
     * @return boolean
     */
    public function pay() {
        $status = \Yii::redisGet("pay:alipay");
        if ($status == null) {
            \app\modules\common\helpers\Commonfun::updatePayLimit();
            $status = \Yii::redisGet("pay:alipay");
        }
        if ($status == 1) {
            return \Yii::jsonError(109, "停用该支付");
        }
        $limitPay = \Yii::redisGet("pay:alipay_limit_pay");
        $config = \config::Config;
        if ($this->type == "PAGE") {
            $payRequestBuilder = new \AlipayTradePagePayContentBuilder();
        } elseif ($this->type == "WAP") {
            $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
        } elseif ($this->type == "APP") {
            $payRequestBuilder = new \AlipayTradeAppPayContentBuilder();
        } else {
            return false;
        }
        $payRequestBuilder->setBody($this->body);
        $payRequestBuilder->setSubject($this->subject);
        $payRequestBuilder->setOutTradeNo($this->out_trade_no);
        $payRequestBuilder->setTotalAmount($this->total_amount);
        $payRequestBuilder->setTimeExpress($this->timeout_express);
        $payRequestBuilder->setDisable_pay_channels($limitPay); //"creditCard,creditCardExpress,creditCardCartoon"

        $payResponse = new \AlipayTradeService($config);
        if ($this->type == "PAGE") {
            $result = $payResponse->pagePay($payRequestBuilder, $this->return_url, $this->notify_url);
        } elseif ($this->type == "WAP") {
            $result = $payResponse->wapPay($payRequestBuilder, $this->return_url, $this->notify_url);
        } elseif ($this->type == "APP") {
            $result = $payResponse->appPay($payRequestBuilder, $this->return_url, $this->notify_url);
            echo json_encode([
                "code" => 600,
                "msg" => "获取成功",
                "result" => $result
            ]);
        } else {
            return false;
        }

        return;
    }

    /**
     * 异步回调
     */
    public function Notify() {
        $post = \Yii::$app->request->post();
        $out_trade_no = $post['out_trade_no'];

        //支付宝交易号

        $trade_no = $post['trade_no'];

        //交易状态
        $trade_status = $post['trade_status'];
        $arr = $post;
        if (isset($arr["s"])) {
            unset($arr["s"]);
        }
        $config = \config::Config;
        $alipaySevice = new \AlipayTradeService($config);
        $result = $alipaySevice->check($arr);
        if ($result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            //商户订单号
            if ($post['trade_status'] == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            } else if ($post['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序			
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
                $paySer = new \app\modules\common\services\PayService();
                $paySer->notify($out_trade_no, $trade_no, $post['total_amount']);
//                $info = \app\modules\common\services\OrderService::getPayRecord($out_trade_no);
//                if ($info["pay_type"] == "1") {
//                    $ret = \app\modules\common\services\OrderService::orderNotify($out_trade_no, $trade_no, $post['total_amount']);
//                }
//                if ($info["pay_type"] == "3") {
//                    $ret = \app\modules\common\services\OrderService::rechargeNotify($out_trade_no, $trade_no, $post['total_amount']);
//                }
//                if ($info["pay_type"] == "5") {
//                    $proSer = new \app\modules\common\services\ProgrammeService();
//                    $ret = $proSer->programmeNotify($out_trade_no, $trade_no, $post['total_amount']);
//                }
//                if ($info["pay_type"] == "7") {
//                    $ret = \app\modules\common\services\PlanService::planNotify($out_trade_no, $trade_no, $post['total_amount']);
//                }
            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            echo "success"; //请不要修改或删除
        } else {
            //验证失败
            echo "fail";
        }
    }

    /**
     * 交易查询
     * @param string $out_trade_no
     * @return array
     */
    public function query($out_trade_no) {
//        $out_trade_no = trim($_GET['WIDTQout_trade_no']);
        $config = \config::Config;
        //请二选一设置
        //构造参数
        $RequestBuilder = new \AlipayTradeQueryContentBuilder();
        $RequestBuilder->setOutTradeNo($out_trade_no);

        $aop = new \AlipayTradeService($config);

        /**
         * alipay.trade.query (统一收单线下交易查询)
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @return $response 支付宝返回的信息
         */
        $response = $aop->Query($RequestBuilder);
        return $response;
//        var_dump($response);
    }

    /**
     * 同步回调
     */
    public function returnUrl() {
        $get = \Yii::$app->request->get();
        $arr = $get;
        if (isset($arr["s"])) {
            unset($arr["s"]);
        }
        $out_trade_no = htmlspecialchars($get['out_trade_no']);
        $trade_no = htmlspecialchars($get['trade_no']);
        $config = \config::Config;
        $alipaySevice = new \AlipayTradeService($config);
        $result = $alipaySevice->check($arr);
        $info = \app\modules\common\services\OrderService::getPayRecord($out_trade_no);
        if ($info == null) {
            echo "错误订单";
            exit();
        }

        /* 实际验证过程建议商户添加以下校验。
          1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
          2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
          3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
          4、验证app_id是否为该商户本身。
         */
        if ($result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代码
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
            //商户订单号
            //支付宝交易号
//            $ret = \app\modules\common\services\OrderService::orderNotify($out_trade_no, $trade_no, $get['total_amount']);
            $paySer = new \app\modules\common\services\PayService();
            $ret = $paySer->notify($out_trade_no, $trade_no, $get['total_amount']);
//            if ($ret["code"] != 0) {
//                echo "错误订单";
//                exit();
//            }
            header($ret["url"]);
            exit();
//            if ($info["pay_type"] == "1") {
//                $ret = \app\modules\common\services\OrderService::orderNotify($out_trade_no, $trade_no, $get['total_amount']);
//                if ($info["way_type"] == "PAGE") {
//                    header("location:/paysuccess?orderCode={$out_trade_no}");
//                    exit();
//                }
//                if ($info["way_type"] == "WAP") {
//                    header("location:/pay/success/{$out_trade_no}?total_amount=" . $get['total_amount']);
//                    exit();
//                }
//                if ($info["way_type"] == "APP") {
//                    header("location:/pay/success/{$out_trade_no}?total_amount=" . $get['total_amount']);
//                    exit();
//                }
//            }
//            if ($info["pay_type"] == "3") {
//                $ret = \app\modules\common\services\OrderService::rechargeNotify($out_trade_no, $trade_no, htmlspecialchars($get['total_amount']));
//                if ($info["way_type"] == "PAGE") {
//                    header("location:/paysuccess?orderCode={$out_trade_no}&recharge=1");
//                    exit();
//                }
//                if ($info["way_type"] == "WAP") {
//                    header("location:/pay/success/{$out_trade_no}?recharge=1&total_amount=" . htmlspecialchars($get['total_amount']));
//                    exit();
//                }
//                if ($info["way_type"] == "APP") {
//                    header("location:/pay/success/{$out_trade_no}?recharge=1&total_amount=" . htmlspecialchars($get['total_amount']));
//                    exit();
//                }
//            }
//            if ($info["pay_type"] == "5") {
//                $proSer = new \app\modules\common\services\ProgrammeService();
//                $ret = $proSer->programmeNotify($out_trade_no, $trade_no, $get['total_amount']);
//                if ($info["way_type"] == "PAGE") {
//                    header("location:/paysuccess?orderCode={$out_trade_no}");
//                    exit();
//                }
//                if ($info["way_type"] == "WAP") {
//                    header("location:/pay/success/{$out_trade_no}?total_amount=" . $get['total_amount']);
//                    exit();
//                }
//                if ($info["way_type"] == "APP") {
//                    header("location:/pay/success/{$out_trade_no}?total_amount=" . $get['total_amount']);
//                    exit();
//                }
//            }
            echo "验证成功<br / >  支付宝交易号  ："
            . $trade_no;

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            $paySer = new \app\modules\common\services\PayService();
            $ret = $paySer->aliReturnUrl($out_trade_no, $get['total_amount']);
            header($ret["url"]);
            exit();
//            if ($info["pay_type"] == "1") {
//                if ($info["way_type"] == "PAGE") {
//                    header("location:/payFail?orderCode={$out_trade_no}&recharge=1");
//                    exit();
//                }
//                if ($info["way_type"] == "WAP") {
//                    header("location:/pay/fail/{$out_trade_no}?total_amount=" . $get['total_amount']);
//                    exit();
//                }
//                if ($info["way_type"] == "APP") {
//                    header("location:/pay/fail/{$out_trade_no}?total_amount=" . $get['total_amount']);
//                    exit();
//                }
//            }
//            if ($info["pay_type"] == "3") {
//                if ($info["way_type"] == "PAGE") {
//                    header("location:/payFail?orderCode={$out_trade_no}");
//                    exit();
//                }
//                if ($info["way_type"] == "WAP") {
//                    header("location:/pay/fail/{$out_trade_no}?recharge=1&total_amount=" . htmlspecialchars($get['total_amount']));
//                    exit();
//                }
//                if ($info["way_type"] == "APP") {
//                    header("location:/pay/fail/{$out_trade_no}?recharge=1&total_amount=" . htmlspecialchars($get['total_amount']));
//                    exit();
//                }
//            }
//            if ($info["pay_type"] == "1") {
//                if ($info["way_type"] == "PAGE") {
//                    header("location:/payFail?orderCode={$out_trade_no}&recharge=1");
//                    exit();
//                }
//                if ($info["way_type"] == "WAP") {
//                    header("location:/pay/fail/{$out_trade_no}?total_amount=" . $get['total_amount']);
//                    exit();
//                }
//                if ($info["way_type"] == "APP") {
//                    header("location:/pay/fail/{$out_trade_no}?total_amount=" . $get['total_amount']);
//                    exit();
//                }
//            }
            header("location:/pay/fail/{$get['out_trade_no']}?total_amount=" . $get["total_amount"]);
            exit();
            //验证失败
            echo "验证失败";
        }
    }

    /**
     * 退款
     * @param string $out_trade_no
     * @param string $trade_no
     * @param string $refund_reason
     * @param string $out_request_no
     * @param float $refund_amount
     * @return boolean
     */
    public function refund($out_trade_no, $trade_no, $refund_reason, $out_request_no, $refund_amount) {
        $config = \config::Config;
        //商户订单号，商户网站订单系统中唯一订单号
//        $out_trade_no = trim($_POST['WIDTRout_trade_no']);
        //支付宝交易号
//        $trade_no = trim($_POST['WIDTRtrade_no']);
        //请二选一设置
        //需要退款的金额，该金额不能大于订单金额，必填 $refund_amount = trim($_POST['WIDTRrefund_amount']);
        //退款的原因说明
//        $refund_reason = trim($_POST['WIDTRrefund_reason']);
        //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
//        $out_request_no = trim($_POST['WIDTRout_request_no']);
        //构造参数
        $RequestBuilder = new \AlipayTradeRefundContentBuilder();
        $RequestBuilder->setOutTradeNo($out_trade_no);
        $RequestBuilder->setTradeNo($trade_no);
        $RequestBuilder->setRefundAmount($refund_amount);
        $RequestBuilder->setOutRequestNo($out_request_no);
        $RequestBuilder->setRefundReason($refund_reason);

        $aop = new \AlipayTradeService($config);

        /**
         * alipay.trade.refund (统一收单交易退款接口)
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @return $response 支付宝返回的信息
         */
        $response = $aop->Refund($RequestBuilder);
        if ($response->code == "10000") {
            return true;
        } else {
            return false;
        }
    }

}

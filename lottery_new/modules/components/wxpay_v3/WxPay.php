<?php
/**
* 	微信支付入口
*/
namespace app\modules\components\wxpay_v3;
use app\modules\components\wxpay_v3\lib\WxPayApi;
use app\modules\components\wxpay_v3\lib\WxPayUnifiedOrder;

require_once dirname(__DIR__).'/wxpay_v3/lib/WxPay.Data.php';

class WxPay
{
    /**
     * 说明: 
     * @author  kevi
     * @date 2017年5月26日 下午1:36:53
     * @param $body  内容
     * @param $orderCode  订单编号
     * @param $money  金额
     * @param $tradeType  类型 APP  JSAPI NATIVE
     * @return 
     */
    public static  function toWxUnifiedOrder($body,$orderCode,$money,$tradeType){
        $result=[];
        $input = new WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetOut_trade_no($orderCode);
        $input->SetTotal_fee($money);
        $input->SetNotify_url('http://test.lottery.com/api/user/user/notify');
        $input->SetTrade_type($tradeType);
        
        
        $wxReturn = WxPayApi::unifiedOrder($input);
        if($wxReturn['reslut_code']=='SUCCESS'){//成功下单，返回APP 
            $result['prepay_id'] = $wxReturn['prepay_id'];
            $result['sign'] = $wxReturn['sign'];
        }
        print_r($return);die;
    }
}

// require_once "../lib/WxPay.Api.php";


// $input = new WxPayUnifiedOrder();
// $input->SetBody("test");
// $input->SetAttach("test");
// $input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
// $input->SetTotal_fee("1");
// $input->SetTime_start(date("YmdHis"));
// $input->SetTime_expire(date("YmdHis", time() + 600));
// $input->SetGoods_tag("test");
// $input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");
// $input->SetTrade_type("NATIVE");
// $input->SetProduct_id("123456789");
// $result = $notify->GetPayUrl($input);
// $url2 = $result["code_url"];
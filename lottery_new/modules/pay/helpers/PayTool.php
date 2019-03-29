<?php

/*
 * 普通工具类
 */

namespace app\modules\pay\helpers;

/**
 * 说明 ：支付工具类
 * @author  kevi
 * @date 2017年7月6日 下午1:41:34
 */
class PayTool {

    /**
     * 说明: 
     * @author  kevi
     * @date 2017年8月18日 上午10:10:13
     * @param   $orderId    上传商户的订单号
     * @param   $money  单位（元），可两位小数
     * @return 
     */
    public function createQbQrcode($money, $orderId, $model = '00') {
        $status = \Yii::redisGet("pay:glc_code");
        if ($status == null) {
            \app\modules\common\helpers\Commonfun::updatePayLimit();
            $status = \Yii::redisGet("pay:glc_code");
        }
        if ($status == 1) {
            return \Yii::jsonError(109, "停用该支付");
        }
        $surl = 'http://open.goodluckchina.net/open/pay/buildPayCode';
        $qbAppId = \Yii::$app->params["withdraw_AppId"];
        $qbCustNo = \Yii::$app->params["withdraw_custNo"];
        $attach = $orderId;
        $qbCallBack = \Yii::$app->params["userDomain"] . '/api/pay/qb-pay/qb-callback';
        if ($money < 1) {
            return false;
        }
        $post_data = [
            'appid' => $qbAppId,
            'custNo' => $qbCustNo,
            'money' => $money,
            'attach' => $orderId,
            'model' => $model,
            'expireTime' => '5',
            'callBackUrl' => $qbCallBack,
        ];
        $qbret = \Yii::sendCurlPost($surl, $post_data);
        return $qbret;
    }

    /**
     * 说明:请求钱包h5支付
     * @author chenqiwei
     * @date 2018/4/4 上午8:55
     * @param   $orderId    上传商户的订单号
     * @param   $money  单位（元），可两位小数
     * @return
     */
    public function createH5Pay($payType,$money, $orderId){

        $surl = "https://open.goodluckchina.net/open/pay/scanCodePayChannel";
        $qbCallBack = \Yii::$app->params["userDomain"] . '/api/pay/qb-pay/qb-callback';
        $qbAppId = \Yii::$app->params["withdraw_AppId"];
        $qbCustNo = \Yii::$app->params["withdraw_custNo"];
        $post_data = [
            'appId' => $qbAppId,//appId
            'custNo' => $qbCustNo,//商户号
            'payChannel' => $payType,//支付方式
            'money' => $money,//金额
            'attach' => $orderId,//附加回传参数（订单号）
            'callBackUrl' => $qbCallBack,//回调地址
        ];
//        $post_data['sign'] = $this->getSign($post_data);//签名
        $qbret = \Yii::sendCurlPost($surl, $post_data);
        return $qbret;
    }

    /**
     * 说明:钱包签名
     * @author chenqiwei
     * @date 2018/4/4 上午9:53
     * @param
     * @return
     */
    public function getSign($post_data){
        $post_data = array_multisort($post_data);
        $str = '';
        $flag = 1;
        foreach ($post_data as $k=>$v){
            if(!empty($v)){
                if($flag==1){
                    $str.= "{$k}={$v}";
                }else{
                    $str.= "&{$k}={$v}";
                }
                $flag++;
            }
        }
        $appKey = \Yii::$app->params["withdraw_AppKey"];
        $sign = md5($str.$appKey);
        return $sign;
    }

}

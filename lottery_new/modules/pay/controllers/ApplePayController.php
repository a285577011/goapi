<?php

namespace app\modules\pay\controllers;

use app\modules\common\models\UserFunds;
use app\modules\experts\services\ExpertService;
use yii\web\Controller;

/**
 * Default controller for the `tools` module
 */
class ApplePayController extends Controller {

    /**
     * 说明:ios 提交验证接口
     * @author chenqiwei
     * @date 2018/3/20 下午2:25
     * @param   type 类型（1、充值）
     * @param   money 金额
     * @param   receipt 凭证
     * @return
     */
    public function actionCheckReceipt(){
        $request = \Yii::$app->request;
        $userId = $this->userId;
        if(!$userId){return $this->jsonError(400,'未登录，充值失败');}
        $type = $request->post('type','1');
        $money = $request->post_nn('money');
        $receipt = $request->post_nn('receipt');
        if(YII_ENV_DEV){
            $url = 'https://sandbox.itunes.apple.com/verifyReceipt';
        }else{
            $url = 'https://buy.itunes.apple.com/verifyReceipt';
        }

        $data = [
            'receipt-data'=>$receipt
        ];
        $receipt = json_encode($data);
        $curlRet = \Yii::sendCurlPost($url,$receipt);

        switch ($curlRet['status'])
        {
            case 0://验证成功
                //处理业务逻辑（新增咕啦币）
                $db = \Yii::$app->db;
                $ret = $db->createCommand("UPDATE user_funds SET user_glcoin = user_glcoin+{$money} WHERE user_id = {$userId}")->execute();
                $msg = '恭喜购买成功';
                return $this->jsonResult(600,$msg,true);
            case 21000:
                $msg = 'App Store不能读取你提供的JSON对象';
                break;
            case 21002:
                $msg = 'receipt-data域的数据有问题';
                break;
            case 21003:
                $msg = 'receipt无法通过验证';
                break;
            case 21004:
                $msg = '提供的shared secret不匹配你账号中的shared secret';
                break;
            case 21005:
                $msg = 'receipt服务器当前不可用';
                break;
            case 21006:
                $msg = 'receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送';
                break;
            case 21007:
                $url = 'https://sandbox.itunes.apple.com/verifyReceipt';
                $curlRet = \Yii::sendCurlPost($url,$receipt);
                if($curlRet['status']==0){//处理业务逻辑（新增咕啦币）
                    $db = \Yii::$app->db;
                    $ret = $db->createCommand("UPDATE user_funds SET user_glcoin = user_glcoin+{$money} WHERE user_id = {$userId}")->execute();
                    $msg = '恭喜购买成功';
                    return $this->jsonResult(600,$msg,true);
                }
                $msg = 'receipt是Sandbox receipt，但却发送至生产系统的验证服务';
                break;
            case 21008:
                $msg = 'receipt是生产receipt，但却发送至Sandbox环境的验证服务';
                break;
            default:
                $msg = '未知错误，请重试';
        }

        return $this->jsonError(400,$msg);

    }


    public function actionPayArticle(){

        $request = \Yii::$app->request;
        $userId = $this->userId;
        $custNo = $this->custNo;
        $articleId = $request->post_nn('article_id');
        $money = $request->post_nn('money');
        $payPassword = $request->post_nn('pay_password');
        $isTrue = UserFunds::find()->where(['pay_password'=>md5($payPassword)])->one();
        if(!$isTrue){
            return $this->jsonError(405,'支付密码错误，请重新输入');
        }
        $orderData = [
            'expert_articles_id'=>$articleId,
            'total'=>$money
        ];
        $expertService = new ExpertService();
        $ret = $expertService->BuyArticle($orderData, $userId, $custNo);
        return $this->jsonResult(600,'购买成功',true);
    }

}

<?php

namespace app\modules\test\controllers;

use app\modules\openapi\services\ApiNoticeService;
use yii\web\Controller;
use app\modules\tools\helpers\Des;
use app\modules\user\models\CouponsDetail;
use app\modules\common\services\OrderService;

class GwpController extends Controller {

    private $key = '354344304D2B2C442B3A492A'; //密钥
    private $iv = '20180117'; //IV 向量

    public function actionA3() {
        $request = \Yii::$app->request;
        $param = $request->post();
        $json_data = json_encode($param);
        $commond = isset($param['commond']) ? $param['commond'] : 1005;
        $venderId = isset($param['venderId']) ? $param['venderId'] : 'GLe9ba658b9efdc1';
        unset($param['commond']);
        unset($param['venderId']);
        $des = new Des($this->key, $this->iv);
        $des_data = $des->encrypt($json_data);

        $request_data = [
            'message' => [
                'head' => [
                    'command' => $commond,
                    'venderId' => $venderId,
                    'messageId' => 1100,
                    'md' => md5($des_data),
                ],
                'body' => $des_data,
            ],
        ];

        $json_data = json_encode($request_data);
        $url = 'http://php.javaframework.cn/api/openapi/thirdapi/transfer';
        //curl
        $data = $this->jsonPost($url, $json_data);
        $data = json_decode($data, true);

        //解密
        $data = $this->checkDate($data);
        echo json_encode($data);
    }

    public function checkDate($data) {
        $body = $data['message']['body'];
        //解密
        $des = new Des($this->key, $this->iv);
        $de_body = $des->decrypt($body);
        $data['message']['body'] = json_decode($de_body, true);
        return $data;
    }

    public function jsonPost($url, $data) {
        $header[] = "Content-type: application/json"; //定义conten t-type为xml
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            \Yii::info(var_export(curl_error($ch), true), 'backuporder_log');
        }
        curl_close($ch);
        return $response;
    }

    public function actionA2() {
        $res = OrderService::sendCouponsVerify(1001,"gl00030109");
        print_r($res);
        die;
        $now = date("Y-m-d H:i:s");
        $type=0;
        if("2018-06-14 00:00:00"<=$now&&$now<="2018-07-15 23:59:59"){
           if($money>=100&&$money<1000){
               $type = 3;
           }elseif($money>=1000){
               $type = 4;
           }else{
               return ["code"=>109,"msg"=>"订单金额未达到活动标准"];
           }
            //验证当天赠送代金券张数
            $coupons = CouponsDetail::getActivityBatch("GL",$type);
            if(!empty($coupons)){
                foreach ($coupons as $k=>$v){
                    $num = CouponsDetail::getUserCouponsNum($v["batch"],$cust_no);
                    if($num>=3){
                        return ["code"=>109,"msg"=>"用户当日获得优惠券已满3张"];
                    }
                    $userAry = UserTool::getUserAry($cust_no,$v["send_num"]);
                    $res = UserTool::regSendCoupons($v["batch"],$userAry);
                    if($res["code"]!=600){
                        KafkaService::addLog("sendCoupons-error",$cust_no.$v["batch"].$res["msg"]);
                    }
                }
                return ["code"=>600,"msg"=>"赠送成功"];
            }else{
                return ["code"=>109,"msg"=>"活动暂未开启"];
            }
        }else{
            return ["code"=>109,"msg"=>"未到活动开始时间"];
        }
    }

    public function actionA4() {
        $ApiNoticeService = new ApiNoticeService();
        $res = $ApiNoticeService->PushNoticePlayOrder(600, '出票成功！', '102120121', '123123123', '208');
        $this->jsonResult(600, '', $res);
    }

    /**
     * 2018-06-14 至 2018-07-15 消费满100 1000 赠送代金券
     */
    public static function sendCouponsVerify($money){
        $now = date("Y-m-d H:i:s");
        $now = "2018-06-15 00:00:00";
        if("2018-06-14 00:00:00"<=$now&&$now<="2018-07-15 23:59:59"){
            echo 111;
        }else{
            echo 222;
        }
    }

}

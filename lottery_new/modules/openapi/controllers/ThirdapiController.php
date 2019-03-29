<?php

namespace app\modules\openapi\controllers;

use app\modules\common\services\KafkaService;
use app\modules\openapi\services\ThirdApiService;
use app\modules\tools\helpers\Des;
use yii\web\Controller;
use app\modules\common\services\SyncService;

class ThirdapiController extends Controller {

    private $key = ''; //密钥
    private $iv = '20180101';//IV 向量

    /**
     * 第三方接口中转方法
     */
    public function actionTransfer(){
        $request = \Yii::$app->request;
        $param = $request->post();
        $this->key = DES_KEY;
        $venderId = $param['message']['head']['venderId'];
        $messageId = $param['message']['head']['messageId'];
        //验证数据，返回参数
        $body = $this -> checkDate($param);
        //接口入口
        $command = $param['message']['head']['command'];
        $thirdApi = new ThirdApiService();
        switch ($command) {
            case 1000:  //投注
                $body = isset($body) ? $body : [] ;
                if(!is_array($body) || empty($body)) {
                    $result = ['code'=>20002, 'msg' => '注码格式错误'];
                }
                $result = $thirdApi->playOrder($body, $messageId);
                break;
            case 1001:  //投注结果查询
                $orderIds = isset($body['orderId']) ? $body['orderId'] : '' ;
//                if(count($orderIds)>10){}
                $result = $thirdApi->inquireOrder($orderIds);
                break;
            case 1002:  //中奖查询
//                $body['tradeNo'] = isset($body['tradeNo']) ? $body['tradeNo'] : '' ;
                $orderIds = isset($body['orderId']) ? $body['orderId'] : '' ;
                $result = $thirdApi->inquireWinOrder($orderIds);
                break;
            case 1003:  //新期查询
                $body['lotteryCode'] = isset($body['lotteryCode']) ? $body['lotteryCode'] : '' ;
                $body['periods'] = isset($body['periods']) ? $body['periods'] : '' ;
                $result = $thirdApi->getPeriods($body['lotteryCode'], $body['periods']);
                break;
            case 1004:  //开奖信息查询
                $body['lotteryCode'] = isset($body['lotteryCode']) ? $body['lotteryCode'] : '' ;
                $body['periods'] = isset($body['periods']) ? $body['periods'] : '' ;
                $result = $thirdApi->getLotteryResult($body['lotteryCode'], $body['periods']);
                break;
            case 1005:  //商户余额查询
                $result = $thirdApi->getCustFunds();
                break;
            case 1006: //获取订单详情
                $orderId = isset($body['orderId']) ? $body['orderId'] : '';
                $result = $thirdApi->getOrderDetail($orderId);
                break;
            case 1007: //获取详情单中奖信息
                $orderId = isset($body['orderId']) ? $body['orderId'] : '';
                $result = $thirdApi->getDetailResult($orderId);
                break;
            default:
                $result = ['code'=>70004, 'msg' => '请求不存在'];
        }

        return $this -> responseDate($result, $venderId, $messageId);
    }

    /**
     * @param $data  处理后返回的数据
     * @param $venderId   第三方商户号密钥
     * @param $messageId  第三方自定义数据
     */
    public function responseDate($data, $venderId, $messageId){
        if($data['code'] === 0){
            $json_data = json_encode($data['data'], JSON_FORCE_OBJECT);
        } else {
            $json_data = json_encode($data['msg'], JSON_FORCE_OBJECT);
        }
        $des = new Des($this->key, $this->iv);
        $de_data = $des -> encrypt($json_data);
        $response_data = [
            'message' => [
                'head' => [
                    'status' => $data['code'],
                    'venderId' => $venderId,
                    'messageId' => $messageId,
                    'md' => md5($de_data),
                ],
                'body' => $de_data,
            ],
        ];
        echo json_encode($response_data);die;
//        return json_encode($response_data);
    }


    /**
     * 验证数据是否一致
     * @param  array $data
     * @return array
     */
    public function checkDate($data){
        $body = $data['message']['body'];
        $md5_body = md5($body);
        if($md5_body !== $data['message']['head']['md']){
            return ['code'=>70002, 'msg'=>'数据错误！'];
        }
        //解密
        $des = new Des($this->key, $this->iv);
        $de_body = $des ->decrypt($body);
        return json_decode($de_body, true);
    }

}

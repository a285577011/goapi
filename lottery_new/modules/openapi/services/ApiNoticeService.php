<?php

namespace app\modules\openapi\services;

use app\modules\common\services\KafkaService;
use app\modules\openapi\models\ApiNotice;
use yii\db\Query;

class ApiNoticeService{

    /**
     * 新期通知数字彩
     * $url 通知URL
     * $lotteryCode 彩种编号
     * $newPeriods 期数
     * $newLotteryTime 开奖时间
     * $newLimitTime 截止投注时间
     * $week  本周几开奖
     */
    public function PushNoticeSzc($lotteryCode,$periods,$lotteryTime,$limitTime,$week,$user_id){
        $url = '';
        $post_data = [
            'lottery_code' => $lotteryCode,
            'periods' => $periods,
            'lottery_time' => $lotteryTime,
            'limit_time' => $limitTime,
            'weeks' => $week
        ];
        $json_data = json_encode($post_data);
        $respons_data = $this->jsonPost($url, $json_data);
        //返回结果写入数据库 - 队列
        $param = [
            'respons_data' => $respons_data,
            'periods' => $periods,
            'name' => '新期通知-数字彩',
            'json_data' => $json_data,
            'url' => $url,
            'type' => 1,
            'user_id' => $user_id,
        ];
        KafkaService::addQue('PushThirdNotice', $param, true);
//        $this->updateTable($respons_data, $periods,"新期通知-数字彩", $json_data, $url, 1, $user_id);
    }
    /**
     * 新期通知-足球胜负彩
     * $url 通知URL
     * $periods 期数
     * $beginsale 开售时间
     * $endsale 停售时间
     */
    public function PushNoticeFourteen($periods,$beginsale,$endsale,$user_id){
        $url = '';
        $post_data = [
            'periods' => $periods,
            'beginsale_time' => $beginsale,
            'endsale_time' => $endsale,
        ];
        $json_data = json_encode($post_data);
        $respons_data = $this->jsonPost($url, $json_data);
        //返回结果写入数据库 - 队列
        $param = [
            'respons_data' => $respons_data,
            'periods' => $periods,
            'name' => '开奖号码通知-数字彩',
            'json_data' => $json_data,
            'url' => $url,
            'type' => 2,
            'user_id' => $user_id,
        ];
        KafkaService::addQue('PushThirdNotice', $param, true);
//        $res = $this->updateTable($respons_data, $periods,"新期通知-足球胜负彩",$json_data, $url, 2, $user_id);
    }
    /**
     * 开奖号码通知-数字彩
     * $url 通知URL
     * $lotteryCode 彩种编号
     * $periods 期数
     * $openNum 开奖号码
     */
    public function PushNoticeResultSzc($lotteryCode,$periods,$openNum,$user_id){
        $url = '';
        $post_data = [
            'lottery_code' => $lotteryCode,
            'periods' => $periods,
            'open_number' => $openNum,
        ];
        $json_data = json_encode($post_data);
        $respons_data = $this->jsonPost($url, $json_data);
        //返回结果写入数据库 - 队列
        $param = [
            'respons_data' => $respons_data,
            'periods' => $periods,
            'name' => '开奖号码通知-数字彩',
            'json_data' => $json_data,
            'url' => $url,
            'type' => 3,
            'user_id' => $user_id,
        ];
        KafkaService::addQue('PushThirdNotice', $param, true);
//        $res = $this->updateTable($respons_data, $periods,"开奖号码通知-数字彩",$json_data, $url, 3, $user_id);
    }

    /**
     * 开奖号码通知-足球胜负彩
     * $url 通知URL
     * $periods 期数
     * $openNum 开奖号码
     */
    public function PushNoticeResultFourteen($periods,$openNum,$user_id){
        $url = '';
        $post_data = [
            'periods' => $periods,
            'open_number' => $openNum,
        ];
        $json_data = json_encode($post_data);
        $respons_data = $this->jsonPost($url, $json_data);
        //返回结果写入数据库 - 队列
        $param = [
            'respons_data' => $respons_data,
            'periods' => $periods,
            'name' => '开奖号码通知-足球胜负彩',
            'json_data' => $json_data,
            'url' => $url,
            'type' => 4,
            'user_id' => $user_id,
        ];
        KafkaService::addQue('PushThirdNotice', $param, true);
//        $res = $this->updateTable($respons_data, $periods,"开奖号码通知-足球胜负彩",$json_data, $url, 4, $user_id);
    }

    /**
     * 下注结果通知
     * @param $code 状态码
     * @param $msg 信息
     * @param $lottery_order_code 咕啦订单编号
     * @param $third_order_code 第三方订单编号
     * @param $type  5 = 下注， 6 = 出票
     */
    public function PushNoticePlayOrder($code, $msg, $lottery_order_code, $third_order_code, $user_id, $type=5){
        $url = 'http://php.javaframework.cn/api/test/gwp/a2';
        $post_data = [
//            'lottery_order_code' => $lottery_order_code,
            'third_order_code' => $third_order_code,
            'msg' => $msg,
            'code' => $code,
        ];
        $json_data = json_encode($post_data);
        $respons_data = $this->jsonPost($url, $json_data);
        //返回结果写入数据库 - 队列
        if($type == 5){
            $name = '下注结果通知';
        }
        if($type == 6){
            $name = '出票结果通知';
        }
        $param = [
            'respons_data' => $respons_data,
            'name' => $name,
            'json_data' => $json_data,
            'url' => $url,
            'type' => $type,
            'user_id' => $user_id,
            'third_order_code' => $third_order_code,
        ];
        KafkaService::addQue('PushThirdNotice', $param, true);
//        $this->updateTable($respons_data,'','下注结果通知',$json_data, $url, 5, $user_id, $third_order_code);
    }


    /**
     * JSON数据CURL POST
     * @param type $url
     * @param type $data
     * @return type
     */
    public function jsonPost($url, $data){
        $header[] = "Content-type: application/json";//定义content-type为xml
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        if(curl_errno($ch))
        {
            print curl_error($ch);
        }
        curl_close($ch);
        return $response;
    }

    /**
     * 定时：重新处理未推送到的通知
     * @return int
     */
    public function api_notice_crontab(){
        $query = new Query();
        $field = 'notice_id, param, status, command, user_id, response_msg';
        $miss_data = $query -> from('api_notice') -> select($field) -> where(['response_msg' => '']) ->andWhere(['<', 'lose_num', 5]) -> all();
        if(empty($miss_data)){
            return -1;
        }
        foreach($miss_data as $v){
            if(empty($v['param'])){ continue; }
            $param = json_decode($v['param'], true); //参数转数组
            switch ($v['command']) {
                case 1100:
                    $this -> PushNoticeSzc($param['lottery_code'], $param['periods'], $param['lottery_time'], $param['limit_time'], $param['weeks'], $v['user_id']);
                    break;
                case 1101:
                    $this -> PushNoticeFourteen($param['periods'], $param['beginsale_time'], $param['endsale_time'], $v['user_id']);
                    break;
                case 1102:
                    $this -> PushNoticeResultSzc($param['lottery_code'], $param['periods'], $param['open_number'], $v['user_id']);
                    break;
                case 1103:
                    $this -> PushNoticeResultFourteen($param['periods'], $param['open_number'], $v['user_id']);
                    break;
                case 1104:
                    $this -> PushNoticePlayOrder($param['code'], $param['msg'], '', $param['third_order_code'], $v['user_id']);
                    break;
                case 1105:
                    $this -> PushNoticePushOrder($param['code'], $param['msg'], '', $param['third_order_code'], $v['user_id']);
                    break;
            }
        }
        return 0;
    }

    /**
     * 通知数据写入表 （已弃用，同功能写入了队列）
     * @param $respons_data     curl返回的json数据
     * @param $periods          期数： lottery_type 1-4有
     * @param $name             接口名
     * @param $json_data        curl请求的json参数
     * @param $url              请求的目标地址
     * @param $type             接口标识：1=新期通知数字彩【1100】,2=新期通知-足球胜负彩【1101】,3=开奖号码通知-数字彩【1102】,
    4=开奖号码通知-足球胜负彩【1103】,5=下注结果通知【1104】,6=出票结果通知【1105】
     * @param string $third_order_code 第三方订单号
     * @param $user_id          商户id
     * @param string $lotteryOrderCode 咕啦订单编号
     * @return array
     */
    public function updateTable($respons_data,$periods,$name,$json_data, $url, $type, $user_id, $third_order_code=''){
        KafkaService::addQue();
        if($type == 5){
            $apiNotice = ApiNotice::find()->where(['third_order_code'=>$third_order_code, 'response_msg' => '']) ->one();
        } else {
            $apiNotice = ApiNotice::find()->where(["periods"=>$periods,'lottery_type'=>$type, 'response_msg' => ''])->one();
        }
        if(!empty($apiNotice)){
            $update = ['lose_num' => ($apiNotice["lose_num"]+1)];
            if($respons_data) $update['response_msg'] = $respons_data;
            if($type == 5){
                ApiNotice::updateAll($update, ["third_order_code" => $third_order_code]);
            } else {
                ApiNotice::updateAll($update, ["periods" => $periods]);
            }
        }else{
            $apiNotice = new ApiNotice();
            $apiNotice->name = $name;
            $apiNotice->periods = $periods;
            $apiNotice->param = $json_data;
            $apiNotice->response_msg = isset($respons_data) ? $respons_data : '' ;
            $apiNotice->lose_num = 1;
            $apiNotice->create_time = date("Y-m-d H:i:s");
            $apiNotice->url = $url;
            $apiNotice->third_order_code = isset($third_order_code) ? $third_order_code : '' ;
            $apiNotice->lottery_type = $type;
            $apiNotice->user_id = $user_id;
            $res = $apiNotice->save();
            if(!$res){
                $errorMsg = $apiNotice->errors;
                return ['code'=>109, 'msg'=>'写入失败', 'data'=>$errorMsg];
            }
        }
        return ['code'=>600, 'msg'=>'写入成功', 'data'=>''];
    }

}

<?php
namespace app\modules\tools\kafka;

use app\modules\common\helpers\Commonfun;
use app\modules\common\services\KafkaService;
use app\modules\openapi\models\ApiNotice;

class PushThirdNotice implements Kafka
{
    

    /*
     * @param respons_data     curl返回的json数据
     * @param periods          期数： lottery_type 1-4有
     * @param name             接口名
     * @param json_data        curl请求的json参数
     * @param url              请求的目标地址
     * @param type             接口标识：1=新期通知数字彩【1100】,2=新期通知-足球胜负彩【1101】,3=开奖号码通知-数字彩【1102】,
                                        4=开奖号码通知-足球胜负彩【1103】,5=下注结果通知【1104】,6=出票结果通知【1105】
     * @param string third_order_code 第三方订单号
     * @param user_id          商户id
     * @param string lottery_order_code 咕啦订单编号
     * @return array
     */
	public function run($params)
	{
	    Commonfun::updateQueue($params['queueId'], 2);

        if($params['type'] == 5 || $params['type'] == 6){
            $apiNotice = ApiNotice::find()->where(['third_order_code'=>$params['third_order_code'], 'response_msg' => '']) ->one();
        } else {
            $apiNotice = ApiNotice::find()->where(["periods"=>$params['periods'],'lottery_type'=>$params['type'], 'response_msg' => ''])->one();
        }
        if(!empty($apiNotice)){
            $update = ['lose_num' => ($apiNotice["lose_num"]+1)];
            if($params['respons_data']) $update['response_msg'] = $params['respons_data'];
            if($params['type'] == 5 || $params['type'] == 6){
                ApiNotice::updateAll($update, ["third_order_code" => $params['third_order_code']]);
            } else {
                ApiNotice::updateAll($update, ["periods" => $params['periods']]);
            }
        }else{
            $apiNotice = new ApiNotice();
            $apiNotice->name = isset($params['name']) ? $params['name'] : '';
            $apiNotice->periods = isset($params['periods']) ? $params['periods'] : '';
            $apiNotice->param = isset($params['json_data']) ? $params['json_data'] : '';
            $apiNotice->response_msg = isset($params['respons_data']) ? $params['respons_data'] : '' ;
            $apiNotice->lose_num = 1;
            $apiNotice->create_time = date("Y-m-d H:i:s");
            $apiNotice->url = isset($params['url']) ? $params['url'] : '' ;
            $apiNotice->third_order_code = isset($params['third_order_code']) ? $params['third_order_code'] : '' ;
//            $apiNotice->lottery_order_code = isset($params['lottery_order_code']) ? $params['lottery_order_code'] : '' ;
            $apiNotice->type = isset($params['type']) ? $params['type'] : '' ;
            $apiNotice->user_id = isset($params['user_id']) ? $params['user_id'] : '';
            $res = $apiNotice->save();
            if(!$res){
                $data = [
                    'msg' => $apiNotice->errors,
                    'param' => $params,
                ];
                KafkaService::addLog('第三方通知数据存储失败', $data);
            }
        }

		Commonfun::updateQueue($params['queueId'], 3);
	}
}
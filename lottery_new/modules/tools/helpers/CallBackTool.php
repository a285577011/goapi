<?php

namespace app\modules\tools\helpers;

use app\modules\common\models\ApiOrder;
use app\modules\common\models\LotteryOrder;
use app\modules\common\services\KafkaService;
use app\modules\openapi\models\CallbackBase;
use app\modules\openapi\services\PlayOrderService;

/**
 * 说明 ：回调系统类
 * @author  kevi
 * @date 2017年7月6日 下午1:41:34
 */
class CallBackTool {

    /**
     * 说明:回调第三方数据入口
     * @author chenqiwei
     * @date 2018/3/19 上午11:12
     * @param  int type 类型 (1、出票通知)
     * @param  array params  参数
     * @return
     */
    public static function addCallBack($type, $params) {
        $callbackCode = '';
        $callbackParams = [];
        if (empty($type)) {
            return false;
        }
        if ($type == 1) {
            //订单出票
            $lotteryOrderCode = $params['lottery_order_code'];
            $order = LotteryOrder::find()->select(['lottery_id', 'cust_no', 'agent_id', 'user_id', 'bet_val', 'odds', 'source', 'source_id', 'status', 'remark', 'out_time', 'refuse_reason'])->where(['lottery_order_code' => $lotteryOrderCode])->asArray()->one();
            if ($order['source'] == 7) {//流量单
                $thirdOrder = ApiOrder::find()->select(['message_id', 'third_order_code', 'status'])->where(['api_order_id' => $order['source_id']])->asArray()->one();
                $callbackObj = CallbackBase::find()->select(['callback_base.code', 'bussiness.des_key as des_key'])
                                ->leftJoin('bussiness', 'bussiness.bussiness_id = callback_base.agent_id')
                                ->where(['callback_base.type' => $type, 'bussiness.user_id' => $order['user_id']])
                                ->asArray()->one();
                if (empty($callbackObj)) {
                    return false;
                }
                $callbackCode = $callbackObj['code'];
                $pos = new PlayOrderService();
                $odds = $pos->midToOpenMid($order['lottery_id'], $order['status'], $order['bet_val'], $order['odds']);
                $callbackParams['messageId'] = $thirdOrder['message_id'];
                $callbackParams['orderId'] = $thirdOrder['third_order_code'];
                $callbackParams['status'] = $thirdOrder['status'];
                $callbackParams['outStatus'] = $order['status'];
                $callbackParams['odds'] = $odds;
                $callbackParams['reason'] = $order['refuse_reason'];
            } else if ($order['agent_id'] != 0) {  //代理商订单
                $callbackObj = CallbackBase::find()->select(['callback_base.code'])
                                ->leftJoin('agents', 'agents.agents_id = callback_base.agent_id')
                                ->where(['callback_base.type' => $type, 'callback_base.agent_id' => $order['agent_id']])
                                ->asArray()->one();
                if (empty($callbackObj)) {
                    return false;
                }
                $callbackCode = $callbackObj['code'];
                $callbackParams['cust_no'] = $order['cust_no'];
            } else {  //其他普通订单直接返回
                return false;
            }
            if ($order['status'] == 3) {
                $callbackParams['result'] = 10002;
            } else {
                $callbackParams['result'] = 10008;
            }
            if ($order['source'] == 7) {
                $callbackParams['outTime'] = $order['out_time'];
                $json_data = json_encode($callbackParams, JSON_UNESCAPED_UNICODE);
                $des = new Des($callbackObj['des_key'], '20180101');
                $des_data = $des->encrypt($json_data);

                $callbackParams = [
                    'message' => [
                        'head' => [
                            'status' => 0,
//                            'venderId'=>,
                            'md' => md5($des_data),
                        ],
                        'body' => $des_data,
                    ]
                ];
            }
        } else {  //其他
        }
        KafkaService::addQue('CallbackThird', ['code' => $callbackCode, 'params' => $callbackParams], false);
        return true;
    }

}

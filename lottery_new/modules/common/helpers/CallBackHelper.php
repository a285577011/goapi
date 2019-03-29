<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\common\helpers;


use app\modules\common\models\LotteryOrder;

class CallBackHelper {

    /**
     * 说明：加入回调队列表
     * @author chenqiwei
     * @date 2018/3/5 下午2:29
     * @param   int  type   回调类型标示
     * @param   array   params  参数
     * @return  array   当且仅当code为1 则可以发送，否则都不发送
     */
    public function AddCallBack($type,$params){



    }


//    /**
//     * 说明:根据回调类型，执行回调前逻辑
//     * @author chenqiwei
//     * @date 2018/3/5 下午2:29
//     * @param   int  type   回调类型
//     * @param   array   params  参数
//     * @return  array   当且仅当code为1 则可以发送，否则都不发送
//     */
//    public function todoByType($type,$params){
//        $code = 0;
//        $data=[];
//        switch ($type){
//            case 1://订单回调
//                $ret = $this->getOrderDetail($params['order_code'],$params['agent_id']);
//                if($ret['code']==600){
//                    $code = 1;
//                    $data = $ret['data'];
//                }
//                break;
//            case 2:
//                break;
//            default:
//                return ['code'=>0,'data'=>''];
//
//        }
//        return ['code'=>$code,'data'=>$data];
//    }

    /**
     * 获取投注订单信息
     */
    public function getOrderDetail($orderCode,$agentId){
        if(empty($orderCode)){
            return ["code"=>109,"msg"=>"订单编号不得为空"];
        }
        $field = LotteryOrder::FIELD;
        $orderDetail = LotteryOrder::find()->select($field)
            ->leftJoin('user','user.cust_no = lottery_order.cust_no')
            ->where(['lottery_order_code' => $orderCode,'agent_id'=>$agentId])
            ->asArray()
            ->one();
        if(empty($orderDetail)){
            return ["code" => 109, "msg" => '查询结果不存在'];
        }
        return ["code" => 600, "msg" => "查询成功","data"=>$orderDetail];
    }

}

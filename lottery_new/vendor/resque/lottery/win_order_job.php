<?php

use app\modules\user\helpers\WechatTool;
use app\modules\common\models\LotteryOrder;
use app\modules\common\helpers\Commonfun;

/**
 * 说明: 
 * @author  
 * @date 2017年6月12日 上午10:20:03
 * @param
 * @return 
 */
class win_order_job {

//     $lotteryqueue = new \LotteryQueue();
//     $lotteryqueue->pushQueue('win_order_job', 'win_order_msg_push', ['user'=>'open_id',"orderId" => '200123']);
    /**
     * 说明： 发送消息--开奖结果--会员
     * @auther  cqw
     * @date 2017年10月09日 下午
     * @param not null $title 标题
     * @param not null $user 用户openId
     * @param not null  $resultMsg  开奖状态
     * @param not null  $betMsg  彩种期次
     * @param not null $betMoney 投注金额
     * @param not null $betTime 投注时间
     * @return type
     */
    public function perform() {
        Commonfun::updateQueue($this->args['queueId'], 2);
        $userOpenId = $this->args['openId'];
        $orderId = $this->args['orderId'];
        $title = $this->args['title'];
        $resultMsg = $this->args['resultMsg'];
        $betMsg = $this->args['betMsg'];
        $betMoney = $this->args['betMoney'];
        $betTime = $this->args['betTime'];
        $remark = $this->args['remark'];

        //回调订单消息发送状态
        $lotteryOrder = LotteryOrder::findone($orderId);

        //发送消息
        if($userOpenId){
            $wechatTool = new WechatTool();
            $wechatTool->sendTemplateMsgAwards($title, $userOpenId, $resultMsg, $betMsg, $betMoney, $betTime, $remark, $lotteryOrder->lottery_order_code);
        }

        if(!empty($lotteryOrder)){
            $lotteryOrder->send_status = 1;
            $lotteryOrder->save();
            Commonfun::updateQueue($this->args['queueId'], 3);
            return $orderId;
        }else{
            return '无此订单记录'.$orderId;
        }
    }

}

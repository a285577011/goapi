<?php

namespace app\modules\tools\kafka;

use app\modules\user\helpers\WechatTool;
use app\modules\common\models\LotteryOrder;
use app\modules\common\helpers\Commonfun;

class WinOrder implements Kafka {

    public $args;

    public function run($params) {
        $this->args = $params;
        Commonfun::updateQueue($this->args['queueId'], 2);
        $userOpenId = $this->args['openId'];
        $orderId = $this->args['orderId'];
        $title = $this->args['title'];
        $resultMsg = $this->args['resultMsg'];
        $betMsg = $this->args['betMsg'];
        $betMoney = $this->args['betMoney'];
        $betTime = $this->args['betTime'];
        $remark = $this->args['remark'];

        // 回调订单消息发送状态
        $lotteryOrder = LotteryOrder::findOne(['lottery_order_id' => $orderId]);


        if (!empty($lotteryOrder)) {
            // 发送消息
            if ($userOpenId) {
                $wechatTool = new WechatTool();
                $wechatTool->sendTemplateMsgAwards($title, $userOpenId, $resultMsg, $betMsg, $betMoney, $betTime, $remark, $lotteryOrder->lottery_order_code);
            }
            $lotteryOrder->send_status = 1;
            $lotteryOrder->save();
            Commonfun::updateQueue($this->args['queueId'], 3);
            return $orderId;
        } else {
            return '无此订单记录' . $orderId;
        }
    }

}

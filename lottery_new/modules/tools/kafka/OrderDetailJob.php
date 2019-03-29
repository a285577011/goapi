<?php

namespace app\modules\tools\kafka;
use app\modules\common\models\LotteryOrder;

/**
 *类说明：获取用户订单信息
 * author   kevi
 * Date
 */
class OrderDetailJob implements Kafka {

    public $args;

    public function run($params) {
        $this->args = $params;
        $orderCode = $this->args['order_code'];
//        Commonfun::updateQueue($this->args['queueId'], 2);
        LotteryOrder::find()->where(['lottery_order_code'=>$orderCode]);
//        SyncService::syncFromQueue('LotteryJob');
        return true;
    }

}

<?php

use app\modules\common\models\LotteryOrder;
use app\modules\common\services\OrderService;
use app\modules\common\models\BettingDetail;
use app\modules\common\helpers\Commonfun;
use app\modules\orders\services\MajorService;
use app\modules\user\models\User;
use app\modules\common\helpers\orderNews;
use yii\db\Query;

/**
 * 说明: 
 * @author  
 * @date 2017年6月12日 上午10:20:03
 * @param
 * @return 
 */
class lottery_job {

    public function perform() {
        Commonfun::updateQueue($this->args['queueId'], 2);
        $db = \Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
            $lotOrder = LotteryOrder::findOne(["lottery_order_id" => $this->args['orderId'], "suborder_status" => "0"]);
            if ($lotOrder == null) {
                return false;
            }
            if ($lotOrder->major_type != 0) {
                $majorService = new MajorService();
                $ret = $majorService->proSubOrder($lotOrder);
            } else {
                $control = new OrderService();
                $ret = $control->proSuborder($lotOrder);
            }

            if ($ret["code"] != "0") {
                $lotOrder->suborder_status = 2;
                $lotOrder->status = 6;
                $lotOrder->save();
                BettingDetail::updateAll([
                    "status" => 6
                        ], 'lottery_order_id=' . $lotOrder->lottery_order_id);
                $ret = OrderService::outOrderFalse($lotOrder->lottery_order_code, 6, null, "详情订单生成出错");
            } else {
                $lotOrder->suborder_status = 1;
                $lotOrder->save();
//                if ($lotOrder->source == 6) {   //6、计划（来源） 直接出票
//                    OrderService::outOrder($lotOrder->lottery_order_code, $lotOrder->store_id);
//                }
                //$ret = OrderService::outOrder($lotOrder->lottery_order_id);

                orderNews::outOrderNotice($lotOrder->store_id, $lotOrder->store_no, $lotOrder->cust_no, $lotOrder->lottery_order_code, $lotOrder->bet_money, $lotOrder->end_time, $lotOrder->lottery_id, $lotOrder->periods);
                $redis = Yii::$app->redis;
                $user = User::findOne(["user_id" => $lotOrder->store_id]);
                $redis->sadd("sockets:new_order_list", $user->cust_no);
                $userOperators = (new Query())->select("cust_no")->from("store_operator s")->join("left join", "user u", "u.user_id=s.user_id")->where(["s.store_id" => $lotOrder->store_no, "s.status" => 1])->all();
                foreach ($userOperators as $val) {
                    $redis->sadd("sockets:new_order_list", $val["cust_no"]);
                }
            }
            Commonfun::updateQueue($this->args['queueId'], 3);
            $tran->commit();
        } catch (\yii\db\Exception $e) {
            $tran->rollBack();
            Commonfun::updateQueue($this->args['queueId'], 4);
            return json_encode($e);
        }
        return true;
    }

}

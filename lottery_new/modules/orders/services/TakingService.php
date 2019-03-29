<?php

namespace app\modules\orders\services;

use Yii;
use app\modules\orders\models\OrderTaking;
use app\modules\common\models\LotteryOrder;
use app\modules\common\services\OrderService;
use yii\base\Exception;
use app\modules\common\services\KafkaService;
use app\modules\common\services\SyncService;
use app\modules\store\models\Store;
use app\modules\common\helpers\OrderNews;
use app\modules\user\models\User;
use yii\db\Query;
use app\modules\common\models\BettingDetail;
use app\modules\tools\helpers\CallBackTool;

class TakingService {

    /**
     * 旗舰店轮循
     * @param type $orderCode 订单编号
     * @return type
     * @throws Exception
     */
    public static function polling($orderCode) {
        $takingNums = OrderTaking::find()->select(['store_code'])->where(['order_code' => $orderCode])->asArray()->all();
        $orderData = LotteryOrder::findOne(['lottery_order_code' => $orderCode, 'status' => 2, 'deal_status' => 0]);
        if (empty($orderData)) {
            return ['code' => 109, 'msg' => '该订单已无法轮循门店'];
        }
        $takingData = OrderTaking::findOne(['store_code' => $orderData->store_no, 'status' => 1]);
        if (empty($takingData)) {
            KafkaService::addLog('order_polling_1', '该接单门店，已失效');
            return ['code' => 109, 'msg' => '该接单门店，已失效'];
        }
        $orderTime = strtotime('+30 minute', strtotime($orderData->create_time));
        $nowTime = time();
        $takingStore = array_column($takingNums, 'store_code');
        $takingData->status = 2;
        $takingData->modify_time = date('Y-m-d H:i:s');
        $takingData->save();
        $db = \Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            if (strtotime($orderData->end_time) < time() || $takingNums == 4) {//超过截止时间 或 轮循次数 拒绝出票
                $refuse = self::refuseOrder($orderCode);
                if ($refuse['code'] != 600) {
                    throw new Exception($refuse['msg']);
                }
            } else {
                $storeData = Store::find()->select(['store_code', 'user_id'])->where(['company_id' => 1, 'status' => 1, 'business_status' => 1])->andWhere(['not in', 'store_code', $takingStore])->asArray()->all();
                if (empty($storeData)) {
                    if ($orderTime < $nowTime) { //无出票门店 且 下单时间已超过30分钟 拒绝出票
                        $refuse = self::refuseOrder($orderCode);
                        if ($refuse['code'] != 600) {
                            throw new Exception($refuse['msg']);
                        }
                    }
                } else {
                    $store = $storeData[array_rand($storeData, 1)]; // 随机获取
                    $orderTaking = new OrderTaking();
                    $orderTaking->order_code = $orderCode;
                    $orderTaking->store_code = $store['store_code'];
                    $orderTaking->create_time = date('Y-m-d H:i:s');
                    if (!$orderTaking->save()) {
                        throw new Exception('接单表写入失败');
                    }
                    $orderData->store_id = $store['user_id'];
                    $orderData->store_no = $store['store_code'];
                    $orderData->modify_time = date('Y-m-d H:i:s');
                    if (!$orderData->save()) {
                        throw new Exception('订单门店修改失败');
                    }
                    OrderNews::outOrderNotice($orderData->store_id, $orderData->store_no, $orderData->cust_no, $orderData->lottery_order_code, $orderData->bet_money, $orderData->end_time, $orderData->lottery_id, $orderData->periods);
                    $redis = \Yii::$app->redis;
                    $user = User::findOne(["user_id" => $orderData->store_id]);
                    $redis->sadd("sockets:new_order_list", $user->cust_no);
                    $userOperators = (new Query())->select("cust_no")->from("store_operator s")->join("left join", "user u", "u.user_id=s.user_id")->where(["s.store_id" => $orderData->store_no, "s.status" => 1])->all();
                    foreach ($userOperators as $val) {
                        $redis->sadd("sockets:new_order_list", $val["cust_no"]);
                    }
                }
            }
            $trans->commit();
            return ['code' => 600, 'msg' => '分配门店成功'];
        } catch (Exception $ex) {
            $trans->rollBack();
            KafkaService::addLog('order_polling_2', $ex->getMessage());
            return ['code' => 109, 'msg' => $ex->getMessage()];
        }
    }

    /**
     * 拒绝出票
     * @param type $orderCode
     * @return type
     */
    public static function refuseOrder($orderCode) {
        $orderData = LotteryOrder::findOne(['lottery_order_code' => $orderCode, 'status' => 2, 'deal_status' => 0]);
        BettingDetail::updateAll(["status" => 10], ['lottery_order_id' => $orderData->lottery_order_id, "status" => 2]);
        $orderData->status = 10;
        $orderData->out_time = date('Y-m-d H:i:s');
        $orderData->refuse_reason = '无门店接单';
        if (!$orderData->saveData()) {
            return ['code' => 109, 'msg' => '拒绝失败'];
        }
        $ret2 = OrderService::outOrderFalse($orderCode, 10, $orderData->store_no);
        if ($ret2 == false) {
            return ['code' => 109, 'msg' => '退款失败'];
        }
        SyncService::syncFromQueue('OrderPollingStore');
        OrderNews::userOutOrder($orderCode, 2);
        CallBackTool::addCallBack(1, ['lottery_order_code' => $orderCode]);
        return ['code' => 600, 'msg' => '拒绝成功'];
    }

}

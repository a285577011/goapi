<?php

namespace app\modules\store\services;

use Yii;
use yii\db\Query;
use app\modules\common\models\Store;
use app\modules\common\models\UserFunds;
use app\modules\common\models\LotteryOrder;
use yii\base\Exception;
use app\modules\common\services\KafkaService;

class StoreService {

    public function createOrUpdateUser($storeTel, $custNo, $storeDetail) {
        $store = Store::find()->where(['phone_num' => $storeTel, 'cust_no' => $custNo])->one();
        if (empty($store)) {
            $storeCode = Store::find()->select(['max(store_code) as store_code'])->asArray()->one();
            $store = new Store();
            if (empty($storeCode['store_code'])) {
                $storeCode = 10001;
            } else {
                $storeCode = intval($storeCode['store_code']) + 1;
            }
            $store->store_code = $storeCode;
            $store->cust_no = $custNo;
            $store->phone_num = $storeTel;
            $store->store_name = $storeTel;
            $store->province = $storeDetail['data']['province'];
            $store->city = $storeDetail['data']['city'];
            $store->area = $storeDetail['data']['country'];
            $store->address = $storeDetail['data']['address'];
            $store->create_time = date('Y-m-d H:i:s');
        }
        $store->real_name_status = $storeDetail['data']['checkStatus'];
        $db = \Yii::$app->db;
        $train = $db->beginTransaction();
        try { // 日志
            if (!$store->validate()) {
                throw new Exception('数据验证失败');
            }
            if (!$store->save()) {
                throw new Exception('数据保存失败');
            }
            $funds = UserFunds::find()->where(['cust_no' => $custNo])->one();
            if (empty($funds)) {
                $funds = new UserFunds;
                $funds->cust_no = $custNo;
                $funds->create_time = date('Y-m-d H:i:s');
                if (!$funds->validate()) {
                    throw new Exception('资金表数据验证失败');
                }
                if (!$funds->save()) {
                    throw new Exception('资金表写入失败');
                }
            }
            $train->commit();
            return $store->attributes;
        } catch (Exception $ex) {
            $train->rollBack();
            return \Yii::jsonError(401, '登录失败,请稍后再试');
        }
    }

    /**
     * 获取APP统计报表今日、昨日数据
     */
    public function getNowReport($storeCode, $timer) {
        $statusArr = [3, 4, 5];
        $data = [];
        $data["days"] = 0;
        $data["count"] = 0;
        $data["salemoney"] = 0;
        $data["realmoney"] = 0;
        $data["ordernum"] = 0;
        $data["winmoney"] = 0;
        $data["award_amount"] = 0;
        $data["paymoney"] = 0;
        $data["no_award"] = 0;
        $query = (new Query())->select(["DATE_FORMAT(create_time,'%Y-%m-%d') days", "count(distinct cust_no) count", "sum(bet_money) salemoney", "count(lottery_order_id) ordernum", "sum(win_amount) winmoney", "sum(award_amount) award_amount"])
                ->from("lottery_order")
                ->where(["store_no" => $storeCode])
                ->andWhere(["in", "status", $statusArr]);
        if ($timer == 0) {
            $query = $query->andWhere(["between", "lottery_order.create_time", date("Y-m-d") . " 00:00:00", date("Y-m-d") . " 23:59:59"]);
        } else {
            $query = $query->andWhere(["between", "lottery_order.create_time", date("Y-m-d", strtotime("-" . $timer . "days")) . " 00:00:00", date("Y-m-d", strtotime("-" . $timer . "days")) . " 23:59:59"]);
        }
        $query = $query->groupBy("days")
                ->orderBy("days desc")
                ->indexBy("days");
        $result = $query->all();
        //统计未兑金额     
        $noAward = (new Query())->select(["DATE_FORMAT(create_time,'%Y-%m-%d') days", "sum(win_amount) no_award"])
                ->from("lottery_order")
                ->where(["store_no" => $storeCode])
                ->andWhere("status=4 and deal_status=1");
        if ($timer == 0) {
            $noAward = $noAward->andWhere(["between", "lottery_order.create_time", date("Y-m-d") . " 00:00:00", date("Y-m-d") . " 23:59:59"]);
        } else {
            $noAward = $noAward->andWhere(["between", "lottery_order.create_time", date("Y-m-d", strtotime("-" . $timer . "days")) . " 00:00:00", date("Y-m-d", strtotime("-" . $timer . "days")) . " 23:59:59"]);
        }
        $noAward = $noAward->groupBy("days")->orderBy("days desc")->indexBy("days");
        $noAwardRes = $noAward->all();
        //统计出票手续费
        $list = (new Query())->select(["DATE_FORMAT(lottery_order.create_time,'%Y-%m-%d') days", "sum(pay_record.pay_money) paymoney"])
                ->from("lottery_order,pay_record")
                ->where(["lottery_order.store_no" => $storeCode])
                ->andWhere("lottery_order.lottery_order_code=pay_record.order_code")
                ->andWhere("pay_record.pay_type=16")
                ->andWhere(["in", "lottery_order.status", $statusArr]);
        if ($timer == 0) {
            $list = $list->andWhere(["between", "lottery_order.create_time", date("Y-m-d") . " 00:00:00", date("Y-m-d") . " 23:59:59"]);
        } else {
            $list = $list->andWhere(["between", "lottery_order.create_time", date("Y-m-d", strtotime("-" . $timer . "days")) . " 00:00:00", date("Y-m-d", strtotime("-" . $timer . "days")) . " 23:59:59"]);
        }
        $list = $list->groupBy("days")
                ->indexBy("days");
        $res = $list->all();
        //数据重组
        if (!empty($result)) {
            foreach ($result as $k => &$v) {
                if (isset($res[$k])) {
                    $v["paymoney"] = $res[$k]["paymoney"];
                } else {
                    $v["paymoney"] = 0;
                }
                if (isset($noAwardRes[$k]) && $noAwardRes[$k] != "") {
                    $v["no_award"] = $noAwardRes[$k]["no_award"];
                } else {
                    $v["no_award"] = 0;
                }
            }
        }
        //数组重组
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $data["days"] = $value["days"];
                $data["count"] = $value["count"] != "" ? $value["count"] : 0;
                $data["salemoney"] = $value["salemoney"] != "" ? $value["salemoney"] : 0;
                $data["paymoney"] = $value["paymoney"] != "" ? $value["paymoney"] : 0;
                $data["realmoney"] = sprintf("%.2f", $value["salemoney"] - $value["paymoney"]);
                $data["ordernum"] = $value["ordernum"] != "" ? $value["ordernum"] : 0;
                $data["winmoney"] = $value["winmoney"] != "" ? $value["winmoney"] : 0;
                $data["award_amount"] = $value["award_amount"] != "" ? $value["award_amount"] : 0;
                $data["no_award"] = $value["no_award"] != "" ? $value["no_award"] : 0;
            }
        }
        return $data;
    }

    /**
     * 获取报表月统计数据
     */
    public function getMonthReport($storeCode, $date) {
        $statusArr = [3, 4, 5];
        $data = [];
        $data["months"] = 0;
        $data["count"] = 0;
        $data["salemoney"] = 0;
        $data["realmoney"] = 0;
        $data["ordernum"] = 0;
        $data["winmoney"] = 0;
        $data["award_amount"] = 0;
        $data["paymoney"] = 0;
        $data["no_award"] = 0;
        $query = (new Query())->select(["DATE_FORMAT(create_time,'%Y-%m') months", "count(distinct cust_no) count", "sum(bet_money) salemoney", "count(lottery_order_id) ordernum", "sum(win_amount) winmoney", "sum(award_amount) award_amount"])
                ->from("lottery_order")
                ->where(["store_no" => $storeCode]);
        if ($date == 0) {
            $query = $query->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => date("Y-m")]);
        } else {
            $query = $query->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => $date]);
        }
        $query = $query->andWhere(["in", "status", $statusArr])->groupBy("months")
                ->orderBy("months desc")
                ->indexBy("months");
        $result = $query->all();
        //统计未兑金额
        $noAward = (new Query())->select(["DATE_FORMAT(create_time,'%Y-%m') months", "sum(win_amount) no_award"])
                ->from("lottery_order")
                ->where(["store_no" => $storeCode])
                ->andWhere("status=4 and deal_status=1");
        if ($date == 0) {
            $noAward = $noAward->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => date("Y-m")]);
        } else {
            $noAward = $noAward->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => $date]);
        }
        $noAward = $noAward->groupBy("months")->orderBy("months desc")->indexBy("months");
        $noAwardRes = $noAward->all();
        //统计出票手续费
        $list = (new Query())->select(["DATE_FORMAT(lottery_order.create_time,'%Y-%m') months", "sum(pay_record.pay_money) paymoney"])
                ->from("lottery_order,pay_record")
                ->where(["lottery_order.store_no" => $storeCode])
                ->andWhere("lottery_order.lottery_order_code=pay_record.order_code")
                ->andWhere("pay_record.pay_type=16");
        if ($date == 0) {
            $list = $list->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => date("Y-m")]);
        } else {
            $list = $list->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => $date]);
        }
        $list = $list->andWhere(["in", "lottery_order.status", $statusArr])
                ->groupBy("months")
                ->indexBy("months");
        $res = $list->all();
        foreach ($result as $k => &$v) {
            if (isset($res[$k])) {
                $v["paymoney"] = $res[$k]["paymoney"];
            } else {
                $v["paymoney"] = 0;
            }
            if (isset($noAwardRes[$k]) && $noAwardRes[$k] != "") {
                $v["no_award"] = $noAwardRes[$k]["no_award"];
            } else {
                $v["no_award"] = 0;
            }
        }
        //数组重组
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $data["months"] = $value["months"];
                $data["count"] = $value["count"] != "" ? $value["count"] : 0;
                $data["salemoney"] = $value["salemoney"] != "" ? $value["salemoney"] : 0;
                $data["paymoney"] = $value["paymoney"] != "" ? $value["paymoney"] : 0;
                $data["realmoney"] = sprintf("%.2f", $value["salemoney"] - $value["paymoney"]);
                $data["ordernum"] = $value["ordernum"] != "" ? $value["ordernum"] : 0;
                $data["winmoney"] = $value["winmoney"] != "" ? $value["winmoney"] : 0;
                $data["award_amount"] = $value["award_amount"] != "" ? $value["award_amount"] : 0;
                $data["no_award"] = $value["no_award"] != "" ? $value["no_award"] : 0;
            }
        }
        //当月每日数据
        $dayReport = $this->getDayReport($storeCode, $date);
        $res = ["month" => $data, "day" => $dayReport];
        return $res;
    }

    /**
     * 根据月份统计每日数据
     */
    public function getDayReport($storeCode, $date) {
        $statusArr = [3, 4, 5];
        $data = [];
        $query = (new Query())->select(["DATE_FORMAT(create_time,'%Y-%m-%d') days", "count(distinct cust_no) count", "sum(bet_money) salemoney", "count(lottery_order_id) ordernum", "sum(win_amount) winmoney", "sum(award_amount) award_amount"])
                ->from("lottery_order")
                ->where(["store_no" => $storeCode])
                ->andWhere(["in", "status", $statusArr]);
        if ($date == 0) {
            $query = $query->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => date("Y-m")]);
        } else {
            $query = $query->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => $date]);
        }
        $query = $query->groupBy("days")
                ->orderBy("days desc")
                ->indexBy("days");
        $result = $query->all();
        //统计未兑金额
        $noAward = (new Query())->select(["DATE_FORMAT(create_time,'%Y-%m-%d') days", "sum(win_amount) no_award"])
                ->from("lottery_order")
                ->where(["store_no" => $storeCode])
                ->andWhere("status=4 and deal_status=1");
        if ($date == 0) {
            $noAward = $noAward->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => date("Y-m")]);
        } else {
            $noAward = $noAward->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => $date]);
        }
        $noAward = $noAward->groupBy("days")->orderBy("days desc")->indexBy("days");
        $noAwardRes = $noAward->all();
        //统计出票手续费
        $list = (new Query())->select(["DATE_FORMAT(lottery_order.create_time,'%Y-%m-%d') days", "sum(pay_record.pay_money) paymoney"])
                ->from("lottery_order,pay_record")
                ->where(["lottery_order.store_no" => $storeCode])
                ->andWhere("lottery_order.lottery_order_code=pay_record.order_code")
                ->andWhere("pay_record.pay_type=16")
                ->andWhere(["in", "lottery_order.status", $statusArr]);
        if ($date == 0) {
            $list = $list->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => date("Y-m")]);
        } else {
            $list = $list->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y-%m')" => $date]);
        }
        $list = $list->groupBy("days")
                ->indexBy("days");
        $res = $list->all();
        //数据重组
        if (!empty($result)) {
            foreach ($result as $k => &$v) {
                if (isset($res[$k])) {
                    $v["paymoney"] = $res[$k]["paymoney"];
                } else {
                    $v["paymoney"] = 0;
                }
                if (isset($noAwardRes[$k]) && $noAwardRes[$k] != "") {
                    $v["no_award"] = $noAwardRes[$k]["no_award"];
                } else {
                    $v["no_award"] = 0;
                }
            }
            $result = array_values($result);
            foreach ($result as $key => &$value) {
                $data[$key]["days"] = $value["days"];
                $data[$key]["count"] = $value["count"] != "" ? $value["count"] : 0;
                $data[$key]["salemoney"] = $value["salemoney"] != "" ? $value["salemoney"] : 0;
                $data[$key]["paymoney"] = $value["paymoney"] != "" ? $value["paymoney"] : 0;
                $data[$key]["realmoney"] = sprintf("%.2f", $value["salemoney"] - $value["paymoney"]);
                $data[$key]["ordernum"] = $value["ordernum"] != "" ? $value["ordernum"] : 0;
                $data[$key]["winmoney"] = $value["winmoney"] != "" ? $value["winmoney"] : 0;
                $data[$key]["award_amount"] = $value["award_amount"] != "" ? $value["award_amount"] : 0;
                $data[$key]["no_award"] = $value["no_award"] != "" ? $value["no_award"] : 0;
            }
        }
        return $data;
    }

    public function orderTaking($orderCode, $storeCode, $optId) {
        $db = \Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            $orderData = LotteryOrder::findOne(['lottery_order_code' => $orderCode, 'status' => 2]);
            if (empty($orderData)) {
                throw new Exception('订单数据不存在');
            }
            if($orderData->store_no != $storeCode) {
                throw new Exception('已超过接单时限！此单已被分配其他店！');
            }
            $orderData->status = 11;
            $orderData->opt_id = (string) $optId;
            $orderData->modify_time = date('Y-m-d H:i:s');
            $orderUpdateSql = "UPDATE betting_detail SET status=11 WHERE lottery_order_code = '{$orderCode}';";
//            $update = ['status' => 8, 'opt_id' => $optId, 'out_time' => date('Y-m-d H:i:s')];
            if (!$orderData->save()) {
                KafkaService::addLog('order_taking', var_export($orderData->getFirstErrors(), true));
                throw new Exception('接单失败');
            }
//            LotteryOrder::upData($update, $where); //修改订单和详情的状态为待开奖3
            $a = $db->createCommand($orderUpdateSql)->execute(); //修改订单和详情的状态为接单状态
            if ($orderData['source'] == 4) {//该订单如果是合买
                $programmeroUpdateSql = "UPDATE programme SET status=12 WHERE programme_id = {$orderData['source_id']};
                    UPDATE programme_user SET status=12 WHERE programme_id = {$orderData['source_id']} and status = 3;";
                $db->createCommand($programmeroUpdateSql)->execute(); //修改合买订单、子单状态
            }
            $trans->commit();
            return ['code' => 600, 'msg' => '接单成功'];
        } catch (Exception $ex) {
            $trans->rollBack();
            return ['code' => 109, 'msg' =>$ex->getMessage()];
        }
    }

}

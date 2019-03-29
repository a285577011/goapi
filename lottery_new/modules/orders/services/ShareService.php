<?php

namespace app\modules\orders\services;

use app\modules\common\models\LotteryOrder;
use app\modules\common\helpers\Constants;
use app\modules\competing\helpers\CompetConst;
use app\modules\common\models\OptionalSchedule;
use app\modules\competing\models\LanSchedule;
use app\modules\common\models\Schedule;
use app\modules\orders\models\OrderShare;
use app\modules\user\models\UserGrowthRecord;
use app\modules\competing\models\BdSchedule;

class ShareService {

    /**
     * 分享订单写入
     * @auther GL zyl
     * @param type $userId 分享人ID
     * @param type $custNo 分享人编号
     * @param type $orderId 订单ID
     * @param type $remark 推荐理由
     * @return type
     */
    public function shareOrder($userId, $custNo, $orderId, $remark) {
        $order = LotteryOrder::find()->select(['lottery_order_id', 'lottery_order_code', 'source'])->where(['lottery_order_id' => $orderId, 'cust_no' => $custNo, 'status' => 3])->asArray()->one();
        if (empty($order)) {
            return ['code' => 109, 'msg' => '此订单不可分享'];
        }
        $share = OrderShare::findOne(['organiz_id' => $userId, 'order_id' => $orderId]);
        if (empty($share)) {
            $share = new OrderShare;
            $share->organiz_id = $userId;
            $share->organiz_no = $custNo;
            $share->order_id = $orderId;
            $share->create_time = date('Y-m-d H:i:s');
        } else {
            $share->modify_time = date('Y-m-d H:i:s');
        }
        $share->recom_remark = $remark;
        if (!$share->validate()) {
            return ['code' => 109, 'msg' => '数据验证失败'];
        }
        if (!$share->save()) {
            return ['code' => 109, 'msg' => '数据存储失败'];
        }
        //订单分享赠送成长值
        $userGrowth = new UserGrowthRecord();
        //是否分享过
        $record = $userGrowth::find()->select('order_code')->where(['order_code' => $order['lottery_order_code'], 'growth_source' => 7])->asArray()->one();
        if (empty($record)) {
            $userGrowth->updateGrowth($custNo, ['order_code' => $order['lottery_order_code'], 'order_source' => $order['source']], 7);
        }
        return ['code' => 600, 'msg' => '数据存储成功'];
    }

    /**
     * 获取分享订单的信息(奖金优化暂不分享)
     * @auther GL zyl
     * @param type $orderId 订单ID
     * @param type $orderCode 订单Code
     * @return type
     */
    public function getOrderInfo($orderId, $orderCode) {
        $field = ['lottery_order_id', 'lottery_id', 'lottery_name', 'lottery_type', 'play_code', 'play_name', 'periods', 'lottery_order.store_id', 'end_time', 'bet_double', 'is_bet_add', 'bet_money', 'bet_val', 'u.user_name',
            'u.user_pic', 's.store_name', 'w.with_nums', 'w.recom_remark', 'w.order_share_id', 'count'];
        $orderData = LotteryOrder::find()->select($field)
                ->leftJoin('user u', 'u.cust_no = lottery_order.cust_no')
                ->leftJoin('store s', 's.user_id = lottery_order.store_id')
                ->leftJoin('order_share w', 'w.order_id = lottery_order.lottery_order_id')
                ->where(['lottery_order_id' => $orderId, 'lottery_order_code' => $orderCode])
                ->asArray()
                ->one();
        $numsArr = Constants::MADE_NUMS_LOTTERY;
        $optionalArr = Constants::MADE_OPTIONAL_LOTTERY;
        $footballArr = Constants::MADE_FOOTBALL_LOTTERY;
        $basketballArr = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bdArr = CompetConst::MADE_BD_LOTTERY;
        $orderData['bet_val'] = trim($orderData['bet_val'], '^');
        $valStr = $orderData['bet_val'];
        if (in_array($orderData['lottery_id'], $numsArr)) {
            $orderData['bet_contents'] = explode('^', $valStr);
        } elseif (in_array($orderData['lottery_id'], $optionalArr)) {
            $scheData = OptionalSchedule::find()->select(['sorting_code', 'league_name', 'start_time', 'home_short_name', 'visit_short_name'])
                    ->where(['periods' => $orderData['periods']])
                    ->orderBy('sorting_code')
                    ->asArray()
                    ->all();
            $bets = explode(',', $valStr);
            foreach ($scheData as $key => &$ov) {
                $betVal = [];
                for ($i = 0; $i < strlen($bets[$key]); $i++) {
                    $betVal[] = $bets[$key][$i];
                }
                $ov['val'] = $betVal;
            }
            $orderData['bet_contents'] = $scheData;
        } elseif (in_array($orderData['lottery_id'], $footballArr)) {
            if ($orderData["lottery_id"] != '3011') {
                $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
            } else {
                $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
            }
            $betNums = explode("|", $valStr);
            $oddsArr = [];
            $betVal = [];
            $result = [];
            $r = [];
            foreach ($betNums as $ball) {
                preg_match($pattern, $ball, $result);
                if ($orderData["lottery_id"] != '3011') {
                    $mids[] = $result[1];
                    $oddsStr = 'odds' . $orderData['lottery_id'];
                    $betVal[$result[1]][$orderData['lottery_id']] = explode(",", $result[2]);
                    $oddsArr[] = $oddsStr;
                } else {
                    $mids[] = $result[1];
                    $result[2] = trim($result[2], "*");
                    $strs = explode("*", $result[2]);
                    foreach ($strs as $str) {
                        preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                        $betVal[$result[1]][$r[1]] = explode(",", $r[2]);
                    }
                    $oddsArr = ['odds3006', 'odds3007', 'odds3008', 'odds3009', 'odds3010'];
                }
            }
            $field = ['schedule_id', 'schedule_code', 'schedule_mid', 'home_short_name', 'visit_short_name', 'start_time', 'rq_nums', 'l.league_short_name', 'endsale_time'];
            $schedules = Schedule::find()->select($field)
                    ->leftJoin('league l', 'l.league_id = schedule.league_id')
                    ->with($oddsArr)
                    ->where(['in', 'schedule_mid', $mids])
                    ->indexBy('schedule_mid')
                    ->orderBy('schedule_mid')
                    ->asArray()
                    ->all();
            foreach ($schedules as $key => &$zv) {
                $zv['val'] = $betVal[$key];
                $orderData['bet_contents'][] = $zv;
            }
        } elseif (in_array($orderData['lottery_id'], $basketballArr)) {
            if ($orderData["lottery_id"] != '3005') {
                $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
            } else {
                $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
            }
            $betNums = explode("|", $valStr);
            $oddsArr = [];
            $betVal = [];
            $result = [];
            $r = [];
            foreach ($betNums as $ball) {
                preg_match($pattern, $ball, $result);
                if ($orderData["lottery_id"] != '3005') {
                    $mids[] = $result[1];
                    $oddsStr = 'odds' . $orderData['lottery_id'];
                    $betVal[$result[1]][$orderData['lottery_id']] = explode(",", $result[2]);
                    $oddsArr[] = $oddsStr;
                } else {
                    $mids[] = $result[1];
                    $result[2] = trim($result[2], "*");
                    $strs = explode("*", $result[2]);
                    foreach ($strs as $str) {
                        preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                        $oddsStr = 'odds' . $r[1];
                        $betVal[$result[1]][$r[1]] = explode(",", $r[2]);
                    }
                    $oddsArr = ['odds3001', 'odds3002', 'odds3003', 'odds3004'];
                }
            }
            $field = ['schedule_code', 'schedule_mid', 'home_short_name', 'visit_short_name', 'start_time', 'league_name', 'endsale_time'];
            $schedules = LanSchedule::find()->select($field)
                    ->with($oddsArr)
                    ->where(['in', 'schedule_mid', $mids])
                    ->indexBy('schedule_mid')
                    ->orderBy('schedule_mid')
                    ->asArray()
                    ->all();
            foreach ($schedules as $key => &$lv) {
                $lv['val'] = $betVal[$key];
                $orderData['bet_contents'][] = $lv;
            }
        } elseif (in_array($orderData['lottery_id'], $bdArr)) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
            $betNums = explode("|", $valStr);
            $oddsArr = [];
            $betVal = [];
            $result = [];
            $r = [];
            foreach ($betNums as $ball) {
                preg_match($pattern, $ball, $result);
                $mids[] = $result[1];
                $oddsStr = 'odds' . $orderData['lottery_id'];
                $betVal[$result[1]][$orderData['lottery_id']] = explode(",", $result[2]);
                $oddsArr[] = $oddsStr;
            }
            if ($orderData['lottery_id'] != '5006') {
                $playType = 1;
            } else {
                $playType = 2;
            }
            $field = ['periods', 'open_mid', 'schedule_mid', 'schedule_type', 'bd_sort', 'start_time', 'beginsale_time', 'endsale_time', 'league_name', 'home_name', 'visit_name', 'spf_rq_nums', 'sfgg_rf_nums',
                'league_code', 'home_code', 'visit_code', 'schedule_date'];
            $schedules = BdSchedule::find()->select($field)
                    ->with($oddsArr)
                    ->where(['play_type' => $playType])
                    ->andWhere(['in', 'open_mid', $mids])
                    ->indexBy('open_mid')
                    ->orderBy('open_mid')
                    ->asArray()
                    ->all();
            foreach ($schedules as $key => &$lv) {
                $lv['val'] = $betVal[$key];
                if ($orderData['lottery_id'] == 5001) {
                    $updateField = ['odds_5001_id', 'open_mid', 'update_nums', 'let_wins', 'trend_3', 'let_level', 'trend_1', 'let_negative', 'trend_0', 'create_time', 'modify_time', 'update_time'];
                    $lv['odds5001'] = array_combine($updateField, $lv['odds5001']);
                } elseif ($orderData['lottery_id'] == 5002) {
                    $updateField = ['odds_5001_id', 'open_mid', 'update_nums', 'total_gold_0', 'trend_0', 'total_gold_1', 'trend_1', 'total_gold_2', 'trend_2', 'total_gold_3', 'trend_3', 'total_gold_4', 'trend_4',
                        'total_gold_5', 'trend_5', 'total_gold_6', 'trend_6', 'total_gold_7', 'trend_7', 'create_time', 'modify_time', 'update_time'];
                    $lv['odds5002'] = array_combine($updateField, $lv['odds5002']);
                } elseif ($orderData['lottery_id'] == 5003) {
                    $updateField = ['odds_5001_id', 'open_mid', 'update_nums', 'bqc_00', 'trend_00', 'bqc_01', 'trend_01', 'bqc_03', 'trend_03', 'bqc_10', 'trend_10', 'bqc_11', 'trend_11',
                        'bqc_13', 'trend_13', 'bqc_30', 'trend_30', 'bqc_31', 'trend_31', 'bqc_33', 'trend_33', 'create_time', 'modify_time', 'update_time'];
                    $lv['odds5003'] = array_combine($updateField, $lv['odds5003']);
                } elseif ($orderData['lottery_id'] == 5005) {
                    $updateField = ['odds_5001_id', 'open_mid', 'update_nums', 'score_wins_10', 'trend_10', 'score_wins_20', 'trend_20', 'score_wins_21', 'trend_21', 'score_wins_30', 'trend_30', 'score_wins_31', 'trend_31',
                        'score_wins_32', 'trend_32', 'score_wins_40', 'trend_40', 'score_wins_41', 'trend_41', 'score_wins_42', 'trend_42', 'score_level_00', 'trend_00', 'score_level_11', 'trend_11', 'score_level_22', 'trend_22',
                        'score_level_33', 'trend_33', 'score_negative_01', 'trend_01', 'score_negative_02', 'trend_02', 'score_negative_12', 'trend_12', 'score_negative_03', 'trend_03', 'score_negative_13', 'trend_13',
                        'score_negative_23', 'trend_23', 'score_negative_04', 'trend_04', 'score_negative_14', 'trend_14', 'score_negative_24', 'trend_24', 'score_wins_90', 'trend_90', 'score_level_99', 'trend_99',
                        'score_negative_09', 'trend_09', 'create_time', 'modify_time', 'update_time'];
                    $lv['odds5005'] = array_combine($updateField, $lv['odds5005']);
                }
                $orderData['bet_contents'][] = $lv;
            }
        }
        $shareOrder = LotteryOrder::find()->select(['lottery_order.create_time', 'bet_money', 'lottery_order.status', 'u.user_name', 'u.user_pic'])
                ->leftJoin('user u', 'u.user_id = lottery_order.user_id')
                ->where(['source' => 5, 'source_id' => $orderData['order_share_id']])
                ->andWhere(['!=', 'lottery_order.status', 1])
                ->asArray()
                ->all();
        $outNums = 0;
        if (!empty($shareOrder)) {
            $orderData['with_nums'] = count($shareOrder);
            foreach ($shareOrder as $sv) {
                if (in_array($sv['status'], [3, 4, 5])) {
                    $outNums += floatval($sv['bet_money']);
                }
            }
            $orderData['with_out'] = $outNums;
            $orderData['with_list'] = $shareOrder;
        } else {
            $orderData['with_nums'] = 0;
            $orderData['with_out'] = $outNums;
            $orderData['with_list'] = [];
        }
        $orderData['service_time'] = time();
        return $orderData;
    }

    public function preValid($shareId, $storeId, $lotteryCode) {
        $shareData = OrderShare::find()->select(['order_share_id'])->where(['order_share_id' => $shareId])->asArray()->one();
        if (empty($shareData)) {
            return ['code' => 109, 'msg' => '分享单不存在'];
        }
        $store = Store::find()->select(['store_id', 'store_code', 'sale_lottery', 'business_status'])->where(['user_id' => $storeId])->asArray()->one();
        if ($store['business_status'] != 1) {
            return ['code' => 2, 'msg' => '门店已暂停营业！！'];
        }
        $saleLotteryArr = explode(',', $store['sale_lottery']);
        if (in_array(3000, $saleLotteryArr)) {
            array_push($saleLotteryArr, '3006', '3007', '3008', '3009', '3010', '3011');
        }
        if (in_array(3100, $saleLotteryArr)) {
            array_push($saleLotteryArr, '3001', '3002', '3003', '3004', '3005');
        }
        if (!in_array($lotteryCode, $saleLotteryArr)) {
            return ['code' => 488, 'msg' => '你所购买的彩种，该门店不可接单！'];
        }
        return ['code' => 600, 'msg' => '成功', 'data' => $store['store_code']];
    }

}

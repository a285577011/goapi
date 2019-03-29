<?php

namespace app\modules\orders\helpers;

use app\modules\common\models\ScheduleResult;
use app\modules\common\models\BettingDetail;
use app\modules\common\services\SyncApiRequestService;
use app\modules\common\helpers\Winning;
use app\modules\competing\models\DealDetail;

class DealOrder {

    /**
     * 说明: 
     * @author  kevi
     * @date 2017年11月7日 上午10:59:59
     * @param
     * @return 
     */
    public static function dealDelayScheduleOrder($scheduleMid, $code) {
        if ($code == 3000) {
            $delaySchedule = ScheduleResult::find()->where(['schedule_mid' => $scheduleMid, 'status' => 3])->one();
            $type = 2;
            $codeArr = ['3006', '3007', '3008', '3009', '3010', '3011'];
        } elseif ($code == 3100) {
            $delaySchedule = LanScheduleResult::find()->where(['schedule_mid' => $scheduleMid, 'result_status' => 3])->one();
            $type = 4;
            $codeArr = ['3001', '3002', '3003', '3004', '3005'];
        }

        if (empty($delaySchedule)) {
            return ['code' => 109, 'msg' => 'succ', 'data' => $scheduleMid . ':无该场次或者该场次赛果并非推迟状态，请检查赛果表！'];
        }
        
        
        $likeStr = '_' . $scheduleMid . '_';
        $field = ['betting_detail_id', 'betting_detail.lottery_order_id', 'betting_detail.lottery_id', 'betting_detail.bet_val', 'o.odds'];
        $betDetial = BettingDetail::find()->select($field)
                ->innerJoin('lottery_order o', 'o.lottery_order_id = betting_detail.lottery_order_id')
                ->where(['betting_detail.status' => 3, 'o.lottery_type' => $type])
                ->andWhere(['like', 'betting_detail.bet_val', $scheduleMid])
                ->andWhere(['not like', 'betting_detail.deal_odds_sche', $likeStr])
                ->limit(100)
                ->asArray()
                ->all();
        $updetail = '';
        $mids = [];
        $res = [];
        $upOdds = [];
        foreach ($betDetial as $val) {
            if ($val["lottery_id"] != '3011' && $val['lottery_id'] != '3005') {
                $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
            } else {
                $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
            }
            $betNums = explode('|', $val['bet_val']);
            foreach ($betNums as $ball) {
                preg_match($pattern, $ball, $res);
                if (!in_array($res[1], $mids)) {
                    $mids[] = $res[1];
                }
            }
        }
        if ($code == 3000) {
            $midStatus = ScheduleResult::find()->select(['schedule_mid', 'status'])->where(['in', 'schedule_mid', $mids])->indexBy('schedule_mid')->asArray()->all();
        } elseif ($code == 3100) {
            $midStatus = LanScheduleResult::find()->select(['schedule_mid', 'result_status status'])->where(['in', 'schedule_mid', $mids])->indexBy('schedule_mid')->asArray()->all();
        }

        foreach ($betDetial as $val) {
            if ($val["lottery_id"] != '3011' && $val['lottery_id'] != '3005') {
                $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
            } else {
                $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
            }
            $odds = json_decode($val['odds'], true);
            $betNums = explode('|', $val['bet_val']);
            $lotteryCode = $val['lottery_id'];
            $result = [];
            $r = [];
            $oddsAmount = 1;
            foreach ($betNums as $ball) {
                preg_match($pattern, $ball, $result);
                if ($lotteryCode != 3011 && $lotteryCode != 3005) {
                    if ($result[1] == $scheduleMid) {
                        $oddsAmount *= 1;
                    } else {
                        if ($midStatus[$result[1]]['status'] == 3) {
                            $oddsAmount *= 1;
                        } else {
                            $oddsAmount *= $odds[$lotteryCode][$result[1]][$result[2]];
                        }
                    }
                } else {
                    $str = explode('*', $result[2]);
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str[1], $r);
                    if ($result[1] == $scheduleMid) {
                        $oddsAmount *= 1;
                    } else {
                        if ($midStatus[$result[1]]['status'] == 3) {
                            $oddsAmount *= 1;
                        } else {
                            $oddsAmount *= $odds[$r[1]][$result[1]][$r[2]];
                        }
                    }
                }
            }
            $oddStr = $scheduleMid . '_';
            $updetail .= "update betting_detail set odds = {$oddsAmount}, deal_odds_sche = concat(deal_odds_sche, '{$oddStr}') where betting_detail_id = {$val['betting_detail_id']} and lottery_order_id = {$val['lottery_order_id']} and deal_odds_sche not like '%{$likeStr}%';";
            $upOdds[] = ['betting_detail_id' => $val['betting_detail_id'], 'odds' => $oddsAmount];
//            BettingDetail::addQueUpdate(['betting_detail_id' => $val['betting_detail_id'], 'lottery_order_id' => $val['lottery_order_id']]);
        }
        $db = \Yii::$app->db;
        $ret = $db->createCommand($updetail)->execute();
        if ($ret === false) {
            return ['code' => 109, 'msg' => 'error', 'data' => $ret];
        }
        self::updateDealOdds($scheduleMid, $codeArr, $code);
        $oddsData = ['periods' => $scheduleMid, 'betting_detail' => $upOdds];
        SyncApiRequestService::updateCancelOdds($oddsData);
        $count = BettingDetail::find() ->where(['status' => 3])->andWhere(['like', 'bet_val', $scheduleMid])->andWhere(['not like', 'deal_odds_sche', $likeStr])->count();
        if($count == 0) {
            $winning = new Winning();
            $e = $winning->cancelLevel($scheduleMid, $code);
            $key = 'cancel_schedule';
            $key1 = $code . '_' . $scheduleMid;
            $redis = \Yii::$app->redis;
            $redis->srem($key, $key1);
        }
        return ['code' => 2, 'msg' => 'succ', 'data' => $ret];
    }

    /**
     * 取消赛程对奖
     * @param type $scheduleMid 赛程MID
     * @return type
     * @throws \app\modules\orders\helpers\Exception
     */
//    public function dealDelayAward($scheduleMid) {
//        $sql = "call CheckZQLQ_Cancel('{$scheduleMid}'); ";
//        $connection = \Yii::$app->db;
//        try {
//            $ret = $connection->createCommand($sql)->execute(1);
//            $remark = "足球 - 兑奖完成!成功执行:{$ret['Update_Row_Count']}条";
//            $data = [
//                'lottery_code' => 4001,
//                'periods' => $scheduleMid,
//                'open_num' => '',
//                'remark' => $remark,
//            ];
//            CheckLotteryResultRecord::tosave($data);
//        } catch (Exception $e) {
//            throw $e;
//        }
//        return $ret;
//    }
    
    /**
     * 修改处理明细赔率
     * @param type $mid
     * @param type $codeArr
     * @param type $code
     * @return type
     */
    public static function updateDealOdds($mid, $codeArr, $code) {
        $likeStr = '_' . $mid . '_';
        $field = ['deal_detail_id', 'deal_detail.deal_order_id', 'deal_detail.lottery_code', 'deal_detail.bet_val', 'o.odds'];
        $betDetial = DealDetail::find()->select($field)
                ->innerJoin('deal_order o', 'o.deal_order_id = deal_detail.deal_order_id')
                ->where(['deal_detail.status' => 3])
                ->andWhere(['in', 'deal_detail.lottery_code', $codeArr])
                ->andWhere(['like', 'deal_detail.bet_val', $mid])
                ->andWhere(['not like', 'deal_detail.deal_odds_sche', $likeStr])
                ->limit(100)
                ->asArray()
                ->all();
        $updetail = '';
        $mids = [];
        $res = [];
        foreach ($betDetial as $val) {
            if ($val["lottery_code"] != '3011' && $val['lottery_code'] != '3005') {
                $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
            } else {
                $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
            }
            $betNums = explode('|', $val['bet_val']);
            foreach ($betNums as $ball) {
                preg_match($pattern, $ball, $res);
                if (!in_array($res[1], $mids)) {
                    $mids[] = $res[1];
                }
            }
        }
        if ($code == 3000) {
            $midStatus = ScheduleResult::find()->select(['schedule_mid', 'status'])->where(['in', 'schedule_mid', $mids])->indexBy('schedule_mid')->asArray()->all();
        } elseif ($code == 3100) {
            $midStatus = LanScheduleResult::find()->select(['schedule_mid', 'result_status status'])->where(['in', 'schedule_mid', $mids])->indexBy('schedule_mid')->asArray()->all();
        }

        foreach ($betDetial as $val) {
            if ($val["lottery_code"] != '3011' && $val['lottery_code'] != '3005') {
                $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
            } else {
                $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
            }
            $odds = json_decode($val['odds'], true);
            $betNums = explode('|', $val['bet_val']);
            $lotteryCode = $val['lottery_code'];
            $result = [];
            $r = [];
            $oddsAmount = 1;
            foreach ($betNums as $ball) {
                preg_match($pattern, $ball, $result);
                if ($lotteryCode != 3011 && $lotteryCode != 3005) {
                    if ($result[1] == $mid) {
                        $oddsAmount *= 1;
                    } else {
                        if ($midStatus[$result[1]]['status'] == 3) {
                            $oddsAmount *= 1;
                        } else {
                            $oddsAmount *= $odds[$lotteryCode][$result[1]][$result[2]];
                        }
                    }
                } else {
                    $str = explode('*', $result[2]);
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str[1], $r);
                    if ($result[1] == $mid) {
                        $oddsAmount *= 1;
                    } else {
                        if ($midStatus[$result[1]]['status'] == 3) {
                            $oddsAmount *= 1;
                        } else {
                            $oddsAmount *= $odds[$r[1]][$result[1]][$r[2]];
                        }
                    }
                }
            }
            $oddStr = $mid . '_';
            $updetail .= "update deal_detail set odds = {$oddsAmount}, deal_odds_sche = concat(deal_odds_sche, '{$oddStr}') where deal_detail_id = {$val['deal_detail_id']} and deal_order_id = {$val['deal_order_id']} and deal_odds_sche not like '%{$likeStr}%';";

        }
        $db = \Yii::$app->db;
        $ret = $db->createCommand($updetail)->execute();
        if ($ret === false) {
            return ['code' => 109, 'msg' => 'error', 'data' => $ret];
        }
        return ['code' => 600, 'msg' => 'true', 'data' => $ret];
    }

}

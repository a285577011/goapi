<?php

namespace app\modules\tools\helpers;

use app\modules\common\models\LotteryOrder;
use app\modules\common\models\BettingDetail;
use app\modules\common\models\Schedule;
use app\modules\competing\models\LanSchedule;
use app\modules\orders\helpers\AutoConsts;
use app\modules\orders\models\AutoOutOrder;
use app\modules\competing\helpers\CompetConst;
use app\modules\common\helpers\Constants;

class UpdateOdds {

    public static function outUpdateOdds($orderCode) {
        $oddsField = AutoConsts::AUTO_ODDS_FIELD;
        $oddsPlay = AutoConsts::AUTO_ODDS_PLAY;
        $autoLottery = AutoConsts::AUTO_PLAY;
        $bask = CompetConst::MADE_BASKETBALL_LOTTERY;
        $foot = Constants::MADE_FOOTBALL_LOTTERY;
        $playAuto = array_flip($autoLottery);

        $field = ['auto_out_order.order_code', 'o.lottery_id', 'zmf.ret_async_data', 'auto_out_order.bet_val', 'auto_out_order.lottery_code as auto_lottery_code', 'o.lottery_type'];
        $data = AutoOutOrder::find()->select($field)
                ->innerJoin('zmf_order zmf', 'zmf.order_code = auto_out_order.out_order_code')
                ->innerJoin('lottery_order o', 'o.lottery_order_code = auto_out_order.order_code')
                ->where(['auto_out_order.order_code' => $orderCode, 'auto_out_order.status' => 4])
                ->asArray()
                ->all();
        if (empty($data)) {
            return false;
        }

        $outOdds = [];
        $scheOdds = [];
        $scheArr = [];
        foreach ($data as $val) {
            $item = json_decode($val['ret_async_data'], true);
            $outOdds = $item['records']['record']['info']['item'];
            $autoLotteryCode = $val['auto_lottery_code'];
            $betArr = explode('^', rtrim($val['bet_val'], '^'));
            foreach ($betArr as $bet) {
                $arr = explode('|', $bet);
                $scheStr = $arr[0] . $arr[2];
                if ($autoLotteryCode != 'FT005' && $autoLotteryCode != 'BSK005') {
                    $str = $arr[0] . '_' . $arr[1] . '_' . $arr[2];
                    $lottery = $playAuto[$autoLotteryCode];
                    $autoCode = $autoLotteryCode;
                } else {
                    $str = $arr[0] . '_' . $arr[1] . '_' . $arr[2] . '_' . $arr[3];
                    $lottery = $playAuto[$arr[3]];
                    $autoCode = $arr[3];
                }
                if (isset($outOdds['id'])) {
                    if ($outOdds['id'] == $str) {
                        $oddsArr = $outOdds[$oddsField[$autoCode]];
                        $play = $oddsPlay[$autoCode];
                        $playOdds = array_flip($play);
                        foreach ($oddsArr as $key => $odd) {
                            if (array_key_exists($playOdds[$key], $scheOdds[$scheStr][$lottery])) {
                                if ($odd != $scheOdds[$scheStr][$lottery][$playOdds[$key]]) {
                                    $falg = 2;
                                } else {
                                    $falg = 1;
                                }
                            }
                            $scheOdds[$scheStr][$lottery][$playOdds[$key]] = $odd;
                        }
                    }
                } else {
                    foreach ($outOdds as $v) {
                        if ($v['id'] == $str) {
                            $oddsArr = $v[$oddsField[$autoCode]];
                            $play = $oddsPlay[$autoCode];
                            $playOdds = array_flip($play);
                            foreach ($oddsArr as $key => $odd) {
                                if (array_key_exists($playOdds[$key], $scheOdds[$scheStr][$lottery])) {
                                    if ($odd != $scheOdds[$scheStr][$lottery][$playOdds[$key]]) {
                                        $falg = 2;
                                    } else {
                                        $falg = 1;
                                    }
                                }
                                $scheOdds[$scheStr][$lottery][$playOdds[$key]] = $odd;
                            }
                        } else {
                            continue;
                        }
                    }
                }
                if (!in_array($scheStr, $scheArr)) {
                    $scheArr[] = $scheStr;
                }
            }
            $orderLotteryCode = $val['lottery_id'];
        }
        if (in_array($orderLotteryCode, $foot)) {
            $scheData = Schedule::find()->select(['schedule_mid', 'open_mid'])->where(['in', 'open_mid', $scheArr])->indexBy('schedule_mid')->asArray()->all();
        } elseif (in_array($orderLotteryCode, $bask)) {
            $scheData = LanSchedule::find()->select(['schedule_mid', 'open_mid'])->where(['in', 'open_mid', $scheArr])->indexBy('schedule_mid')->asArray()->all();
        }
        $order = LotteryOrder::findOne(['lottery_order_code' => $orderCode]);
        $lotteryCode = $order->lottery_id;
        $odds = [];
        $mids = [];
        if ($lotteryCode != 3011 && $lotteryCode != 3005) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $betNums = explode("|", trim($order->bet_val, '^'));
        $result = [];
        $r = [];
        foreach ($betNums as $v) {
            preg_match($pattern, $v, $result);
            $scheInfo = $scheData[$result[1]];
            if ($lotteryCode != '3011' && $lotteryCode != '3005') {
                $mids[] = $result[1];
                $arr = explode(",", $result[2]);
                if (!array_key_exists($lotteryCode, $odds)) {
                    $odds[$lotteryCode] = [];
                }
                $odds[$lotteryCode][$result[1]] = $scheOdds[$scheInfo['open_mid']][$lotteryCode];
            } else {
                $resultBalls = trim($result[2], "*");
                $resultBalls = explode("*", $resultBalls);
                $thisCount = 0;
                foreach ($resultBalls as $str) {
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                    $mids[] = $result[1];
                    $arr = explode(",", $r[2]);
                    $thisCount += count($arr);
                    if (!isset($odds[$r[1]])) {
                        $odds[$r[1]] = [];
                    }
                    $odds[$r[1]][$result[1]] = $scheOdds[$scheInfo['open_mid']][$r[1]];
                }
            }
        }
        $order->odds = str_replace('+', '', json_encode($odds, JSON_FORCE_OBJECT));
        $order->saveData();
        $updateDetail = self::updateBettingOdds($orderCode, $lotteryCode, $odds);
        if($updateDetail['code'] != 600) {
            return ['code' => 109, 'msg' => $updateDetail['msg']];
        }
        return ['code' => 600, 'msg' => '赔率修改成功'];
    }

    public static function updateBettingOdds($orderCode, $lotteryCode, $odds) {
        if ($lotteryCode != 3011 && $lotteryCode != 3005) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $detail = BettingDetail::find()->select(['betting_detail_id', 'lottery_order_id', 'bet_val'])->where(['lottery_order_code' => $orderCode])->asArray()->all();
        $updetail = '';
        foreach ($detail as $val) {
            $betArr = explode('|', $val['bet_val']);
            $oddsAmount = 1;
            $fenData = [];
            foreach ($betArr as $it) {
                preg_match($pattern, $it, $res);
                if ($lotteryCode != 3011 && $lotteryCode != 3005) {
                    $oddsAmount *= $odds[$lotteryCode][$res[1]][$res[2]];
                    if ($lotteryCode == 3002) {
                        $fenData[$res[1]] = str_replace('+', '', $odds[$lotteryCode][$res[1]]['rf_nums']);
                    } elseif ($lotteryCode == 3004) {
                        $fenData[$res[1]] = $odds[$lotteryCode][$res[1]]['fen_cutoff'];
                    }
                } else {
                    $str = explode('*', $res[2]);
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str[1], $r);
                    $oddsAmount *= $odds[$r[1]][$res[1]][$r[2]];
                    if ($r[1] == 3002) {
                        $fenData[$res[1]] = str_replace('+', '', $odds[$r[1]][$res[1]]['rf_nums']);
                    } elseif ($r[1] == 3004) {
                        $fenData[$res[1]] = $odds[$r[1]][$res[1]]['fen_cutoff'];
                    }
                }
            }
            $updetail .= "update betting_detail set odds = {$oddsAmount}, fen_json = '" . json_encode($fenData) . "' where betting_detail_id = {$val['betting_detail_id']} and lottery_order_id = {$val['lottery_order_id']};";
            BettingDetail::addQueSync(BettingDetail::$syncUpdateType, 'odds,fen_json', ['betting_detail_id' => $val['betting_detail_id'], 'lottery_order_id' => $val['lottery_order_id']]);
        }
        $db = \Yii::$app->db;
        $updateId = $db->createCommand($updetail)->execute();
        if ($updateId == false) {
            return ['code' => 109, 'msg' => '详情表修改失败'];
        }
        return ['code' => 600, 'msg' => '成功'];
    }

    public static function outNmUpdateOdds($orderCode) {
        $bask = CompetConst::MADE_BASKETBALL_LOTTERY;
        $foot = Constants::MADE_FOOTBALL_LOTTERY;

        $field = ['auto_out_order.order_code', 'o.lottery_id', 'zmf.ret_async_data', 'auto_out_order.bet_val', 'auto_out_order.lottery_code as auto_lottery_code', 'o.lottery_type'];
        $data = AutoOutOrder::find()->select($field)
                ->innerJoin('zmf_order zmf', 'zmf.order_code = auto_out_order.out_order_code')
                ->innerJoin('lottery_order o', 'o.lottery_order_code = auto_out_order.order_code')
                ->where(['auto_out_order.order_code' => $orderCode, 'auto_out_order.status' => 4])
                ->asArray()
                ->all();
        if (empty($data)) {
            return false;
        }

        $mids = [];
        $result = [];
        $oddsArr = [];
        foreach ($data as $val) {
            $item = json_decode($val['ret_async_data'], true);
            if ($val['auto_lottery_code'] != '3011' && $val['auto_lottery_code'] != '3005') {
                $pattern = '/^([0-9]+)\((([0-9]|.[0-9]+\.?[0-9])+)\)$/';
            } else {
                $pattern = '/^([0-9]+)(\*([0-9]+)\((([0-9]|.[0-9]+\.?[0-9])+)\)+)$/';
            }
            $oddStr = $item['ticketcontent']['sp'];
            $spArr = explode('|', $oddStr);
            foreach ($spArr as $sp) {
                preg_match($pattern, $sp, $result);
                $mids[] = $result[1];
                if ($val['auto_lottery_code'] != '3011' && $val['auto_lottery_code'] != '3005') {
                    $arr = explode(",", $result[2]);
                    foreach ($arr as $a) {
                        if (!array_key_exists($val['auto_lottery_code'], $oddsArr)) {
                            $oddsArr[$val['auto_lottery_code']] = [];
                        }
                        if ($val['auto_lottery_code'] == '3002') {
                            $rfNums = explode(':', $a);
                            $a = $rfNums[1];
                            $oddsArr[$val['auto_lottery_code']][$result[1]]['rf_nums'] = $rfNums[0];
                        }
                        if ($val['auto_lottery_code'] == '3004') {
                            $cutFen = explode(':', $a);
                            $a = $cutFen[1];
                            $oddsArr[$val['auto_lottery_code']][$result[1]]['fen_cutoff'] = $cutFen[0];
                        }
                        $vArr = explode('_', $a);
                        $oddsArr[$val['auto_lottery_code']][$result[1]][$vArr[0]] = $vArr[1];
                    }
                } else {
                    $mids[] = $result[1];
                    $arr = explode(",", $result[4]);
                    foreach ($arr as $a) {
                        if (!array_key_exists($result[3], $oddsArr)) {
                            $oddsArr[$val['auto_lottery_code']] = [];
                        }
                        if ($result[3] == '3002') {
                            $rfNums = explode(':', $a);
                            $a = $rfNums[1];
                            $oddsArr[$result[3]][$result[1]]['rf_nums'] = $rfNums[0];
                        }
                        if ($result[3] == '3004') {
                            $cutFen = explode(':', $a);
                            $a = $cutFen[1];
                            $oddsArr[$result[3]][$result[1]]['fen_cutoff'] = $cutFen[0];
                        }
                        $vArr = explode('_', $a);
                        $oddsArr[$result[3]][$result[1]][$vArr[0]] = $vArr[1];
                    }
                }
            }
            $orderLotteryCode = $val['lottery_id'];
        }
        if (in_array($orderLotteryCode, $foot)) {
            $scheData = Schedule::find()->select(['schedule_mid', 'open_mid'])->where(['in', 'open_mid', $mids])->indexBy('schedule_mid')->asArray()->all();
        } elseif (in_array($orderLotteryCode, $bask)) {
            $scheData = LanSchedule::find()->select(['schedule_mid', 'open_mid'])->where(['in', 'open_mid', $mids])->indexBy('schedule_mid')->asArray()->all();
        }
        $odds = str_replace('+', '', json_encode($oddsArr));
        foreach ($scheData as $sche) {
            $odds = str_replace($sche['open_mid'], $sche['schedule_mid'], $odds);
        }
        $order = LotteryOrder::findOne(['lottery_order_code' => $orderCode]);
        $lotteryCode = $order->lottery_id;
        $order->odds = str_replace('+', '', json_encode($odds, JSON_FORCE_OBJECT));
        $order->saveData();
        $updateDetail = self::updateBettingOdds($orderCode, $lotteryCode, json_decode($odds, true));
        if($updateDetail['code'] != 600) {
            return ['code' => 109, 'msg' => $updateDetail['msg']];
        }
        return ['code' => 600, 'msg' => '赔率修改成功'];
    }

}

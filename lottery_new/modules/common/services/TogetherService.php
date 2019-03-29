<?php

namespace app\modules\common\services;

use Yii;
use app\modules\common\models\Programme;
use yii\db\Query;
use app\modules\common\models\Schedule;
use app\modules\common\helpers\Constants;
use app\modules\common\models\ProgrammeUser;
use app\modules\user\models\User;
use app\modules\common\models\OptionalSchedule;
use app\modules\competing\helpers\CompetConst;
use app\modules\competing\models\LanSchedule;
use app\modules\competing\services\BasketService;

class TogetherService {

    /**
     * 获取所以的方案列表
     * @auther GL zyl
     * @param type $pn
     * @param type $size
     * @param type $by
     * @param type $code
     * @return type
     */
    public static function getAllProgramme($pn, $size, $by, $code) {
        $status = Constants::PROGRAMME_STATUS;
        $where = [];
        $cwhere = [];
        $pwhere = [];
        if ($by == 'speed') {
            $orderBy = 'p.programme_speed desc, p.programme_id desc';
        } elseif ($by == 'grade') {
            $orderBy = 'el.level desc, p.programme_id desc';
        } elseif ($by == 'bet_money') {
            $orderBy = 'p.bet_money desc, p.programme_id desc';
        } elseif ($by == 'made_nums') {
            $orderBy = 'el.made_nums desc, p.programme_id desc';
        }
        $where['p.status'] = 2;
        $numsLottery = Constants::MADE_NUMS_LOTTERY;
        $footballLottery = Constants::MADE_FOOTBALL_LOTTERY;
        $basketballLotery = CompetConst::MADE_BASKETBALL_LOTTERY;
        if ($code != '') {
            if (in_array($code, $numsLottery)) {
                $cwhere = ['p.lottery_code' => $code];
                $pwhere['lottery_code'] = $code;
            } elseif ($code == 3000) {
                array_push($footballLottery, '3000');
                $cwhere = ['in', 'p.lottery_code', $footballLottery];
                $pwhere = ['in', 'lottery_code', $footballLottery];
            } elseif ($code = 3100) {
                array_push($footballLottery, '3000');
                $cwhere = ['in', 'p.lottery_code', $basketballLotery];
                $pwhere = ['in', 'lottery_code', $basketballLotery];
            }
        }
        $date = date('Y-m-d H:i:s');
        $total = Programme::find()->where(['status' => 2])->andWhere($pwhere)->andWhere([ '<', 'programme_speed', 100])->andWhere(['>=', 'programme_end_time', $date])->count();
        $pages = ceil($total / $size);
        $query = new Query;
        $data = $query->select(['p.programme_id', 'p.programme_code', 'p.programme_title', 'p.expert_no', 'p.lottery_code', 'p.lottery_name', 'p.bet_money', 'p.programme_all_number', 'p.programme_buy_number', 'p.programme_peoples',
                    'p.minimum_guarantee', 'p.programme_speed', 'p.programme_last_amount', 'p.cust_type', 'u.user_name', 'el.level_name', 'p.status', 'p.made_amount', 'el.level', 'p.programme_last_number', 'p.programme_univalent', 'p.made_nums'])
                ->from('programme as p')
                ->leftJoin('expert_level as el', 'el.cust_no = p.expert_no')
                ->leftJoin('user as u', 'u.cust_no = p.expert_no')
                ->andWhere(['>=', 'p.programme_end_time', $date])
                ->andWhere([ '<', 'programme_speed', 100])
                ->andWhere($where)
                ->andWhere($cwhere)
                ->limit($size)
                ->offset(($pn - 1) * $size)
                ->orderBy($orderBy)
                ->all();
        foreach ($data as &$val) {
            $buyAmount = $val['bet_money'] - $val['programme_last_amount'];
            if ($buyAmount == 0) {
                $buyAmount = 1;
            }
            $perOrderSpeed = floor(($val['minimum_guarantee'] * $val['programme_univalent'] / $val['bet_money'] ) * 100) . '%';
            $val['per_order_speed'] = $perOrderSpeed;
            $val['status_name'] = $status[$val['status']];
            if (in_array($val['lottery_code'], $footballLottery)) {
                $val['lottery_name'] = '竞彩足球';
            } elseif (in_array($val['lottery_code'], $basketballLotery)) {
                $val['lottery_name'] = '竞彩篮球';
            }
        }
        $preList = ['page' => $pn, 'size' => $size, 'pages' => $pages, 'total' => $total, 'data' => $data];
        return $preList;
    }

    /**
     * 获取认购或我的方案详情
     * @param type $pId
     * @param type $expertNo
     * @param type $isWith
     * @param type $programmeCode
     * @return type
     */
    public static function getSubscribeDetail($pId = '', $userId, $isWith = 0, $programmeCode = '') {
        $query = new Query;
        $date = date('Y-m-d H:i:s');
        $status = Constants::PROGRAMME_STATUS;
        $securityArr = Constants::PROGRAMME_SECURITY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $list = [];
        $where = [];
        $field = ['p.programme_id', 'o.lottery_order_id', 'p.programme_code', 'p.expert_no', 'p.cust_type', 'p.store_id', 'p.lottery_name', 'p.lottery_code', 'p.periods', 'p.bet_val', 'p.play_code', 'p.play_name', 'p.bet_double', 'p.is_bet_add',
            'p.bet_money', 'p.count', 'p.security', 'p.royalty_ratio', 'p.minimum_guarantee', 'p.programme_end_time', 'p.programme_reason', 'p.programme_all_number', 'p.programme_peoples', 'p.programme_speed', 'p.programme_last_amount',
            'p.status', 'p.win_amount', 'p.create_time', 'l.lottery_pic', 'p.bet_status', 's.store_name as ticke_name', 's.store_code as ticke_code', 'u.user_name', 'el.level_name', 'el.made_nums', 'el.win_nums', 'el.win_amount as all_win_amount',
            'el.level', 'p.programme_last_number', 'p.programme_univalent', 'p.made_nums', 'p.store_no', 'o.refuse_reason','p.user_id', 's.telephone phone_num', 'o.deal_status'];
        if ($pId != '') {
            $where['p.programme_id'] = $pId;
        }
        if ($programmeCode != '') {
            $where['p.programme_code'] = $programmeCode;
        }
//        $where['p.user_id'] = $userId;
        $data = $query->select($field)
                ->from('programme as p')
                ->leftJoin('lottery_order as o', 'o.source_id = p.programme_id and source = 4')
                ->leftJoin('lottery as l', 'l.lottery_code = p.lottery_code')
                ->leftJoin('store as s', 's.store_code = p.store_no and s.status = 1')
                ->leftJoin('user as u', 'u.cust_no = p.expert_no')
                ->leftJoin('expert_level as el', 'el.cust_no = p.expert_no')
                ->where($where)
                ->one();
        if (empty($data)) {
            return ['code' => 109, 'msg' => '该方案不存在'];
        }
        if ((in_array($data['lottery_code'], $football) || in_array($data['lottery_code'], $basketball)) && !empty($data['count'])) {
            if ($data['security'] == 1 || ($data['user_id'] == $userId) || in_array($data['status'], [4, 5, 6]) || ($data['security'] == 2 && $isWith == 1) || ($data['security'] == 3 && (strtotime($date) > strtotime($data['programme_end_time'])))) {
                $odds = [];
                if (in_array($data['status'], [3, 4, 5, 6])) {
                    $oddsArr = (new Query)->select(['odds'])->from('lottery_order')->where(['source_id' => $data['programme_id'], 'source' => 4])->one();
                    if (!empty($oddsArr)) {
                        $odds = json_decode($oddsArr['odds'], true);
                    }
                }
                $list['detail'] = self::getOdds($data['bet_val'], $odds, $data['lottery_code']);
            }
        } elseif (in_array($data['lottery_code'], [4001, 4002])) {
            if ($data['security'] == 1 || ($data['user_id'] == $userId) || in_array($data['status'], [4, 5, 6]) || ($data['security'] == 2 && $isWith == 1) || ($data['security'] == 3 && (strtotime($date) > strtotime($data['programme_end_time'])))) {
                $list['detail'] = self::getOptionalOrder($data['periods'], $data['bet_val']);
            }
        }
        $list['bet_val'] = $data['bet_val'];
        $list['play_code'] = $data['play_code'];
        $list['play_name'] = $data['play_name'];
        $list['bet_double'] = $data['bet_double'];
        $list['is_bet_add'] = $data['is_bet_add'];
        $list['bet_money'] = $data['bet_money'];
        $list['count'] = $data['count'];
        if ($data['security'] == 1 || ($data['user_id'] == $userId) || in_array($data['status'], [4, 5, 6]) || ($data['security'] == 2 && $isWith == 1) || ($data['security'] == 3 && (strtotime($date) > strtotime($data['programme_end_time'])))) {
            if (in_array($data['lottery_code'], ['1001', '1002', '1003', '2001', '2002', '2003', '2004'])) {
                $resultData = (new Query)->select(['lottery_time', 'lottery_numbers'])->from('lottery_record')->where(['lottery_code' => $data['lottery_code'], 'periods' => $data['periods']])->one();
                if (empty($resultData)) {
                    return ['code' => 109, 'msg' => '查询结果不存在，请稍后再试'];
                }
                $list['lottery_time'] = $resultData['lottery_time'];
                $list['lottery_result'] = $resultData['lottery_numbers'] == null ? '' : $resultData['lottery_numbers'];
            }
            $data['contents'] = $list;
        } else {
            $data['contents'] = null;
        }
        if (in_array($data['lottery_code'], ['1001', '1002', '1003', '2001', '2002', '2003', '2004'])) {
            $data['lottery_type'] = 1;
        } elseif (in_array($data['lottery_code'], $football)) {
            $data['lottery_type'] = 2;
            $playCodeArr = explode(',', $data['play_code']);
            if (in_array(1, $playCodeArr)) {
                if ($data['lottery_code'] == 3011) {
                    $data['lottery_name'] = '混合单关';
                } else {
                    $data['lottery_name'] .= '(单)';
                }
            }
        } elseif (in_array($data['lottery_code'], $basketball)) {
            $data['lottery_type'] = 4;
            $playCodeArr = explode(',', $data['play_code']);
            if (in_array(1, $playCodeArr)) {
                if ($data['lottery_code'] == 3005) {
                    $data['lottery_name'] = '混合单关';
                } else {
                    $data['lottery_name'] .= '(单)';
                }
            }
        } else {
            if ($data['lottery_code'] == 3000) {
                $data['lottery_type'] = 2;
            } else {
                $data['lottery_type'] = 3;
            }
        }
        if (empty($data['count'])) {
            $data['security_name'] = '方案待上传';
            $data['security'] = 0;
            $data['contents'] = null;
        } else {
            $data['security_name'] = $securityArr[$data['security']];
        }
        unset($data['bet_val'], $data['play_code'], $data['play_name'], $data['bet_double'], $data['is_bet_add'], $data['count']);

        $perOrderSpeed = floor(($data['minimum_guarantee'] * $data['programme_univalent'] / $data['bet_money'] ) * 100) . '%';
        $data['per_order_speed'] = $perOrderSpeed;
        $data['status_name'] = $status[$data['status']];

        $prize['win_amount'] = $data['win_amount'];
        $prize['commission'] = round($data['win_amount'] * ($data['royalty_ratio'] / 100), 2);
//        $data['all_win_amount'] = round(floatval($data['all_win_amount'] / 10000), 2);
        $data['prize'] = $prize;
        return ['code' => 600, 'msg' => '方案详情', 'data' => $data];
    }

    /**
     * 获取列表方案详情
     * @auther GL zyl
     * @param type $pId
     * @param type $expertNo
     * @param type $isWith
     * @return type
     */
    public static function getListDetail($pId = '', $userId, $isWith = 0, $programmeCode = '') {
        $query = new Query;
        $date = date('Y-m-d H:i:s');
        $status = Constants::PROGRAMME_STATUS;
        $securityArr = Constants::PROGRAMME_SECURITY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $list = [];
        $where = [];
        $field = ['p.programme_id', 'p.programme_code', 'p.expert_no', 'p.cust_type', 'p.store_id', 'p.lottery_name', 'p.lottery_code', 'p.periods', 'p.bet_val', 'p.play_code', 'p.play_name', 'p.bet_double', 'p.is_bet_add',
            'p.bet_money', 'p.count', 'p.security', 'p.royalty_ratio', 'p.minimum_guarantee', 'p.programme_end_time', 'p.programme_reason', 'p.programme_all_number', 'p.programme_peoples',
            'p.programme_speed', 'p.programme_last_amount', 'p.create_time', 'p.status', 'l.lottery_pic', 'p.bet_status', 's.store_name as ticke_name', 's.store_code as ticke_code', 'u.user_name', 'el.level_name',
            'el.made_nums', 'el.win_nums', 'el.win_amount as all_win_amount', 'el.level', 'p.programme_last_number', 'p.programme_univalent', 'p.made_nums', 'o.lottery_order_id', 'o.refuse_reason','p.user_id', 's.telephone phone_num', 'o.deal_status'];
        if ($pId != '') {
            $where['p.programme_id'] = $pId;
        }
        if ($programmeCode != '') {
            $where['p.programme_code'] = $programmeCode;
        }

        $data = $query->select($field)
                ->from('programme as p')
                ->leftJoin('lottery_order as o', 'o.source_id = p.programme_id and source = 4')
                ->leftJoin('lottery as l', 'l.lottery_code = p.lottery_code')
                ->leftJoin('expert_level as el', 'el.cust_no = p.expert_no')
                ->leftJoin('store as s', 's.store_code = p.store_no and s.status = 1')
                ->leftJoin('user as u', 'u.cust_no = p.expert_no')
                ->where($where)
                ->one();
        if (empty($data)) {
            return ['code' => 109, 'msg' => '该方案不存在'];
        }
        if ((in_array($data['lottery_code'], $football) || in_array($data['lottery_code'], $basketball)) && !empty($data['count'])) {
            if ($data['security'] == 1 || ($data['user_id'] == $userId) || in_array($data['status'], [4, 5, 6]) || ($data['security'] == 2 && $isWith == 1) || ($data['security'] == 3 && (strtotime($date) > strtotime($data['programme_end_time'])))) {
                $odds = [];
                if (in_array($data['status'], [3, 4, 5, 6])) {
                    $oddsArr = (new Query)->select(['odds'])->from('lottery_order')->where(['source_id' => $data['programme_id'], 'source' => 4])->one();
                    if (!empty($oddsArr)) {
                        $odds = json_decode($oddsArr['odds'], true);
                    }
                }
                $list['detail'] = self::getOdds($data['bet_val'], $odds, $data['lottery_code']);
            }
        } elseif (in_array($data['lottery_code'], [4001, 4002])) {
            if ($data['security'] == 1 || ($data['user_id'] == $userId) || in_array($data['status'], [4, 5, 6]) || ($data['security'] == 2 && $isWith == 1) || ($data['security'] == 3 && (strtotime($date) > strtotime($data['programme_end_time'])))) {
                $list['detail'] = self::getOptionalOrder($data['periods'], $data['bet_val']);
            }
        }
        $list['bet_val'] = $data['bet_val'];
        $list['play_code'] = $data['play_code'];
        $list['play_name'] = $data['play_name'];
        $list['bet_double'] = $data['bet_double'];
        $list['is_bet_add'] = $data['is_bet_add'];
        $list['bet_money'] = $data['bet_money'];
        $list['count'] = $data['count'];
        $list['lottery_time'] = null;
        $list['lottery_result'] = null;
        if ($data['security'] == 1 || ($data['user_id'] == $userId) || in_array($data['status'], [4, 5, 6]) || ($data['security'] == 2 && $isWith == 1) || ($data['security'] == 3 && (strtotime($date) > strtotime($data['programme_end_time'])))) {
            $data['contents'] = $list;
        } else {
            $data['contents'] = null;
        }
        if (in_array($data['lottery_code'], ['1001', '1002', '1003', '2001', '2002', '2003', '2004'])) {
            $data['lottery_type'] = 1;
        } elseif (in_array($data['lottery_code'], $football)) {
            $data['lottery_type'] = 2;
            $playCodeArr = explode(',', $data['play_code']);
            if (in_array(1, $playCodeArr)) {
                if ($data['lottery_code'] == 3011) {
                    $data['lottery_name'] = '混合单关';
                } else {
                    $data['lottery_name'] .= '(单)';
                }
            }
        } elseif (in_array($data['lottery_code'], $basketball)) {
            $data['lottery_type'] = 4;
            $playCodeArr = explode(',', $data['play_code']);
            if (in_array(1, $playCodeArr)) {
                if ($data['lottery_code'] == 3005) {
                    $data['lottery_name'] = '混合单关';
                } else {
                    $data['lottery_name'] .= '(单)';
                }
            }
        } else {
            if ($data['lottery_code'] == 3000) {
                $data['lottery_type'] = 2;
            } else {
                $data['lottery_type'] = 3;
            }
        }
        if (empty($data['count'])) {
            $data['security_name'] = '方案待上传';
            $data['security'] = 0;
            $data['contents'] = null;
        } else {
            $data['security_name'] = $securityArr[$data['security']];
        }
        unset($data['bet_val'], $data['play_code'], $data['play_name'], $data['bet_double'], $data['is_bet_add'], $data['count']);

        $perOrderSpeed = floor(($data['minimum_guarantee'] * $data['programme_univalent'] / $data['bet_money'] ) * 100) . '%';
        $data['per_order_speed'] = $perOrderSpeed;
        $data['status_name'] = $status[$data['status']];
        $data['prize'] = null;
        return ['code' => 600, 'msg' => '方案详情', 'data' => $data];
    }

    /**
     * 获取赔率
     * @auther GL zyl
     * @param type $bet
     * @param type $odds
     * @param type $code
     * @return type
     */
    public static function getOdds($bet, $odds, $code) {
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $betVal = trim($bet, "^");
        if ($code != '3011' && $code != '3005') {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $betNums = explode("|", $betVal);
        $mids = [];
        $bets = [];
        $result = [];
        foreach ($betNums as $key => $ball) {
            $bets[$key] = [];
            preg_match($pattern, $ball, $result);
            $n = 0;
            if ($code != '3011' && $code != '3005') {
                $bets[$key]["mid"] = $result[1];
                $mids[] = $result[1];
                $arr = explode(",", $result[2]);
                foreach ($arr as $v) {
                    $bets[$key]["lottery"][$n] = [];
                    $bets[$key]["lottery"][$n]["bet"] = $v;
                    $bets[$key]["lottery"][$n]["play"] = $code;
                    if (in_array($code, $football)) {
                        $bets[$key]["lottery"][$n]["odds"] = isset($odds[$code][$result[1]][$v]) ? $odds[$code][$result[1]][$v] : self::getTheOdds($code, $result[1], $v); //赔率
                    } elseif (in_array($code, $basketball)) {
                        $basketService = new BasketService();
                        $theOdds = $basketService->getOdds($code, $result[1], [$v]);
                        $bets[$key]["lottery"][$n]["odds"] = isset($odds[$code][$result[1]][$v]) ? $odds[$code][$result[1]][$v] : $theOdds[$v]; //赔率
                        if ($code == 3002) {
                            $bets[$key]["lottery"][$n]['rf_nums'] = isset($odds[$code][$result[1]]['rf_nums']) ? $odds[$code][$result[1]]['rf_nums'] : $theOdds['rf_nums'];
                        } elseif ($code == 3004) {
                            $bets[$key]["lottery"][$n]['fen_cutoff'] = isset($odds[$code][$result[1]]['fen_cutoff']) ? $odds[$code][$result[1]]['fen_cutoff'] : $theOdds['fen_cutoff'];
                        }
                    }
                    $n++;
                }
            } else {
                $mids[] = $result[1];
                $bets[$key]["mid"] = $result[1];
                $result[2] = trim($result[2], "*");
                $strs = explode("*", $result[2]);
                foreach ($strs as $str) {
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                    $arr = explode(",", $r[2]);
                    foreach ($arr as $v) {
                        $bets[$key]["lottery"][$n] = [];
                        $bets[$key]["lottery"][$n]["bet"] = $v;
                        $bets[$key]["lottery"][$n]["play"] = $r[1];
                        if (in_array($code, $football)) {
                            $bets[$key]["lottery"][$n]["odds"] = isset($odds[$r[1]][$result[1]][$v]) ? $odds[$r[1]][$result[1]][$v] : self::getTheOdds($r[1], $result[1], $v); //赔率
                        } elseif (in_array($code, $basketball)) {
                            $basketService = new BasketService();
                            $theOdds = $basketService->getOdds($r[1], $result[1], [$v]);
                            $bets[$key]["lottery"][$n]["odds"] = isset($odds[$r[1]][$result[1]][$v]) ? $odds[$r[1]][$result[1]][$v] : $theOdds[$v]; //赔率
                            if ($r[1] == 3002) {
                                $bets[$key]['rf_nums'] = isset($odds[$r[1]][$result[1]]['rf_nums']) ? $odds[$r[1]][$result[1]]['rf_nums'] : $theOdds['rf_nums'];
                            } elseif ($r[1] == 3004) {
                                $bets[$key]['fen_cutoff'] = isset($odds[$r[1]][$result[1]]['fen_cutoff']) ? $odds[$r[1]][$result[1]]['fen_cutoff'] : $theOdds['fen_cutoff'];
                            }
                        }
                        $n++;
                    }
                }
            }
        }
        if (in_array($code, $football)) {
            $field = ['schedule.schedule_code', 'schedule.schedule_mid', 'schedule.rq_nums', 'schedule.home_short_name', 'schedule.visit_short_name', 'sr.schedule_result_3006', 'sr.schedule_result_3007', 'sr.schedule_result_3008', 'sr.schedule_result_3009', 'sr.schedule_result_3010', 'sr.status'];
            $schedules = Schedule::find()
                    ->select($field)
                    ->join("left join", "schedule_result sr", "schedule.schedule_mid=sr.schedule_mid")
                    ->where(["in", "schedule.schedule_mid", $mids])
                    ->indexBy("schedule_mid")
                    ->asArray()
                    ->all();
        } elseif (in_array($code, $basketball)) {
            $field = ['lan_schedule.schedule_code', 'lan_schedule.schedule_mid', 'lan_schedule.visit_short_name', 'lan_schedule.home_short_name', 'sr.result_3001', 'sr.result_3003', 'sr.result_status', 'sr.schedule_zf', 'sr.result_qcbf', 'sr.schedule_fc'];
            $schedules = LanSchedule::find()->select($field)
                    ->join("left join", "lan_schedule_result sr", "lan_schedule.schedule_mid=sr.schedule_mid")
                    ->where(["in", "lan_schedule.schedule_mid", $mids])
                    ->indexBy("schedule_mid")
                    ->asArray()
                    ->all();
        }

        $plays = Constants::LOTTERY;
        $bf = Constants::COMPETING_3007_RESULT;
        foreach ($bets as &$val) {
            $schedule = $schedules[$val["mid"]];
            $val["schedule_code"] = $schedule["schedule_code"];
//            $val["home_team_name"] = $schedule["home_team_name"];
//            $val["visit_team_name"] = $schedule["visit_team_name"];
            $val['visit_short_name'] = $schedule['visit_short_name'];
            $val['home_short_name'] = $schedule['home_short_name'];
            if (in_array($code, $football)) {
                foreach ($val["lottery"] as $key => $v) {
                    $val["lottery"][$key]["play_name"] = $plays[$v["play"]];
                }
                $val["schedule_result_3006"] = ($schedule["status"] != 2) ? "" : $schedule["schedule_result_3006"];
                $val["schedule_result_bf"] = ($schedule["status"] != 2) ? "" : $schedule["schedule_result_3007"];
                if (!empty($val["schedule_result_bf"])) {
                    if (!isset($bf[$val["schedule_result_bf"]])) {
                        $val["schedule_result_bf"] = str_replace(" ", "", $val["schedule_result_bf"]);
                        if (isset($bf[$val["schedule_result_bf"]])) {
                            $val["schedule_result_3007"] = $bf[$val["schedule_result_bf"]];
                        } else {
                            $bfBalls = explode(":", $val["schedule_result_bf"]);
                            if ($bfBalls[0] > $bfBalls[1]) {
                                $val["schedule_result_3007"] = "90";
                            } else if ($bfBalls[0] == $bfBalls[1]) {
                                $val["schedule_result_3007"] = "99";
                            } else {
                                $val["schedule_result_3007"] = "09";
                            }
                        }
                    } else {
                        $val["schedule_result_3007"] = $bf[$val["schedule_result_bf"]];
                    }
                } else {
                    $val["schedule_result_3007"] = "";
                }
                $val["schedule_result_3008"] = ($schedule["status"] != 2) ? "" : $schedule["schedule_result_3008"];
                $val["schedule_result_3009"] = ($schedule["status"] != 2) ? "" : $schedule["schedule_result_3009"];
                $val["schedule_result_3010"] = ($schedule["status"] != 2) ? "" : $schedule["schedule_result_3010"];
                $val["rq_nums"] = $schedule["rq_nums"];
                $val['status'] = $schedule['status'];
            } elseif (in_array($code, $basketball)) {
                foreach ($val["lottery"] as $key => $v) {
                    $val["lottery"][$key]["play_name"] = $plays[$v["play"]];
                    if ($v['play'] == 3001) {
                        if ($schedule["result_status"] == 2 && !empty($schedule['result_qcbf'])) {
                            $bfArr = explode(':', $schedule['result_qcbf']);
                            if ((int) $bfArr[1] > (int) $bfArr[0]) {
                                $val['result_3001'] = '3';
                            } else {
                                $val['result_3001'] = '0';
                            }
                        }
                    } elseif ($v['play'] == 3002) {
                        if (array_key_exists('rf_nums', $val)) {
                            if (!empty($schedule['result_qcbf']) && $schedule['result_status'] == 2) {
                                $bfArr = explode(':', $schedule['result_qcbf']);
                                if ((int) $bfArr[1] + (float) $val['rf_nums'] > (int) $bfArr[0]) {
                                    $val['result_3002'] = '3';
                                } else {
                                    $val['result_3002'] = '0';
                                }
                            }
                        }
                    } elseif ($v['play'] == 3003) {
                        $val["result_sfc"] = ($schedule["result_status"] != 2) ? "" : $sfcArr[$schedule["result_3003"]];
                        $val["result_3003"] = ($schedule["result_status"] != 2) ? "" : $schedule["result_3003"];
                    } elseif ($v['play'] == 3004) {
                        if (array_key_exists('fen_cutoff', $val) && $schedule['result_status'] == 2) {
                            if ($val['fen_cutoff'] > $schedule['schedule_zf']) {
                                $val['result_3004'] = '2';
                            } else {
                                $val['result_3004'] = '1';
                            }
                        }
                        $val['schedule_zf'] = ($schedule["result_status"] != 2) ? "" : $schedule["schedule_zf"];
                    }
                    $val['schedule_fc'] = ($schedule["result_status"] != 2) ? "" : $schedule["schedule_fc"];
                    $val['result_qcbf'] = ($schedule["result_status"] != 2) ? "" : $schedule["result_qcbf"];
                    $val['status'] = $schedule['result_status'];
                }
            }
        }
        return $bets;
    }

    public static function getTheOdds($lotteryCode, $scheduleMid, $val) {
        switch ($lotteryCode) {
            case "3006":
                $arr = [
                    "let_wins" => "3",
                    "let_level" => "1",
                    "let_negative" => "0"
                ];
                $key = array_search($val, $arr);
                break;
            case "3007":
                $v1 = substr($val, 0, 1);
                $v2 = substr($val, 1, 1);
                if ($v1 > $v2) {
                    $key = "score_wins_" . $val;
                } else if ($v1 == $v2) {
                    $key = "score_level_" . $val;
                } else {
                    $key = "score_negative_" . $val;
                }
                break;
            case "3008":
                $key = "total_gold_" . $val;
                break;
            case "3009":
                $key = "bqc_" . $val;
                break;
            case "3010":
                $arr = [
                    "outcome_wins" => "3",
                    "outcome_level" => "1",
                    "outcome_negative" => "0"
                ];
                $key = array_search($val, $arr);
                break;
        }
        $data = (new Query())->select($key)->from("odds_" . $lotteryCode)->where(["schedule_mid" => $scheduleMid])->orderBy("updates_nums desc")->one();
        return $data[$key];
    }

    /**
     * 获取专家信息
     * @auther GL zyl
     * @param type $expertNo
     * @param type $custNo
     * @return type
     */
    public static function getExpertInfo($expertNo, $custNo) {
        $field = ['user.user_id', 'user.cust_no', 'user.user_name', 'user.user_pic', 'el.level_name', 'el.level', 'el.made_nums', 'el.win_nums', 'el.issue_nums', 'el.succ_issue_nums', 'el.win_amount', 'df.lottery_codes',
            'df.follow_type', 'df.follow_num', 'df.bet_nums', 'df.follow_percent', 'df.max_bet_money', 'df.stop_money'];
        $expertInfo = User::find()->select($field)
                ->leftJoin('expert_level as el', 'el.user_id = user.user_id')
                ->leftJoin('diy_follow as df', "df.expert_no = user.cust_no and df.cust_no = '" . $custNo . "'")
                ->where(['user.cust_no' => $expertNo])
                ->asArray()
                ->one();
        return $expertInfo;
    }

    /**
     * 获取跟单人员
     * @param type $pn 当前页
     * @param type $size 条数
     * @param type $programmeId 方案ID
     * @param type $programmeCode 方案编码
     * @return type
     */
    public static function getWithPeople($pn, $size, $programmeId = '', $programmeCode = '') {
        $where = [];
        if ($programmeId != '') {
            $where['programme_id'] = $programmeId;
        }
        if ($programmeCode != '') {
            $where['programme_code'] = $programmeCode;
        }
        $sta = ['not in', 'status', [1,8]];
        $total = ProgrammeUser::find()->where($where)->andWhere($sta)->count();
        $pages = ceil($total / $size);
        $offset = ($pn - 1) * $size;
        $status = Constants::PROGRAMME_STATUS;
        $withPeople = ProgrammeUser::find()->select(['user_name', 'bet_money', 'buy_number', 'win_amount', 'create_time', 'status'])->where($where)->andWhere($sta)->limit($size)->offset($offset)->orderBy('buy_number desc, create_time')->asArray()->all();
        foreach ($withPeople as &$val) {
            $cSub = mb_substr($val['user_name'], 3, strlen($val['user_name']) - 6);
            $cLen = strlen($cSub);
            $val['user_name'] = str_replace($cSub, '*', $val['user_name'], $cLen);
            $val['status_name'] = $status[$val['status']];
        }
        $data = ['page_num' => $pn, 'data' => $withPeople, 'size' => $size, 'pages' => $pages, 'total' => $total];
        return $data;
    }

    /**
     * 任选订单详情
     * @auther GL zyl
     * @param type $periods
     * @param type $bet
     * @return type
     */
    public static function getOptionalOrder($periods, $bet) {
        $scheduleData = OptionalSchedule::find()->select(['sorting_code', 'league_name', 'schedule_mid', 'start_time', 'home_short_name', 'visit_short_name', 'schedule_result'])
                ->where(['periods' => $periods])
                ->asArray()
                ->all();
        $betval = explode(',', trim($bet, '^'));
        foreach ($scheduleData as $val) {
            $data[] = ['sid' => $val['sorting_code'], 'home_team' => $val['home_short_name'], 'visit_team' => $val['visit_short_name'], 'bet_val' => $betval[$val['sorting_code'] - 1], 'result' => $val['schedule_result']];
        }
        return $data;
    }

}

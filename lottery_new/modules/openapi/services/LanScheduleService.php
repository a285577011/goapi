<?php

namespace app\modules\openapi\services;

use app\modules\competing\models\LanSchedule;
use app\modules\competing\models\LanScheduleResult;
use app\modules\competing\models\LanScheduleLive;
use app\modules\common\models\Team;
use app\modules\common\models\League;
use app\modules\competing\models\LanTeamRank;
use app\modules\competing\models\LanRangfenOdds;
use app\modules\competing\models\LanEuropeOdds;
use app\modules\competing\models\LanDaxiaoOdds;

class LanScheduleService {

    public function getSchedule($status = '', $date = '') {
        $where = [];
        $sWhere = [];
        $field = ['lan_schedule.open_mid', 'lan_schedule.start_time', 'l.league_id', 'l.league_short_name', 'l.league_long_name', 'ht.team_long_name home_team_name', 'vt.team_long_name visit_team_name',
            'htr.team_position home_team_position', 'htr.team_rank home_team_rank', 'vtr.team_position visit_team_position', 'vtr.team_rank visit_team_rank', 'lr.result_status', 'lr.guest_one',
            'lr.guest_two', 'lr.guest_three', 'lr.guest_four', 'lr.guest_add_one', 'lr.guest_add_two', 'lr.guest_add_three', 'lr.guest_add_four', 'lr.result_qcbf', 'lr.match_time'];
        if ($status == 0) {
            $where['lr.result_status'] = 0;
        } elseif ($status == 1) {
            $where['lr.result_status'] = 1;
        } elseif ($status == '') {
            $where['lan_schedule.schedule_date'] = $date;
            $sWhere = ['!=', 'lr.result_status', 5];
        }
        $schedules = LanSchedule::find()->select($field)
                ->innerJoin('lan_schedule_result lr', 'lr.schedule_mid = lan_schedule.schedule_mid')
                ->leftJoin('league l', 'l.league_code = lan_schedule.league_id and l.league_type = 2')
                ->leftJoin('team ht', 'ht.team_code = lan_schedule.home_team_id and ht.team_type = 2')
                ->leftJoin('team vt', 'vt.team_code = lan_schedule.visit_team_id and vt.team_type = 2')
                ->leftJoin('lan_team_rank htr', 'htr.team_code = lan_schedule.home_team_id')
                ->leftJoin('lan_team_rank vtr', 'htr.team_code = lan_schedule.visit_team_id')
                ->where($where)
                ->andWhere($sWhere)
                ->asArray()
                ->all();
        $data = [];
        if (empty($schedules)) {
            return $data;
//            return ['code' => 109, 'msg' => '暂无数据'];
        }
        foreach ($schedules as $val) {
            $schedule['schedule_id'] = $val['open_mid'];
            $schedule['start_time'] = $val['start_time'];
            $schedule['status'] = $val['result_status'];
            if ($val['home_team_position'] == 1) {
                $schedule['rank']['home'] = '东部' . $val['home_team_rank'];
            } elseif ($val['home_team_position'] == 2) {
                $schedule['rank']['home'] = '西部' . $val['home_team_rank'];
            } else {
                $schedule['rank']['home'] = '其他' . $val['home_team_rank'];
            }
            if ($val['visit_team_position'] == 1) {
                $schedule['rank']['visit'] = '东部' . $val['visit_team_rank'];
            } elseif ($val['visit_team_position'] == 2) {
                $schedule['rank']['visit'] = '西部' . $val['visit_team_rank'];
            } else {
                $schedule['rank']['visit'] = '其他' . $val['visit_team_rank'];
            }
            $team['home_team_name'] = $val['home_team_name'];
            $team['visit_team_name'] = $val['visit_team_name'];
            $league['league_name'] = $val['league_long_name'];
            $league['league_short_name'] = $val['league_short_name'];
            $league['league_id'] = $val['league_id'];
            if (!empty($date) || $status == 1) {
                if (in_array($val['result_status'], [1, 2, 6])) {
                    if (!empty($val['guest_one'])) {
                        $oneArr = explode(':', $val['guest_one']);
                        $schedule['home_guest'][] = $oneArr[1];
                        $schedule['visit_guest'][] = $oneArr[0];
                    }
                    if (!empty($val['guest_two'])) {
                        $twoArr = explode(':', $val['guest_two']);
                        if ($twoArr[0] != 0 && $twoArr[1] != 0) {
                            $schedule['home_guest'][] = $twoArr[1];
                            $schedule['visit_guest'][] = $twoArr[0];
                        }
                    }
                    if (!empty($val['guest_three'])) {
                        $threeArr = explode(':', $val['guest_three']);
                        if ($threeArr[0] != 0 && $threeArr[1] != 0) {
                            $schedule['home_guest'][] = $threeArr[1];
                            $schedule['visit_guest'][] = $threeArr[0];
                        }
                    }
                    if (!empty($val['guest_four'])) {
                        $fourArr = explode(':', $val['guest_four']);
                        if ($fourArr[0] != 0 && $fourArr[1] != 0) {
                            $schedule['home_guest'][] = $fourArr[1];
                            $schedule['visit_guest'][] = $fourArr[0];
                        }
                    }
                    if (!empty($val['guest_add_one'])) {
                        $addArr1 = explode(':', $val['guest_add_one']);
                        if ($addArr1[0] != 0 && $addArr1[1] != 0) {
                            $schedule['home_add_guest'][] = $addArr1[1];
                            $schedule['visit_add_guest'][] = $addArr1[0];
                        }
                    }
                    if (!empty($val['guest_add_two'])) {
                        $addArr2 = explode(':', $val['guest_add_two']);
                        if ($addArr2[0] != 0 && $addArr2[1] != 0) {
                            $schedule['home_add_guest'][] = $addArr2[1];
                            $schedule['visit_add_guest'][] = $addArr2[0];
                        }
                    }
                    if (!empty($val['guest_add_three'])) {
                        $addArr3 = explode(':', $val['guest_add_three']);
                        if ($addArr3[0] != 0 && $addArr3[1] != 0) {
                            $schedule['home_add_guest'][] = $addArr3[1];
                            $schedule['visit_add_guest'][] = $addArr3[0];
                        }
                    }
                    if (!empty($val['guest_add_four'])) {
                        $addArr4 = explode(':', $val['guest_add_four']);
                        if ($addArr4[0] != 0 && $addArr4[1] != 0) {
                            $schedule['home_add_guest'][] = $addArr4[1];
                            $schedule['visit_add_guest'][] = $addArr4[0];
                        }
                    }
                    if (!empty($data['result_qcbf'])) {
                        $bfArr = explode(':', $data['result_qcbf']);
                        if ($bfArr[0] != 0 && $bfArr[1] != 0) {
                            $schedule['home_all_guest'] = $bfArr[1];
                            $schedule['visit_all_guest'] = $bfArr[0];
                        }
                    }
                    $schedule['match_time'] = str_replace('_', "'", $val['match_time']);
                }
            }
            $data[] = ['schedule' => $schedule, 'team' => $team, 'league' => $league];
        }
//        return ['code' => 600, 'msg' => '成功', 'data' => $data];
        return $data;
    }

    public function getScheduleDetail($openMid) {
        $field = ['lan_schedule.schedule_mid', 'lan_schedule.open_mid', 'lan_schedule.start_time', 'l.league_id', 'l.league_short_name', 'l.league_long_name', 'ht.team_long_name home_team_name', 'vt.team_long_name visit_team_name',
            'htr.team_position home_team_position', 'htr.team_rank home_team_rank', 'vtr.team_position visit_team_position', 'vtr.team_rank visit_team_rank', 'lr.result_status', 'lr.guest_one', 'lr.match_time',
            'lr.guest_two', 'lr.guest_three', 'lr.guest_four', 'lr.guest_add_one', 'lr.guest_add_two', 'lr.guest_add_three', 'lr.guest_add_four', 'lr.result_qcbf', 'ht.team_id home_team_id', 'vt.team_id visit_team_id'];
        $where['open_mid'] = $openMid;
        $oddStr = ['odds3002', 'odds3004'];
        $detail = LanSchedule::find()->select($field)
                ->innerJoin('lan_schedule_result lr', 'lr.schedule_mid = lan_schedule.schedule_mid')
                ->leftJoin('league l', 'l.league_code = lan_schedule.league_id and l.league_type = 2')
                ->leftJoin('team ht', 'ht.team_code = lan_schedule.home_team_id and ht.team_type = 2')
                ->leftJoin('team vt', 'vt.team_code = lan_schedule.visit_team_id and vt.team_type = 2')
                ->leftJoin('lan_team_rank htr', 'htr.team_code = lan_schedule.home_team_id')
                ->leftJoin('lan_team_rank vtr', 'vtr.team_code = lan_schedule.visit_team_id')
                ->with($oddStr)
                ->where($where)
                ->asArray()
                ->one();
        if (empty($detail)) {
            $data = [];
            return $data;
//            return ['code' => 109, 'msg' => '该赛程不存在'];
        }
        $schedule['schedule_id'] = $detail['open_mid'];
        $schedule['start_time'] = $detail['start_time'];
        $schedule['status'] = $detail['result_status'];
        $schedule['rf_nums'] = $detail['odds3002']['rf_nums'];
        $schedule['fen_cutoff'] = $detail['odds3004']['fen_cutoff'];
        $schedule['wins_odds'] = $detail['odds3002']['wins_3002'];
        $schedule['lose_odds'] = $detail['odds3002']['lose_3002'];
        $schedule['dafen_odds'] = $detail['odds3004']['da_3004'];
        $schedule['xiaofen_odds'] = $detail['odds3004']['xiao_3004'];
        $team['home_team_name'] = $detail['home_team_name'];
        $team['visit_team_name'] = $detail['visit_team_name'];
        $team['home_team_id'] = $detail['home_team_id'];
        $team['visit_team_id'] = $detail['visit_team_id'];
        $league['league_name'] = $detail['league_long_name'];
        $league['league_short_name'] = $detail['league_short_name'];
        $league['league_id'] = $detail['league_id'];
        if ($detail['home_team_position'] == 1) {
            $team['home_team_rank'] = '东部' . $detail['home_team_rank'];
        } elseif ($detail['home_team_position'] == 2) {
            $team['home_team_rank'] = '西部' . $detail['home_team_rank'];
        }
        if ($detail['visit_team_position'] == 1) {
            $team['visit_team_rank'] = '东部' . $detail['visit_team_rank'];
        } elseif ($detail['visit_team_position'] == 2) {
            $team['visit_team_rank'] = '西部' . $detail['visit_team_rank'];
        }
        if (in_array($detail['result_status'], [1, 2, 6])) {
            if (!empty($detail['guest_one'])) {
                $oneArr = explode(':', $detail['guest_one']);
                $schedule['home_guest'][] = $oneArr[1];
                $schedule['visit_guest'][] = $oneArr[0];
            }
            if (!empty($detail['guest_two'])) {
                $twoArr = explode(':', $detail['guest_two']);
                if ($twoArr[0] != 0 && $twoArr[1] != 0) {
                    $schedule['home_guest'][] = $twoArr[1];
                    $schedule['visit_guest'][] = $twoArr[0];
                }
            }
            if (!empty($detail['guest_three'])) {
                $threeArr = explode(':', $detail['guest_three']);
                if ($threeArr[0] != 0 && $threeArr[1] != 0) {
                    $schedule['home_guest'][] = $threeArr[1];
                    $schedule['visit_guest'][] = $threeArr[0];
                }
            }
            if (!empty($detail['guest_four'])) {
                $fourArr = explode(':', $detail['guest_four']);
                if ($fourArr[0] != 0 && $fourArr[1] != 0) {
                    $schedule['home_guest'][] = $fourArr[1];
                    $schedule['visit_guest'][] = $fourArr[0];
                }
            }
            if (!empty($detail['guest_add_one'])) {
                $addArr1 = explode(':', $detail['guest_add_one']);
                if ($addArr1[0] != 0 && $addArr1[1] != 0) {
                    $schedule['home_add_guest'][] = $addArr1[1];
                    $schedule['visit_add_guest'][] = $addArr1[0];
                }
            }
            if (!empty($detail['guest_add_two'])) {
                $addArr2 = explode(':', $detail['guest_add_two']);
                if ($addArr2[0] != 0 && $addArr2[1] != 0) {
                    $schedule['home_add_guest'][] = $addArr2[1];
                    $schedule['visit_add_guest'][] = $addArr2[0];
                }
            }
            if (!empty($detail['guest_add_three'])) {
                $addArr3 = explode(':', $detail['guest_add_three']);
                if ($addArr3[0] != 0 && $addArr3[1] != 0) {
                    $schedule['home_add_guest'][] = $addArr3[1];
                    $schedule['visit_add_guest'][] = $addArr3[0];
                }
            }
            if (!empty($detail['guest_add_four'])) {
                $addArr4 = explode(':', $detail['guest_add_four']);
                if ($addArr4[0] != 0 && $addArr4[1] != 0) {
                    $schedule['home_add_guest'][] = $addArr4[1];
                    $schedule['visit_add_guest'][] = $addArr4[0];
                }
            }
            if (!empty($detail['result_qcbf'])) {
                $bfArr = explode(':', $detail['result_qcbf']);
                if ($bfArr[0] != 0 && $bfArr[1] != 0) {
                    $schedule['home_all_guest'] = $bfArr[1];
                    $schedule['visit_all_guest'] = $bfArr[0];
                }
            }
            $schedule['match_time'] = str_replace('_', "'", $detail['match_time']);
        }
        $data = ['schedule' => $schedule, 'team' => $team, 'league' => $league];
//        return ['code' => 600, 'msg' => '获取成功', 'data' => $data];
        return $data;
    }

    public function getScheduleAnalyze($openMid) {
        $field = ['hr.team_position home_team_position', 'hr.team_rank home_team_rank', 'hr.game_nums home_game_nums', 'hr.win_nums home_win_nums', 'hr.lose_nums home_lose_nums', 'hr.win_rate home_win_rate',
            'hr.wins_diff home_wins_diff', 'hr.defen_nums home_defen_nums', 'hr.shifen_nums hoem_shifen_nums', 'hr.home_result home_home_result', 'hr.visit_result home_visit_result', 'hr.ten_result home_ten_result',
            'hr.near_result home_near_result', 'vr.game_nums visit_game_nums', 'vr.win_nums visit_win_nums', 'vr.lose_nums visit_lose_nums', 'vr.win_rate visit_win_rate', 'vr.wins_diff visit_wins_diff',
            'vr.defen_nums visit_defen_nums', 'vr.shifen_nums visit_shifen_nums', 'vr.home_result visit_home_result', 'vr.visit_result visit_visit_result', 'vr.ten_result visit_ten_result',
            'vr.near_result visit_near_result', 'vr.team_rank visit_team_rank', 'vr.team_position visit_team_position'];
        $analyze = LanSchedule::find()->select($field)
                ->leftJoin('lan_team_rank hr', 'hr.team_code = lan_schedule.home_team_id')
                ->leftJoin('lan_team_rank vr', 'vr.team_code = lan_schedule.visit_team_id')
                ->where(['open_mid' => $openMid])
                ->asArray()
                ->one();
        $data = [];
        if (empty($analyze)) {
            return $data;
//            return ['code' => 109, 'msg' => '该赛程暂无相关分析'];
        }
        if ($analyze['home_team_position'] == 1) {
            $analyze['home_team_rank'] = '东部' . $analyze['home_team_rank'];
        } elseif ($analyze['home_team_position'] == 2) {
            $analyze['home_team_rank'] = '西部' . $analyze['home_team_rank'];
        } else {
            $analyze['home_team_rank'] = '其他' . $analyze['home_team_rank'];
        }
        if ($analyze['visit_team_position'] == 1) {
            $analyze['visit_team_rank'] = '东部' . $analyze['visit_team_rank'];
        } elseif ($analyze['visit_team_position'] == 2) {
            $analyze['visit_team_rank'] = '西部' . $analyze['visit_team_rank'];
        } else {
            $analyze['visit_team_rank'] = '其他' . $analyze['visit_team_rank'];
        }
        $data['home'] = ['rank' => $analyze['home_team_rank'], 'game_nums' => $analyze['home_game_nums'], 'win_nums' => $analyze['home_win_nums'], 'lose_nums' => $analyze['home_lose_nums'],
            'win_rate' => $analyze['home_win_rate'], 'margin_victory' => $analyze['home_wins_diff'], 'avg_defen' => $analyze['home_defen_nums'], 'avg_shifen' => $analyze['hoem_shifen_nums'],
            'home_record' => $analyze['home_home_result'], 'visit_record' => $analyze['home_visit_result'], 'ten_result' => $analyze['home_ten_result'], 'near_result' => $analyze['home_near_result']];
        $data['visit'] = ['rank' => $analyze['visit_team_rank'], 'game_nums' => $analyze['visit_game_nums'], 'win_nums' => $analyze['visit_win_nums'], 'lose_nums' => $analyze['visit_lose_nums'],
            'win_rate' => $analyze['visit_win_rate'], 'margin_victory' => $analyze['visit_wins_diff'], 'avg_defen' => $analyze['visit_defen_nums'], 'avg_shifen' => $analyze['visit_shifen_nums'],
            'home_record' => $analyze['visit_home_result'], 'visit_record' => $analyze['visit_visit_result'], 'ten_result' => $analyze['visit_ten_result'], 'near_result' => $analyze['visit_near_result']];
//        return ['code' => 600, 'msg' => '成功', 'data' => $data];
        return $data;
    }

    public function getTeamSchedule($openMid) {
        $field = ['lan_schedule.home_team_id', 'lan_schedule.visit_team_id', 'h.play_time', 'h.schedule_bf', 'h.schedule_sf_nums', 'h.rf_nums', 'h.cutoff_nums', 'h.league_name', 'h.home_team_name',
            'h.visit_team_name', 'h.home_team_code', 'h.visit_team_code'];
        $teamSchedule = LanSchedule::find()->select($field)
                ->leftJoin('lan_schedule_history h', 'h.home_team_code = lan_schedule.home_team_id or h.visit_team_code = lan_schedule.home_team_id or h.home_team_code = lan_schedule.visit_team_id or h.visit_team_code = lan_schedule.visit_team_id')
                ->where(['open_mid' => $openMid])
                ->asArray()
                ->all();
        if (empty($teamSchedule)) {
            $data = [];
//            return ['code' => 109, 'msg' => '该赛程相关球队暂无其他对阵信息'];
            return $data;
        }
        $homeHistory = [];
        $homeFuture = [];
        $visitHistory = [];
        $visitFuture = [];
        foreach ($teamSchedule as $val) {
            if ($val['home_team_id'] == $val['home_team_code'] || $val['home_team_id'] == $val['visit_team_code']) {
                if (strtotime($val['play_time']) < time()) {
                    $homeHistory[] = ['league_name' => $val['league_name'], 'home_team_name' => $val['home_team_name'], 'visit_team_name' => $val['visit_team_name'], 'start_time' => $val['play_time'],
                        'bf_nums' => $val['schedule_bf'], 'sf_nums' => $val['schedule_sf_nums'], 'rf_nums' => $val['rf_nums'], 'cutoff_nums' => $val['cutoff_nums']];
                } else {
                    $homeFuture[] = ['league_name' => $val['league_name'], 'home_team_name' => $val['home_team_name'], 'visit_team_name' => $val['visit_team_name'], 'start_time' => $val['play_time']];
                }
            }
            if ($val['visit_team_id'] == $val['home_team_code'] || $val['visit_team_id'] == $val['visit_team_code']) {
                if (strtotime($val['play_time']) < time()) {
                    $visitHistory[] = ['league_name' => $val['league_name'], 'home_team_name' => $val['home_team_name'], 'visit_team_name' => $val['visit_team_name'], 'start_time' => $val['play_time'],
                        'bf_nums' => $val['schedule_bf'], 'sf_nums' => $val['schedule_sf_nums'], 'rf_nums' => $val['rf_nums'], 'cutoff_nums' => $val['cutoff_nums']];
                } else {
                    $visitFuture[] = ['league_name' => $val['league_name'], 'home_team_name' => $val['home_team_name'], 'visit_team_name' => $val['visit_team_name'], 'start_time' => $val['play_time']];
                }
            }
        }
        $data = ['homeHistory' => $homeHistory, 'homeFuture' => $homeFuture, 'visitHistory' => $visitHistory, 'visitFuture' => $visitFuture];
//        return ['code' => 600, 'msg' => '成功', 'data' => $data];
        return $data;
    }

    public function getScheduleForecast($openMid) {
        $field = ['open_mid', 'p.pre_result_title', 'p.pre_result_3001', 'p.pre_result_3002', 'p.pre_result_3004', 'p.confidence_index', 'p.expert_analysis'];
        $data = LanSchedule::find()->select($field)
                ->leftJoin('lan_pre_result p', 'p.schedule_mid = lan_schedule.schedule_mid')
                ->where(['open_mid' => $openMid])
                ->asArray()
                ->one();
        if (empty($data)) {
            $data = [];
            return $data;
        }
        $result['schedule_id'] = $data['open_mid'];
        $result['title'] = $data['pre_result_title'];
        $result['result'] = $data['pre_result_3001'];
        $result['rfResult'] = $data['pre_result_3002'];
        $result['dxfResult'] = $data['pre_result_3004'];
        $result['confidence'] = $data['confidence_index'];
        $result['expertAnalyze'] = $data['expert_analysis'];
        return $result;
    }

    /**
     * 获取篮球赛程实况信息
     * @auther GL zyl
     * @param type $mid  赛程MID
     * @return string|array
     */
    public function getScheduleCount($openMid) {
        $field = ['lan_schedule.open_mid', 'lan_schedule.visit_short_name', 'lan_schedule.home_short_name', 'sr.result_status', 'sr.guest_one', 'sr.guest_two', 'sr.guest_three', 'sr.guest_four', 'sr.guest_add_one', 'sr.guest_add_two', 'sr.guest_add_three',
            'sr.guest_add_four', 'sr.result_qcbf', 'c.home_shots', 'c.visit_shots', 'c.home_three_point', 'c.visit_three_point', 'c.home_penalty', 'c.visit_penalty', 'c.home_rebound', 'c.visit_rebound', 'c.home_assist',
            'c.visit_assist', 'c.home_steals', 'c.visit_steals', 'c.home_cap', 'c.visit_cap', 'c.home_foul', 'c.visit_foul', 'c.home_all_miss', 'c.visit_all_miss'];
        $data = LanSchedule::find()->select($field)
                ->leftJoin('lan_schedule_result as sr', 'sr.schedule_mid = lan_schedule.schedule_mid')
                ->leftJoin('lan_schedule_count as c', 'c.schedule_mid = lan_schedule.schedule_mid')
                ->where(['lan_schedule.open_mid' => $openMid])
                ->asArray()
                ->one();
        $list = [];
        $scoreArr = [];
        $teamArr = [];
        if ($data['result_status'] != 0) {
            if (!empty($data['guest_one'])) {
                $oneArr = explode(':', $data['guest_one']);
                $scoreArr['home']['guest'][] = $oneArr[1];
                $scoreArr['visit']['guest'][] = $oneArr[0];
            }
            if (!empty($data['guest_two'])) {
                $twoArr = explode(':', $data['guest_two']);
                if ($twoArr[0] != 0 && $twoArr[1] != 0) {
                    $scoreArr['home']['guest'][] = $twoArr[1];
                    $scoreArr['visit']['guest'][] = $twoArr[0];
                }
            }
            if (!empty($data['guest_three'])) {
                $threeArr = explode(':', $data['guest_three']);
                if ($threeArr[0] != 0 && $threeArr[1] != 0) {
                    $scoreArr['home']['guest'][] = $threeArr[1];
                    $scoreArr['visit']['guest'][] = $threeArr[0];
                }
            }
            if (!empty($data['guest_four'])) {
                $fourArr = explode(':', $data['guest_four']);
                if ($fourArr[0] != 0 && $fourArr[1] != 0) {
                    $scoreArr['home']['guest'][] = $fourArr[1];
                    $scoreArr['visit']['guest'][] = $fourArr[0];
                }
            }
            if (!empty($data['guest_add_one'])) {
                $addArr1 = explode(':', $data['guest_add_one']);
                if ($addArr1[0] != 0 && $addArr1[1] != 0) {
                    $scoreArr['home']['guest'][] = $addArr1[1];
                    $scoreArr['visit']['guest'][] = $addArr1[0];
                }
            }
            if (!empty($data['guest_add_two'])) {
                $addArr2 = explode(':', $data['guest_add_two']);
                if ($addArr2[0] != 0 && $addArr2[1] != 0) {
                    $scoreArr['home']['guest'][] = $addArr2[1];
                    $scoreArr['visit']['guest'][] = $addArr2[0];
                }
            }
            if (!empty($data['guest_add_three'])) {
                $addArr3 = explode(':', $data['guest_add_three']);
                if ($addArr3[0] != 0 && $addArr3[1] != 0) {
                    $scoreArr['home']['guest'][] = $addArr3[1];
                    $scoreArr['visit']['guest'][] = $addArr3[0];
                }
            }
            if (!empty($data['guest_add_four'])) {
                $addArr4 = explode(':', $data['guest_add_four']);
                if ($addArr4[0] != 0 && $addArr4[1] != 0) {
                    $scoreArr['home']['guest'][] = $addArr4[1];
                    $scoreArr['visit']['guest'][] = $addArr4[0];
                }
            }
            if (!empty($data['result_qcbf'])) {
                $bfArr = explode(':', $data['result_qcbf']);
                if ($bfArr[0] != 0 && $bfArr[1] != 0) {
                    $scoreArr['home']['all_guest'] = $bfArr[1];
                    $scoreArr['visit']['all_guest'] = $bfArr[0];
                }
            }
            $teamArr['shots'] = ['h_shots' => $data['home_shots'], 'v_shots' => $data['visit_shots']];
            $teamArr['point'] = ['h_point' => $data['home_three_point'], 'v_point' => $data['visit_three_point']];
            $teamArr['penalty'] = ['h_penalty' => $data['home_penalty'], 'v_penalty' => $data['visit_penalty']];
            $teamArr['rebound'] = ['h_rebound' => $data['home_rebound'], 'v_rebound' => $data['visit_rebound']];
            $teamArr['assist'] = ['h_assist' => $data['home_assist'], 'v_assist' => $data['visit_assist']];
            $teamArr['steals'] = ['h_steals' => $data['home_steals'], 'v_steals' => $data['visit_steals']];
            $teamArr['cap'] = ['h_cap' => $data['home_cap'], 'v_cap' => $data['visit_cap']];
            $teamArr['foul'] = ['h_foul' => $data['home_foul'], 'v_foul' => $data['visit_foul']];
            $teamArr['miss'] = ['h_miss' => $data['home_all_miss'], 'v_miss' => $data['visit_all_miss']];
        }
        $list['schedule_id'] = $data['open_mid'];
        $list['visit_short_name'] = $data['visit_short_name'];
        $list['home_short_name'] = $data['home_short_name'];
        $list['result_status'] = $data['result_status'];
        $list['result_qcbf'] = $data['result_qcbf'];
        $list['score'] = $scoreArr;
        $list['team'] = $teamArr;
        return $list;
    }

    public function getSaleSchedule($date, $sp = false) {
        $field = ['schedule_mid', 'schedule_code', 'open_mid', 'start_time', 'beginsale_time', 'endsale_time', 'schedule_sf', 'schedule_rfsf', 'schedule_dxf', 'schedule_sfc', 'schedule_status'];

        $oddStr = ['odds3001', 'odds3002', 'odds3003', 'odds3004'];
        $where['schedule_date'] = $date;
        $where['schedule_status'] = 1;
        $scheDetail = LanSchedule::find()->select($field)
                ->with($oddStr)
                ->where($where)
                ->asArray()
                ->all();
        if (empty($scheDetail)) {
            $data = [];
            return $data;
        }
        $list = [];
        foreach ($scheDetail as $key => $val) {
            $result['schedule_id'] = $val['open_mid'];
            $result['schedule_code'] = $val['schedule_code'];
            $result['start_time'] = $val['start_time'];
            $result['beginsale_time'] = $val['beginsale_time'];
            $result['endsale_time'] = $val['endsale_time'];
            $result['sale_status'] = $val['schedule_status'];
            $result['sf_sale_status'] = $val['schedule_sf'];
            $result['rfsf_sale_status'] = $val['schedule_rfsf'];
            $result['dxf_sale_status'] = $val['schedule_dxf'];
            $result['sfc_sale_status'] = $val['schedule_sfc'];
            if ($sp == true) {
                if (!empty($val['odds3001'])) {
                    $odds3001 = ['wins' => $val['odds3001']['wins_3001'], 'lose' => $val['odds3001']['lose_3001'], 'update_time' => $val['odds3001']['update_time']];
                    $result['odds3001'] = $odds3001;
                }
                if (!empty($val['odds3002'])) {
                    $odds3002 = ['rf_nums' => $val['odds3002']['rf_nums'], 'wins' => $val['odds3002']['wins_3002'], 'lose' => $val['odds3002']['lose_3002'], 'update_time' => $val['odds3002']['update_time']];
                    $result['odds3002'] = $odds3002;
                }
                if (!empty($val['odds3003'])) {
                    $odds3003 = ['zhuwins_1-5' => $val['odds3003']['cha_01'], 'zhuwins_6-10' => $val['odds3003']['cha_02'], 'zhuwins_11-15' => $val['odds3003']['cha_03'], 'zhuwins_16-20' => $val['odds3003']['cha_04'],
                        'zhuwins_21-25' => $val['odds3003']['cha_05'], 'zhuwins_26+' => $val['odds3003']['cha_06'], 'zhulose_1-5' => $val['odds3003']['cha_11'], 'zhulose_6-10' => $val['odds3003']['cha_12'],
                        'zhulose_11-15' => $val['odds3003']['cha_13'], 'zhulose_16-20' => $val['odds3003']['cha_14'], 'zhulose_21-25' => $val['odds3003']['cha_15'], 'zhulose_26+' => $val['odds3003']['cha_16'],
                        'update_time' => $val['odds3003']['update_time']];
                    $result['odds3003'] = $odds3003;
                }
                if (!empty($val['odds3004'])) {
                    $odds3004 = ['fen_cutoff' => $val['odds3004']['fen_cutoff'], 'dafen' => $val['odds3004']['da_3004'], 'xiaofen' => $val['odds3004']['xiao_3004'], 'update_time' => $val['odds3004']['update_time']];
                    $result['odds3004'] = $odds3004;
                }
            }
            $list[$key]['schedule'] = $result;
        }
        return $list;
    }

    public function getScheduleSp($openMid) {
        $oddStr = ['odds3001', 'odds3002', 'odds3003', 'odds3004'];

        $where['open_mid'] = $openMid;
        $scheOdds = LanSchedule::find()->select(['open_mid', 'schedule_mid'])->with($oddStr)->where($where)->asArray()->one();
        if (empty($scheOdds)) {
            $data = [];
            return $data;
        }
        if (!empty($scheOdds['odds3001'])) {
            $odds3001 = ['wins' => $scheOdds['odds3001']['wins_3001'], 'wins_trend' => $scheOdds['odds3001']['wins_trend'], 'lose' => $scheOdds['odds3001']['lose_3001'], 'lose_trend' => $scheOdds['odds3001']['lose_trend'], 'update_time' => $scheOdds['odds3001']['update_time']];
            $list['odds3001'] = $odds3001;
        }
        if (!empty($scheOdds['odds3002'])) {
            $odds3002 = ['rf_nums' => $scheOdds['odds3002']['rf_nums'], 'wins' => $scheOdds['odds3002']['wins_3002'], 'wins_trend' => $scheOdds['odds3001']['wins_trend'], 'lose' => $scheOdds['odds3002']['lose_3002'], 'lose_trend' => $scheOdds['odds3001']['lose_trend'], 'update_time' => $scheOdds['odds3002']['update_time']];
            $list['odds3002'] = $odds3002;
        }
        if (!empty($scheOdds['odds3003'])) {
            $odds3003 = ['zhuwins_1-5' => $scheOdds['odds3003']['cha_01'], 'zhuwins_6-10' => $scheOdds['odds3003']['cha_02'], 'zhuwins_11-15' => $scheOdds['odds3003']['cha_03'], 'zhuwins_16-20' => $scheOdds['odds3003']['cha_04'],
                'zhuwins_21-25' => $scheOdds['odds3003']['cha_05'], 'zhuwins_26+' => $scheOdds['odds3003']['cha_06'], 'zhulose_1-5' => $scheOdds['odds3003']['cha_11'], 'zhulose_6-10' => $scheOdds['odds3003']['cha_12'],
                'zhulose_11-15' => $scheOdds['odds3003']['cha_13'], 'zhulose_16-20' => $scheOdds['odds3003']['cha_14'], 'zhulose_21-25' => $scheOdds['odds3003']['cha_15'], 'zhulose_26+' => $scheOdds['odds3003']['cha_16'],
                'update_time' => $scheOdds['odds3003']['update_time'], 'zhuwins_1-5_trend' => $scheOdds['odds3003']['cha_01_trend'], 'zhuwins_6-10_trend' => $scheOdds['odds3003']['cha_02_trend'], 'zhuwins_11-15_trend' => $scheOdds['odds3003']['cha_03_trend'],
                'zhuwins_16-20_trend' => $scheOdds['odds3003']['cha_04_trend'], 'zhuwins_21-25_trend' => $scheOdds['odds3003']['cha_05_trend'], 'zhuwins_26+_trend' => $scheOdds['odds3003']['cha_06_trend'], 'zhulose_1-5_trend' => $scheOdds['odds3003']['cha_11_trend'],
                'zhulose_6-10_trend' => $scheOdds['odds3003']['cha_12_trend'], 'zhulose_11-15_trend' => $scheOdds['odds3003']['cha_13_trend'], 'zhulose_16-20_trend' => $scheOdds['odds3003']['cha_14_trend'], 'zhulose_21-25_trend' => $scheOdds['odds3003']['cha_15_trend'],
                'zhulose_26+_trend' => $scheOdds['odds3003']['cha_16_trend'],];
            $list['odds3003'] = $odds3003;
        }
        if (!empty($scheOdds['odds3004'])) {
            $odds3004 = ['fen_cutoff' => $scheOdds['odds3004']['fen_cutoff'], 'dafen' => $scheOdds['odds3004']['da_3004'], 'dafen_trend' => $scheOdds['odds3004']['da_3004_trend'],
                'xiaofen' => $scheOdds['odds3004']['xiao_3004'], 'xiaofen_trend' => $scheOdds['odds3004']['xiao_3004_trend'], 'update_time' => $scheOdds['odds3004']['update_time']];
            $list['odds3004'] = $odds3004;
        }
        $list['schedule_id'] = $scheOdds['open_mid'];
        return $list;
    }

    /**
     * 获取联赛列表
     * @return type
     */
    public function getLeague() {
        $field = ['league_id', 'league_category_id', 'league_long_name', 'league_short_name', 'league_remarks'];
        $data = League::find()->select($field)->where(['league_type' => 2, 'league_status' => 1])->asArray()->all();
        return $data;
    }

    /**
     * 获取球队列表
     * @return type
     */
    public function getTeam() {
        $field = ['team_id', 'team_long_name', 'team_short_name'];
        $data = Team::find()->select($field)->where(['team_type' => 2])->asArray()->all();
        return $data;
    }

    public function getTeamCount($teamId) {
        $field = ['h.play_time', 'h.schedule_bf', 'h.schedule_sf_nums', 'h.rf_nums', 'h.cutoff_nums', 'h.league_name', 'h.home_team_name', 'h.visit_team_name'];
        $teamSchedule = Team::find()->select($field)
                ->leftJoin('lan_schedule_history h', 'h.home_team_code = team.team_code or h.visit_team_code = team.team_code')
                ->where(['team.team_id' => $teamId, 'team.team_type' => 2])
                ->asArray()
                ->all();
        if (empty($teamSchedule)) {
//            return ['code' => 109, 'msg' => '该赛程相关球队暂无其他对阵信息'];
            $data = [];
            return $data;
        }
        $teamHistory = [];
        $teamFuture = [];
        foreach ($teamSchedule as $val) {
            if (strtotime($val['play_time']) < time()) {
                $teamHistory[] = ['league_name' => $val['league_name'], 'home_team_name' => $val['home_team_name'], 'visit_team_name' => $val['visit_team_name'], 'start_time' => $val['play_time'],
                    'bf_nums' => $val['schedule_bf'], 'sf_nums' => $val['schedule_sf_nums'], 'rf_nums' => $val['rf_nums'], 'cutoff_nums' => $val['cutoff_nums']];
            } else {
                $teamFuture[] = ['league_name' => $val['league_name'], 'home_team_name' => $val['home_team_name'], 'visit_team_name' => $val['visit_team_name'], 'start_time' => $val['play_time']];
            }
        }
        $data = ['history' => $teamHistory, 'future' => $teamFuture];
        return $data;
    }

    public function getLeagueTeamRank($leagueId) {
        $field = ['lan_team_rank.team_name', 'lan_team_rank.team_position', 'lan_team_rank.team_rank', 'l.league_long_name', 't.team_id', 't.team_long_name', 'l.league_id'];
        $rank = LanTeamRank::find()->select($field)
                ->innerJoin('league l', 'l.league_code = lan_team_rank.league_code')
                ->leftJoin('team t', 't.team_code = lan_team_rank.team_code and t.team_type = 2')
                ->where(['league_id' => $leagueId, 'league_type' => 2])
                ->asArray()
                ->all();
        if (empty($rank)) {
            $data = [];
            return $data;
        }
        $list = [];
        foreach ($rank as $val) {
            $team['league_id'] = $val['league_id'];
            $team['league_name'] = $val['league_long_name'];
            $team['team_id'] = $val['team_id'];
            $team['team_long_name'] = $val['team_long_name'];
            $team['team_name'] = $val['team_name'];
            if ($val['team_position'] == 1) {
                $team['team_rank'] = '东部' . $val['team_rank'];
            } elseif ($val['team_position'] == 2) {
                $val['team_rank'] = '西部' . $val['team_rank'];
            } else {
                $val['team_rank'] = '其他' . $val['team_rank'];
            }
            $list[] = $team;
        }
        return $list;
    }

    public function getEuropeOdds($openMid) {
        $field = ['open_mid', "e.company_name", "e.handicap_name", "e.odds_3", "e.odds_0", "e.profit_rate", "e.handicap_type", "e.update_time"];
        $odds = LanSchedule::find()->select($field)
                ->leftJoin('lan_europe_odds e', 'e.schedule_mid = lan_schedule.schedule_mid')
                ->where(['open_mid' => $openMid])
                ->asArray()
                ->all();
        if (empty($odds)) {
            $data = [];
            return $data;
        }
        $list = [];
        foreach ($odds as $val) {
            if ($val['handicap_type'] == 1) {
                $oldOdds['schedule_id'] = $val['open_mid'];
                $oldOdds['company_name'] = $val['company_name'];
                $oldOdds['wins_odds'] = $val['odds_3'];
                $oldOdds['lose_odds'] = $val['odds_0'];
                $oldOdds['profit_rate'] = $val['profit_rate'];
                $oldOdds['update_time'] = $val['update_time'];
                $list['oldData'][] = $oldOdds;
            } elseif ($val['handicap_type'] == 2) {
                $newOdds['schedule_id'] = $val['open_mid'];
                $newOdds['company_name'] = $val['company_name'];
                $newOdds['wins_odds'] = $val['odds_3'];
                $newOdds['lose_odds'] = $val['odds_0'];
                $newOdds['profit_rate'] = $val['profit_rate'];
                $newOdds['update_time'] = $val['update_time'];
                $list['newData'][] = $newOdds;
            }
        }
        return $list;
    }

    public function getAsiaOdds($openMid) {
        $field = ['open_mid', "a.company_name", "a.handicap_name", "a.odds_3", "a.odds_0", "a.profit_rate", "a.handicap_type", "a.update_time", "a.rf_nums"];
        $odds = LanSchedule::find()->select($field)
                ->leftJoin('lan_rangfen_odds a', 'a.schedule_mid = lan_schedule.schedule_mid')
                ->where(['open_mid' => $openMid])
                ->asArray()
                ->all();
        if (empty($odds)) {
            $data = [];
            return $data;
        }
        $list = [];
        foreach ($odds as $val) {
            if ($val['handicap_type'] == 1) {
                $oldOdds['schedule_id'] = $val['open_mid'];
                $oldOdds['rf_nums'] = $val['rf_nums'];
                $oldOdds['company_name'] = $val['company_name'];
                $oldOdds['wins_odds'] = $val['odds_3'];
                $oldOdds['lose_odds'] = $val['odds_0'];
                $oldOdds['profit_rate'] = $val['profit_rate'];
                $oldOdds['update_time'] = $val['update_time'];
                $list['oldData'][] = $oldOdds;
            } elseif ($val['handicap_type'] == 2) {
                $newOdds['schedule_id'] = $val['open_mid'];
                $newOdds['rf_nums'] = $val['rf_nums'];
                $newOdds['company_name'] = $val['company_name'];
                $newOdds['wins_odds'] = $val['odds_3'];
                $newOdds['lose_odds'] = $val['odds_0'];
                $newOdds['profit_rate'] = $val['profit_rate'];
                $newOdds['update_time'] = $val['update_time'];
                $list['newData'][] = $newOdds;
            }
        }
        return $list;
    }

    public function getTotalScoreOdds($openMid) {
        $field = ['open_mid', "d.company_name", "d.handicap_name", "d.odds_da", "d.odds_xiao", "d.profit_rate", "d.handicap_type", "d.update_time", "d.cutoff_fen"];
        $odds = LanSchedule::find()->select($field)
                ->leftJoin('lan_daxiao_odds d', 'd.schedule_mid = lan_schedule.schedule_mid')
                ->where(['open_mid' => $openMid])
                ->asArray()
                ->all();
        if (empty($odds)) {
            $data = [];
            return $data;
        }
        $list = [];
        foreach ($odds as $val) {
            if ($val['handicap_type'] == 1) {
                $oldOdds['schedule_id'] = $val['open_mid'];
                $oldOdds['cutoff_fen'] = $val['cutoff_fen'];
                $oldOdds['company_name'] = $val['company_name'];
                $oldOdds['dafen_odds'] = $val['odds_da'];
                $oldOdds['xiaofen_odds'] = $val['odds_xiao'];
                $oldOdds['profit_rate'] = $val['profit_rate'];
                $oldOdds['update_time'] = $val['update_time'];
                $list['oldData'][] = $oldOdds;
            } elseif ($val['handicap_type'] == 2) {
                $newOdds['schedule_id'] = $val['open_mid'];
                $newOdds['cutoff_fen'] = $val['cutoff_fen'];
                $newOdds['company_name'] = $val['company_name'];
                $newOdds['dafen_odds'] = $val['odds_da'];
                $newOdds['xiaofen_odds'] = $val['odds_xiao'];
                $newOdds['profit_rate'] = $val['profit_rate'];
                $newOdds['update_time'] = $val['update_time'];
                $list['newData'][] = $newOdds;
            }
        }
        return $list;
    }

    public function getEuropeList($type) {
        $field = ['open_mid', "e.company_name", "e.handicap_name", "e.odds_3", "e.odds_0", "e.profit_rate", "e.handicap_type", "e.update_time"];
        $odds = LanSchedule::find()->select($field)
                ->innerJoin('lan_europe_odds e', 'e.schedule_mid = lan_schedule.schedule_mid')
                ->where(['e.handicap_type' => $type])
                ->asArray()
                ->all();
        if (empty($odds)) {
            $data = [];
            return $data;
        }
        $list = [];
        foreach ($odds as $val) {
            $Odds['schedule_id'] = $val['open_mid'];
            $Odds['company_name'] = $val['company_name'];
            $Odds['wins_odds'] = $val['odds_3'];
            $Odds['lose_odds'] = $val['odds_0'];
            $Odds['profit_rate'] = $val['profit_rate'];
            $Odds['update_time'] = $val['update_time'];
            $list['data'][] = $Odds;
        }
        return $list;
    }

    public function getAsiaList($type) {
        $field = ['open_mid', "a.company_name", "a.handicap_name", "a.odds_3", "a.odds_0", "a.profit_rate", "a.handicap_type", "a.update_time", "a.rf_nums"];
        $odds = LanSchedule::find()->select($field)
                ->leftJoin('lan_rangfen_odds a', 'a.schedule_mid = lan_schedule.schedule_mid')
                ->where(['a.handicap_type' => $type])
                ->asArray()
                ->all();
        if (empty($odds)) {
            $data = [];
            return $data;
        }
        $list = [];
        foreach ($odds as $val) {
            $Odds['schedule_id'] = $val['open_mid'];
            $Odds['rf_nums'] = $val['rf_nums'];
            $Odds['company_name'] = $val['company_name'];
            $Odds['wins_odds'] = $val['odds_3'];
            $Odds['lose_odds'] = $val['odds_0'];
            $Odds['profit_rate'] = $val['profit_rate'];
            $Odds['update_time'] = $val['update_time'];
            $list['data'][] = $Odds;
        }
        return $list;
    }

    public function getTotalScoreList($type) {
        $field = ['open_mid', "d.company_name", "d.handicap_name", "d.odds_da", "d.odds_xiao", "d.profit_rate", "d.handicap_type", "d.update_time", "d.cutoff_fen"];
        $odds = LanSchedule::find()->select($field)
                ->leftJoin('lan_daxiao_odds d', 'd.schedule_mid = lan_schedule.schedule_mid')
                ->where(['d.handicap_type' => $type])
                ->asArray()
                ->all();
        if (empty($odds)) {
            $data = [];
            return $data;
        }
        $list = [];
        foreach ($odds as $val) {
            $Odds['schedule_id'] = $val['open_mid'];
            $Odds['cutoff_fen'] = $val['cutoff_fen'];
            $Odds['company_name'] = $val['company_name'];
            $Odds['dafen_odds'] = $val['odds_da'];
            $Odds['xiaofen_odds'] = $val['odds_xiao'];
            $Odds['profit_rate'] = $val['profit_rate'];
            $Odds['update_time'] = $val['update_time'];
            $list['data'][] = $Odds;
        }
        return $list;
    }

}

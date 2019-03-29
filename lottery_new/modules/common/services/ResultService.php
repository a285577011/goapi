<?php

namespace app\modules\common\services;

use app\modules\common\models\LotteryRecord;
use app\modules\common\models\ScheduleResult;
use yii\db\Query;
use Yii;
use app\modules\common\helpers\Constants;
use app\modules\common\models\Lottery;
use app\modules\common\models\FootballFourteen;
use app\modules\competing\models\LanScheduleResult;
use app\modules\competing\models\LanSchedule;
use app\modules\competing\helpers\CompetConst;
use app\modules\competing\models\BdSchedule;
use app\modules\competing\models\BdScheduleResult;
use app\modules\common\models\Schedule;

class ResultService {

    /**
     * 获取赛果
     * @return int
     */
    public static function getResult() {
        $foots = $nums = $list = [];
        $order = Constants::ORDER_DATA;
        $date = (int) (date('Ymd'));
        $list = \Yii::redisGet('cache:get_result', 2);
        if (empty($list)) {
            $rets = Lottery::find()
                ->select(['lottery_code', 'lottery_name', 'lottery_pic'])
                ->with(['newPeriods'])
                ->where(['result_status' => 1])
                ->indexBy('lottery_code')
                ->asArray()
                ->all();
            foreach ($rets as $k => $ret) {
                $ret['newPeriods']['lottery_type'] = 1;
                $ret['newPeriods']['lottery_pic'] = $ret['lottery_pic'];
                $nums[$k] = $ret['newPeriods'];
            }
            if (isset($nums['3000'])) {
                $count1 = 0;
                $startTime = date('Y-m-d 00:00:00');
                $endTime = date('Y-m-d 23:59:59');
                $football = Schedule::find()->select(['sr.schedule_date', 'sr.status'])
                        ->leftJoin('schedule_result sr', 'sr.schedule_mid = schedule.schedule_mid')
                        ->where(['between', 'start_time', $startTime, $endTime])
                        ->asArray()
                        ->all();
                foreach ($football as $val) {
                    if ($val['status'] == 0) {
                        $count1 += 1;
                    }
                }
                $foots = Schedule::find()->select(['schedule_code', 'home_short_name home_name', 'visit_short_name visit_name', 'sr.schedule_result_3007', 'start_time'])
                    ->innerJoin('schedule_result sr', 'sr.schedule_mid = schedule.schedule_mid')
                    ->where(['sr.status' => 2])
                    ->orderBy('start_time desc')
                    ->asArray()
                    ->one();
                $foots['lottery_code'] = '3000';
                $foots['name'] = $rets['3000']['lottery_name'];
                $foots['date'] = date('Y-m-d');
                $foots['total'] = count($football);
                $foots['finished'] = count($football) - $count1;
                $foots['no_finish'] = $count1;
                $foots['lottery_type'] = 2;
                $foots['lottery_pic'] = $rets['3000']['lottery_pic'];
                $nums['3000'] = $foots;
            }
            if (isset($nums['4001'])) {
                $fourteen = FootballFourteen::find()->select(['periods', 'schedule_results'])->where(['in', 'status', [2, 3]])->orderBy('periods desc')->asArray()->one();
                $four['lottery_code'] = '4001';
                $four['lottery_name'] = '足球胜负彩';
                $four['periods'] = $fourteen['periods'];
                $four['schedule_results'] = explode(',', $fourteen['schedule_results']);
                $four['lottery_type'] = 3;
                $four['lottery_pic'] = $rets['4001']['lottery_pic'];
                $nums['4001'] = $four;
            }
            if (isset($nums['3100'])) {
                $count1 = 0;
                $startTime = date('Y-m-d 00:00:00');
                $endTime = date('Y-m-d 23:59:59');
                $basketball = LanSchedule::find()->select(['sr.schedule_date', 'sr.result_status'])
                        ->leftJoin('lan_schedule_result sr', 'sr.schedule_mid = lan_schedule.schedule_mid')
                        ->where(['between', 'start_time', $startTime, $endTime])
                        ->asArray()
                        ->all();
                foreach ($basketball as $val) {
                    if ($val['result_status'] == 0) {
                        $count1 += 1;
                    }
                }
                $baskets = LanSchedule::find()->select(['schedule_code', 'home_short_name home_name', 'visit_short_name visit_name', 'sr.result_qcbf', 'start_time'])
                    ->innerJoin('lan_schedule_result sr', 'sr.schedule_mid = lan_schedule.schedule_mid')
                    ->where(['sr.result_status' => 2])
                    ->orderBy('start_time desc')
                    ->asArray()
                    ->one();
                $baskets['lottery_code'] = '3100';
                $baskets['lottery_name'] = $rets['3100']['lottery_name'];
                $baskets['date'] = date('Y-m-d');
                $baskets['total'] = count($basketball);
                $baskets['finished'] = count($basketball) - $count1;
                $baskets['no_finish'] = $count1;
                $baskets['lottery_type'] = 4;
                $baskets['lottery_pic'] = $rets['3100']['lottery_pic'];
                $nums['3100'] = $baskets;
            }
            if (isset($nums['5000'])) {
                $bd = BdSchedule::find()->select(['bd_schedule.periods', 'home_name', 'visit_name', 'spf_rq_nums', 'sr.result_5005', 'start_time'])
                    ->innerJoin('bd_schedule_result sr', 'sr.open_mid = bd_schedule.open_mid')
                    ->where(['sr.status' => 2])
                    ->orderBy('start_time desc')
                    ->asArray()
                    ->one();
                $bd['lottery_code'] = '5000';
                $bd['lottery_name'] = $rets['5000']['lottery_name'];
                $bd['lottery_type'] = 5;
                $bd['lottery_pic'] = $rets['5000']['lottery_pic'];
                $nums['5000'] = $bd;
            }
            foreach ($order as $v) {
                foreach ($nums as $k => $num) {
                    if ($v != $k) {
                        continue;
                    }
                    $list['data'][] = $num;
                }
            }
        }
        \Yii::redisSet('cache:get_result', $list, 600);
        return $list;
    }

    /**
     * 获取数字彩开奖结果
     * @param type $code
     * @param type $pn
     * @param type $size
     * @return type
     */
    public static function getNumsResult($code, $pn, $size) {
        $where['lottery_code'] = $code;
        $where['status'] = 2;
        $query = LotteryRecord::find();
        $count = $query->where($where)->count();
        $pages = ceil($count / $size);
        $offset = ($pn - 1) * $size;
//        ->andWhere(['in', 'status', [2,3]])
        $result = LotteryRecord::find()->select(['periods', 'lottery_numbers', 'lottery_time', 'test_numbers', 'l.lottery_pic', 'l.lottery_name', 'l.lottery_code', 'lottery_record.status'])
                ->leftJoin('lottery l', 'l.lottery_code = lottery_record.lottery_code')
                ->where(['lottery_record.lottery_code' => $code])
                ->andWhere(['in', 'lottery_record.status', [2, 3]])
                ->orderBy('periods desc')
                ->limit($size)
                ->offset($offset)
                ->asArray()
                ->all();
        foreach ($result as &$val) {
            $val['lottery_type'] = 1;
        }
        if (empty($result[0]['lottery_numbers'])) {
            $result[0]['lottery_numbers'] = '开奖中....';
        }
        $data['page'] = $pn;
        $data['size'] = count($result);
        $data['pages'] = $pages;
        $data['total'] = $count;
        $data['data'] = $result;
        return $data;
    }

    /**
     * 获取足球赛果
     * @param type $date
     * @return type
     */
    public static function getFootballResult($date) {
        $data = [];
//        $sche = ScheduleResult::find()
//                ->select(['schedule_date'])
//                ->where(['<', 'schedule_date', $date])
//                ->orderBy('schedule_date desc')
//                ->asArray()
//                ->one();
        $where = ['and', ['sr.status' => 2]];
        if(empty($date)) {
            $startDate = date('Ymd', strtotime('-3 day'));
            $endDate = date('Ymd', strtotime('-1 day'));
            array_push($where, ['between', 'sr.schedule_date', $startDate, $endDate]);
        }  else {
            array_push($where, ['sr.schedule_date' => $date]);
        }
        $field = ['sr.schedule_date', 'sr.schedule_result_3010', 'sr.schedule_result_3006', 'sr.schedule_result_3007', 'sr.schedule_result_3008', 'sr.schedule_result_3009', 'sr.schedule_result_sbbf',
            'sr.odds_3006', 'sr.odds_3007', 'sr.odds_3008', 'sr.odds_3009', 'sr.odds_3010', 's.schedule_code', 's.start_time', 's.visit_short_name', 's.home_short_name', 's.rq_nums', 'l.league_short_name'];
        $football = (new Query)->select($field)
                ->from('schedule_result as sr')
                ->leftJoin('schedule as s', 's.schedule_id = sr.schedule_id')
                ->leftJoin('league as l', 'l.league_id = s.league_id')
                ->where($where)
                ->orderBy('s.start_time desc,sr.schedule_mid desc')
                ->all();
        $lotteryPic = Lottery::find()->select(['lottery_pic', 'lottery_name'])->where(['lottery_code' => 3000])->asArray()->one();
        foreach ($football as &$val) {
            $val['start_time'] = date('m-d h:i', strtotime($val['start_time']));
            $val['schedule_code'] = substr($val['schedule_code'], 6);
            $val['result'] = $val['schedule_result_3007'] . '(' . $val['schedule_result_sbbf'] . ')';
            $val['against'] = $val['home_short_name'] . 'vs' . $val['visit_short_name'];
            if($val['rq_nums'] > 0) {
                $str = '+';
            }  else {
                $str = '';
            }
            if ($val['schedule_result_3006'] == 3) {
                $val['name_3006'] = '胜(' . $str . $val['rq_nums'] . ')';
            } elseif ($val['schedule_result_3006'] == 1) {
                $val['name_3006'] = '平(' . $str . $val['rq_nums'] . ')';
            } elseif ($val['schedule_result_3006'] == 0) {
                $val['name_3006'] = '负(' . $str . $val['rq_nums'] . ')';
            } else {
                $val['name_3006'] = '';
            }
            $val['name_3007'] = $val['schedule_result_3007'];
            $val['name_3008'] = $val['schedule_result_3008'] . '球';
            if ($val['schedule_result_3009'] == '33') {
                $val['name_3009'] = '胜胜';
            } elseif ($val['schedule_result_3009'] == '31') {
                $val['name_3009'] = '胜平';
            } elseif ($val['schedule_result_3009'] == '30') {
                $val['name_3009'] = '胜负';
            } elseif ($val['schedule_result_3009'] == '13') {
                $val['name_3009'] = '平胜';
            } elseif ($val['schedule_result_3009'] == '11') {
                $val['name_3009'] = '平平';
            } elseif ($val['schedule_result_3009'] == '10') {
                $val['name_3009'] = '平负';
            } elseif ($val['schedule_result_3009'] == '03') {
                $val['name_3009'] = '负胜';
            } elseif ($val['schedule_result_3009'] == '01') {
                $val['name_3009'] = '负平';
            } elseif ($val['schedule_result_3009'] == '00') {
                $val['name_3009'] = '负负';
            } else {
                $val['name_3009'] = '';
            }
            if ($val['schedule_result_3010'] == 3) {
                $val['name_3010'] = '胜';
            } elseif ($val['schedule_result_3010'] == 1) {
                $val['name_3010'] = '平';
            } elseif ($val['schedule_result_3010'] == 0) {
                $val['name_3010'] = '负';
            } else {
                $val['name_3010'] = '';
            }
            if ($val['odds_3006'] == null) {
                $val['odds_3006'] = '';
            }
            if ($val['odds_3007'] == null) {
                $val['odds_3007'] = '';
            }
            if ($val['odds_3008'] == null) {
                $val['odds_3008'] = '';
            }
            if ($val['odds_3009'] == null) {
                $val['odds_3009'] = '';
            }
            if ($val['odds_3010'] == null) {
                $val['odds_3010'] = '';
            }
            $val['lottery_pic'] = $lotteryPic['lottery_pic'];
            $val['lottery_name'] = $lotteryPic['lottery_name'];
            $val['lottery_type'] = 2;
        }
        $data['data'] = $football;
        return $data;
    }

    /**
     * 获取胜负彩开奖结果
     * @param type $pn
     * @param type $size
     * @return type
     */
    public static function getWinsResult($pn, $size) {
        $where = ['in', 'status', [2, 3]];
        $query = FootballFourteen::find();
        $count = $query->where($where)->count();
        $pages = ceil($count / $size);
        $offset = ($pn - 1) * $size;
        $result = FootballFourteen::find()->where(['in', 'status', [2, 3]])->orderBy('periods desc')->limit($size)->offset($offset)->select(['periods', 'schedule_results'])->asArray()->all();
        $lotteryPic = Lottery::find()->select(['lottery_pic', 'lottery_name'])->where(['lottery_code' => 4001])->asArray()->one();
        foreach ($result as $key => &$val) {
            if (empty($val['schedule_results'])) {
                $val['schedule_results'] = '';
            } else {
                $val['schedule_results'] = explode(',', $val['schedule_results']);
            }
            $val['lottery_type'] = 3;
            $val['lottery_pic'] = $lotteryPic['lottery_pic'];
            $val['lottery_name'] = $lotteryPic['lottery_name'];
        }
        $data['page'] = $pn;
        $data['size'] = count($result);
        $data['pages'] = $pages;
        $data['total'] = $count;
        $data['data'] = $result;
        return $data;
    }

    /**
     * 获取篮球赛果
     * @param type $date
     * @return type
     */
    public static function getBastketResult($date) {
        $data = [];
        $where = ['and', ['sr.result_status' => 2]];
        if(empty($date)) {
            $startDate = date('Ymd', strtotime('-3 day'));
            $endDate = date('Ymd', strtotime('-1 day'));
            array_push($where, ['between', 'sr.schedule_date', $startDate, $endDate]);
        }  else {
            array_push($where, ['sr.schedule_date' => $date]);
        }
        $oddStr = ['odds3001', 'odds3002', 'odds3003', 'odds3004'];
        $field = ['lan_schedule.schedule_mid', 'sr.schedule_date', 'sr.result_3001', 'sr.result_3003', 'sr.schedule_fc', 'sr.schedule_zf', 'sr.result_qcbf', 'lan_schedule.schedule_code', 'lan_schedule.start_time', 'lan_schedule.visit_short_name',
            'lan_schedule.home_short_name', 'lan_schedule.league_name'];
        $basketball = LanSchedule::find()->select($field)
                ->innerJoin('lan_schedule_result as sr', 'sr.schedule_mid = lan_schedule.schedule_mid')
                ->with($oddStr)
                ->where($where)
                ->orderBy('lan_schedule.start_time desc, sr.schedule_mid desc')
                ->asArray()
                ->all();
        $lotteryPic = Lottery::find()->select(['lottery_pic', 'lottery_name'])->where(['lottery_code' => 3100])->asArray()->one();
        foreach ($basketball as &$val) {
            $val['start_time'] = date('m-d h:i', strtotime($val['start_time']));
            $val['schedule_code'] = substr($val['schedule_code'], 6);
            $val['result'] = $val['result_qcbf'] . '(' . $val['schedule_zf'] . ')';
            $val['against'] = $val['visit_short_name'] . ' vs ' . $val['home_short_name'];
            $bifenArr = explode(':', $val['result_qcbf']);
            if ((int) $bifenArr[1] > (int) $bifenArr[0]) {
                $val['name_3001'] = '胜';
                $val['odds_3001'] = empty($val['odds3001']) ? '' : $val['odds3001']['wins_3001'];
            } else {
                $val['name_3001'] = '负';
                $val['odds_3001'] = empty($val['odds3001']) ? '' : $val['odds3001']['lose_3001'];
            }
            if($val['odds3002']['rf_nums'] > 0) {
                $str = '+';
            }  else {
                $str = '';
            }
            if (!empty($val['odds3002'])) {
                if ((int) $bifenArr[1] + (int) $val['odds3002']['rf_nums'] > (int) $bifenArr[0]) {
                    $val['name_3002'] = '胜(' . $str . $val['odds3002']['rf_nums'] . ')';
                    $val['odds_3002'] = $val['odds3002']['wins_3002'];
                } else {
                    $val['name_3002'] = '负(' . $str . $val['odds3002']['rf_nums'] . ')';
                    $val['odds_3002'] = $val['odds3002']['lose_3002'];
                }
            } else {
                $val['name_3002'] = '';
                $val['odds_3002'] = '';
            }
            if (!empty($val['result_3003'])) {
                $fenArr = CompetConst::SFC_BETWEEN_ARR;
                $val['name_3003'] = $fenArr[$val['result_3003']];
                $itStr = 'cha_' . $val['result_3003'];
                $val['odds_3003'] = empty($val['odds3003']) ? '' : $val['odds3003'][$itStr];
            } else {
                $val['name_3003'] = '';
                $val['odds_3003'] = '';
            }
            if (!empty($val['odds3004'])) {
                if ($val['schedule_zf'] > $val['odds3004']['fen_cutoff']) {
                    $val['name_3004'] = '大分(' . $val['odds3004']['fen_cutoff'] . ')';
                    $val['odds_3004'] = $val['odds3004']['da_3004'];
                } else {
                    $val['name_3004'] = '小分(' . $val['odds3004']['fen_cutoff'] . ')';
                    $val['odds_3004'] = $val['odds3004']['xiao_3004'];
                }
            } else {
                $val['name_3004'] = '';
                $val['odds_3004'] = '';
            }
            $val['lottery_pic'] = $lotteryPic['lottery_pic'];
            $val['lottery_name'] = $lotteryPic['lottery_name'];
            $val['lottery_type'] = 4;
            unset($val['odds3001'], $val['odds3002'], $val['odds3003'], $val['odds3004']);
        }
        $data['data'] = $basketball;
        return $data;
    }

    /**
     * 获取足球赛果
     * @param type $date
     * @return type
     */
    public static function getBdResult($lotteryCode, $periods, $page, $size) {
        $data = [];
        if ($lotteryCode != 5006) {
            $playType = 1;
        } else {
            $playType = 2;
        }
        $bdPeriods = BdScheduleResult::find()->select('periods')->where(['play_type' => $playType, 'status' => 2])->groupBy('periods')->limit(10)->orderBy('periods desc')->asArray()->all();
        foreach ($bdPeriods as $v) {
            $periodsList[] = $v['periods'];
        }
        if (empty($periods)) {
            $periods = $bdPeriods[0]['periods'];
        }
        $field = ['league_name', 'bd_schedule.home_name', 'bd_schedule.visit_name', 'bd_schedule.spf_rq_nums', 'bd_schedule.sfgg_rf_nums', 'bd_schedule.bd_sort', 'sr.result_5005 result_qcbf', 'sr.result_bcbf',
            'sr.result_' . $lotteryCode, 'sr.odds_' . $lotteryCode, 'bd_schedule.start_time', 'bd_schedule.periods'];
        $query = BdSchedule::find()->select($field)
                ->innerJoin('bd_schedule_result as sr', 'sr.open_mid = bd_schedule.open_mid')
                ->where(['sr.status' => 2, 'sr.play_type' => $playType, 'bd_schedule.periods' => $periods]);
        $total = $query->count();
        if ($page != 0) {
            $offset = ($page - 1) * $size;
            $query->limit($size)->offset($offset);
        }
        $bdResult = $query->orderBy('bd_schedule.bd_sort desc')->asArray()->all();
        $lotteryPic = Lottery::find()->select(['lottery_pic', 'lottery_name'])->where(['lottery_code' => 5000])->asArray()->one();
        foreach ($bdResult as &$val) {
            $val['start_time'] = date('m-d h:i', strtotime($val['start_time']));
            $val['against'] = $val['home_name'] . 'vs' . $val['visit_name'];

            if ($lotteryCode == 5001) {
                if ($val['result_5001'] == 3) {
                    $val['name_5001'] = '主胜';
                } elseif ($val['result_5001'] == 1) {
                    $val['name_5001'] = '平';
                } elseif ($val['result_5001'] == 0) {
                    $val['name_5001'] = '主负';
                } else {
                    $val['name_5001'] = '';
                }
            } elseif ($lotteryCode == 5002) {
                if ($val['result_5002'] >= 7) {
                    $val['name_5002'] = '7+';
                } else {
                    $val['name_5002'] = $val['result_5002'] . '球';
                }
            } elseif ($lotteryCode == 5003) {
                if ($val['result_5003'] == '33') {
                    $val['name_5003'] = '胜胜';
                } elseif ($val['result_5003'] == '31') {
                    $val['name_5003'] = '胜平';
                } elseif ($val['result_5003'] == '30') {
                    $val['name_5003'] = '胜负';
                } elseif ($val['result_5003'] == '13') {
                    $val['name_5003'] = '平胜';
                } elseif ($val['result_5003'] == '11') {
                    $val['name_5003'] = '平平';
                } elseif ($val['result_5003'] == '10') {
                    $val['name_5003'] = '平负';
                } elseif ($val['result_5003'] == '03') {
                    $val['name_5003'] = '负胜';
                } elseif ($val['result_5003'] == '01') {
                    $val['name_5003'] = '负平';
                } elseif ($val['result_5003'] == '00') {
                    $val['name_5003'] = '负负';
                } else {
                    $val['name_5003'] = '';
                }
            } elseif ($lotteryCode == 5004) {
                if ($val['result_5004'] == 1) {
                    $val['name_5004'] = '上单';
                } elseif ($val['result_5004'] == 2) {
                    $val['name_5004'] = '上双';
                } elseif ($val['result_5004'] == 3) {
                    $val['name_5004'] = '下单';
                } elseif ($val['result_5004'] == 4) {
                    $val['name_5004'] = '下双';
                } else {
                    $val['name_5004'] = '';
                }
            } elseif ($lotteryCode == 5005) {
                $bf = explode(':', $val['result_5005']);
                $bfArr = CompetConst::BD_BF;
                $str = [];
                if ($bf[0] > $bf[1]) {
                    $a = 3;
                    $str[$a] = '胜其他';
                } elseif ($bf[0] == $bf[1]) {
                    $a = 1;
                    $str[$a] = '平其他';
                } else {
                    $a = 0;
                    $str[$a] = '负其他';
                }
                if (!in_array($val['result_5005'], $bfArr[$a])) {
                    $val['name_5005'] = $str[$a];
                } else {
                    $val['name_5005'] = $val['result_5005'];
                }
            } elseif ($lotteryCode == 5006) {
                if ($val['result_5006'] == 3) {
                    $val['name_5006'] = '主胜';
                } elseif ($val['result_5006'] == 0) {
                    $val['name_3010'] = '主负';
                } else {
                    $val['name_3010'] = '';
                }
            }
            $val['lottery_pic'] = $lotteryPic['lottery_pic'];
            $val['lottery_name'] = $lotteryPic['lottery_name'];
            $val['lottery_type'] = 5;
        }
        $data['page'] = $page;
        $data['size'] = count($bdResult);
        $data['pages'] = ceil($total / $size);
        $data['total'] = $total;
        $data['periodsList'] = $periodsList;
        $data['data'] = $bdResult;
        return $data;
    }

}

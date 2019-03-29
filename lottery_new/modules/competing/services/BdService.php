<?php

namespace app\modules\competing\services;

use Yii;
use app\modules\competing\models\BdSchedule;
use app\modules\common\models\Lottery;
use app\modules\common\helpers\Constants;
use app\modules\competing\helpers\CompetConst;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\OrderService;
use app\modules\common\models\LotteryOrder;
use app\modules\common\services\PayService;
use yii\db\Query;
use app\modules\common\models\BettingDetail;

class BdService {

    /**
     * 北单可投注赛程
     * @param type $lotteryCode
     * @return type
     */
    public function getBetSchedule($lotteryCode) {
        if ($lotteryCode != 5006) {
            $playType = 1;
        } else {
            $playType = 2;
        }
        $lottery = Lottery::findOne(['lottery_code' => $lotteryCode, 'status' => 1]);
        if (empty($lottery)) {
            return ['code' => 109, 'msg' => '投注失败，此彩种已经停止投注，请选择其他彩种进行投注'];
        }
        $field = ['periods', 'open_mid', 'schedule_mid', 'schedule_type', 'bd_sort', 'start_time', 'beginsale_time', 'endsale_time', 'league_name', 'home_name', 'visit_name', 'spf_rq_nums', 'sfgg_rf_nums',
            'league_code', 'home_code', 'visit_code', 'schedule_date'];
        $oddStr = ['odds' . $lotteryCode];
        $scheList = BdSchedule::find()->select($field)
                ->with($oddStr)
                ->where(['sale_status' => 1, 'play_type' => $playType])
                ->andWhere(['>', 'start_time', date('Y-m-d H:i:s', strtotime("+10 minute"))])
                ->andWhere(['<', 'beginsale_time', date('Y-m-d H:i:s')])
                ->andWhere(['>', 'endsale_time', date('Y-m-d H:i:s')])
                ->orderBy('periods,bd_sort')
                ->asArray()
                ->all();
        $list = [];
        foreach ($scheList as &$value) {
            $gameDate = $value['schedule_date'];
            $weekarray = array("日", "一", "二", "三", "四", "五", "六");
            if ($lotteryCode == 5001) {
                $updateField = ['odds_5001_id', 'open_mid', 'update_nums', 'let_wins', 'trend_3', 'let_level', 'trend_1', 'let_negative', 'trend_0', 'create_time', 'modify_time', 'update_time'];
                if (!empty($value['odds5001'])) {
                    $value['odds5001'] = array_combine($updateField, $value['odds5001']);
                }
            } elseif ($lotteryCode == 5002) {
                $updateField = ['odds_5001_id', 'open_mid', 'update_nums', 'total_gold_0', 'trend_0', 'total_gold_1', 'trend_1', 'total_gold_2', 'trend_2', 'total_gold_3', 'trend_3', 'total_gold_4', 'trend_4',
                    'total_gold_5', 'trend_5', 'total_gold_6', 'trend_6', 'total_gold_7', 'trend_7', 'create_time', 'modify_time', 'update_time'];
                if (!empty($value['odds5002'])) {
                    $value['odds5002'] = array_combine($updateField, $value['odds5002']);
                }
            } elseif ($lotteryCode == 5003) {
                $updateField = ['odds_5001_id', 'open_mid', 'update_nums', 'bqc_00', 'trend_00', 'bqc_01', 'trend_01', 'bqc_03', 'trend_03', 'bqc_10', 'trend_10', 'bqc_11', 'trend_11',
                    'bqc_13', 'trend_13', 'bqc_30', 'trend_30', 'bqc_31', 'trend_31', 'bqc_33', 'trend_33', 'create_time', 'modify_time', 'update_time'];
                if (!empty($value['odds5003'])) {
                    $value['odds5003'] = array_combine($updateField, $value['odds5003']);
                }
            } elseif ($lotteryCode == 5005) {
                $updateField = ['odds_5001_id', 'open_mid', 'update_nums', 'score_wins_10', 'trend_10', 'score_wins_20', 'trend_20', 'score_wins_21', 'trend_21', 'score_wins_30', 'trend_30', 'score_wins_31', 'trend_31',
                    'score_wins_32', 'trend_32', 'score_wins_40', 'trend_40', 'score_wins_41', 'trend_41', 'score_wins_42', 'trend_42', 'score_level_00', 'trend_00', 'score_level_11', 'trend_11', 'score_level_22', 'trend_22',
                    'score_level_33', 'trend_33', 'score_negative_01', 'trend_01', 'score_negative_02', 'trend_02', 'score_negative_12', 'trend_12', 'score_negative_03', 'trend_03', 'score_negative_13', 'trend_13',
                    'score_negative_23', 'trend_23', 'score_negative_04', 'trend_04', 'score_negative_14', 'trend_14', 'score_negative_24', 'trend_24', 'score_wins_90', 'trend_90', 'score_level_99', 'trend_99',
                    'score_negative_09', 'trend_09', 'create_time', 'modify_time', 'update_time'];
                if (!empty($value['odds5005'])) {
                    $value['odds5005'] = array_combine($updateField, $value['odds5005']);
                }
            }
            if (array_key_exists($gameDate, $list)) {
                $list[$gameDate]['game'][] = $value;
                $list[$gameDate]['field_num'] += 1;
            } else {
                $list[$gameDate]['game'][] = $value;
                $list[$gameDate]['field_num'] = 1;

                $list[$gameDate]['game_date'] = date('Y-m-d', strtotime($gameDate));
                $list[$gameDate]['week'] = '周' . $weekarray[date('w', strtotime($gameDate))];
            }
        }
        foreach ($list as $val) {
            $scheduleList[] = $val;
        }
        return ['code' => 600, 'msg' => '获取可投注赛程成功', 'data' => $scheduleList];
    }

    /**
     * 北单下注 
     * @param type $lotteryCode  彩种玩法
     * @param type $custNo 会员编号
     * @param type $userId 会员ID
     * @param type $storeId 门店店主ID
     * @param type $storeCode 门店编号
     * @param type $source 来源
     * @param type $sourceId 来源ID
     * @param type $outType 是否自动出票 1：否 2：是
     * @return type
     */
    public function playOrder($lotteryCode, $custNo, $userId, $storeId, $storeCode, $source, $sourceId = '', $outType) {
        $layName = CompetConst::BD_MCN;
        $lotteryType = Constants::LOTTERY_ABBREVI;
        $lotteryName = Constants::LOTTERY;
        $bdChuan = CompetConst::BD_CHUAN;
        $request = \Yii::$app->request;
        $post = $request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $betNums = $orderData['contents']; // 投注内容
        $total = $orderData['total']; //总价
        $countBet = $orderData['count_bet']; // 注数
        $remark = isset($post['remark']) ? $post['remark'] : '';
        $count = 0;
        $playName = [];
        $mCN = CompetConst::BD_M_CHUAN_N;
        $buildPlay = '';
        $buildName = '';
        if (array_key_exists($betNums['play'], $mCN)) {
            $buildPlay = $betNums['play'];
            $buildName = $layName[$buildPlay];
            $betNums['play'] = $mCN[$buildPlay];
        }
        $arr = explode(",", $betNums["play"]);
        if ($arr == array_intersect($arr, $bdChuan[$lotteryCode])) {
            foreach ($arr as $val) {
                $playName[] = $layName[$val];
            }
        } else {
            return ["code" => 109, "msg" => "投注串关方式不对！"];
        }
        $playCode = $betNums["play"];
        $playNum = $betNums["nums"];

        $ret = $this->calculationCount($lotteryCode, $betNums);
        if ($ret["code"] == 0) {
            $count = $ret["result"];
            $odds = json_encode($ret["odds"], JSON_FORCE_OBJECT);
        } else {
            return $ret;
        }
        $multiple = 1;
        $price = Constants::PRICE;
        $subCount = 0;
        $majorData = '';
        if (array_key_exists('major_type', $orderData) && array_key_exists('major_data', $orderData)) {
            $betMoney = 0;
            foreach ($orderData['major_data'] as $vm) {
                if (!is_int($vm['mul']) || $vm['mul'] <= 0) {
                    return \Yii::jsonError(109, '投注倍数格式不对');
                }
                $betMoney += $price * $vm['mul'];
                $subCount += 1;
            }
            $majorType = $orderData['major_type'];
            $majorData = json_encode($orderData['major_data']);
        } else {
            $multiple = $orderData['multiple']; // 倍数
            $subCount = $count;
            $betMoney = $price * $count;
            if ($multiple >= 1) {
                $betMoney *= $multiple;
            } else {
                return ["code" => 2, "msg" => "投注加倍参数错误！"];
            }
            $majorType = 0;
        }
        if ($countBet != $count || $countBet != $subCount || $count != $subCount) {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }
        $totalMoney = $betMoney;
        if ($total != $totalMoney) {
            return [ "code" => 2, "msg" => "投注总金额错误！"];
        }
//        $period = strtotime('+3 hour', (int) $maxTime);
//        $periods = (string) $period;
        $endTime = $ret['limit_time'];
        $periods = $ret['max_time'];
        $insert = [
            "lottery_type" => $lotteryType[$lotteryCode],
            "play_code" => $playCode,
            "play_name" => implode(',', $playName),
            "lottery_id" => $lotteryCode,
            "lottery_name" => $lotteryName[$lotteryCode],
            "periods" => $periods,
            "cust_no" => $custNo,
            "store_id" => $storeId,
            "source_id" => $sourceId,
            "agent_id" => "0",
            "bet_val" => $playNum . "^",
            "bet_double" => $multiple,
            "is_bet_add" => 0,
            "bet_money" => $betMoney,
            "source" => $source,
            "count" => $count,
            "periods_total" => 1,
            "is_random" => 0,
            "win_limit" => 0,
            "is_limit" => 0,
            "odds" => $odds,
            "end_time" => $endTime,
            'user_id' => $userId,
            'store_no' => $storeCode,
            'build_code' => $buildPlay,
            'build_name' => $buildName,
            'major_type' => $majorType,
            'auto_type' => $outType,
            'remark' => $remark
        ];
        return OrderService::selfDoLotterOrder($insert, false, $majorData);
    }

    /**
     * 计算注数
     * @auther GL zyl
     * @param string $lotteryCode 彩种编号
     * @param array $contents 投注内容
     * @return array
     */
    public function calculationCount($lotteryCode, $contents, $isLimit = true) {
        $lottery = Lottery::findOne(["lottery_code" => $lotteryCode, 'status' => 1]);
        if (empty($lottery)) {
            return ["code" => 109, "msg" => "投注失败，此彩种已经停止投注，请选择其他彩种进行投注"];
        }
        $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';

        $betNums = explode("|", $contents["nums"]);
        $playCodes = explode(",", $contents['play']);
        $count = 0;
        $bdChuan = CompetConst::BD_CHUAN;
        if ($playCodes != array_intersect($playCodes, $bdChuan[$lotteryCode])) {
            return ["code" => 109, "msg" => "投注串关方式不对！"];
        }
        if (!is_array($betNums)) {
            return ["code" => 2, "msg" => "投注格式出错"];
        }
        $scheduleMids = $this->getMids($lotteryCode, $betNums);
        $betCounts = [];
        $timeArr = [];
        $result = [];
        $odds = [];
        foreach ($betNums as $v) {
            preg_match($pattern, $v, $result);
            if ($isLimit == true) {
                if (!array_key_exists($result[1], $scheduleMids)) {
                    return ["code" => 415, "msg" => "已选择的场次含有已停售场次，请重新选择"];
                } else {
                    if (!array_key_exists($result[1], $timeArr)) {
                        $timeArr[$result[1]]['max_time'] = $scheduleMids[$result[1]]['start_time'];
                        $timeArr[$result[1]]['end_time'] = $scheduleMids[$result[1]]['endsale_time'];
                    }
                }
            }

            $arr = explode(",", $result[2]);
            $betCounts[] = count($arr);
            if (!array_key_exists($lotteryCode, $odds)) {
                $odds[$lotteryCode] = [];
            }
            $oddStr = 'odds' . $lotteryCode;
            foreach ($arr as $a) {
                $oddField = 'odds_' . $a;
                $odds[$lotteryCode][$result[1]][$a] = $scheduleMids[$result[1]][$oddStr][$oddField];
            }
        }
        foreach ($playCodes as $playCode) {
            $needBall = 0;
            switch ($playCode) {
                case "0101":
                    $needBall = 1;
                    break;
                case "0201":
                    $needBall = 2;
                    break;
                case "0301":
                    $needBall = 3;
                    break;
                case "0401":
                    $needBall = 4;
                    break;
                case "0501":
                    $needBall = 5;
                    break;
                case "0601":
                    $needBall = 6;
                    break;
                case "0701":
                    $needBall = 7;
                    break;
                case "0801":
                    $needBall = 8;
                    break;
                case '0901':
                    $needBall = 9;
                    break;
                case '1001':
                    $needBall = 10;
                    break;
                case '1101':
                    $needBall = 11;
                    break;
                case '1201':
                    $needBall = 12;
                    break;
                case '1301':
                    $needBall = 13;
                    break;
                case '1401':
                    $needBall = 14;
                    break;
                case '1501':
                    $needBall = 15;
                    break;
                default :
                    return ["code" => 2, "msg" => "玩法未开放"];
            }

            if (is_array($betNums) && count($betNums) >= $needBall) {
                $ret = Commonfun::getCombination_array($betCounts, $needBall);
                if (!is_array($ret)) {
                    return ["code" => 2, "msg" => "投注格式出错"];
                }
                foreach ($ret as $nums) {
                    $theCount = 1;
                    foreach ($nums as $v) {
                        $theCount = $theCount * $v;
                    }
                    $count +=$theCount;
                }
            } else {
                return ["code" => 2, "msg" => "投注格式出错"];
            }
        }
        $endTime = min(array_column($timeArr, 'end_time'));
        $maxTime = max(array_column($timeArr, 'max_time'));
        $awardTime = Commonfun::getAwardTime($maxTime);
        return ["code" => 0, "msg" => "获取成功", "result" => $count, "odds" => $odds, "limit_time" => $endTime, 'max_time' => $awardTime];
    }

    /**
     * 获取赛程
     * @param type $lotteryCode
     * @param type $betArr
     * @return array
     */
    public function getMids($lotteryCode, $betArr) {
        $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        $schePlay = CompetConst::BD_SCHEDULE_PLAY;
        if ($lotteryCode != 5006) {
            $playType = 1;
        } else {
            $playType = 2;
        }
        $playField = $schePlay[$lotteryCode];
        $result = [];
        $midArr = [];
        foreach ($betArr as $bv) {
            preg_match($pattern, $bv, $result);
            if (!in_array($result[1], $midArr)) {
                $midArr[] = $result[1];
            }
        }
        $oddStr = ['odds' . $lotteryCode];
        $format = date('Y-m-d H:i:s');
        $scheData = BdSchedule::find()->select(['open_mid', 'start_time', 'endsale_time'])
                ->with($oddStr)
                ->where([">", "start_time", date($format, strtotime("+10 minute"))])
                ->andWhere(["<", "beginsale_time", date($format)])
                ->andWhere([">", "endsale_time", date($format)])
                ->andWhere(["sale_status" => 1, $playField => 1])
                ->indexBy("open_mid")
                ->asArray()
                ->all();

        return $scheData;
    }

    /**
     * 篮球投注验证
     * @auther  GL zyl
     * @param string $lotteryCode 彩种
     * @return array
     */
    public function playVerification($lotteryCode) {
        $layName = CompetConst::BD_MCN;
        $lotteryName = Constants::LOTTERY;
        $request = Yii::$app->request;
        $post = $request->post();
        $betNums = $post['contents']; // 投注内容
        $total = $post['total']; //总价
        $multiple = $post['multiple']; // 倍数
        $countBet = $post['count_bet']; // 注数
        $count = 0;
        $mCN = CompetConst::BD_M_CHUAN_N;
        $flag = 0;
        if (array_key_exists($betNums['play'], $mCN)) {
            $mCNPlay = $betNums['play'];
            $betNums['play'] = $mCN[$betNums['play']];
            $arr = explode(',', $betNums['play']);
            $flag = 1;
        } else {
            $arr = explode(",", $betNums["play"]);
        }
        $playName = [];
        if (is_array($arr)) {
            if ($flag == 0) {
                foreach ($arr as $val) {
                    $playName[] = $layName[$val];
                }
                $playCode = $betNums["play"];
            } else {
                $playName[] = $layName[$mCNPlay];
                $playCode = $mCNPlay;
            }
        } else {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }
        $playNum = $betNums["nums"];
        $limit_time = "";
        $ret = $this->calculationCount($lotteryCode, $betNums);
        if ($ret["code"] == 0) {
            $count = $ret["result"];
            $limit_time = $ret["limit_time"];
            $end_time = $ret['max_time'];
        } else {
            return $ret;
        }
        $price = Constants::PRICE;
        $subCount = 0;
        if (array_key_exists('major_type', $post) && array_key_exists('major_data', $post)) {
            $betMoney = 0;
            foreach ($post['major_data'] as $vm) {
                $betMoney += $price * $vm['mul'];
                $subCount += 1;
            }
        } else {
            $subCount = $count;
            $betMoney = $price * $count;
            if ($multiple >= 1) {
                $betMoney *= $multiple;
            } else {
                return ["code" => 2, "msg" => "投注加倍参数错误！"];
            }
        }
        if ($countBet != $count || $countBet != $subCount || $count != $subCount) {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }
        $totalMoney = $betMoney;
        if ($total != $totalMoney) {
            return [ "code" => 2, "msg" => "投注总金额错误！"];
        }
        return ["code" => 0, "msg" => "投注信息正确！", "data" => ["lottery_name" => $lotteryName[$lotteryCode], "play_name" => (implode(',', $playName)), "play_code" => $playCode, "bet_val" => ($playNum . "^"), "limit_time" => $limit_time, "max_time" => $end_time]];
    }

    /**
     * 生成子单
     * @auther GL zyl
     * @param model $model
     * @return array
     */
    public function productSuborder($model) {
        $infos = [];
        $infos["agent_id"] = $model->agent_id;
        $infos["bet_double"] = $model->bet_double;
        $infos["bet_money"] = $model->bet_money;
        $infos["is_bet_add"] = $model->is_bet_add;
        $infos["lottery_id"] = $model->lottery_id;
        $infos["lottery_name"] = $model->lottery_name;
        $infos["lottery_order_code"] = $model->lottery_order_code;
        $infos["lottery_order_id"] = $model->lottery_order_id;
        $infos["opt_id"] = $model->opt_id;
        $infos["periods"] = $model->periods;
        $infos["status"] = $model->status;
        $infos["cust_no"] = $model->cust_no;
        $infos["count"] = $model->count;
        $contents = trim($model->bet_val, "^");
        $lotteryCode = $model->lottery_id;
        $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        $infos['odds'] = '';
        $betNums = explode("|", $contents);
        $playCodes = explode(",", $model->play_code);
        $playNames = explode(",", $model->play_name);
        $order = [];
        $n = 0;
        $bdChuan = CompetConst::BD_CHUAN;
        if ($playCodes != array_intersect($playCodes, $bdChuan[$lotteryCode])) {
            return ["code" => 109, "msg" => "投注串关方式不对！"];
        }
        foreach ($playCodes as $key => $playCode) {
            $needBall = 0;
            switch ($playCode) {
                case "0101":
                    $needBall = 1;
                    break;
                case "0201":
                    $needBall = 2;
                    break;
                case "0301":
                    $needBall = 3;
                    break;
                case "0401":
                    $needBall = 4;
                    break;
                case "0501":
                    $needBall = 5;
                    break;
                case "0601":
                    $needBall = 6;
                    break;
                case "0701":
                    $needBall = 7;
                    break;
                case "0801":
                    $needBall = 8;
                    break;
                case '0901':
                    $needBall = 9;
                    break;
                case '1001':
                    $needBall = 10;
                    break;
                case '1101':
                    $needBall = 11;
                    break;
                case '1201':
                    $needBall = 12;
                    break;
                case '1301':
                    $needBall = 13;
                    break;
                case '1401':
                    $needBall = 14;
                    break;
                case '1501':
                    $needBall = 15;
                    break;
                default :
                    return ["code" => 2, "msg" => "玩法未开放"];
            }

            if (is_array($betNums) && count($betNums) >= $needBall) {
                $ret = Commonfun::getCombination_array($betNums, $needBall);
                if (!is_array($ret)) {
                    return ["code" => 2, "msg" => "投注格式出错"];
                }
                foreach ($ret as $nums) {
                    $combData = [];
                    $crossData = [];
                    $crossNum = 0;
                    foreach ($nums as $v) {
                        preg_match($pattern, $v, $result);
                        $arr = explode(",", $result[2]);
                        $crossData[$crossNum] = [];
                        foreach ($arr as $a) {
                            $crossData[$crossNum][] = $result[1] . "({$a})";
                        }
                        $crossNum++;
                    }
                    if ($needBall != 1) {
                        $str = '';
                        for ($i = 0; $i < $needBall; $i++) {
                            $str.=',$crossData[' . $i . ']';
                        }
                        eval('$combData[] = \app\modules\common\helpers\Commonfun::proCross_string("|"' . $str . ');');
                    } else {
                        foreach ($crossData as $crossVal) {
                            $combData[] = $crossVal;
                        }
                    }
                    foreach ($combData as $v1) {
                        foreach ($v1 as $v2) {
                            $order[$n]["bet_val"] = $v2;
                            $order[$n]["play_code"] = $playCode;
                            $order[$n]["play_name"] = $playNames[$key];
                            $n++;
                        }
                    }
                }
            } else {
                return ["code" => 2, "msg" => "投注格式出错"];
            }
        }
        $infos["content"] = $order;
        $result = OrderService::insertDetail($infos);
        if ($result["error"] === true) {
            return ["code" => 0, "msg" => "操作成功"];
        } else {
            return ["code" => 2, "msg" => "操作失败", "err" => $result];
        }
    }

    /**
     * 获取北单投注单
     * @auther  GL zyl
     * @param type $lotteryOrderCode  订单编号
     * @param type $cust_no  会员编号
     * @param type $orderId  订单ID
     * @return type
     */
    public function getOrder($lotteryOrderCode, $cust_no = '', $orderId = '') {
        $where['lottery_order.lottery_order_code'] = $lotteryOrderCode;
        if (!empty($cust_no)) {
            $where['lottery_order.cust_no'] = $cust_no;
        }
        if (!empty($orderId)) {
            $where['lottery_order.lottery_order_id'] = $orderId;
        }
        $status = Constants::ORDER_STATUS;
        $majorArr = CompetConst::MAJOR_ARR;
        $lotOrder = LotteryOrder::find()
                ->select("bet_val,odds,lottery_order.lottery_id,lottery_order.lottery_name,refuse_reason,bet_money,lottery_order_code,lottery_order.create_time,lottery_order_id,lottery_order.status,win_amount,play_code,play_name,bet_double,count,periods,s.store_name,s.store_code,s.telephone as phone_num,major_type,build_code,build_name,l.lottery_pic,lottery_order.deal_status")
                ->leftJoin('store as s', 's.store_code = lottery_order.store_no and s.status = 1')
                ->leftJoin('lottery l', 'l.lottery_code = lottery_order.lottery_id')
                ->where($where)
                ->asArray()
                ->one();
        if ($lotOrder == null) {
            return ["code" => 2, "msg" => "查询结果不存在"];
        }
        $odds = [];
        if (!empty($lotOrder["odds"])) {
            $odds = json_decode($lotOrder["odds"], 256);
        }
        $lotOrder["status_name"] = $status[$lotOrder["status"]];
        $lotOrder['award_time'] = date('Y-m-d H:i:s', $lotOrder['periods']);
        $lotOrder['major_name'] = $majorArr[$lotOrder['major_type']];
        if (empty($lotOrder['build_code'])) {
            $lotOrder['build_code'] = '';
            $lotOrder['build_name'] = '';
        }
//        $playCodeArr = explode(',', $lotOrder['play_code']);
//        if (in_array(1, $playCodeArr)) {
//            if ($lotOrder['lottery_id'] == 3011) {
//                $lotOrder['lottery_name'] = '混合单关';
//            } else {
//                $lotOrder['lottery_name'] .= '(单)';
//            }
//        }
        $data = $lotOrder;
        $betVal = trim($lotOrder["bet_val"], "^");
        $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        $result = [];
        $betNums = explode("|", $betVal);
        $mids = [];
        $bets = [];
        foreach ($betNums as $key => $ball) {
            $bets[$key] = [];
            preg_match($pattern, $ball, $result);
            $n = 0;
            $bets[$key]["mid"] = $result[1];
            $mids[] = $result[1];
            $arr = explode(",", $result[2]);
            foreach ($arr as $v) {
                $bets[$key]["lottery"][$n] = [];
                $bets[$key]["lottery"][$n]["bet"] = $v;
                $bets[$key]["lottery"][$n]["play"] = $lotOrder["lottery_id"];
//                $bets[$key]["lottery"][$n]["odds"] = isset($odds[$lotOrder["lottery_id"]][$result[1]][$v]) ? $odds[$lotOrder["lottery_id"]][$result[1]][$v] : "赔率"; //赔率
                $n++;
            }
        }
        $field = ['bd_schedule.periods', 'bd_schedule.open_mid', 'bd_schedule.schedule_mid', 'bd_schedule.home_name', 'bd_schedule.visit_name', 'sr.result_5001', 'sr.result_5002', 'sr.result_5003', 'sr.result_5004',
            'sr.result_5005', 'sr.result_5006', 'sr.status', 'sr.odds_5001', 'sr.odds_5002', 'sr.odds_5003', 'sr.odds_5004', 'sr.odds_5005', 'sr.odds_5006', 'bd_schedule.spf_rq_nums', 'bd_schedule.sfgg_rf_nums', 'bd_schedule.bd_sort'];
        $schedules = BdSchedule::find()->select($field)
                ->join("left join", "bd_schedule_result sr", "bd_schedule.open_mid = sr.open_mid")
                ->where(["in", "bd_schedule.open_mid", $mids])
                ->indexBy("open_mid")
                ->asArray()
                ->all();
        $plays = Constants::LOTTERY;
        $bf = Constants::COMPETING_3007_RESULT;
        foreach ($bets as &$val) {
            $schedule = $schedules[$val["mid"]];
            foreach ($val["lottery"] as $key => $v) {
                $val["lottery"][$key]["play_name"] = $plays[$v["play"]];
                if($schedule['status'] == 2 && $v['bet'] == $schedule['result_' . $lotOrder['lottery_id']]) {
                    $val["lottery"][$key]["odds"] =  $schedule['odds_' . $lotOrder['lottery_id']];
                } else {
                    $val["lottery"][$key]["odds"] = $odds[$lotOrder["lottery_id"]][$schedule['open_mid']][$v['bet']];
                }
                
            }

            $val['visit_short_name'] = $schedule['visit_name'];
            $val['home_short_name'] = $schedule['home_name'];
            $val["schedule_result_5001"] = ($schedule["status"] != 2) ? "" : $schedule["result_5001"];
            $val["schedule_result_bf"] = ($schedule["status"] != 2) ? "" : $schedule["result_5005"];
            $val['status'] = $schedule['status'];
            if (!empty($val["schedule_result_bf"])) {
                if (!isset($bf[$val["schedule_result_bf"]])) {
                    $val["schedule_result_bf"] = str_replace(" ", "", $val["schedule_result_bf"]);
                    if (isset($bf[$val["schedule_result_bf"]])) {
                        $val["schedule_result_5005"] = $bf[$val["schedule_result_bf"]];
                    } else {
                        $bfBalls = explode(":", $val["schedule_result_bf"]);
                        if ($bfBalls[0] > $bfBalls[1]) {
                            $val["schedule_result_5005"] = "90";
                        } else if ($bfBalls[0] == $bfBalls[1]) {
                            $val["schedule_result_5005"] = "99";
                        } else {
                            $val["schedule_result_5005"] = "09";
                        }
                    }
                } else {
                    $val["schedule_result_5005"] = $bf[$val["schedule_result_bf"]];
                }
            } else {
                $val["schedule_result_5005"] = "";
            }
            $val["schedule_result_5002"] = ($schedule["status"] != 2) ? "" : $schedule["result_5002"];
            $val["schedule_result_5003"] = ($schedule["status"] != 2) ? "" : $schedule["result_5003"];
            $val["schedule_result_5004"] = ($schedule["status"] != 2) ? "" : $schedule["result_5004"];
            $val["schedule_result_5006"] = ($schedule["status"] != 2) ? "" : $schedule["result_5006"];
            $val["rq_nums"] = $schedule["spf_rq_nums"];
            $val['rf_nums'] = $schedule['sfgg_rf_nums'];
            $val['bd_sort'] = $schedule['bd_sort'];
        }
        $data["contents"] = $bets;
        $data['discount_data'] = PayService::getDiscount(['order_code' => $lotteryOrderCode]);
        return [
            "code" => 600,
            "msg" => "获取成功",
            "result" => $data
        ];
    }

    /**
     * 获取北单联赛
     * @param type $lotteryCode 玩法彩种
     * @return type
     */
    public function getBdLeague($lotteryCode) {
        if ($lotteryCode != 5006) {
            $playType = 1;
        } else {
            $playType = 2;
        }
        $bdLeague = BdSchedule::find()->select(['league_code', 'league_name'])
                ->where(['sale_status' => 1, 'play_type' => $playType])
                ->andWhere(['>', 'start_time', date('Y-m-d H:i:s', strtotime("+10 minute"))])
                ->andWhere(['<', 'beginsale_time', date('Y-m-d H:i:s')])
                ->andWhere(['>', 'endsale_time', date('Y-m-d H:i:s')])
                ->groupBy('league_code')
                ->asArray()
                ->all();
        return $bdLeague;
    }

    /**
     * 获取历史交锋
     * @auther GL zyl
     * @param type $mid
     * @return type
     */
    public function getHistoryCount($mid) {
        $field = ['h.double_play_num', 'h.num3', 'h.num1', 'h.num0', 'h.home_num_3', 'h.home_num_1', 'h.home_num_0', 'h.visit_num_3', 'h.visit_num_1', 'h.visit_num_0', 'h.home_team_rank', 'h.visit_team_rank',
            'h.scale_3010_3', 'h.scale_3010_1', 'h.scale_3010_0', 'h.scale_3006_3', 'h.scale_3006_1', 'h.scale_3006_0', 'h.europe_odds_3', 'h.europe_odds_1', 'h.europe_odds_0', 'h.home_team_league', 'h.visit_team_league',
            'p.json_data', 'p.pre_result_title', 's.home_name', 's.visit_name', 's.league_name'];
        $data = (new Query())->select($field)
                ->from("bd_history_count h")
                ->leftJoin('bd_pre_result p', 'p.schedule_mid = h.schedule_mid')
                ->leftJoin('bd_schedule s', 's.open_mid = h.schedule_mid')
                ->where(["h.schedule_mid" => $mid, 's.play_type' => 1])
                ->one();

        if (empty($data)) {
            return ['code' => 109, 'msg' => '未找到对应赛程统计'];
        }
        $jsonData = json_decode($data["json_data"], true);
        $data["avg_visit_per"] = sprintf("%.1f", $jsonData["avg_visit_per"]);
        $data["avg_home_per"] = sprintf("%.1f", $jsonData["avg_home_per"]);
        return ['code' => 600, 'msg' => '获取成功', 'data' => $data];
    }

    /**
     * 获取赛程信息
     * @param type $mid
     * @return type
     */
    public function getScheduleInfo($mid) {
        $status = [
            "0" => "未开赛",
            "1" => "比赛中",
            "2" => "完结",
            "3" => "取消",
            "4" => "延迟",
            "5" => "完结",
            "6" => "未出赛果",
            "7" => "腰斩"
        ];
        $field = ["s.home_name home_team_name", "s.visit_name visit_team_name", "h.home_team_rank", "h.visit_team_rank", "p.average_home_percent", "p.average_visit_percent", "s.home_code home_team_mid", "t1.team_img as home_team_img",
            "s.visit_code visit_team_mid", "t2.team_img as visit_team_img", "s.start_time", "s.bd_sort", "h.visit_team_league", "h.home_team_league", 's.sale_status endsale_status', 's.endsale_time', 'sr.result_5005',
            'sr.status', 's.league_name league_short_name', 's.spf_rq_nums', 's.sfgg_rf_nums'];
        $data = (new Query())->select($field)
                ->from("bd_schedule s")
                ->join("left join", "bd_team t1", "t1.team_code=s.home_code")
                ->join("left join", "bd_team t2", "t2.team_code=s.visit_code")
                ->join("left join", "bd_history_count h", "h.schedule_mid=s.open_mid")
                ->join("left join", "bd_pre_result p", "p.schedule_mid=s.open_mid")
                ->join('left join', 'bd_schedule_result sr', 'sr.open_mid = s.open_mid')
                ->where(["s.open_mid" => $mid, 's.play_type' => 1])
                ->one();

        if (empty($data)) {
            return ['msg' => '未找到该赛程', 'data' => null];
        }
        $result = [];
        $result['result_5005'] = $data['result_5005'];
        $result['status'] = $data['status'];
//        $result = (new Query())->select("schedule_result_3007,status")->from("schedule_result")->where(["schedule_mid" => $mid])->one();
        $result['status_name'] = $status[$data['status']];
        if (strtotime($data['endsale_time']) <= time()) {
            $data['endsale_status'] = 2;
        }
        if (isset($status[$data["status"]])) {
            $data["status_name"] = $status[$data["status"]];
        } else {
            $data["status_name"] = "";
        }
        return ['msg' => '获取成功', 'data' => ['info' => $data, 'result' => $result]];
    }

    /**
     * 双方历史交战比赛
     * @param type $mid
     * @param type $teamType
     * @param type $size
     * @param type $sameLeague
     * @return type
     */
    public function getDoubleHistoryMatch($mid, $teamType, $size) {
        $schedule = BdSchedule::findOne(["open_mid" => $mid, 'play_type' => 1]);
        if ($schedule == null) {
            return['msg' => '未找到该赛程', 'data' => null];
        }

//        $t1 = \app\modules\common\models\Team::findOne(["team_id" => $schedule->home_team_id]);
//        $t2 = \app\modules\common\models\Team::findOne(["team_id" => $schedule->visit_team_id]);
        $homeTeamMid = $schedule->home_code;
        $visitTeamMid = $schedule->visit_code;
        $query = (new Query())->select("schedule_mid,league_code,league_name,play_time,home_team_name,home_team_mid,visit_team_mid,visit_team_name,result_3007,result_3009_b,result_3010")->from("bd_schedule_history")->where(["<", "play_time", $schedule->start_time]);
        if ($teamType == 1) {
            $query = $query->andWhere(["home_team_mid" => $homeTeamMid, "visit_team_mid" => $visitTeamMid])->andWhere(['!=', 'result_3007', '']);
        } else if ($teamType == 2) {
            $query = $query->andWhere(["home_team_mid" => $visitTeamMid, "visit_team_mid" => $homeTeamMid])->andWhere(['!=', 'result_3007', '']);
        } else {
            $query = $query->andWhere(["or", ["home_team_mid" => $homeTeamMid, "visit_team_mid" => $visitTeamMid], ["home_team_mid" => $visitTeamMid, "visit_team_mid" => $homeTeamMid]])->andWhere(['!=', 'result_3007', '']);
        }

//        if ($sameLeague == 1) {
//            $league = (new Query())->select("league_code")->from("league")->where(["league_id" => $schedule->league_id])->one();
//            $query = $query->andWhere(["league_code" => $schedule->league_code]);
//        }
        $doubleConData = $query->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all();
        $data = $this->scheduleHistoryDeal($doubleConData, $homeTeamMid);
        return [ 'msg' => '获取成功', 'data' => $data];
    }

    /**
     * 单方历史交战比赛
     * @param type $mid
     * @param type $teamMid
     * @param type $size
     * @param type $sameLeague
     * @param type $teamType
     * @return type
     */
    public function getHistoryMatch($mid, $teamMid, $size, $teamType) {
        $schedule = BdSchedule::findOne(["open_mid" => $mid, 'play_type' => 1]);
        if ($schedule == null) {
            return[ 'msg' => '未找到该赛程', 'data' => null];
        }

        $query = (new Query())->select("schedule_mid,league_code,league_name,play_time,home_team_name,home_team_mid,visit_team_mid,visit_team_name,result_3007,result_3009_b,result_3010")->from("bd_schedule_history")->where(["<", "play_time", $schedule->start_time])->andWhere(["!=", "result_3007", ""]);
        if ($teamType == 1) {
            $query = $query->andWhere(["home_team_mid" => $teamMid]);
        } else if ($teamType == 2) {
            $query = $query->andWhere(["visit_team_mid" => $teamMid]);
        } else {
            $query = $query->andWhere(["or", ["visit_team_mid" => $teamMid], ["home_team_mid" => $teamMid]]);
        }
//        if ($sameLeague == 1) {
//            $league = (new Query())->select("league_code")->from("league")->where(["league_id" => $schedule->league_id])->one();
//            $query = $query->andWhere(["league_code" => $schedule->league_code]);
//        }
        $conData = $query->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all();
        $data = $this->scheduleHistoryDeal($conData, $teamMid);
        return [ 'msg' => '获取成功', 'data' => $data];
    }

    /**
     * 实力对比
     * @param type $mid
     * @return type
     */
    public function getStrengthContrast($mid) {
        $result = (new Query())->select("json_data,pre_result_title")->from("bd_pre_result")->where(["schedule_mid" => $mid])->one();
        if (empty($result)) {
            return['msg' => '未找到该赛程', 'data' => null];
        }
        if (empty($result["json_data"])) {
            return['msg' => '该赛程未有实力分析', 'data' => null];
        }
        $data = json_decode($result["json_data"], true);
        $data["avg_visit_per"] = sprintf("%.1f", $data["avg_visit_per"]);
        $data["avg_home_per"] = sprintf("%.1f", $data["avg_home_per"]);
        $data['pre_result_title'] = $result['pre_result_title'];
        return ['msg' => '获取成功', 'data' => $data];
    }

    /**
     * 球队未来赛程
     * @param type $mid
     * @return type
     */
    public function getFutureSchedule($mid) {
        $schedule = BdSchedule::findOne(["open_mid" => $mid, 'play_type' => 1]);
        if (empty($schedule)) {
            return['msg' => '未找到该赛程', 'data' => null];
        }
//
//        $t1 = \app\modules\common\models\Team::findOne(["team_id" => $schedule->home_team_id]);
//        $t2 = \app\modules\common\models\Team::findOne(["team_id" => $schedule->visit_team_id]);
        $homeTeamMid = $schedule->home_code;
        $visitTeamMid = $schedule->visit_code;

        $homeQuery = (new Query())->select("schedule_mid,league_code,league_name,play_time,home_team_name,home_team_mid,visit_team_mid,visit_team_name")->from("bd_schedule_history")->where([">", "play_time", $schedule->start_time])->andWhere(["or", ["visit_team_mid" => $homeTeamMid], ["home_team_mid" => $homeTeamMid]]);
        $homeList = $homeQuery->orderBy("play_time asc")->all();
        foreach ($homeList as &$val) {
            $val["later_days"] = ceil((strtotime($val["play_time"]) - time()) / 24 / 3600);
        }
        $visitQuery = (new Query())->select("schedule_mid,league_code,league_name,play_time,home_team_name,home_team_mid,visit_team_mid,visit_team_name")->from("bd_schedule_history")->where([">", "play_time", $schedule->start_time])->andWhere(["or", ["visit_team_mid" => $visitTeamMid], ["home_team_mid" => $visitTeamMid]]);
        $visitList = $visitQuery->orderBy("play_time asc")->all();
        foreach ($visitList as &$val) {
            $val["later_days"] = ceil((strtotime($val["play_time"]) - time()) / 24 / 3600);
        }
        $data["home_list"] = $homeList;
        $data["visit_list"] = $visitList;
        return ['msg' => '获取成功', 'data' => $data];
    }

    /**
     * 获取预测赛果
     * @param type $mid
     * @return type
     */
    public function getPreResult($mid) {
        $info = (new Query())->select("pre_result_title,pre_result_3010,pre_result_3007,confidence_index,expert_analysis")->from("bd_pre_result")->where(["schedule_mid" => $mid])->one();
        $list = (new Query())->select("content")->from("bd_schedule_remind")->where(["schedule_mid" => $mid, 'schedule_type' => 3])->all();
        $odds = $this->getOdds($mid);
        return ['info' => $info, 'list' => $list, 'odds' => $odds];
    }

    /**
     * 获取亚盘赔率
     * @param type $mid
     * @return type
     */
    public function getAsianHandicap($mid) {
        $field = ["a.company_name as company_name", "a.handicap_name as begin_handicap_name", "a.home_discount as begin_home_discount", "a.let_index as begin_let_index", "a.visit_discount as begin_visit_discount",
            "b.handicap_name as handicap_name", "b.home_discount as home_discount", "b.let_index as let_index", "b.visit_discount as visit_discount", "a.home_discount_trend as begin_home_discount_trend",
            "a.visit_discount_trend as begin_visit_discount_trend", "b.home_discount_trend as home_discount_trend", "b.visit_discount_trend as visit_discount_trend"];
        $data = (new Query())->select($field)->from("bd_asian_handicap a")->join("left join", "bd_asian_handicap b", "a.schedule_mid=b.schedule_mid and a.company_name=b.company_name and b.handicap_type=2")->where(["a.schedule_mid" => $mid, "a.handicap_type" => 1])->all();
        return $data;
    }

    /**
     * 获取欧盘赔率
     * @param type $mid
     * @return type
     */
    public function getEuropeOdds($mid) {
        $field = ["a.company_name as company_name", "a.handicap_name as begin_handicap_name", "a.odds_3 as begin_odds_3", "a.odds_1 as begin_odds_1", "a.odds_0 as begin_odds_0", "b.handicap_name as handicap_name",
            "b.odds_3 as odds_3", "b.odds_1 as odds_1", "b.odds_0 as odds_0", 'a.odds_3_trend as begin_odds_3_trend', 'a.odds_1_trend as begin_odds_1_trend', 'a.odds_0_trend as begin_odds_0_trend',
            'b.odds_3_trend as odds_3_trend', 'b.odds_1_trend as odds_1_trend', 'b.odds_0_trend as odds_0_trend',];
        $data = (new Query())->select($field)->from("bd_europe_odds a")->join("left join", "bd_europe_odds b", "a.schedule_mid=b.schedule_mid and a.company_name=b.company_name and b.handicap_type=2")->where(["a.schedule_mid" => $mid, "a.handicap_type" => 1])->all();
        return $data;
    }

    /**
     * 获取比赛实况
     * @param type $mid
     * @return type
     */
    public function getScheduleLives($mid) {
        $scheduleEvents = (new Query())->select(["team_type", "team_name", "event_type", "event_type_name", "event_content", "event_time"])->from("bd_schedule_event")->where(["schedule_mid" => $mid])->orderBy("event_time asc")->all();
        $scheduleTechnic = (new Query())->select(["home_ball_rate", "visit_ball_rate", "home_shoot_num", "visit_shoot_num", "home_shoot_right_num", "visit_shoot_right_num", "home_corner_num", "visit_corner_num", "home_foul_num", "visit_foul_num"])->from("bd_schedule_technic")->where(["schedule_mid" => $mid])->one();
        $scheduleResult = (new Query())->select(["result_5005"])->from("bd_schedule_result")->where(["open_mid" => $mid, 'play_type' => 1])->one();
        return ['events' => $scheduleEvents, 'technic' => $scheduleTechnic, "schedule_result_3007" => $scheduleResult["schedule_result_3007"]];
    }

    /**
     * 赛程历史处理
     * @auther GL ctx
     * @return array
     */
    public function scheduleHistoryDeal($data, $mid, $needList = true) {
        $result = [];
        $count = count($data);
        $num_3 = 0;
        $num_1 = 0;
        $num_0 = 0;
        $num_home_3 = 0;
        $num_home_1 = 0;
        $num_home_0 = 0;
        $gainBalls = 0;
        $loseBalls = 0;
        $key = 0;
        $result["list"] = [];
        foreach ($data as $val) {
            $arr = explode(":", $val["result_3007"]);
            if (count($arr) != 2) {
                continue;
            }
            if ($mid == $val["home_team_mid"]) {
                $homeJq = $arr[0];
                $visitJq = $arr[1];
            } else {
                $homeJq = $arr[1];
                $visitJq = $arr[0];
            }
            $gainBalls+=$homeJq;
            $loseBalls+=$visitJq;
            if ($homeJq > $visitJq) {
                $val["result_3010_home"] = 3;
                $val["result_3010_home_name"] = "胜";
                $num_home_3++;
            } else if ($homeJq == $visitJq) {
                $val["result_3010_home"] = 1;
                $val["result_3010_home_name"] = "平";
                $num_home_1++;
            } else {
                $val["result_3010_home"] = 0;
                $val["result_3010_home_name"] = "负";
                $num_home_0++;
            }

            if ($arr[0] > $arr[1]) {
                $num_3++;
            } else if ($arr[0] == $arr[1]) {
                $num_1++;
            } else {
                $num_0++;
            }
            $result["list"][$key] = $val;
            $key++;
        }
        $result["num_3"] = $num_3;
        $result["num_1"] = $num_1;
        $result["num_0"] = $num_0;
        $result["num_home_3"] = $num_home_3;
        $result["num_home_1"] = $num_home_1;
        $result["num_home_0"] = $num_home_0;
        if ($needList == true) {
            $result["count"] = $count;
        } else {
            unset($result["list"]);
            $result["integral"] = $num_3 * 3 + $num_1;
            $result["average_gain_balls"] = 0 ? 0 : number_format($gainBalls / $count, 1);
            $result["average_lose_balls"] = 0 ? 0 : number_format($loseBalls / $count, 1);
        }
        return $result;
    }

    /**
     * 获取赔率
     * @auther GL zyl
     * @param type $mid
     * @return type
     */
    public function getOdds($mid) {
        $oddStr = ['odds5001', 'odds5002', 'odds5003', 'odds5004', 'odds5005'];
        $field = ['bd_schedule.open_mid', 'bd_schedule.schedule_mid', 'bd_schedule.visit_name', 'bd_schedule.home_name', 'h.scale_3010_3', 'h.scale_3010_1', 'h.scale_3010_0', 'h.scale_3006_3', 'h.scale_3006_1', 'h.scale_3006_0', 'bd_schedule.spf_rq_nums', 'bd_schedule.sfgg_rf_nums'];
        $scheOdds = BdSchedule::find()->select($field)
                ->leftJoin('history_count as h', 'h.schedule_mid = bd_schedule.open_mid')
                ->with($oddStr)
                ->where(['bd_schedule.open_mid' => $mid, 'bd_schedule.play_type' => 1])
                ->asArray()
                ->all();
        return $scheOdds;
    }

    /**
     * 获取处理明细
     * @param type $lotteryOrderCode
     * @param type $conditionData
     * @param type $page
     * @param type $size
     * @param type $isStore
     * @return type
     */
    public function getDetail($lotteryOrderCode, $page, $size) {
        $status = Constants::ORDER_STATUS;
        $lottery = Constants::LOTTERY;
        $schedulePlay = CompetConst::SCHEDULE_PLAY;
        $lotOrder = LotteryOrder::find()
                ->select(["lottery_order_id", "odds", "bet_val", "lottery_id", "lottery_name", "build_code", "build_name", "play_name", "play_code", "bet_money", "lottery_name"])
                ->where(["lottery_order_code" => $lotteryOrderCode])
                ->asArray()
                ->one();
        if ($lotOrder == null) {
            return \Yii::jsonError(109, '查询结果不存在');
        }
        $odds = [];
        if (!empty($lotOrder["odds"])) {
            $odds = json_decode($lotOrder["odds"], 256);
        }
        $total = BettingDetail::find()->where(['lottery_order_id' => $lotOrder['lottery_order_id']])->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $bettingDetails = (new Query())
                ->select("betting_detail_id,lottery_order_id,lottery_order_code,lottery_id,lottery_name,play_name,play_code,bet_double,win_amount,bet_val,status,win_level,bet_money")
                ->from("betting_detail")
                ->where(["lottery_order_id" => $lotOrder["lottery_order_id"]])
                ->limit($size)
                ->offset($offset)
                ->all();
        $betVal = trim($lotOrder["bet_val"], "^");
        $patternDetail = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';

        $betNums = explode("|", $betVal);
        foreach ($betNums as $ball) {
            preg_match($pattern, $ball, $result);
            $mids[] = $result[1];
        }
        if ($lotOrder['lottery_id'] != 5006) {
            $playType = 1;
        } else {
            $playType = 2;
        }
        $field = ["bd_schedule.open_mid", "bd_schedule.bd_sort", "bd_schedule.visit_name", "bd_schedule.home_name", "bd_schedule.spf_rq_nums", "bd_schedule.sfgg_rf_nums", "sr.result_5005 result_qcbf",
            "sr.result_bcbf", "sr.result_" . $lotOrder['lottery_id'], "sr.odds_" . $lotOrder['lottery_id'], 'sr.status'];
        $schedules = BdSchedule::find()->select($field)
                ->leftJoin('bd_schedule_result sr', "sr.open_mid = bd_schedule.open_mid and sr.play_type = {$playType}")
                ->where(["in", "bd_schedule.open_mid", $mids])
                ->andWhere(['bd_schedule.play_type' => $playType])
                ->indexBy("open_mid")
                ->asArray()
                ->all();
        $betPlay = [];
        foreach ($bettingDetails as &$val) {
            $betNums = explode("|", $val["bet_val"]);
            $val["bet"] = [];
            foreach ($betNums as $key => $ball) {
                preg_match($patternDetail, $ball, $result);
                $mid = $result[1];
                $theOdds = ($schedules[$mid]['status'] != 2) ? $odds[$lotOrder["lottery_id"]][$mid][$result[2]] : $schedules[$mid]['odds_' . $lotOrder['lottery_id']];
//                        isset($odds[$lotOrder["lottery_id"]][$mid][$result[2]]) ? $odds[$lotOrder["lottery_id"]][$mid][$result[2]] : "赔率";
                $betPlay[$lotOrder["lottery_id"]][$result[2]] = $schedulePlay[$lotOrder['lottery_id']][$result[2]];
                $val["bet"][] = $schedules[$mid]["bd_sort"] . ($lotOrder["lottery_id"] == '5001' ? '[' . $schedules[$mid]["spf_rq_nums"] . ']' : '') . '(' . $betPlay[$lotOrder["lottery_id"]][$result[2]] . '|' . $theOdds . ')';
                $val["content"][$key] = [];
                $val["content"][$key]["bd_sort"] = $schedules[$mid]["bd_sort"];
                $val["content"][$key]["lottery_code"] = $lotOrder["lottery_id"];
                $val["content"][$key]["rq_nums"] = $schedules[$mid]["spf_rq_nums"];
                $val["content"][$key]["rf_nums"] = $schedules[$mid]["sfgg_rf_nums"];
                $val["content"][$key]["bet_play"] = $betPlay[$lotOrder["lottery_id"]][$result[2]];
                $val['content'][$key]['bet_code'] = $result[2];
                $val["content"][$key]["bet_odds"] = $theOdds; //赔率
                $val['content'][$key]['visit_team_name'] = $schedules[$mid]['visit_name'];
                $val['content'][$key]['home_team_name'] = $schedules[$mid]['home_name'];
                $srOpen = 'result_' . $lotOrder["lottery_id"];
                $val['content'][$key]['result'] = $schedules[$mid][$srOpen];
                $val["content"][$key]["bet_play"] = $schedulePlay[$lotOrder['lottery_id']][$result[2]];
            }
            $val['status_name'] = $status[$val['status']];
            $val["bet"] = implode("x", $val["bet"]);
        }
        if (!empty($lotOrder['build_code'])) {
            $playStr = $lotOrder['build_name'];
        } else {
            $playStr = $lotOrder['play_name'];
        }
        $betAbb = $lotOrder['lottery_name'];
        return ["code" => 600, "msg" => "获取成功", "result" => ['page' => $page, 'pages' => $pages, 'size' => count($bettingDetails), 'total' => $total, 'data' => $bettingDetails, 'count_sche' => count($mids), 'play_str' => $playStr, 'order_money' => $lotOrder['bet_money'], 'bet_abb' => $betAbb]];
    }

}

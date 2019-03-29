<?php

namespace app\modules\openapi\services;

use app\modules\common\models\Schedule;
use app\modules\competing\models\LanSchedule;
use app\modules\common\helpers\Constants;
use app\modules\competing\services\BasketService;
use app\modules\common\helpers\Commonfun;
use app\modules\common\models\FootballFourteen;
use app\modules\common\models\UserFunds;
use app\modules\competing\helpers\CompetConst;
use app\modules\common\models\LotteryRecord;
use app\modules\common\services\PayService;
use app\modules\common\models\ApiOrder;
use app\modules\common\services\OrderService;
use app\modules\orders\helpers\OrderDeal;
use yii\base\Exception;
use app\modules\competing\services\FootballService;
use app\modules\orders\models\AutoOutOrder;
use app\modules\orders\helpers\SzcDeal;
use app\modules\numbers\helpers\SzcConstants;
use app\modules\competing\services\BdService;
use app\modules\competing\services\WorldcupService;
use app\modules\orders\models\MajorData;
use app\modules\orders\helpers\DetailDeal;

class PlayOrderService {

    public function getExistOrder($orderId, $userId, $status) {
//        $order = LotteryOrder::find()->select(['lottery_order_code', 'status'])->where(['user_id' => $userId, 'source_id' => $orderId])->asArray()->one();
        $where = ['in', 'status', $status];
        $orderCount = ApiOrder::find()->where(['third_order_code' => $orderId, 'user_id' => $userId])->andWhere($where)->count();
        return $orderCount;
    }

    public function getUserFunds($custNo) {
        $userFunds = UserFunds::find()->select(['able_funds', 'all_funds', 'ice_funds', 'no_withdraw'])->where(['cust_no' => $custNo])->asArray()->one();
        return $userFunds;
    }

    public function getNewBet($lotteryCode, $betNums, $pCode) {
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $mCN = CompetConst::M_CHUAN_N;
        $playCodeName = Constants::MANNER;
        $thirdPlayCode = Constants::THIRD_MCHUANN;
        if ($lotteryCode != 3011 && $lotteryCode != 3005) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $result = [];
        $betNumsArr = explode("|", rtrim($betNums, '^'));
        $openMids = [];
        foreach ($betNumsArr as $val) {
            preg_match($pattern, $val, $result);
            if (!in_array($result[1], $openMids)) {
                $openMids[] = $result[1];
            }
        }
        $pArr = explode(',', $pCode);
        $plays = '';
        foreach ($pArr as $it) {
            $plays .= $thirdPlayCode[$it] . ',';
        }
        $plays = rtrim($plays, ',');
        $mids = $this->getScheduleMid($lotteryCode, $openMids);
        $buildPlay = '';
        $buildName = '';
        if (array_key_exists($plays, $mCN)) {
            $buildPlay = $plays;
            $buildName = $playCodeName[$buildPlay];
            $plays = $mCN[$buildPlay];
        }
        $arr = explode(",", $plays);
        $detailDeal = new DetailDeal();
        $existCode = $detailDeal->doComb($openMids, 1, $arr);
        if(empty($existCode)) {
            return ['code' => 20002, 'msg' => '投注注码错误！请注意串关'];
        }
        foreach ($arr as $val) {
            $playName[] = $playCodeName[$val];
        }
        $playNameStr = implode(',', $playName);
        $playStr = $plays;
        foreach ($betNumsArr as &$val) {
            preg_match($pattern, $val, $result);
            $result[1] = $mids[$result[1]]['schedule_mid'];
            if ($lotteryCode != 3011 && $lotteryCode != 3005) {
                $val = $result[1] . '(' . $result[2] . ')';
            } else {
                $val = $result[1] . $result[2];
            }
        }
        $newBetNums = implode('|', $betNumsArr);
        if (in_array($lotteryCode, $football)) {
            $competService = new FootballService();
            $data = $competService->getCompetingCount($lotteryCode, ['nums' => $newBetNums, 'play' => $plays], true);
        } else {
            $competService = new BasketService();
            $data = $competService->calculationCount($lotteryCode, ['nums' => $newBetNums, 'play' => $plays], true);
        }
        if ($data['code'] != 0) {
            if ($data['code'] == 2 || $data['code'] == 415) {
                return ['code' => 20002, 'msg' => $data['msg']];
            } elseif ($data['code'] == 109) {
                return ['code' => 3, 'msg' => $data['msg']];
            }
        }
        return ['code' => 600, 'msg' => '成功', 'data' => ['bet_nums' => $newBetNums, 'count' => $data['result'], 'odds' => json_encode($data['odds'], JSON_FORCE_OBJECT), 'end_time' => $data['limit_time'], 'play_name' => $playNameStr,
                'play_code' => $playStr, 'build_code' => $buildPlay, 'build_name' => $buildName, 'max_time' => $data['max_time']]];
    }

    public function getScheduleMid($lotteryCode, $openMids) {
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        if (in_array($lotteryCode, $football)) {
            $mids = Schedule::find()->select(['schedule_mid', 'open_mid'])->where(['in', 'open_mid', $openMids])->indexBy('open_mid')->asArray()->all();
        } else {
            $mids = LanSchedule::find()->select(['schedule_mid', 'open_mid'])->where(['in', 'open_mid', $openMids])->indexBy('open_mid')->asArray()->all();
        }
        return $mids;
    }

    public function getOptionalCount($betNums, $periods, $lotteryCode, $playCode) {
        $playCodeName = Constants::OPTIONAL_PLAYNAME;
        $isOk = FootballFourteen::find()->where(['periods' => $periods, 'status' => 1])->asArray()->one();
        if (empty($isOk)) {
            return ['code' => 40013, 'msg' => '投注失败!此期非当前期，请重新投注'];
        }
        $numArr = explode(',', rtrim($betNums, '^'));
        if (count($numArr) != 14) {
            return ['code' => 20002, 'msg' => '投注失败！投注场次数不对，应为14场'];
        }
        if ($lotteryCode == 4001) {
            $nums = 14;
            if (in_array('_', $numArr)) {
                return ['code' => 20002, 'msg' => '投注场次数错误'];
            }
        } elseif ($lotteryCode == 4002) {
            $nums = 9;
            foreach ($numArr as $val) {
                if ($val !== '_') {
                    $valdata[] = $val;
                }
            }
            if (count($valdata) < 9) {
                return ['code' => 20002, 'msg' => '投注场次数错误'];
            }
            $numArr = $valdata;
        }
        $combination = Commonfun::getCombination_array($numArr, $nums);
        if (!is_array($combination)) {
            return ['code' => 20002, 'msg' => $combination];
        }
        $count = 0;
        foreach ($combination as $v) {
            $d = 1;
            foreach ($v as $val) {
                $d *= strlen($val);
            }
            $count += $d;
        }
        return ['code' => 600, 'msg' => '成功', 'data' => ['count' => $count, 'play_name' => $playCodeName[$playCode], 'end_time' => $isOk['endsale_time']]];
    }

    public function getSzcCount($betNums, $lotteryCode, $playCode, $periods) {
        $limitTime = LotteryRecord::find()->select(['lottery_time', 'limit_time'])->where(['lottery_code' => $lotteryCode, 'periods' => $periods, 'status' => 1])->asArray()->one();
        if (empty($limitTime)) {
            return ['code' => 40007, 'msg' => '该彩种/期数暂时未开售'];
        }
        $nowTime = strtotime(date('Y-m-d H:i:s'));
        $endTime = empty($limitTime['limit_time']) ? $limitTime['lottery_time'] : $limitTime['limit_time'];
        if ($nowTime >= strtotime($endTime)) {
            return ['code' => 40013, 'msg' => '投注失败!此期非当前期，请重新投注'];
        }
        $singleCost = Constants::PRICE;
        $playParam = SzcConstants::SZC_PLAYNAME;
        $betArr = explode('^', trim($betNums, '^'));
        if (count($betArr) > 5) {
            return ['code' => 20002, 'msg' => '投注内容格式不对'];
        }
        $allCount = 0;
        $playCodeArr = [];
        $playName = [];
        $playArr = explode(',', $playCode);
        foreach ($betArr as $key => $bet) {
            $noteNums = SzcDeal::noteNums($lotteryCode, $bet, $playArr[$key]);
            $allCount += $noteNums['data'];
            $playCodeArr[] = $playArr[$key];
            $playName[] = $playParam[$lotteryCode][$playArr[$key]];
        }
        $playCodes = implode(',', $playCodeArr);
        $playNames = implode(',', $playName);
        if (!$noteNums) {
            return ["code" => 20002, "msg" => "投注内容格式错误！"];
        }
        return ['code' => 600, 'msg' => '成功', 'data' => ['count' => $allCount, 'price' => $singleCost, 'play_name' => $playNames, 'play_code' => $playCodes, 'end_time' => $endTime]];
    }

    public function getOrder($userId, $orderIdArr) {
        $field = ['l.lottery_id', 'api_order.third_order_code as orderId', 'api_order.status', 'l.bet_val', 'l.lottery_id', 'l.odds', 'l.status order_status', 'l.refuse_reason', 'l.out_time', 'l.lottery_order_code'];
        $orders = ApiOrder::find()->select($field)
                ->leftJoin('lottery_order l', 'l.source_id = api_order.api_order_id and source = 7')
                ->where(['api_order.user_id' => $userId])
                ->andWhere(['in', 'third_order_code', $orderIdArr])
                ->asArray()
                ->all();
        if (empty($orders)) {
            return ['code' => 5, 'msg' => '查询结果不存在'];
        }
        foreach ($orders as $val) {
            $orderCodeArr[] = $val['lottery_order_code'];
        }
        $outTicket = [];
        $autoIds = AutoOutOrder::find()->select(['order_code', 'ticket_code'])->where(['in', 'order_code', $orderCodeArr])->asArray()->all();
        foreach ($autoIds as $ids) {
            $outTicket[$ids['order_code']][] = $ids['ticket_code'];
        }
        foreach ($orders as $order) {
            $odds = $this->midToOpenMid($order['lottery_id'], $order['order_status'], $order['bet_val'], $order['odds']);
            $orderObj['orderId'] = $order['orderId'];
            $orderObj['status'] = $order['status'];
            $orderObj['outStatus'] = $order['order_status'];
            $orderObj['odds'] = empty($odds) ? '' : json_decode($odds);
            $orderObj['reason'] = $order['refuse_reason'];
            $orderObj['outTime'] = $order['out_time'];
            $orderObj['outTicketId'] = empty($outTicket[$order['lottery_order_code']]) ? [] : $outTicket[$order['lottery_order_code']];
            $data['orderList'][] = $orderObj;
        }

        return ['code' => 0, 'msg' => '成功', 'data' => $data];
    }

    /**
     * 说明:mid 转换
     * @author chenqiwei
     * @date 2018/3/24 下午1:34
     * @param
     * @return
     */
    public function midToOpenMid($lotteryCode, $status, $betVal, $odds) {
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        if (in_array($lotteryCode, $football) || in_array($lotteryCode, $basketball)) {
            if (in_array($status, [3, 4, 5])) {
                $betNumsArr = explode('|', trim($betVal, '^'));
                $mids = [];
                $result = [];
                if ($lotteryCode != 3011 && $lotteryCode != 3005) {
                    $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
                } else {
                    $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
                }
                foreach ($betNumsArr as $val) {
                    preg_match($pattern, $val, $result);
                    if (!in_array($result[1], $mids)) {
                        $mids[] = $result[1];
                    }
                }
                $openMids = $this->getScheduleOpenMids($mids, $lotteryCode);
                foreach ($openMids as $item) {
                    $odds = str_replace($item['schedule_mid'], $item['open_mid'], $odds);
                }
            } else {
                $odds = '';
            }
        }
        return $odds;
//        $orderObj['orderId'] = $order['orderId'];
//        $orderObj['status'] = $order['status'];
//        $orderObj['outStatus'] = $order['order_status'];
//        $orderObj['odds'] = empty($order['odds'])? '' : json_decode($order['odds']) ;
//        $orderObj['reason'] = $order['refuse_reason'];
    }

    public function getScheduleOpenMids($mids, $lotteryCode) {
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        if (in_array($lotteryCode, $football)) {
            $openMids = Schedule::find()->select(['schedule_mid', 'open_mid'])->where(['in', 'schedule_mid', $mids])->indexBy('schedule_mid')->asArray()->all();
        } else {
            $openMids = LanSchedule::find()->select(['schedule_mid', 'open_mid'])->where(['in', 'schedule_mid', $mids])->indexBy('schedule_mid')->asArray()->all();
        }
        return $openMids;
    }

    public function getOutOrder($userId, $orderIds) {
//        $football = Constants::MADE_FOOTBALL_LOTTERY;
//        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $field = ['api_order.third_order_code orderId', 'l.status outStatus', 'l.zmf_award_money winMoney', 'l.lottery_order_code', 'l.award_amount', 'l.auto_type'];

        $order = ApiOrder::find()->select($field)
                ->innerJoin('lottery_order l', 'l.source_id = api_order.api_order_id and l.source = 7')
                ->where(['l.user_id' => $userId])
                ->andWhere(['in', 'third_order_code', $orderIds])
                ->asArray()
                ->all();
        if (empty($order)) {
            return ['code' => 30002, 'msg' => '订单不存在'];
        }
//        if (in_array($order['lottery_id'], $football) || in_array($order['lottery_id'], $basketball)) {
//            if (in_array($order['order_status'], [3, 4, 5])) {
//                $betNumsArr = explode('|', trim($order['bet_val'], '^'));
//                $mids = [];
//                $result = [];
//                if ($order['lottery_id'] != 3011 && $order['lottery_id'] != 3005) {
//                    $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
//                } else {
//                    $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
//                }
//                foreach ($betNumsArr as $val) {
//                    preg_match($pattern, $val, $result);
//                    if (!in_array($result[1], $mids)) {
//                        $mids[] = $result[1];
//                    }
//                }
//                $openMids = $this->getScheduleOpenMids($mids, $order['lottery_id']);
//                foreach ($openMids as $item) {
//                    $order['odds'] = str_replace($item['schedule_mid'], $item['open_mid'], $order['odds']);
//                }
//            } else {
//                $order['odds'] = '';
//            }
//        }
//        $data['orderId'] = $order['third_order_code'];
//        $data['tradeNo'] = $order['lottery_order_code'];
//        $data['status'] = $order['order_status'];
//        $data['winAmount'] = $order['win_amount'];
//        $data['odds'] = empty($order['odds'])? '' : json_decode($order['odds']) ;
//        $data['reason'] = $order['refuse_reason'];
        foreach ($order as $val) {
            $orderCodeArr[] = $val['lottery_order_code'];
        }
        $outTicket = [];
        $autoIds = AutoOutOrder::find()->select(['order_code', 'ticket_code'])->where(['in', 'order_code', $orderCodeArr])->asArray()->all();
        foreach ($autoIds as $ids) {
            $outTicket[$ids['order_code']][] = $ids['ticket_code'];
        }
        $data = [];
        foreach ($order as $item) {
            $orderObj['orderId'] = $item['orderId'];
            $orderObj['outStatus'] = $item['outStatus'];
            if($item['auto_type'] == 2) {
                $winMoney = $item['winMoney'];
            } else {
                $winMoney = $item['award_amount'];
            } 
            $orderObj['winMoney'] = $winMoney;
            $orderObj['outTicketId'] = empty($outTicket[$item['lottery_order_code']]) ? [] : $outTicket[$item['lottery_order_code']];
            $data['orderList'][] = $orderObj;
        }
        return ['code' => 0, 'msg' => '获取成功', 'data' => $data];
    }

    public function getPeriods($lotteryCode, $periods) {
        if ($lotteryCode == 4001 || $lotteryCode == 4002 || $lotteryCode == 4000) {
            if (empty($periods)) {
                $where['football_fourteen.status'] = 1;
            } else {
                $where['football_fourteen.periods'] = $periods;
            }
            $field = ['football_fourteen.periods', 'football_fourteen.beginsale_time', 'football_fourteen.endsale_time', 'os.sorting_code', 'os.league_name as league_short_name', 'os.start_time', 'os.home_short_name',
                'os.visit_short_name', 'os.schedule_mid',];
            $ftData = FootballFourteen::find()->select($field)
                    ->innerJoin('optional_schedule as os', 'os.periods = football_fourteen.periods')
                    ->where($where)
                    ->asArray()
                    ->all();
            if (empty($ftData)) {
                return ['code' => 40007, 'msg' => '此彩种暂时未有开售期数'];
            }
            $list = [];
//            $periodArr = [];
            foreach ($ftData as $value) {
                $period = $value['periods'];
                if (array_key_exists($period, $list)) {
                    $list[$period]['game'][] = $value;
                } else {
                    $list[$period]['game'][] = $value;
                    $list[$period]['periods'] = $period;
                    $list[$period]['beginsale_time'] = $value['beginsale_time'];
                    $list[$period]['endsale_time'] = $value['endsale_time'];
                }
            }
            $ftSche = [];
            foreach ($list as $key => $v) {
                $ftSche[] = $v;
//                $periodArr[] = $key;
            }
//            $data['all_periods'] = $periodArr;
            $data = $ftSche;
        } else {
            if (empty($periods)) {
                $where['status'] = 1;
            } else {
                $where['periods'] = $periods;
            }
            $where['lottery_code'] = $lotteryCode;
            $data = LotteryRecord::find()->select(['lottery_code', 'lottery_name', 'periods', 'lottery_time', 'limit_time', 'status'])->where($where)->asArray()->one();
        }
        if (empty($data)) {
            return ['code' => 40007, 'msg' => '此彩种期数不存在'];
        }
        return ['code' => 0, 'msg' => '获取成功', 'data' => $data];
    }

    public function getLotteryResult($lotteryCode, $periods) {
        if ($lotteryCode == 4001 || $lotteryCode == 4002 || $lotteryCode == 4000) {
            $data = FootballFourteen::find()->select(['periods', 'schedule_results', 'first_prize', 'second_prize', 'nine_prize'])->where(['periods' => $periods])->asArray()->one();
        } else {
            $data = LotteryRecord::find()->select(['lottery_code', 'lottery_name', 'lottery_numbers', 'periods', 'total_sales', 'pool'])->where(['lottery_code' => $lotteryCode, 'periods' => $periods])->asArray()->one();
        }
        \Yii::info(var_export($data, true), 'backuporder_log');
        if (empty($data)) {
            return ['code' => 40007, 'msg' => '此彩种期数不存在'];
        }
        return ['code' => 0, 'msg' => '获取成功', 'data' => $data];
    }

    public function orderPay($custNo, $userId, $orderCode) {
        $service = new PayService();
        $service->order_code = $orderCode;
        $service->way_type = "YE";
        $service->pay_way = "3";
        $service->cust_no = $custNo;
        $service->user_id = $userId;
        $service->activeType = 1;
        $payData = $service->Pay();
        return $payData;
    }

    /**
     * 说明:api_order 过来的的订单下单
     * @author zyl
     * @date 2018/1/22 上午9:55
     * @param
     * @return
     */
    public function playOrder($apiOrderId, $thirdOrderCode, $userId, $custNo) {
        $field = ['api_order_id', 'third_order_code', 'lottery_code', 'periods', 'play_code', 'bet_val', 'bet_money', 'multiple', 'is_add', 'end_time', 'major_type', 'out_type'];
        $apiOrder = ApiOrder::find()->select($field)->where(['api_order_id' => $apiOrderId, 'third_order_code' => $thirdOrderCode, 'status' => 1])->asArray()->one();
        if (empty($apiOrder)) {
            return ['code' => 100, 'msg' => '该订单不存在'];
        }
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $optional = Constants::MADE_OPTIONAL_LOTTERY;
        $bd = CompetConst::MADE_BD_LOTTERY;
        $wcup = CompetConst::MADE_WCUP_LOTTERY;
        $price = Constants::PRICE;
        $lotArr = Constants::LOTTERY;
        $abbArr = Constants::LOTTERY_ABBREVI;
        $codeArr = array_keys($abbArr);
        $odds = '';
        $buildCode = '';
        $buildName = '';
        $lotteryCode = $apiOrder['lottery_code'];
        $betNums = $apiOrder['bet_val'];
        $playCode = $apiOrder['play_code'];
        $periods = $apiOrder['periods'];
        $multiple = $apiOrder['multiple'];
        $total = $apiOrder['bet_money'];
        $add = $apiOrder['is_add'];
        $userFunds = $this->getUserFunds($custNo);
//        $apiNotice = new ApiNoticeService();
        if ((floatval($userFunds['able_funds'])) < floatval($apiOrder['bet_money'])) {
//            $apiNotice->PushNoticePlayOrder(10004, '余额不足,请先充值！', '', $thirdOrderCode, $userId, 5);
            return ['code' => 109, 'msg' => '余额不足,请先充值！', 'data' => 4];
        }
        if (!in_array($lotteryCode, $codeArr)) {
//            $apiNotice->PushNoticePlayOrder(10005, '此彩种暂未开放下单', '', $thirdOrderCode, $userId, 5);
            return ['code' => 109, 'msg' => '此彩种暂未开放下单', 'data' => 5];
        }
        //自动出票处理 (暂时)
//        $autoLottery = Constants::AUTO_LOTTERY;
//        if (in_array($lotteryCode, $autoLottery)) {
//            $outType = \Yii::$app->params['openapi_out_type'];
//        } else {
//            $outType = 1;
//        }
//        $outType = $apiOrder['out_type'];
        $majorData = [];
        $majorType = 0;
        if (in_array($lotteryCode, $basketball) || in_array($lotteryCode, $football)) {
            $cacul = $this->getNewBet($lotteryCode, $betNums, $playCode);
            if ($cacul['code'] != 600) {
//                $apiNotice->PushNoticePlayOrder(10006, $cacul['msg'], '', $thirdOrderCode, $userId, 5);
                return ['code' => 109, 'msg' => $cacul['msg'], 'data' => 6];
            }
            $betNums = $cacul['data']['bet_nums'];
            $odds = $cacul['data']['odds'];
            $buildCode = $cacul['data']['build_code'];
            $buildName = $cacul['data']['build_name'];
            $playCode = $cacul['data']['play_code'];
            if ($apiOrder['major_type'] != 0) {
                $major = MajorData::find()->select(['major', 'major_type'])->where(['order_id' => $apiOrderId, 'source' => 7])->asArray()->one();
                $majorData = json_decode($major['major'], true);
                $majorType = $major['major_type'];
            }
            $outTicket = OrderDeal::deal($lotteryCode, $cacul['data']['bet_nums'], $cacul['data']['play_code'], $cacul['data']['build_code'], $multiple, $majorType, $majorData);
            $outNums = count($outTicket);
            if ($outNums > 30) {
                $outType = 2;
            }
        } elseif (in_array($lotteryCode, $optional)) {
            $cacul = $this->getOptionalCount($betNums, $periods, $lotteryCode, $playCode);
            if ($cacul['code'] != 600) {
//                $apiNotice->PushNoticePlayOrder(10006, $cacul['msg'], '', $thirdOrderCode, $userId, 5);
                return ['code' => 109, 'msg' => $cacul['msg'], 'data' => 6];
            }
            $outNums = ceil($cacul['data']['count'] / 5) * ceil($multiple / 99);
        } elseif (in_array($lotteryCode, $bd)) {
            $cacul = $this->getBdCount($lotteryCode, $betNums, $playCode);
            if ($cacul['code'] != 600) {
                return ['code' => 109, 'msg' => $cacul['msg'], 'data' => 6];
            }
            $odds = json_encode($cacul['data']['odds']);
            $outNums = ceil($multiple / 99);
            if ($outNums > 30) {
                $outType = 2;
            }
        } elseif (in_array($lotteryCode, $wcup)) {
            $cacul = $this->getWcupCount($lotteryCode, $betNums);
            if ($cacul['code'] != 600) {
                return ['code' => 109, 'msg' => $cacul['msg'], 'data' => 6];
            }
            $odds = json_encode($cacul['data']['odds']);
            $outNums = ceil($multiple / 99);
            if ($outNums > 30) {
                $outType = 2;
            }
        } else {
            $cacul = $this->getSzcCount($betNums, $lotteryCode, $playCode, $periods);
            if ($cacul['code'] != 600) {
//                $apiNotice->PushNoticePlayOrder(10006, $cacul['msg'], '', $thirdOrderCode, $userId, 5);
                return ['code' => 109, 'msg' => $cacul['msg'], 'data' => 6];
            }
            $price = $cacul['data']['price'];
            $outNums = ceil($cacul['data']['count'] / 5) * ceil($multiple / 99);
            $playCode = $cacul['data']['play_code'];
        }
        if ($majorType == 0) {
            $betTotal = $price * $cacul['data']['count'] * $multiple;
            if ($add == 1) {
                $betTotal *= 1.5;
            }
            if ($total != $betTotal) {
//            $apiNotice->PushNoticePlayOrder(10006, $cacul['msg'], '', $thirdOrderCode, $userId, 5);
                return ['code' => 109, 'msg' => $cacul['msg'], 'data' => 6];
            }
        }
        $endTime = $cacul['data']['end_time'];
        $outType = $apiOrder['out_type'];
        $overTime = OrderDeal::judgeTimeout($outNums, $endTime, $outType);
        if ($overTime['code'] != 600) {
//            $apiNotice->PushNoticePlayOrder(10007, $overTime['msg'], '', $thirdOrderCode, $userId, 5);
            return ['code' => 109, 'msg' => $overTime['msg'], 'data' => 7];
        }
        $store = OrderDeal::getOutStore($lotteryCode, $outNums, $outType);
        if ($store['code'] != 600) {
//            $apiNotice->PushNoticePlayOrder(10005, $store['msg'], '', $thirdOrderCode, $userId, 5);
            return ['code' => 109, 'msg' => $store['msg'], 'data' => 5];
        }

        $exist2 = $this->getExistOrder($thirdOrderCode, $userId, [2]);
        if ($exist2 != 0) {
//            $apiNotice->PushNoticePlayOrder(10003, '该订单已存在,请勿重复下单！', '', $thirdOrderCode, $userId, 5);
            return ['code' => 109, 'msg' => '该订单已存在,请勿重复下单！', 'data' => 3];
        }
        $betval = rtrim($betNums, '^');
        $insert = ["lottery_type" => $abbArr[$lotteryCode], "play_code" => $playCode, "play_name" => $cacul['data']['play_name'], "lottery_id" => $lotteryCode, "lottery_name" => $lotArr[$lotteryCode],
            "periods" => $periods, "cust_no" => $custNo, "store_id" => $store['data']['store_id'], "agent_id" => "0", "bet_val" => $betval . "^", "bet_double" => $multiple, "is_bet_add" => $add,
            "bet_money" => $total, "source" => 7, "count" => $cacul['data']['count'], "periods_total" => 1, "is_random" => 0, "win_limit" => 0, "is_limit" => 0, "odds" => $odds, 'source_id' => $apiOrderId,
            "end_time" => $endTime, 'user_id' => $userId, 'store_no' => $store['data']['store_no'], 'auto_type' => $outType, 'major_type' => $majorType];
        $db = \Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            $ret = OrderService::insertOrder($insert, false);
            if ($ret["error"] !== true) {
                $trans->rollBack();
//                $apiNotice->PushNoticePlayOrder(10007, '下单失败！请重新下单！', '', $thirdOrderCode, $userId, 5);
                return ['code' => 109, 'msg' => '下单失败！', 'data' => 7];
            }
            $orderCode = $ret['orderCode'];
            $service = new PayService();
            $service->productPayRecord($custNo, $orderCode, 1, 1, $total, 1);

//        $queue = new \LotteryQueue();
//        $queue->pushQueue('third_order_pay_job', 'orderPay', );
//        return $this->jsonResult(600, '接单成功！等待处理！', ['orderId' => $openOrderId, 'tradeId' => $orderCode]);
//        
//        KafkaService::addQue('ThirdOrderPay', ['custNo' => $custNo, 'userId' => $userId, 'orderCode' => $orderCode]);
            $service->order_code = $ret['orderCode'];
            $service->way_type = "YE";
            $service->pay_way = "3";
            $service->cust_no = $custNo;
            $service->activeType = 1;
            $payData = $service->Pay();
            if ($payData['code'] != 600) {
                $trans->rollBack();
                if ($payData['code'] == 407) {
//                    $apiNotice->PushNoticePlayOrder(10004, '余额不足,请先充值！', '', $thirdOrderCode, $userId, 5);
                    return ['code' => 109, 'msg' => '余额不足,请先充值！', 'data' => 4];
                } else {
//                    $apiNotice->PushNoticePlayOrder(10007, '下单失败！请重新下单！', '', $thirdOrderCode, $userId, 5);
                    return ['code' => 109, 'msg' => '下单失败！', 'data' => 7];
                }
            }
            $trans->commit();
//            $apiNotice->PushNoticePlayOrder(10002, '下单成功！', $ret['orderCode'], $thirdOrderCode, $userId, 5);
            return ['code' => 600, 'msg' => '下单成功', 'data' => 2];
        } catch (Exception $ex) {
            $trans->rollBack();
//            $apiNotice->PushNoticePlayOrder(10007, '下单失败！请重新下单！', '', $thirdOrderCode, $userId, 5);
            return ['code' => 109, 'msg' => '下单失败！', 'data' => 7];
        }
    }

    public function createApiOrder($insertInfo) {
//        $apiOrder = ApiOrder::findOne(['third_order_code' => $insertInfo['third_order_code'], 'user_id' => $insertInfo['user_id']]);
//        if (empty($apiOrder)) {
        $apiOrder = new ApiOrder;
        $apiOrder->api_order_code = Commonfun::getCode('API', "");
        $apiOrder->create_time = date('Y-m-d H:i:s');
//        } else {
//            $apiOrder->status = 1;
//            $apiOrder->modify_time = date('Y-m-d H:i:s');
//        }

        $apiOrder->third_order_code = $insertInfo['third_order_code'];
        $apiOrder->user_id = $insertInfo['user_id'];
        $apiOrder->lottery_code = $insertInfo['lottery_code'];
        $apiOrder->periods = $insertInfo['periods'];
        $apiOrder->play_code = $insertInfo['play_code'];
        $apiOrder->bet_val = $insertInfo['bet_val'];
        $apiOrder->bet_money = $insertInfo['bet_money'];
        $apiOrder->multiple = $insertInfo['multiple'];
        $apiOrder->is_add = $insertInfo['is_add'];
        $apiOrder->end_time = $insertInfo['end_time'];
        $apiOrder->message_id = $insertInfo['message_id'];
        $apiOrder->major_type = $insertInfo['major_type'];
        $apiOrder->out_type = $insertInfo['out_type'];
        if (!$apiOrder->validate()) {
            return ['code' => 20004, 'msg' => '接单失败,数据验证失败', 'data' => $apiOrder->getFirstErrors()];
        }
        if (!$apiOrder->save()) {
            return ['code' => 20004, 'msg' => '接单失败,数据保存失败', 'data' => $apiOrder->getFirstErrors()];
        }
        return ['code' => 600, 'msg' => '接单成功', 'data' => $apiOrder->attributes];
    }

    public static function getBdCount($lotteryCode, $betNums, $playCode) {
        $mCN = CompetConst::BD_M_CHUAN_N;
        $layName = CompetConst::BD_MCN;
        $bdChuan = CompetConst::BD_CHUAN;
        $buildPlay = '';
        $buildName = '';
        if (array_key_exists($playCode, $mCN)) {
            $buildPlay = $betNums['play'];
            $buildName = $layName[$buildPlay];
            $playCode = $mCN[$buildPlay];
        }
        $arr = explode(",", $playCode);
        if ($arr == array_intersect($arr, $bdChuan[$lotteryCode])) {
            foreach ($arr as $val) {
                $playName[] = $layName[$val];
            }
        } else {
            return ["code" => 20002, "msg" => "投注串关方式不对！"];
        }
        $conent = ['nums' => rtrim($betNums, '^'), 'play' => $playCode];
        $bdService = new BdService();
        $data = $bdService->calculationCount($lotteryCode, $conent);
        if ($data['code'] != 0) {
            return ['code' => 20002, 'msg' => $data['msg']];
        }
        return ['code' => 600, 'msg' => '成功', 'data' => ['count' => $data['result'], 'end_time' => $data['limit_time'], 'play_name' => implode(',', $playName), 'play_code' => $playCode,
                'build_code' => $buildPlay, 'build_name' => $buildName, 'max_time' => $data['max_time'], 'odds' => $data['odds']]];
    }

    public static function getWcupCount($lotteryCode, $betNums) {
        $numArr = explode(',', rtrim($betNums, '^'));
        $combination = Commonfun::getCombination_array($numArr, 1);
        if (!is_array($combination)) {
            return ['code' => 20002, 'msg' => $combination];
        }
        $count = count($combination);
        $oddArr = WorldcupService::getOdds($lotteryCode, $numArr);
        foreach ($oddArr as $k => $v) {
            $odds[$k] = $v['team_odds'];
        }
        return ['code' => 600, 'msg' => '成功', 'data' => ['count' => $count, 'end_time' => '2018-07-16 00:00:00', 'odds' => $odds, 'play_name' => '固定单场']];
    }

    public function getOrderDetail($userId, $orderId) {
        $field = ['api_order.third_order_code orderId', 'b.lottery_order_id', 'b.lottery_order_code', 'b.betting_detail_code', 'b.lottery_id', 'b.lottery_name', 'b.periods', 'b.bet_val', 'b.odds',
            'b.fen_json', 'b.play_name', 'b.play_code', 'b.bet_double', 'b.is_bet_add', 'b.win_amount', 'b.status', 'b.deal_status', 'b.win_level', 'b.bet_money',];

        $order = ApiOrder::find()->select($field)
                ->innerJoin('lottery_order l', 'l.source_id = api_order.api_order_id and l.source = 7')
                ->innerJoin('betting_detail b', 'b.lottery_order_id = l.lottery_order_id')
                ->where(['l.user_id' => $userId, 'third_order_code' => $orderId, 'l.status' => 3])
                ->asArray()
                ->all();
        if (empty($order)) {
            return ['code' => 30002, 'msg' => '订单不存在'];
        }
        return ['code' => 0, 'msg' => '获取成功', 'data' => $order];
    }

    public function getDetailResult($userId, $orderId) {
        $field = ['api_order.third_order_code orderId', 'b.betting_detail_code', 'b.win_amount', 'b.status', 'b.deal_status', 'b.win_level'];
        $detail = ApiOrder::find()->select($field)
                ->innerJoin('lottery_order l', 'l.source_id = api_order.api_order_id and l.source = 7')
                ->innerJoin('betting_detail b', 'b.lottery_order_id = l.lottery_order_id')
                ->where(['l.user_id' => $userId, 'third_order_code' => $orderId, 'l.status' => 3])
                ->asArray()
                ->all();
        if (empty($detail)) {
            return ['code' => 30002, 'msg' => '订单不存在'];
        }
        return ['code' => 0, 'msg' => '获取成功', 'data' => $detail];
    }

}

<?php

namespace app\modules\competing\services;

use Yii;
use app\modules\common\services\OrderService;
use app\modules\common\helpers\Commonfun;
use app\modules\common\helpers\Constants;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\Schedule;
use app\modules\common\services\PayService;
use app\modules\common\models\FootballFourteen;
use app\modules\common\models\OptionalSchedule;

class OptionalService {

    /**
     * 任选投注下单
     * @param type $lotteryCode
     * @param type $custNo
     * @param type $storeId
     * @param type $source
     * @param type $userPlanId
     * @return type
     */
    public function optionalOrder($lotteryCode, $custNo, $userId, $storeId, $storeCode, $source, $sourceId = '', $outType) {
        $playCodeName = Constants::OPTIONAL_PLAYNAME;
        $lotteryType = Constants::LOTTERY_ABBREVI;
        $lotteryName = Constants::LOTTERY;
        $request = Yii::$app->request;
        $post = $request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $remark = isset($post['remark']) ? $post['remark'] : '';
        $periods = $orderData['periods']; // 期数
        $isOk = FootballFourteen::find()->where(['periods' => $periods, 'status' => 1])->asArray()->one();
        if (empty($isOk)) {
            return ['code' => 2, 'msg' => '投注失败，超时,此期已过期，请重新投注'];
        }
        if(time() < strtotime($isOk['beginsale_time'])) {
            return ['code' => 488, 'msg' => '投注失败！！该彩种不接受预售，请稍后再试'];
        }
        $betNums = $orderData['contents']; // 投注内容
        $total = $orderData['total']; //总价
        $multiple = $orderData['multiple']; // 倍数
        $countBet = $orderData['count_bet']; // 注数
//        if (isset($orderData['end_time'])) {
            $endTime = $isOk['endsale_time'];
//        } else {
//            $endTime = '';
//        }
        $playName = [];
        $arr = explode(",", $betNums["play"]);
        if (is_array($arr)) {
            foreach ($arr as $val) {
                $playName[] = $playCodeName[$val];
            }
        } else {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }
        $playCode = $betNums["play"];
        $playNum = $betNums["nums"];
        $numArr = explode(',', $playNum);
        if ($lotteryCode == 4001) {
            $nums = 14;
            if (in_array('_', $numArr)) {
                return ['code' => 2, 'msg' => '投注场次数错误'];
            }
        } elseif ($lotteryCode == 4002) {
            $nums = 9;
            foreach ($numArr as $val) {
                if ($val !== '_') {
                    $valdata[] = $val;
                }
            }
            if (count($valdata) < 9) {
                return ['code' => 2, 'msg' => '投注场次数错误'];
            }
            $numArr = $valdata;
        } else {
            return ['code' => 2, 'msg' => '无此彩种'];
        }
        $combination = Commonfun::getCombination_array($numArr, $nums);
        if (!is_array($combination)) {
            return ['code' => 2, 'msg' => $combination];
        }
        $count = 0;
        foreach ($combination as $v) {
            $d = 1;
            foreach ($v as $val) {
                $d *= strlen($val);
            }
            $count += $d;
        }
        if ($countBet != $count) {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }
        $betMoney = Constants::PRICE * $count;

        if ($multiple >= 1) {
            $betMoney *=$multiple;
        } else {
            return ["code" => 2, "msg" => "投注加倍参数错误！"];
        }

        $totalMoney = $betMoney;
        if ($total != $totalMoney) {
            return ["code" => 2, "msg" => "投注总金额错误！"];
        }
        $insert=["lottery_type" => $lotteryType[$lotteryCode], "play_code" => (string) $playCode, "play_name" => implode(',', $playName), "lottery_id" => $lotteryCode, "lottery_name" => $lotteryName[$lotteryCode],
                    "periods" => (string) $periods, "cust_no" => $custNo, "store_id" => $storeId, "source_id" => $sourceId, "agent_id" => "0", "bet_val" => $playNum . "^", "bet_double" => $multiple, "is_bet_add" => 0,
                    "bet_money" => $betMoney, "source" => $source, "count" => $count, "periods_total" => 1, "is_random" => 0, "win_limit" => 0, "is_limit" => 0, "odds" => '', "end_time" => $endTime, 'user_id' => $userId, 
            'store_no' => $storeCode, 'auto_type' => $outType, 'remark' => $remark];
        return OrderService::selfDoLotterOrder($insert,false);
       /* $ret = OrderService::insertOrder(, false);
        if ($ret["error"] === true) {
            if ($source != 6) {
                $paySer = new PayService();
                $paySer->productPayRecord($custNo, $ret["orderCode"], 1, 1, $betMoney, 1);
            }
            return ["code" => 600, "msg" => "下注成功！", "result" => ["lottery_order_code" => $ret["orderCode"]]];
        } elseif ($ret == false) {
            return ["code" => 2, "msg" => "下注失败！"];
        } else {
            return ["code" => 2, "msg" => "下注失败！", "result" => $ret];
        }*/
    }

    /**
     * 任选生成详情单
     * @auther GL zyl
     * @param type $model
     */
    public function optionalDetail($model) {
        $playName = Constants::OPTIONAL_PLAYNAME;
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
        $infos["odds"] = $model->odds;

        $contents = trim($model->bet_val, "^");
        $lotteryCode = $model->lottery_id;
        $numArr = explode(',', $contents);
        if (!is_array($numArr)) {
            return ['code' => 2, 'msg' => '投注内容不正确'];
        }
        foreach ($numArr as &$val) {
            $arr = [];
            if (strlen($val) > 1) {
                for ($i = 0; $i < strlen($val); $i++) {
                    $arr[] = $val[$i];
                }
                $val = implode('|', $arr);
            }
        }
        if ($lotteryCode == 4002) {
            $orders = $this->optionalNineDetai($model->periods, $numArr);
        } else {
            $orders = $this->OptionalFourteenDetail($numArr);
        }
        if ($orders['code'] != 1) {
            return ['code' => 2, 'msg' => '操作失败', 'err' => $orders['msg']];
        }
        $playCode = $lotteryCode . '01';
        $orderArr = [];
        $n = 0;
        foreach ($orders['data'] as $key => $ii) {
            $orderArr[$n]['bet_val'] = ltrim($ii, ",");
            $orderArr[$n]['play_code'] = $playCode;
            $orderArr[$n]['play_name'] = $playName[$playCode];
            $n++;
        }
        $infos['content'] = $orderArr;
        $result = OrderService::insertDetail($infos);
        if ($result['error'] === true) {
            return ['code' => 0, 'msg' => '操作成功'];
        } else {
            return [
                'code' => 2, 'msg' => '操作失败', 'err' => $result];
        }
    }

    /**
     * 任选订单详情
     * @auther GL zyl
     * @param type $orderCode
     * @param type $custNo
     * @return type
     */
    public function getOptionalOrder($orderCode, $custNo = '', $orderId = '') {
        $where = [];
        $status = [
            '1' => '未支付',
            '2' => '处理中',
            '3' => '待开奖',
            '4' => '中奖',
            '5' => '未中奖',
            "6" => "出票失败",
            '9' => '过点撤销',
            '10' => '拒绝出票',
            '11' => '未上传方案撤单',
            '12' => '等待出票'
        ];
        $where['lottery_order.lottery_order_code'] = $orderCode;
        if (!empty($custNo)) {
            $where['lottery_order.cust_no'] = $custNo;
        }
        if (!empty($orderId)) {
            $where['lottery_order.lottery_order_id'] = $orderId;
        }
        $field = ['bet_val', 'lottery_order.lottery_id', 'lottery_order.lottery_name', 'bet_money', 'lottery_order_code', 'lottery_order.create_time', 'lottery_order_id', 'lottery_order.status', 'win_amount', 'play_code', 'play_name', 'bet_double', 'refuse_reason',
            'count', 'lottery_order.periods', 's.store_name', 's.store_code', 's.telephone phone_num', 'f.beginsale_time', 'f.endsale_time', 'f.schedule_mids', 'f.schedule_results', 'f.status result_status', 'l.lottery_pic', 'lottery_order.deal_status'];
        $data = LotteryOrder::find()->select($field)
                ->leftJoin('store as s', 's.store_code = lottery_order.store_no and s.status = 1')
                ->leftJoin('football_fourteen as f', 'f.periods = lottery_order.periods')
                ->leftJoin('lottery l', 'l.lottery_code = lottery_order.lottery_id')
                ->where($where)
                ->asArray()
                ->one();
        if (empty($data)) {
            return ['code' => 109, 'data' => '该订单有误，请重新查看'];
        }
        $scheduleData = OptionalSchedule::find()->select(['sorting_code', 'league_name', 'schedule_mid', 'start_time', 'home_short_name', 'visit_short_name'])
                ->where(['periods' => $data['periods']])
                ->asArray()
                ->all();
        if (!empty($scheduleData)){
            foreach ($scheduleData as $val) {
                $betval = explode(',', trim($data['bet_val'], '^'));
                $res = explode(',', trim($data['schedule_results']));
                if($data['result_status'] == 0 || $data['result_status'] == 1) {
                    $scheResult = '';
                }  else {
                    $scheResult = $res[$val['sorting_code'] - 1];
                }
                $data['betval_arr'][] = ['sid' => $val['sorting_code'], 'home_team' => $val['home_short_name'], 'visit_team' => $val['visit_short_name'], 'bet_val' => $betval[$val['sorting_code'] - 1], 'result' => $scheResult];
            }
        }else{
            $data['betval_arr']=[];
        }
        $data['status_name'] = $status[$data['status']];
        $data['discount_data']=PayService::getDiscount(['order_code'=>$orderCode]);//优惠信息
        return ['code' => 600, 'result' => $data];
    }

    /**
     * 任九详情单的生成
     * @param type $periods
     * @param type $numArr
     * @return type
     */
    public function optionalNineDetai($periods, $numArr) {
        $nums = 9;
        $midsArr = OptionalSchedule::find()->select(['sorting_code'])->where(['periods' => $periods])->asArray()->all();
        $existArr = [];
        foreach ($midsArr as $key => $value) {
            $existArr[$value['sorting_code']] = $numArr[$key];
        }
        foreach ($numArr as $k => $item) {
            if ($item !== '_') {
                $valdata[] = $midsArr[$k]['sorting_code'];
            }
        }
        $combination = Commonfun::getCombination_array($valdata, $nums);
        if (!is_array($combination)) {
            return ['code' => 2, 'msg' => $combination];
        }
        foreach ($combination as $val) {
            $digtMids = $existArr;
            $em = '';
            foreach ($digtMids as $key => $vi) {
                if (in_array($key, $val)) {
                    $em .= $vi . ',';
                } else {
                    $em .= '_' . ',';
                }
            }
            $emArr = explode(',', $em);
            $s1 = explode('|', $emArr[0]);
            $s2 = explode('|', $emArr[1]);
            $s3 = explode('|', $emArr[2]);
            $s4 = explode('|', $emArr[3]);
            $s5 = explode('|', $emArr[4]);
            $s6 = explode('|', $emArr[5]);
            $s7 = explode('|', $emArr[6]);
            $s8 = explode('|', $emArr[7]);
            $s9 = explode('|', $emArr[8]);
            $s10 = explode('|', $emArr[9]);
            $s11 = explode('|', $emArr[10]);
            $s12 = explode('|', $emArr[11]);
            $s13 = explode('|', $emArr[12]);
            $s14 = explode('|', $emArr[13]);
            $details = Commonfun::proCross_string(",", $s1, $s2, $s3, $s4, $s5, $s6, $s7, $s8, $s9, $s10, $s11, $s12, $s13, $s14);
            if (!is_array($details)) {
                return ['code' => 2, 'msg' => $details];
            }
            $orders[] = $details;
        }
        foreach ($orders as $val) {
            foreach ($val as $it) {
                $allOrders[] = $it;
            }
        }
        return ['code' => 1, 'msg' => '生成成功', 'data' => $allOrders];
    }

    /**
     * 任选十四详情单的生成
     * @param type $numArr
     * @return type
     */
    public function OptionalFourteenDetail($numArr) {
        $s1 = explode('|', $numArr[0]);
        $s2 = explode('|', $numArr[1]);
        $s3 = explode('|', $numArr[2]);
        $s4 = explode('|', $numArr[3]);
        $s5 = explode('|', $numArr[4]);
        $s6 = explode('|', $numArr[5]);
        $s7 = explode('|', $numArr[6]);
        $s8 = explode('|', $numArr[7]);
        $s9 = explode('|', $numArr[8]);
        $s10 = explode('|', $numArr[9]);
        $s11 = explode('|', $numArr[10]);
        $s12 = explode('|', $numArr[11]);
        $s13 = explode('|', $numArr[12]);
        $s14 = explode('|', $numArr[13]);
        $orders = Commonfun::proCross_string(",", $s1, $s2, $s3, $s4, $s5, $s6, $s7, $s8, $s9, $s10, $s11, $s12, $s13, $s14);
        if (!is_array($orders)) {
            return ['code' => 2, 'msg' => $orders];
        }
        return ['code' => 1, 'msg' => '生成成功', 'data' => $orders];
    }

    /**
     * 投注验证
     * @param string $lotteryCode
     * @return array
     */
    public function OptionalVerification($lotteryCode) {
        $playCodeName = Constants::OPTIONAL_PLAYNAME;
        $lotteryName = Constants::LOTTERY;
        $request = Yii::$app->request;
        $orderData = $request->post();
        $format = date('Y-m-d H:i:s');
        $periods = $orderData['periods']; // 期数
        $isOk = FootballFourteen::find()->where(['periods' => $periods])->andWhere(['>', 'endsale_time', $format])->asArray()->one();
        if (empty($isOk)) {
            return ['code' => 2, 'msg' => '投注失败，超时,此期已过期，请重新投注'];
        }
        $betNums = $orderData['contents']; // 投注内容
        $total = $orderData['total']; //总价
        $multiple = $orderData['multiple']; // 倍数
        $countBet = $orderData['count_bet']; // 注数
        $playName = [];
        $arr = explode(",", $betNums["play"]);
        if (is_array($arr)) {
            foreach ($arr as $val) {
                $playName[] = $playCodeName[$val];
            }
        } else {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }
        $playCode = $betNums["play"];
        $playNum = $betNums["nums"];
        $numArr = explode(',', $playNum);
        if ($lotteryCode == 4001) {
            $nums = 14;
            if (in_array('_', $numArr)) {
                return ['code' => 2, 'msg' => '投注场次数错误'];
            }
        } elseif ($lotteryCode == 4002) {
            $nums = 9;
            foreach ($numArr as $val) {
                if ($val !== '_') {
                    $valdata[] = $val;
                }
            }
            if (count($valdata) < 9) {
                return ['code' => 2, 'msg' => '投注场次数错误'];
            }
            $numArr = $valdata;
        } else {
            return ['code' => 2, 'msg' => '无此彩种'];
        }
        $combination = Commonfun::getCombination_array($numArr, $nums);
        if (!is_array($combination)) {
            return ['code' => 2, 'msg' => $combination];
        }
        $count = 0;
        foreach ($combination as $v) {
            $d = 1;
            foreach ($v as $val) {
                $d *= strlen($val);
            }
            $count += $d;
        }
        if ($countBet != $count) {
            return [
                "code" => 2,
                "msg" => "投注内容注数不对应！"
            ];
        }
        $betMoney = Constants::PRICE * $count;

        if ($multiple >= 1) {
            $betMoney *=$multiple;
        } else {
            return [
                "code" => 2,
                "msg" => "投注加倍参数错误！"
            ];
        }

        $totalMoney = $betMoney;
        if ($total != $totalMoney) {
            return [
                "code" => 2,
                "msg" => "投注总金额错误！"
            ];
        }
        return [
            "code" => 0,
            "msg" => "投注信息正确！",
            "data" => [
                "lottery_name" => $lotteryName[$lotteryCode],
                "play_name" => (implode(',', $playName)),
                "play_code" => $playCode,
                "bet_val" => ($playNum . "^"),
                "limit_time" => $isOk['endsale_time']
            ]
        ];
    }

    /**
     * 获取任选14的赛程
     * @return type
     */
    public function getSchedule() {
        $field = ['football_fourteen.periods', 'football_fourteen.beginsale_time', 'football_fourteen.endsale_time', 'os.sorting_code', 'os.league_name as league_short_name', 'os.start_time', 'os.home_short_name',
            'os.visit_short_name', 'os.schedule_mid', 'os.odds_win', 'os.odds_flat', 'os.odds_lose'];
        $format = date('Y-m-d H:i:s');
        $ftData = FootballFourteen::find()->select($field)
                ->innerJoin('optional_schedule as os', 'os.periods = football_fourteen.periods')
                ->where(['in', 'football_fourteen.status', [0, 1]])
                ->andWhere(['>', 'football_fourteen.endsale_time', $format])
                ->asArray()
                ->all();
        if (empty($ftData)) {
            return ['code' => 109, 'msg' => '此彩种暂时未有开售期数'];
        }

        $list = [];
        $periodArr = [];
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
            $periodArr[] = $key;
        }
        $data['all_periods'] = $periodArr;
        $data['ft_sche'] = $ftSche;
        return ['code' => 600, 'msg' => '获取成功', 'data' => $data];
    }

}

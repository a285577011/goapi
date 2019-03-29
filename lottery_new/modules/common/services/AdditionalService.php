<?php

namespace app\modules\common\services;

use app\modules\common\models\LotteryAdditional;
use app\modules\common\helpers\Commonfun;
use app\modules\common\models\LotteryOrder;
use app\modules\common\services\OrderService;
use app\modules\common\helpers\Constants;
use app\modules\common\services\PayService;
use app\modules\common\models\PayRecord;
use Yii;
use yii\db\Query;
use app\modules\orders\models\TicketDispenser;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class AdditionalService {

    /**
     * 写入追号队列
     * @param type $lotteryCode  彩种
     * @param type $periods 期数
     * @param type $endTime  截止时间
     */
    public function traceJob($lotteryCode, $periods, $endTime) {
        $addOrder = LotteryAdditional::find()->select(['lottery_additional_id'])->where(['lottery_id' => $lotteryCode, 'status' => 2])->andWhere(['>', 'periods_total', 1])->asArray()->all();
        if (empty($addOrder)) {
            return ['code' => 600, 'msg' => '此彩种无追期单'];
        }
        //$lotteryqueue = new \LotteryQueue();
        foreach ($addOrder as $val) {
            KafkaService::addQue('OrderTrace', ['traceInfo' => $val['lottery_additional_id'], 'periods' => (string) $periods, 'endTime' => $endTime], true);
            //$lotteryqueue->pushQueue('order_trace_job', 'trace', ['traceInfo' => $val['lottery_additional_id'], 'periods' => $periods, 'endTime' => $endTime]);
        }

    }

    /**
     * 追号--订单生成
     * @param type $traceInfo  // 追号内容
     * @return type
     */
    public function doTrace($traceId, $periods, $endTime) {
        $field = ['lottery_additional_id', 'lottery_name', 'lottery_id', 'play_name', 'play_code', 'lottery_additional_code', 'chased_num', 'periods_total', 'cust_no', 'user_id', 'store_id', 'store_no', 'bet_val',
            'bet_double', 'is_bet_add', 'bet_money', 'total_money', 'count', 'opt_id', 'is_random', 'is_limit', 'win_limit', 'agent_id', 'remark'];
        $traceInfo = LotteryAdditional::find()->select($field)->where(['lottery_additional_id' => $traceId])->asArray()->one();
        if (empty($traceInfo)) {
            return ['code' => 600, 'msg' => '无此追期单'];
        }
//        $currentPeriodsInfo = Commonfun::currentPeriods($traceInfo['lottery_id']);
//        if ($currentPeriodsInfo['error'] === false) {
//            return ['code' => 109, 'msg' => $currentPeriodsInfo['此彩种当前期数不存在']];
//        }
//        $periods = $currentPeriodsInfo['periods'];
//        $endTime = $currentPeriodsInfo["data"]['limit_time'];
        $exist = LotteryOrder::find()->select(['lottery_order_id'])->where(['source' => 2, 'source_id' => $traceInfo['lottery_additional_id'], 'periods' => $periods])->andWhere(['in', 'status', [2, 3, 4, 5, 6, 9, 10]])->asArray()->one();
        if (!empty($exist)) {
            return ['code' => 109, 'msg' => '此单已追'];
        }
        $traceModel = LotteryAdditional::findOne(['lottery_additional_id' => $traceInfo['lottery_additional_id']]);
        if($traceInfo['periods_total'] <= $traceInfo['chased_num']) {
            $traceModel->status  = 3;
            $traceModel->save();
            return ['code' => 109, 'msg' => '此单已过截止期'];
        }
        if ($traceInfo['is_limit'] != 0) {
            $allWinAmount = LotteryOrder::find()->select(['sum(win_amount) as all_win'])->where(['source' => 2, 'source_id' => $traceInfo['lottery_additional_id'], 'status' => 4])->asArray()->one();
            if (floatval($allWinAmount['all_win']) > floatval($traceInfo['win_limit'])) {
                $traceModel->status = 3;
                $ret = self::refundAdditional($traceInfo['lottery_additional_id'], $traceInfo['periods_total'] - $traceInfo['chased_num']);    //解冻冻结金额
                if ($ret == false) {
                    return ['code' => 109, 'msg' => '冻结金额解冻失败'];
                }
                $traceModel->save();
                return ['code' => 600, 'msg' => '追号限制，解冻成功'];
            }
        }
        if ($traceInfo['is_random'] == 1) {
            $betVal = self::randomOrder($traceInfo['lottery_id'], $traceInfo['bet_val']);
        } else {
            $betVal = $traceInfo['bet_val'];
        }
        $orderService = new OrderService();
        $lotteryAbb = Constants::LOTTERY_ABBREVI;
        $autoLottery = [];
        $store = TicketDispenser::find()->select(['out_lottery'])->where(['store_no' => $traceInfo['store_no'], 'type' => 2, 'status' => 1])->asArray()->one();
        if (!empty($store)) {
            $autoLottery = explode(',', $store['out_lottery']);
            if (in_array(3000, $autoLottery)) {
                array_push($autoLottery, '3006', '3007', '3008', '3009', '3010', '3011');
            }
            if (in_array(3100, $autoLottery)) {
                array_push($autoLottery, '3001', '3002', '3003', '3004', '3005');
            }
            if (in_array(5000, $autoLottery)) {
                array_push($autoLottery, '5001', '5002', '5003', '5004', '5005', '5006');
            }
            if (in_array(3300, $autoLottery)) {
                array_push($autoLottery, '301201', '301301');
            }
        }
        $outType = 1;
        if ($traceInfo['store_no'] == \Yii::$app->params['auto_store_no'] && in_array($traceInfo['lottery_id'], $autoLottery)) {
            $outType = 2;
        }
        $chaseNums = $traceInfo['chased_num'] + 1;
        $insert = ['lottery_type' => $lotteryAbb[$traceInfo['lottery_id']], 'play_code' => $traceInfo['play_code'], 'play_name' => $traceInfo['play_name'], 'lottery_id' => $traceInfo['lottery_id'], 'lottery_name' => $traceInfo['lottery_name'],
            'periods' => (string) $periods, 'cust_no' => $traceInfo['cust_no'], 'user_id' => $traceInfo['user_id'], 'store_id' => $traceInfo['store_id'], 'store_no' => $traceInfo['store_no'], 'agent_id' => $traceInfo['agent_id'],
            'bet_val' => $betVal, 'bet_double' => $traceInfo['bet_double'], 'is_bet_add' => $traceInfo['is_bet_add'], 'bet_money' => $traceInfo['bet_money'], 'source' => 2, 'count' => $traceInfo['count'],
            'chased_nums' => $chaseNums, 'periods_total' => $traceInfo['periods_total'], 'odds' => "", 'source_id' => $traceInfo['lottery_additional_id'], 'end_time' => $endTime, 'auto_type' => $outType, 'remark' => $traceInfo['remark']];
        $ret = $orderService->insertOrder($insert, false);          //这里的false表示直插入订单表中的记录
        $traceModel->chased_num = $chaseNums;
        if ($ret['error'] == false) {
            self::refundAdditional($traceInfo['lottery_additional_id'], 1);    //解冻冻结金额
            if ($chaseNums == $traceInfo['periods_total']) {
                $traceModel->status = 3;
            }
            $traceModel->save();
            return ['code' => 111, 'msg' => '追号生成订单失败,解冻金额'];
        }
        $orderCode = $ret["orderCode"];
        $betMoney = $traceInfo['bet_money'];
        $paySer = new PayService();
        $paySer->productPayRecord($traceInfo['cust_no'], $orderCode, 1, 1, $betMoney, 5);

        $payRecord = PayRecord::findOne(['order_code' => $orderCode, 'status' => 0]);
        $payRecord->pay_way = 3;
        $payRecord->pay_name = "余额";
        $payRecord->way_type = "YE";
        $payRecord->way_name = "余额";
        $payRecord->saveData();
        $fundsSer = new FundsService();
        $fundRet = $fundsSer->operateUserFunds($traceInfo['cust_no'], (0 - $betMoney), 0, (0 - $betMoney), true, '追号扣除冻结金额');
        if ($fundRet["code"] != 0) {
            $traceModel->chased_num = $chaseNums;
            if($chaseNums == $traceInfo['periods_total']) {
                $traceModel->status  = 3;
            }
            $traceModel->save();
            return ['code' => 109, 'msg' => '追号扣除冻结金额失败'];
        }
        $fundsSer->iceRecord($traceInfo['cust_no'], $payRecord->cust_type, $payRecord->order_code, $betMoney, 2, '追号扣除冻结金额');

        LotteryOrder::upData(['status' => 2], ['lottery_order_id' => $ret['orderId']]);
        //Yii::$app->db->createCommand()->update('lottery_order', ['status' => 2], ['lottery_order_id' => $ret['orderId']])->execute();

        $outer_no = Commonfun::getCode('YEP', 'Z');
        $funds = (new Query())->select('all_funds')->from('user_funds')->where(['cust_no' => $traceInfo['cust_no']])->one();
        \app\modules\common\models\PayRecord::upData(['status' => 1, 'outer_no' => $outer_no, 'modify_time' => date('Y-m-d H:i:s'), 'pay_time' => date('Y-m-d H:i:s'), 'pay_money' => $betMoney, 'balance' => $funds['all_funds']], ['order_code' => $orderCode]);
        //Yii::$app->db->createCommand()->update('pay_record', ['status' => 1, 'outer_no' => $outer_no, 'modify_time' => date('Y-m-d H:i:s'), 'pay_time' => date('Y-m-d H:i:s'), 'pay_money' => $betMoney, 'balance' => $funds['all_funds']], ['order_code' => $orderCode])->execute();
        if ($chaseNums == $traceInfo['periods_total']) {
            $traceModel->status = 3;
        }
        $traceModel->save();
        KafkaService::addQue('LotteryJob', ["orderId" => $ret['orderId']], true);
        //$lotteryqueue = new \LotteryQueue();
        //$lotteryqueue->pushQueue('lottery_job', 'default', ["orderId" => $ret['orderId']]);
        return ['code' => 600, 'msg' => '追号成功'];
    }

    /**
     * 追号解冻
     * @param integer $lotteryAdditionalId  
     * @param integer $chasedNum 退款几期
     */
    public static function refundAdditional($lotteryAdditionalId, $chasedNum) {
        $lotAddInfo = LotteryAdditional::findOne(["lottery_additional_id" => $lotteryAdditionalId, "status" => 2]);
        if ($lotAddInfo == null) {
            return false;
        }
        $refundMoney = $lotAddInfo->bet_money * $chasedNum;
        $fundsSer = new FundsService();
        $ret = $fundsSer->operateUserFunds($lotAddInfo->cust_no, 0, $refundMoney, (0 - $refundMoney), true, "追号解冻冻结金额");
        if ($refundMoney > 0) {
            $fundsSer->iceRecord($lotAddInfo->cust_no, $lotAddInfo->cust_type, $lotAddInfo->lottery_additional_code, $refundMoney, 2, "追号解冻冻结金额");
        }
        if ($ret["code"] == 0) {
            return true;
        }
        return false;
    }

    /**
     * 生成对应追号单
     * @param string $lotteryCode
     * @param string $oldVal
     * @return string
     */
    public static function randomOrder($lotteryCode, $oldVal) {
        $oldValArr = rtrim($oldVal, "^");
        $vals = explode("^", $oldValArr);
        $data = [];
        foreach ($vals as $val) {
            switch ($lotteryCode) {
                case "1001":       //双色球
                    $vs = explode("|", $val);
                    $redBall = explode(",", $vs[0]);
                    $blueBall = explode(",", $vs[1]);
                    $rBallArr = self::randomBall(1, 33, count($redBall), true);
                    $bBallArr = self::randomBall(1, 16, count($blueBall), true);
                    $data[] = implode(",", $rBallArr) . "|" . implode(",", $bBallArr);
                    break;
                case "1003":      //七乐彩
                    $redBall = explode(",", $val);
                    $rBallArr = self::randomBall(1, 30, count($redBall), true);
                    $data[] = implode(",", $rBallArr);
                    break;
                case "2001":      //大乐透
                    $vs = explode("|", $val);
                    $redBall = explode(",", $vs[0]);
                    $blueBall = explode(",", $vs[1]);
                    $rBallArr = self::randomBall(1, 35, count($redBall), true);
                    $bBallArr = self::randomBall(1, 12, count($blueBall), true);
                    $data[] = implode(",", $rBallArr) . "|" . implode(",", $bBallArr);
                    break;
                case "1002":     //福彩3D
                case "2002":     //排列三
                case "2003":     //排列五
                case "2004":     //七星彩
                    $vs = explode("|", $val);
                    $val = [];
                    foreach ($vs as $v) {
                        $balls = explode(",", $v);
                        $balls = self::randomBall(0, 9, count($balls));
                        $val[] = implode(",", $balls);
                    }
                    $data[] = implode("|", $val);
                    break;
                case "2005":     //广东11X5
                case "2006":     //江西11X5
                case "2007":     //山东11X5
                case '2010':     //湖北11X5
                case '2011':     //福建11X5
                    if (strpos($val, '#')) {
                        $vs = explode("#", $val);
                        $val = [];
                        foreach ($vs as $v) {
                            $balls = explode(",", $v);
                            $balls = self::randomBall(1, 11, count($balls), true);
                            $val[] = implode(",", $balls);
                        }
                        $data[] = implode("#", $val);
                    } else {
                        $vs = explode(";", $val);
                        $val = [];
                        foreach ($vs as $v) {
                            $balls = explode(",", $v);
                            $balls = self::randomBall(1, 11, count($balls), true);
                            $val[] = implode(",", $balls);
                        }
                        $data[] = implode(";", $val);
                    }
                    break;
            }
        }
        return implode("^", $data) . "^";
    }

    /**
     * 生成随机数数组
     * @param integer $min
     * @param integer $max
     * @param integer $num
     * @param boolean $addZero
     * @return array
     */
    public static function randomBall($min, $max, $num, $addZero = false) {
        $data = [];
        for ($key = 0; $key < $num; $key++) {
            while (1) {
                $val = rand($min, $max);
                if ($addZero == true) {
                    if ($val < 10) {
                        $val = sprintf("%02d", $val);
                    }
                }
                if (!in_array($val, $data)) {
                    $data[] = $val;
                    break;
                }
            }
        }

        sort($data);
        return $data;
    }

}

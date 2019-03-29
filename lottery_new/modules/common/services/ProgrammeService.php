<?php

namespace app\modules\common\services;

use Yii;
use app\modules\common\models\ProgrammeUser;
use app\modules\common\models\Programme;
use app\modules\common\services\OrderService;
use app\modules\common\services\FundsService;
use app\modules\common\helpers\Constants;
use app\modules\common\services\PayService;
use app\modules\common\helpers\Commonfun;
use yii\db\Query;
use app\modules\user\models\ThirdUser;
use app\modules\user\helpers\WechatTool;
use app\modules\competing\services\OptionalService;
use app\modules\competing\services\BasketService;
use app\modules\competing\helpers\CompetConst;
use app\modules\orders\services\MajorService;
use app\modules\orders\models\MajorData;
use app\modules\common\models\PayRecord;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\Store;
use app\modules\competing\services\FootballService;
use app\modules\orders\helpers\OrderDeal;
use app\modules\competing\services\BdService;
use app\modules\competing\services\WorldcupService;
use app\modules\orders\models\TicketDispenser;
use app\modules\numbers\services\SzcsService;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class ProgrammeService {

    /**
     * 合买方案操作
     * @param integer $programme_id
     * @param decimal $userBetMoney
     * @return boolean
     */
    public function operateProgramme($programme_id, $userBetMoney, $userBetNums) {
        $programme = Programme::findOne(["programme_id" => $programme_id]);
        if ($programme == null) {
            return false;
        }
        if ($programme->programme_last_amount < $userBetMoney) {
            return false;
        }
        $db = \Yii::$app->db;
        $ret = $db->createCommand("update programme set programme_buy_number=programme_buy_number+{$userBetNums},programme_last_amount=programme_last_amount-{$userBetMoney},programme_last_number = programme_last_number - {$userBetNums},programme_peoples=programme_peoples+1,programme_speed=floor(programme_buy_number/programme_all_number*100) where programme_id={$programme_id}")->execute();
        return $ret;
    }

    /**
     * 方案下单出票（由截止时间、满额触发）
     * @param integer $programmeId
     * @return boolean
     */
    public function playProgramme($programmeId) {
        $lotteryType = Constants::LOTTERY_ABBREVI;
        $programme = Programme::findOne(["programme_id" => $programmeId, "status" => 2]);
        if ($programme == null) {
            return false;
        }
        if ($programme->bet_status == 1) {
            $this->outProgrammeFalse($programmeId, 11, 'NoCon'); //11未上传方案撤单
            return false;
        }
        $ret = $this->playProgrammeBefore($programmeId);
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bd = CompetConst::MADE_BD_LOTTERY;
        $worldCup = CompetConst::MADE_WCUP_LOTTERY;
        $mCN = CompetConst::M_CHUAN_N;
        $guanArr = Constants::MANNER;
        if ($ret == true) {
            $odds = "";
            if (array_key_exists($programme->play_code, $mCN)) {
                $buildCode = $programme->play_code;
                $buildName = $programme->play_name;
                $playCode = $mCN[$buildCode];
                $arr = explode(',', $playCode);
                foreach ($arr as $iv) {
                    $playNameArr[] = $guanArr[$iv];
                }
                $playName = implode(',', $playNameArr);
            } else {
                $playCode = $programme->play_code;
                $playName = $programme->play_name;
                $buildCode = '';
                $buildName = '';
            }
            if (in_array($programme->lottery_code, $football)) {
                $competing = new FootballService();
                $ret = $competing->getCompetingCount($programme->lottery_code, ["nums" => trim($programme->bet_val, "^"), "play" => $playCode]);
                if ($ret["code"] == 0) {
                    $odds = json_encode($ret["odds"], true);
                } else {
                    $this->outProgrammeFalse($programmeId, 8, 'falseCon'); //方案失败
                    return false;
                }
            } elseif (in_array($programme->lottery_code, $basketball)) {
                $basketballService = new BasketService();
                $ret = $basketballService->calculationCount($programme->lottery_code, ['nums' => trim($programme->bet_val, '^'), 'play' => $playCode]);
                if ($ret["code"] == 0) {
                    $odds = json_encode($ret["odds"], true);
                } else {
                    $this->outProgrammeFalse($programmeId, 8, 'falseCon'); //方案失败
                    return false;
                }
            } elseif (in_array($programme->lottery_code, $bd)) {
                $bdService = new BdService();
                $ret = $bdService->calculationCount($programme->lottery_code, ['nums' => trim($programme->bet_val, '^'), 'play' => $playCode]);
                if ($ret['code'] == 0) {
                    $odds = json_encode($ret['odds'], true);
                } else {
                    $this->outProgrammeFalse($programmeId, 8, 'falseCon'); //方案失败
                    return false;
                }
            } elseif (in_array($programme->lottery_code, $worldCup)) {
                $ret = WorldcupService::calculationCount($programme->lottery_code, ['nums' => trim($programme->bet_val, '^'), 'play' => $playCode]);
                if ($ret['code'] == 0) {
                    $odds = json_encode($ret['odds'], true);
                } else {
                    $this->outProgrammeFalse($programmeId, 8, 'falseCon'); //方案失败
                    return false;
                }
            }
            $majorType = 0;
            if (in_array($programme->lottery_code, $football) || in_array($programme->lottery_code, $basketball)) {
                $majorData = MajorData::find()->select(['major_type'])->where(['order_id' => $programmeId, 'source' => 2])->asArray()->one();
                if (!empty($majorData)) {
                    $majorType = $majorType['major_type'];
                }
            }
            $orderData = ['lottery_code' => $programme->lottery_code, 'contents' => ['play' => $programme->play_code, 'nums' => $programme->bet_val], 'multiple' => $programme->bet_double,
                'count_bet' => $programme->count, 'end_time' => strtotime($programme->programme_end_time) * 1000];
            if ($majorType != 0) {
                $orderData['major_type'] = $majorType;
                $orderData['major_data'] = $majorData;
            }
            $outTicket = OrderDeal::judgeOutType($orderData);
            $overTime = OrderDeal::judgeTimeout($outTicket['outNums'], $outTicket['endTime'], $outTicket['outType'], $programme->store_no);
            if ($overTime['code'] != 600) {
                $this->outProgrammeFalse($programmeId, 8, 'outTime');
                return false;
            }
            $outType = $overTime['data'];
//            $autoLottery = Constants::AUTO_LOTTERY;
            $autoLottery = [];
            $store = TicketDispenser::find()->select(['out_lottery'])->where(['store_no' => $programme->store_no, 'type' => 2, 'status' => 1])->asArray()->one();
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
            if ($programme->store_no == \Yii::$app->params['auto_store_no'] && in_array($programme->lottery_code, $autoLottery)) {
                $outType = 2;
            }
            $ret = OrderService::insertOrder([
                        "lottery_type" => $lotteryType[$programme->lottery_code],
                        "play_code" => $playCode,
                        "play_name" => $playName,
                        "lottery_id" => $programme->lottery_code,
                        "lottery_name" => $programme->lottery_name,
                        "periods" => $programme->periods,
                        "cust_no" => $programme->expert_no,
                        "user_id" => $programme->user_id,
                        "cust_type" => $programme->cust_type,
                        "store_no" => (string) $programme->store_no,
                        "store_id" => $programme->store_id,
                        "agent_id" => "0",
                        "bet_val" => $programme->bet_val, "bet_double" => $programme->bet_double, "is_bet_add" => $programme->is_bet_add, "bet_money" => $programme->bet_money,
                        "source" => 4,
                        "count" => $programme->count,
                        "periods_total" => 1,
                        "odds" => $odds,
                        "source_id" => $programmeId,
                        "end_time" => $programme->programme_end_time,
                        "build_code" => $buildCode,
                        "build_name" => $buildName,
                        "major_type" => $majorType,
                        'auto_type' => $outType
                            ], false);
            if ($ret["error"] === true) {
                LotteryOrder::upData(["status" => "2"], ["lottery_order_id" => $ret["orderId"]]);
                /* Yii::$app->db->createCommand()->update("lottery_order", [
                  "status" => "2"
                  ], [
                  "lottery_order_id" => $ret["orderId"]
                  ])->execute(); */
                $programme1 = Programme::findOne(["programme_id" => $programmeId]);
                $programme1->bet_status = 3;
                $programme1->status = 3;
                $programme1->save();
                ProgrammeUser::updateAll(["status" => 3], ["programme_id" => $programme->programme_id, "status" => 2]);   //2、招募中  3、处理中
                KafkaService::addQue('LotteryJob', ["orderId" => $ret["orderId"]], true);
                //$lotteryqueue = new \LotteryQueue();
                //$lotteryqueue->pushQueue('lottery_job', 'default', ["orderId" => $ret["orderId"]]);
                return true;
            } else {
                $this->outProgrammeFalse($programmeId, 8, 'falsePlay'); //8、方案失败
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 方案出单前处理
     * @param integer $programmeId
     * @return boolean
     */
    public function playProgrammeBefore($programmeId) {
        $programme = Programme::findOne(["programme_id" => $programmeId]);
        if ($programme->programme_all_number == $programme->programme_buy_number && $programme->programme_last_amount == 0) {
            $fundSer = new FundsService();
            $minimumMoney = $programme->minimum_guarantee * $programme->programme_univalent; //保底金额
            $ret = $fundSer->operateUserFunds($programme->expert_no, 0, $minimumMoney, (0 - $minimumMoney), true, "解冻合买保底");
            if ($minimumMoney > 0) {
                $fundSer->iceRecord($programme->expert_no, $programme->cust_type, $programme->programme_code, $minimumMoney, 2, "解冻合买保底");
            }
            $programme->guarantee_status = 3;
            $programme->save();
            return true;
        } else {
            $ret = $this->guaranteeBuy($programmeId);
            if ($ret == false) {
                return false;
            }
            return true;
        }
    }

    /**
     * 方案保底购买处理
     * @param integer $programmeId
     * @return boolean
     */
    public function guaranteeBuy($programmeId) {
        $programme = Programme::findOne(["programme_id" => $programmeId]);
        $fundSer = new FundsService();
        $minimumMoney = $programme->minimum_guarantee * $programme->programme_univalent; //保底金额
        if ($programme->programme_last_amount <= $minimumMoney) {
            $db = Yii::$app->db;
            $tran = $db->beginTransaction();
            try {
                $payAmount = (0 - $programme->programme_last_amount);
                $returnAmount = ($minimumMoney - $programme->programme_last_amount);
                //$store = \app\modules\store\models\Store::findOne(["cust_no" => $programme->expert_no]);
                $user = \app\modules\user\models\User::findOne(['cust_no' => $programme->expert_no]);
                $ret = $fundSer->operateUserFunds($programme->expert_no, $payAmount, $returnAmount, 0 - $minimumMoney, true, "合买保底");
                if ($minimumMoney > 0) {
                    $fundSer->iceRecord($programme->expert_no, $programme->cust_type, $programme->programme_code, $minimumMoney, 2, "合买保底");
                }
                if ($ret["code"] != 0) {
                    $tran->rollBack();
                    $this->outProgrammeFalse($programmeId, 8); //8、方案失败
                    return false;
                }
                $ret = $this->sysBuyProgramme($programme->programme_id, $programme->programme_last_amount, $programme->minimum_guarantee, $programme->expert_no, $user->user_name, 1, $programme->cust_type, false);
                if ($ret == false) {
                    $tran->rollBack();
                    $this->outProgrammeFalse($programmeId, 8); //8、方案失败
                    return false;
                } else {
                    $programme->guarantee_status = 2;
                    $programme->save();
                    $tran->commit();
                    return true;
                }
            } catch (\yii\db\Exception $e) {
                $tran->rollBack();
                $this->outProgrammeFalse($programmeId, 8); //8、方案失败
                return false;
            }
        } else {
            $programme->status = 7;
            $programme->bet_status = 4;
            $ret = $programme->save();
            if ($ret === false) {
                //站内信 通知客服处理
            }
            ProgrammeUser::updateAll(["status" => 7], ["programme_id" => $programme->programme_id, "status" => 2]); //2、招募中  7、未满员撤单
            $this->refundProgramme($programme->programme_id, 7, "未满员退款");
            return false;
        }
    }

    /**
     * 方案退款处理
     * @param integer $programmeId
     * @return boolean
     */
    public function refundProgramme($programmeId, $status, $refund_reason = "退款理由", $type = '') {
        $fundSer = new FundsService();
        $proUsers = ProgrammeUser::find()->select("programme_user.programme_user_id, u.user_name as expert_name")
                        ->leftJoin('user as u', 'u.cust_no = programme_user.expert_no')
                        ->where(["programme_user.programme_id" => $programmeId, "programme_user.status" => $status, "programme_user.deal_status" => 1])->asArray()->all();
        $programme = Programme::findOne($programmeId);
        foreach ($proUsers as $proUser) {
            $programmeUser = ProgrammeUser::findOne($proUser["programme_user_id"]);
            $paySer = new PayService();
            $ret = $paySer->refund($programmeUser->programme_user_code, $refund_reason);
            if ($ret == false) {
                $programmeUser->deal_status = 5;
            } else {
                $programmeUser->deal_status = 4;
            }
            $ret = $programmeUser->save();
            if ($ret === false) {
                //站内信 通知客服处理
            }
            $thirdUser = ThirdUser::find()->select(['third_uid'])->where(['uid' => $programmeUser->user_id])->asArray()->one();
            if ($thirdUser['third_uid']) {
                $wechatTool = new WechatTool();
                $wechatTool->sendTemplateMsgProgrammeUse($thirdUser['third_uid'], $proUser['expert_name'], $programmeUser->bet_money, $programmeUser->programme_code, $programmeUser->create_time, $programme->programme_code, $type);
            }
        }
        if ($programme->guarantee_status == 1) {   //未参操作
            $minimumMoney = $programme->minimum_guarantee * $programme->programme_univalent; //保底金额
            $ret = $fundSer->operateUserFunds($programme->expert_no, 0, $minimumMoney, (0 - $minimumMoney), true, "解冻合买保底");
            if ($minimumMoney > 0) {
                $fundSer->iceRecord($programme->expert_no, $programme->cust_type, $programme->programme_code, $minimumMoney, 2, "解冻合买保底");
            }
            if ($ret["code"] != 0) {
                //站内信 通知客服处理
            }
        }
        $programme->bet_status = 5;
        $ret = $programme->save();
        if ($ret === false) {
            //站内信 通知客服处理
            return false;
        }
        return true;
    }

    /**
     * 系统自动购买方案
     * @param integer $programmeId
     * @param integer $payAmount
     * @param string $custNo
     * @param string $userName
     * @param integer $buyType
     * @param boolean $playProV
     * @return boolean
     */
    public function sysBuyProgramme($programmeId, $payAmount, $buyNums, $custNo, $userName = "", $buyType = 1, $custType = 1, $playProV = true) {
        $tran = Yii::$app->db->beginTransaction();
        try {
            $programme = Programme::findOne(["programme_id" => $programmeId]);
            $programmeUser = new ProgrammeUser();
            $programmeUser->expert_no = $programme->expert_no;
            $programmeUser->store_id = $programme->store_id;
            $programmeUser->programme_id = $programmeId;
            $programmeUser->bet_money = $payAmount;
            $programmeUser->buy_number = $buyNums;
            $programmeUser->programme_code = $programme->programme_code;
            $programmeUser->programme_user_code = Commonfun::getCode("FL", "G");
            $programmeUser->periods = $programme->periods;
            $programmeUser->lottery_code = $programme->lottery_code;
            $programmeUser->lottery_name = $programme->lottery_name;
            $programmeUser->buy_type = $buyType;
            $programmeUser->cust_no = $custNo;
            $programmeUser->user_id = $programme->user_id;
            $programmeUser->cust_type = $custType;
            $programmeUser->user_name = $userName;
            $programmeUser->status = 1; //未支付状态
            $programmeUser->create_time = date("Y-m-d H:i:s");
            $ret = $programmeUser->save();
            if ($ret === false) {
                return false;
            }
            $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $custNo])->one();
            $add=PayRecord::addData([
                "body" => "购彩消费-合买",
                "order_code" => $programmeUser->programme_user_code,
                "pay_no" => Commonfun::getCode("PAY", "L"),
                "pay_way" => 3,
                "pay_name" => "余额",
                "way_type" => "YE",
                "way_name" => "余额",
                "pay_type" => 5,
                "pay_type_name" => "合买",
                "pay_pre_money" => $payAmount,
                "cust_no" => $custNo,
                "cust_type" => 1,
                "balance" => $funds["all_funds"],
                "pay_time" => date("Y-m-d H:i:s"),
                "modify_time" => date("Y-m-d H:i:s"),
                "create_time" => date("Y-m-d H:i:s"),
                "status" => 1,
            ]);
            /* \Yii::$app->db->createCommand()->insert("pay_record", [
              "body" => "购彩消费-合买",
              "order_code" => $programmeUser->programme_user_code,
              "pay_no" => Commonfun::getCode("PAY", "L"),
              "pay_way" => 3,
              "pay_name" => "余额",
              "way_type" => "YE",
              "way_name" => "余额",
              "pay_type" => 5,
              "pay_type_name" => "合买",
              "pay_pre_money" => $payAmount,
              "cust_no" => $custNo,
              "cust_type" => 1,
              "balance" => $funds["all_funds"],
              "pay_time" => date("Y-m-d H:i:s"),
              "modify_time" => date("Y-m-d H:i:s"),
              "create_time" => date("Y-m-d H:i:s"),
              "status" => 1,
              ])->execute();
             */
            $outer_no = Commonfun::getCode("YEP", "Z");
            $ret = $this->programmeNotify($programmeUser->programme_user_code, $outer_no, $payAmount, date("Y-m-d H:i:s"), $playProV);
            if ($ret === true) {
                $tran->commit();
                return true;
            } else {
                return false;
            }
        } catch (\yii\db\Exception $e) {
        	KafkaService::addLog('program-log1', $e->getMessage());
            return false;
        }
    }

    /**
     * 合买回调
     * @param string $orderCode
     * @param string $outer_no
     * @param decimal $total_amount
     * @param boolean $playProV
     * @return boolean
     */
    public function programmeNotify($orderCode, $outer_no, $total_amount, $payTime, $playProV = true) {
        try {
        	$tran = Yii::$app->db->beginTransaction();
            $paySer = new PayService();
            $programmeUser = ProgrammeUser::find()->where(["programme_user_code" => $orderCode])->andWhere(["status" => 1])->// 1、未支付
                    one();
            if ($programmeUser != null) {
                $programme = Programme::findOne(["programme_id" => $programmeUser->programme_id]);
                if ($programme == null) {
                    return ["code" => 109, "msg" => "未找到该方案"];
                }
                $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $programmeUser->cust_no])->one();
                $ret = PayRecord::upData([
                            "status" => 1,
                            "outer_no" => $outer_no,
                            "modify_time" => date("Y-m-d H:i:s"),
                            "pay_time" => $payTime,
                            "pay_money" => $total_amount,
                            "balance" => $funds["all_funds"]
                                ], [
                            "order_code" => $orderCode,
                ]);
                /* $ret = \Yii::$app->db->createCommand()->update("pay_record", [
                  "status" => 1,
                  "outer_no" => $outer_no,
                  "modify_time" => date("Y-m-d H:i:s"),
                  "pay_time" => $payTime,
                  "pay_money" => $total_amount,
                  "balance" => $funds["all_funds"]
                  ], [
                  "order_code" => $orderCode,
                  ])->execute();
                 */
                if ($ret == false) {
                    return ["code" => 109, "msg" => "交易记录更新失败"];
                } else {
//                    if ($programmeUser->bet_money != $total_amount) {
//                        $ret = $paySer->refund($orderCode, "金额出错");
//                        $tran->commit();
//                        return false;
//                    }
                }
                $ret = $this->operateProgramme($programmeUser->programme_id, $programmeUser->bet_money, $programmeUser->buy_number);
                if ($ret === false) {
                    $programmeUser->status = 8;   //8、份额购买失败
                    $programmeUser->save();
                    $paySer->refund($orderCode, "份额购买失败");
                    $tran->commit();
                    return ["code" => 109, "msg" => "份额购买失败，已退款"];
                }
                ProgrammeUser::updateAll([
                    "status" => 2   //2、招募中
                        ], "programme_user_code='{$orderCode}' and status=1");  //1、未支付
                if ($playProV === true) {
                    $programme = Programme::findOne(["programme_id" => $programmeUser->programme_id]);
                    if ($programme->programme_last_amount == 0 && $programme->bet_status == 2) {
                        //调用异步出单
                        KafkaService::addQue('Programme', ["programmeId" => $programmeUser->programme_id], true);
                        //$lotteryqueue = new \LotteryQueue();
                        //$lotteryqueue->pushQueue('programme_job', 'default', ["programmeId" => $programmeUser->programme_id]);
                        // $lotteryqueue->pushQueue('backupOrder_job', 'backup', ['tablename' => 'lottery_order', "keyname" => 'lottery_order_id', 'keyval' => $lotOrder['lottery_order_id']]);
                    }
                }
            }
            $tran->commit();
            return true;
        } catch (\yii\db\Exception $e) {
            return ["code" => 109, "msg" => json_encode($e, true)];
        }
    }

    /**
     * 合买出单失败
     * @param integer $programmeId
     */
    public function outProgrammeFalse($programmeId, $falseStatus = 8, $type = '') {
        $programme = Programme::findOne(["programme_id" => $programmeId]);
        $programme->status = $falseStatus;
        $programme->save();
        ProgrammeUser::updateAll(["status" => $falseStatus], ["programme_id" => $programmeId, "status" => 2]); //2、招募中   8、出票失败
        $this->refundProgramme($programme->programme_id, $falseStatus, "合买出单失败", $type);
    }

    /**
     * 合买出票失败
     * @param integer $programmeId
     */
    public function outOrderFalse($programmeId, $falseStatus, $type = '') {
        $programme = Programme::findOne(["programme_id" => $programmeId]);
        $programme->status = $falseStatus;
        $programme->save();
        ProgrammeUser::updateAll(["status" => $falseStatus], ['or', ["programme_id" => $programmeId, "status" => 3], ["programme_id" => $programmeId, "status" => 12]]); //2、招募中   8、出票失败 12、 等待出票
        return $this->refundProgramme($programme->programme_id, $falseStatus, "合买出票失败", $type);
    }

    /**
     * 合买截止时间30分钟内的出单
     * @return boolean
     */
    public function limitTimePlay() {
        $time = date("Y-m-d H:i:s", strtotime("+40 minute"));
        $programmes = Programme::find()->select("programme_id")->where(["status" => 2])->andWhere(["<=", "programme_end_time", $time])->asArray()->all();
        foreach ($programmes as $val) {
            KafkaService::addQue('Programme', ["programmeId" => $val["programme_id"]], true);
            //$ret = $this->playProgramme($val["programme_id"]);
            //书写日志
        }
        return true;
    }

    /**
     * 添加方案
     * @param type $post   post数据
     * @param type $ownerBuyNum     自己订购份额
     * @param type $custNo          
     * @param type $storeId         门店id
     * @param type $payAmount       支付金额
     * @param type $name            用户名
     * @param type $custType        用户类型
     * @return type
     */
    public function addProgramme($post, $onePrice, $custNo, $storeId, $payAmount, $name, $custType = 1, $userId, $storeNo) {
        $tran = Yii::$app->db->beginTransaction();
        try {
            if (!isset($post["royalty_ratio"])) {
                return Yii::jsonResult(109, "提成比例未设置", "");
            }
            if ($post["royalty_ratio"] > 8 || $post["royalty_ratio"] < 0) {
                return Yii::jsonResult(109, "提成比例参数错误", "");
            }
            if ($post['owner_buy_number'] > $post["total"]) {
                return Yii::jsonResult(109, "自购金额大于总金额", "");
            }
            $programme = new Programme();
            $programme->lottery_code = $post["lottery_code"];
//                $programme->programme_title = $post["programme_title"];
            $programme->programme_money = $post["total"];
            $programme->security = $post["security"];
            $programme->minimum_guarantee = $post["minimum_guarantee"];
            $programme->owner_buy_number = $post['owner_buy_number'];
            $programme->programme_reason = $post["programme_reason"];
            $programme->royalty_ratio = $post["royalty_ratio"];
            $programme->create_time = date("Y-m-d H:i:s");
            $programme->expert_no = $custNo;
            $programme->user_id = $userId;
            $programme->store_id = $storeId;
            $programme->store_no = $storeNo;
            $programme->cust_type = $custType;
            $programme->programme_code = Commonfun::getCode("FA", "F");
            $programme->status = 2;
            if (isset($post["chase"]) && !empty($post["chase"]) && $post["chase"] != 1) {
                return Yii::jsonResult(109, "合买不允许追期", "");
            }
            if ($post["lottery_type"] == 1) {
                $ret = $this->playNumVerification($programme->lottery_code);
                $programme->periods = $post["periods"];
                if ($programme->lottery_code == "2001") {
                    $programme->is_bet_add = isset($post["is_bet_add"]) ? $post["is_bet_add"] : 0;
                } else {
                    $programme->is_bet_add = 0;
                }
            } elseif ($post["lottery_type"] == 2) {
                $competing = new FootballService();
                $ret = $competing->playVerification($programme->lottery_code);
                $periods = $ret['data']['max_time'];
                $programme->periods = $periods;
            } elseif ($post['lottery_type'] == 3) {
                $competing = new OptionalService();
                $ret = $competing->OptionalVerification($programme->lottery_code);
                $programme->periods = $post['periods'];
            } elseif ($post['lottery_type'] == 4) {
                $basketService = new BasketService();
                $ret = $basketService->playVerification($programme->lottery_code);
                $periods = $ret['data']['max_time'];
            } else {
                return Yii::jsonResult(109, "未开放该彩票类型", "");
            }
            if ($ret["code"] != 0) {
                return Yii::jsonError(2, $ret['msg']);
            }
            $programme->programme_all_number = $post["programme_all_number"];
            $programme->programme_last_amount = $post["total"];
            $programme->programme_last_number = $post['programme_all_number'];
            $programme->programme_end_time = $ret["data"]["limit_time"];
            $programme->bet_val = $ret["data"]["bet_val"];
            $programme->bet_money = $post["total"];
            $programme->lottery_name = $ret["data"]["lottery_name"];
            $programme->play_code = $ret["data"]["play_code"];
            $programme->play_name = $ret["data"]["play_name"];
            $programme->bet_double = $post["multiple"];
            $programme->count = $post["count_bet"];
            $programme->bet_status = 2;
            $programme->programme_univalent = $onePrice;
            if ($programme->validate()) {
                $ret = $programme->save();
                if ($ret === false) {
                    return Yii::jsonResult(109, "参数错误", "");
                } else {
                    $fundsSer = new FundsService();
                    $guaranteeAmount = $programme->minimum_guarantee * $onePrice;
                    $ownerAmount = $programme->owner_buy_number * $onePrice;
                    $ret1 = [];
                    ($ownerAmount > 0 || $guaranteeAmount > 0) && $ret1 = $fundsSer->operateUserFunds($custNo, (0 - $ownerAmount), (0 - $payAmount), $guaranteeAmount, true, "发布方案保底、自己认购百分之十");
                    if ($ownerAmount > 0) {
                        $ret2 = $this->sysBuyProgramme($programme->programme_id, $ownerAmount, $programme->owner_buy_number, $custNo, $name, 1, $custType);
                        if ($ret2 == false) {
                            $tran->rollBack();
                            return \Yii::jsonResult(109, "方案发布失败", "");
                        }
                        if ($ret1["code"] != 0) {
                            $tran->rollBack();
                            \Yii::jsonError(109, "方案发布失败,余额操作失败");
                        }
                    }
                    if ($guaranteeAmount > 0) {
                        $fundsSer->iceRecord($programme->expert_no, $programme->cust_type, $programme->programme_code, $guaranteeAmount, 1, "发布方案保底");
                    }

                    $tran->commit();
                    $footBallCode = Constants::MADE_FOOTBALL_LOTTERY;
                    $baskballCode = CompetConst::MADE_BASKETBALL_LOTTERY;
                    if (in_array($post['lottery_code'], $footBallCode)) {
                        $madeCode = "3000";
                    } elseif (in_array($post['lottery_code'], $baskballCode)) {
                        $madeCode = '3100';
                    } else {
                        $madeCode = $post['lottery_code'];
                    }
                    if (array_key_exists('major_type', $post) && array_key_exists('major_data', $post)) {
                        $majorService = new MajorService();
                        $majorData = json_encode($post['major_data']);
                        $majorService->createMajor($programme->programme_id, $majorData, $post['major_type'], 2);
                    }
                    KafkaService::addQue('CustomMade', ['expert_no' => $custNo, 'lottery_code' => $madeCode, 'bet_nums' => $programme->programme_last_number, 'programme_id' => $programme->programme_id, 'programme_price' => $onePrice]);
                    //$lotteryqueue = new \LotteryQueue();
                    //$lotteryqueue->pushQueue('custom_made_job', 'default', ['expert_no' => $custNo, 'lottery_code' => $madeCode, 'bet_nums' => $programme->programme_last_number, 'programme_id' => $programme->programme_id, 'programme_price' => $onePrice]);
                    $expertLevel = new ExpertLevelService;
                    $expertLevel->createUpdate($userId, $custNo);
                    return \Yii::jsonResult(600, "方案发布成功", ["programme_code" => $programme->programme_code]);
                }
            } else {
                return \Yii::jsonResult(109, "参数错误", $programme->getFirstErrors());
            }
        } catch (\yii\db\Exception $e) {
            $tran->rollBack();
            return \Yii::jsonResult(109, "方案发布失败", $e);
        }
    }

    /**
     * 创建预方案（未上传方案）
     * @param type $post   post数据
     * @param type $ownerBuyNum   自己认购份额
     * @param type $custNo
     * @param type $storeId
     * @param type $payAmount     支付金额
     * @param type $name           用户名称
     * @param type $custType       用户类型
     * @return type
     */
    public function addPreProgramme($post, $onePrice, $custNo, $storeId, $payAmount, $name, $custType = 1, $userId, $storeNo) {
        $tran = Yii::$app->db->beginTransaction();
        try {
            if (!isset($post["royalty_ratio"])) {
                return Yii::jsonResult(109, "提成比例未设置", "");
            }
            if ($post["royalty_ratio"] > 8 || $post["royalty_ratio"] < 0) {
                return Yii::jsonResult(109, "提成比例参数错误", "");
            }
            if ($post['owner_buy_number'] > $post["total"]) {
                return Yii::jsonResult(109, "自购金额大于总金额", "");
            }
            $lotterys = Constants::LOTTERY;
            $programme = new Programme();
            $programme->lottery_code = $post["lottery_code"];
            $programme->lottery_name = $lotterys[$post["lottery_code"]];
            $programme->programme_money = $post["total"];
            $programme->security = $post["security"];
            $programme->minimum_guarantee = $post["minimum_guarantee"];
            $programme->owner_buy_number = $post['owner_buy_number'];
            $programme->programme_reason = $post["programme_reason"];
            $programme->royalty_ratio = $post["royalty_ratio"];
            $programme->create_time = date("Y-m-d H:i:s");
            $programme->user_id = $userId;
            $programme->expert_no = $custNo;
            $programme->store_id = $storeId;
            $programme->store_no = $storeNo;
            $programme->cust_type = $custType;
            $programme->programme_code = Commonfun::getCode("FA", "F");
            $programme->programme_end_time = date("Y-m-d H:i:s", strtotime("+120 hours"));
            $programme->status = 2;
            $programme->programme_all_number = $post["programme_all_number"];
            $programme->programme_last_amount = $post["total"];
            $programme->programme_last_number = $post['programme_all_number'];
            $programme->bet_money = $post["total"];
            $programme->bet_status = 1;
            $programme->programme_univalent = $onePrice;
            if (isset($post["chase"]) && !empty($post["chase"]) && $post["chase"] != 1) {
                return Yii::jsonResult(109, "合买不允许追期", "");
            }
            if ($programme->validate()) {
                $ret = $programme->save();
                if ($ret === false) {
                    return Yii::jsonResult(109, "参数错误", "");
                } else {
                    $fundsSer = new FundsService();
                    $guaranteeAmount = $programme->minimum_guarantee * $onePrice;
                    $ownerAmount = $programme->owner_buy_number * $onePrice;
                    $ret1 = [];
                    ($ownerAmount > 0 || $guaranteeAmount > 0) && $ret1 = $fundsSer->operateUserFunds($custNo, (0 - $ownerAmount), (0 - $payAmount), $guaranteeAmount, true, "发布方案保底、自己认购百分之十");
                    if ($ownerAmount > 0) {
                        $ret2 = $this->sysBuyProgramme($programme->programme_id, $ownerAmount, $programme->owner_buy_number, $custNo, $name, 1, $custType);
                        if ($ret2 == false) {
                            $tran->rollBack();
                            return \Yii::jsonResult(109, "方案发布失败", "");
                        }
                        if ($ret1["code"] != 0) {
                            $tran->rollBack();
                            return json_encode($ret);
                        }
                    }
                    if ($guaranteeAmount > 0) {
                        $fundsSer->iceRecord($programme->expert_no, $programme->cust_type, $programme->programme_code, $guaranteeAmount, 1, "发布方案保底");
                    }
                    $tran->commit();
                    $footBallCode = Constants::MADE_FOOTBALL_LOTTERY;
                    if (in_array($post['lottery_code'], $footBallCode)) {
                        $madeCode = "3000";
                    } else {
                        $madeCode = $post['lottery_code'];
                    }
                    KafkaService::addQue('CustomMade', ['expert_no' => $custNo, 'lottery_code' => $madeCode, 'bet_nums' => $programme->programme_last_number, 'programme_id' => $programme->programme_id, 'programme_price' => $onePrice]);
                    //$lotteryqueue = new \LotteryQueue();
                    //$lotteryqueue->pushQueue('custom_made_job', 'default', ['expert_no' => $custNo, 'lottery_code' => $madeCode, 'bet_nums' => $programme->programme_last_number, 'programme_id' => $programme->programme_id, 'programme_price' => $onePrice]);
                    $expertLevel = new ExpertLevelService;
                    $expertLevel->createUpdate($userId, $custNo);
                    return \Yii::jsonResult(600, "方案发布成功", ["programme_code" => $programme->programme_code]);
                }
            } else {
                return \Yii::jsonResult(109, "参数错误", $programme->getFirstErrors());
            }
        } catch (\yii\db\Exception $e) {
            $tran->rollBack();
            return \Yii::jsonResult(109, "方案发布失败", $e);
        }
    }

    /**
     * 上传投注内容
     * @param type $expertNo    专家no
     * @param type $programmeCode   合买方案code
     * @param type $post            post合买数据
     * @return json
     */
    public function playBetVal($expertNo, $programmeCode, $post) {
        $programme = Programme::findOne(["expert_no" => $expertNo, "programme_code" => $programmeCode, "bet_status" => 1, "status" => 2]);
        if ($programme == null) {
            return Yii::jsonResult(109, "未找到该方案", "");
        }
        if ($programme->bet_money < $post["total"]) {
            return Yii::jsonResult(109, "金额超过方案总金额", "");
        }
        if ($programme->bet_money > $post["total"]) {
            return Yii::jsonResult(109, "金额低于方案总金额", "");
        }

        if ($programme->programme_end_time < date("Y-m-d H:i:s")) {
            return Yii::jsonResult(109, "该方案已超时", "");
        }
        if (in_array($programme->lottery_code, ["1001", "1002", "1003", "2001", "2002", "2003", "2004"])) {
            $ret = $this->playNumVerification($programme->lottery_code);
            if ($ret["code"] != 0) {
                echo json_encode($ret);
                exit();
            }
            $programme->periods = $post["periods"];
            if ($programme->lottery_code == "2001") {
                $programme->is_bet_add = isset($post["is_bet_add"]) ? $post["is_bet_add"] : 0;
            } else {
                $programme->is_bet_add = 0;
            }
        } elseif (in_array($programme->lottery_code, ["3000", "3006", "3007", "3008", "3009", "3010", "3011"]) && in_array($post["lottery_code"], ["3006", "3007", "3008", "3009", "3010", "3011"])) {
            $competing = new FootballService();
            $programme->lottery_code = $post["lottery_code"];
            $lotterys = Constants::LOTTERY;
            $programme->lottery_name = $lotterys[$post["lottery_code"]];
            $ret = $competing->playVerification($post["lottery_code"]);
            if ($ret["code"] != 0) {
                echo json_encode($ret);
                exit();
            }
            $periods = $ret['data']['max_time'];
            $programme->periods = $periods;
        } else {
            return Yii::jsonResult(109, "未开放该彩票类型", "");
        }
        $programme->programme_end_time = $ret["data"]["limit_time"];
        $programme->bet_val = $ret["data"]["bet_val"];
        $programme->play_code = $ret["data"]["play_code"];
        $programme->play_name = $ret["data"]["play_name"];
        $programme->bet_double = $post["multiple"];
        $programme->count = $post["count_bet"];
        $programme->bet_status = 2;
        if ($programme->validate()) {
            $ret = $programme->save();
            if ($ret === false) {
                return Yii::jsonResult(109, "参数错误", "");
            } else {
                if (array_key_exists('major_type', $post) && array_key_exists('major_data', $post)) {
                    $majorService = new MajorService();
                    $majorData = json_encode($post['major_data']);
                    $majorService->createMajor($programme->programme_id, $majorData, $post['major_type'], 2);
                }
                if ($programme->programme_last_amount == 0 && $programme->bet_status == 2) {
                    //调用异步出单
                    KafkaService::addQue('Programme', ["programmeId" => $programme->programme_id], true);
                    //$lotteryqueue = new \LotteryQueue();
                    //$lotteryqueue->pushQueue('programme_job', 'default', ["programmeId" => $programme->programme_id]);
                }
                return \Yii::jsonResult(600, "方案投注添加成功", '');
            }
        } else {
            return \Yii::jsonResult(109, "参数错误", $programme->getFirstErrors());
        }
    }

    /**
     * 合买投注内容校验
     * @param string $lotteryCode   玩法
     * @return type
     */
    public function playNumVerification($lotteryCode) {
        $request = Yii::$app->request;
        $post = $request->post();
        $current = Commonfun::currentPeriods($post['lottery_code']);
        if ($current['error'] == true) {
            if ($post['periods'] != $current['periods']) {
                return \Yii::jsonResult(40008, '投注失败，超时,此期已过期，请重新投注', '');
            }
        } else {
            return \Yii::jsonResult(40007, '投注失败，此彩种已经停止投注，请选择其他彩种进行投注', '');
        }

        if (!isset($post['contents'])) {
            return \Yii::jsonResult(2, '投注内容不可为空,请重新投注', '');
        }

        if (!is_array($post['contents'])) {
            return \Yii::jsonResult(2, '投注失败，请重新投注', '');
        }

        if (!Commonfun::numsDifferent($post['contents'])) {
            return \Yii::jsonResult(2, '投注失败，请重新投注', '');
        }
        switch ($lotteryCode) {
            case "1001":
            case "1002":
            case "1003":
            case "2001":
            case "2002":
            case "2003":
            case "2004":
                $ret = SzcsService::playVerification();
                break;
            default :
                return \Yii::jsonResult(109, '错误彩种', '');
        }
        $ret["data"]["limit_time"] = $current["data"]["limit_time"];
        return $ret;
    }

    /**
     * 获取中奖历史合买
     * @param type $expertNo   专家号
     * @param type $page        页码
     * @param type $size        长度
     * @return type
     */
    public function getWinHistoryProgrammes($expertNo, $page = 1, $size = 10) {
        $total = Programme::find()->where(["expert_no" => $expertNo, "status" => 6])->count();
        $programmes = Programme::find()->select(["bet_money", "lottery_code", "lottery_name", "win_amount", "create_time"])->where(["expert_no" => $expertNo, "status" => 6])->offset($size * ($page - 1))->limit($size)->orderBy("create_time desc")->asArray()->all();
        foreach ($programmes as &$val) {
            $val["return_rate"] = sprintf("%.1f", $val["win_amount"] * 100 / $val["bet_money"]);
            if ($val["lottery_code"] > 3000 && $val["lottery_code"] < 3012) {
                $val["lottery_name"] = "竞彩" . $val["lottery_name"];
            }
        }
        $data = [];
        $data["data"] = $programmes;
        $data["page"] = $page;
        $data["size"] = count($programmes);
        $data["pages"] = ceil($total / $size);
        $data["total"] = $total;

        return $data;
    }

    public function getRecentProgrammes($expertNo, $page = 1, $size = 10) {
        $total = Programme::find()->where(["programme.expert_no" => $expertNo])->andWhere(["in", "programme.status", [2, 3, 4]])->count();
        $programmes = Programme::find()->select(["programme.programme_univalent", "programme.programme_code", "programme.bet_money", "programme.lottery_code", "programme.lottery_name", "programme.programme_speed", "programme.programme_peoples", "programme.programme_last_amount", "programme.programme_all_number", "programme.minimum_guarantee", "programme.create_time", "user.user_name"])->join("left join", "user", "user.cust_no=programme.expert_no")->where(["programme.expert_no" => $expertNo])->andWhere(["in", "programme.status", [2, 3, 4]])->offset($size * ($page - 1))->limit($size)->orderBy("create_time desc")->asArray()->all();
        foreach ($programmes as &$val) {
            $minimumMoney = $val["minimum_guarantee"] * $val["programme_univalent"]; //保底金额
            $val["per_order_speed"] = sprintf("%d", $minimumMoney * 100 / $val["bet_money"]);
            if ($val["lottery_code"] > 3000 && $val["lottery_code"] < 3012) {
                $val["lottery_name"] = "竞彩足球";
            }
        }

        $data = [];
        $data["data"] = $programmes;
        $data["page"] = $page;
        $data["size"] = count($programmes);
        $data["pages"] = ceil($total / $size);
        $data["total"] = $total;
        return $data;
    }

    /**
     * 追加保底金额
     * @param type $expertNo         专家号
     * @param type $programmeCode   合买方案code
     * @param type $money           追加金额
     * @return boolean
     */
    public function appendMinimumGuarantee($expertNo, $programmeCode, $money) {
        $db = \Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
            $programme = Programme::findOne(["expert_no" => $expertNo, "programme_code" => $programmeCode]);
            if ($programme == null) {
                return \Yii::jsonResult(109, "未找到该合买方案", "");
            }
            if ($money > 0) {
                return \Yii::jsonResult(109, "追加金额错误", "");
            }
            if ($programme->status != 2) {
                return \Yii::jsonResult(109, "不可追加保底", "");
            }
            $minimumMoney = $programme->minimum_guarantee * $programme->programme_univalent; //保底金额
            $minimumMoney+=$money;
            $fundSer = new FundsService();
            $ret = $fundSer->operateUserFunds($expertNo, 0, (0 - $money), $money, true);
            if ($ret["code"] == 0) {
                $fundSer->iceRecord($expertNo, 1, $programmeCode, $money, 1);
                $ret = $programme->save();
                if ($ret === false) {
                    $tran->rollBack();
                    return \Yii::jsonResult(109, "追加保底失败", "");
                }
            } else {
                echo json_encode($ret);
                exit();
            }
            $tran->commit();
            return true;
        } catch (yii\base\Exception $e) {
            $tran->rollBack();
            return \Yii::jsonResult(109, "抛出错误", $e);
        }
    }

    /**
     *
     * 发起合买订单(金额为0的话直接下单 ps走旧接口)
     *
     * @param unknown $params 提交的参数
     */
    public static function creatProgramOrder($userNo, $userId, $params) {
        $vali = self::validateCreatProgramOrder($params);
        $user = \app\modules\user\models\User::findOne(['cust_no' => $userNo]);
        $userFunds = \app\modules\common\models\UserFunds::findOne(['cust_no' => $userNo]);
        if ($vali['code'] == 600) {
            $programme = new \app\modules\common\services\ProgrammeService;
            $payAmount = ($params['minimum_guarantee'] + $params['owner_buy_number']) * $vali['data']['onePrice'];
            $payAmount == 0 && Yii::jsonError("支付金额不正确", 110);
            $programmeCode = self::addUnpayProgramOrder($params, $vali['data']['onePrice'], $userNo, $vali['data']['storeId'], $payAmount, $user->user_name, 1, $userId, $vali['data']['store_code']);
            return ["code" => 600, "msg" => "下注成功！", "result" => ["lottery_order_code" => $programmeCode]];
        }
        //Yii::jsonError($vali['code'], $vali['msg']);
    }

    /**
     * 验证发起合买参数合法性
     * 
     */
    public static function validateCreatProgramOrder($params) {
        $storeNo = $params['store_id'];
        !$storeNo && Yii::jsonError(109, "出票门店未设置");
        $store = Store::find()->select(['user_id', 'store_code', 'sale_lottery', 'business_status'])->where(['store_code' => $storeNo, 'status' => 1])->one();
        !$store && Yii::jsonError("设置的出票门店不存在", 109);
        ($store['business_status'] != 1) && Yii::jsonError("该门店已暂停营业！！", 2);
        $storeId = $store['user_id']; // 店铺用户的UID
        $lotteryCode = $params['lottery_code']; // 彩种编码
        $saleLotteryArr = explode(',', $store['sale_lottery']); // 店铺出售的彩种
        in_array(3000, $saleLotteryArr) && array_push($saleLotteryArr, '3006', '3007', '3008', '3009', '3010', '3011');
        in_array(3100, $saleLotteryArr) && array_push($saleLotteryArr, '3001', '3002', '3003', '3004', '3005');
        in_array(3300, $saleLotteryArr) && array_push($saleLotteryArr, '301201', '301301');
        !in_array($lotteryCode, $saleLotteryArr) && Yii::jsonError(488, "你所购买的彩种，该门店不可接单！");
        $onePrice = intval($params['total']) / intval($params['programme_all_number']);
        !is_int($onePrice) && Yii::jsonError(109, "份额配比不对");
        ($params['owner_buy_number'] > $params["total"]) && Yii::jsonError(109, "自购金额错误范围有误");
        ($lotteryCode != 3000 && !isset($params["lottery_type"])) && Yii::jsonError(109, "未设置彩票类型");
        !isset($params["royalty_ratio"]) && Yii::jsonError(109, "提成比例未设置");
        ($params["royalty_ratio"] > 8 || $params["royalty_ratio"] < 0) && Yii::jsonError(109, "提成比例参数错误");
        ($params['owner_buy_number'] > $params["total"]) && Yii::jsonError(109, "自购金额大于总金额");
        (isset($params["chase"]) && !empty($params["chase"]) && $params["chase"] != 1) && Yii::jsonError(109, "合买不允许追期");
        return ['code' => 600, 'data' => ['storeId' => $storeId, 'onePrice' => $onePrice, 'store_code' => $store['store_code']]];
    }

    /**
     * 创建合买未支付订单
     *  @param type $post   post数据
     * @param type $ownerBuyNum     自己订购份额
     * @param type $custNo          
     * @param type $storeId         门店id
     * @param type $payAmount       支付金额
     * @param type $name            用户名
     * @param type $custType        用户类型
     * @return type
     */
    public static function addUnpayProgramOrder($post, $onePrice, $custNo, $storeId, $payAmount, $name, $custType = 1, $userId, $storeNo) {
        $tran = Yii::$app->db->beginTransaction();
        try {
            $programme = new Programme();
            $programme->lottery_code = $post["lottery_code"];
            $programme->programme_money = $post["total"];
            $programme->security = $post["security"];
            $programme->minimum_guarantee = $post["minimum_guarantee"];
            $programme->owner_buy_number = $post['owner_buy_number'];
            $programme->programme_reason = $post["programme_reason"];
            $programme->royalty_ratio = $post["royalty_ratio"];
            $programme->create_time = date("Y-m-d H:i:s");
            $programme->expert_no = $custNo;
            $programme->user_id = $userId;
            $programme->store_id = $storeId;
            $programme->store_no = $storeNo;
            $programme->cust_type = $custType;
            $programme->programme_code = Commonfun::getCode("FA", "F");
            $programme->status = 1; //未发布（未支付）
            if ($post["lottery_code"] != 3000) {//足彩已发布方案的情况
                $ret = self::doHasChooseBetContent($programme, $post);
                $programme->count = $post["count_bet"];
                $programme->bet_double = $post["multiple"];
                $programme->programme_end_time = $ret["data"]["limit_time"];
                $programme->bet_val = $ret["data"]["bet_val"];
                $programme->lottery_name = $ret["data"]["lottery_name"];
                $programme->play_code = $ret["data"]["play_code"];
                $programme->play_name = $ret["data"]["play_name"];
            } else {
                $lotterys = Constants::LOTTERY;
                $programme->lottery_name = $lotterys[$post["lottery_code"]];
                $programme->programme_end_time = date("Y-m-d H:i:s", strtotime("+120 hours"));
            }
            $programme->programme_all_number = $post["programme_all_number"];
            $programme->programme_last_amount = $post["total"];
            $programme->programme_last_number = $post['programme_all_number'];
            $programme->bet_money = $post["total"];
            $programme->bet_status = $post["lottery_code"] != 3000 ? 2 : 1;
            $programme->programme_univalent = $onePrice;
            if ($programme->validate()) {
                if (!$programme->save()) {
                    throw new \Exception('添加合买方案失败', 111);
                }
            } else {
                \Yii::jsonError(112, "参数不完整");
            }
            if ($programme->owner_buy_number > 0) {//自己有认购(回调时候统一处理)
                //$ownerAmount = $programme->owner_buy_number * $onePrice;
                //$proUserCode=self::creatUnpayProUser($programme, $ownerAmount, $programme->owner_buy_number, $custNo, $name, 1, $custType);
            }
            $tran->commit();
            if (array_key_exists('major_type', $post) && array_key_exists('major_data', $post)) {//奖金优化表处理
                $majorService = new MajorService();
                $majorData = json_encode($post['major_data']);
                $majorService->createMajor($programme->programme_id, $majorData, $post['major_type'], 2);
            }
        } catch (\Exception $e) {
            $tran->rollBack();
            \Yii::jsonError($e->getCode(), $e->getMessage());
        }
        return $programme->programme_code;
    }

    /**
     * 已发布方案方案内容处理
     * @param \app\modules\common\models\Programme $programme
     * @param unknown $post
     * @return array
     */
    public static function doHasChooseBetContent(\app\modules\common\models\Programme $programme, $post) {
        switch (true) {
            case $post["lottery_type"] == 1:
                $programmeS = new ProgrammeService();
                $ret = $programmeS->playNumVerification($programme->lottery_code);
                $programme->periods = $post["periods"];
                if ($programme->lottery_code == "2001") {
                    $programme->is_bet_add = (isset($post["is_bet_add"]) && $post['is_bet_add'] == true)  ? 1 : 0;
                } else {
                    $programme->is_bet_add = 0;
                }
                break;
            case $post["lottery_type"] == 2:
                $competing = new FootballService();
                $ret = $competing->playVerification($programme->lottery_code);
                $maxTime = (int) $post['max_time'];
                $period = strtotime('+3 hour', $maxTime);
                $periods = (string) $period;
                $programme->periods = $periods;
                break;
            case $post['lottery_type'] == 3:
                $competing = new OptionalService();
                $ret = $competing->OptionalVerification($programme->lottery_code);
                $programme->periods = $post['periods'];
                break;
            case $post['lottery_type'] == 4:
                $basketService = new BasketService();
                $ret = $basketService->playVerification($programme->lottery_code);
                break;
            case $post['lottery_type'] == 5:
                $bdService = new BdService();
                $ret = $bdService->playVerification($programme->lottery_code);
                break;
            case $post['lottery_type'] == 6:
                $ret = WorldcupService::WorldcupVerification($programme->lottery_code);
                break;
        }
        $ret["code"] != 0 && Yii::jsonError(2, $ret['msg']);
        return $ret;
    }

    /**
     * 生成一条为支付状态的合买记录
     */
    public static function creatUnpayProUser(\app\modules\common\models\Programme $programme, $payAmount, $buyNums, $custNo, $userName = "", $buyType = 1, $custType = 1, $playProV = true) {
        $tran = Yii::$app->db->beginTransaction();
        try {
            $programmeUser = new ProgrammeUser();
            $programmeUser->expert_no = $programme->expert_no;
            $programmeUser->store_id = $programme->store_id;
            $programmeUser->programme_id = $programmeId;
            $programmeUser->bet_money = $payAmount;
            $programmeUser->buy_number = $buyNums;
            $programmeUser->programme_code = $programme->programme_code;
            $programmeUser->programme_user_code = Commonfun::getCode("FL", "G");
            $programmeUser->periods = $programme->periods;
            $programmeUser->lottery_code = $programme->lottery_code;
            $programmeUser->lottery_name = $programme->lottery_name;
            $programmeUser->buy_type = $buyType;
            $programmeUser->cust_no = $custNo;
            $programmeUser->user_id = $programme->user_id;
            $programmeUser->cust_type = $custType;
            $programmeUser->user_name = $userName;
            $programmeUser->status = 1; //未支付状态
            $programmeUser->create_time = date("Y-m-d H:i:s");
            if (!$programmeUser->save()) {
                throw new \Exception('添加认购合买失败', 112);
            }
            $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $custNo])->one();
            if (!PayRecord::addData([
                        "body" => "购彩消费-合买",
                        "order_code" => $programmeUser->programme_user_code,
                        "pay_no" => Commonfun::getCode("PAY", "L"),
                        "pay_way" => 3,
                        "pay_name" => "余额",
                        "way_type" => "YE",
                        "way_name" => "余额",
                        "pay_type" => 5,
                        "pay_type_name" => "合买",
                        "pay_pre_money" => $payAmount,
                        "cust_no" => $custNo,
                        "cust_type" => 1,
                        "balance" => $funds["all_funds"],
                        "pay_time" => date("Y-m-d H:i:s"),
                        "modify_time" => date("Y-m-d H:i:s"),
                        "create_time" => date("Y-m-d H:i:s"),
                        "status" => 1,
                    ])) {
                throw new \Exception('支付记录表失败', 113);
            }
            $tran->commit();
        } catch (\Exception $e) {
            $tran->rollBack();
            \Yii::jsonError($e->getCode(), $e->getMessage());
        }
        return $programmeUser->programme_user_code;
    }

    /**
     * 发起合买回调
     * @param string $orderCode
     * @param string $outer_no
     * @param decimal $total_amount
     * @param boolean $playProV
     * @return boolean
     */
    public function creatProgrammeNotify(\app\modules\common\models\Programme $programme) {
        if ($programme->status != 1) {
            return ["code" => 110, "msg" => '已经支付'];
        }
        $tran = Yii::$app->db->beginTransaction();
        try {
            $fundsSer = new FundsService();
            $guaranteeAmount = $programme->minimum_guarantee * $programme->programme_univalent;
            $ownerAmount = $programme->owner_buy_number * $programme->programme_univalent;
            $payAmount = $guaranteeAmount + $ownerAmount;
            $custNo = $programme->expert_no;
            $userData = \app\modules\user\models\User::findOne(['cust_no' => $custNo]);
            $name = $userData->user_name;
            if (($ownerAmount > 0 || $guaranteeAmount > 0)) {
                $ret1 = $fundsSer->operateUserFunds($custNo, (0 - $ownerAmount), (0 - $payAmount), $guaranteeAmount, true, "发布方案保底、自己认购百分之十");
                if ($ownerAmount > 0) {
                    $ret2 = $this->sysBuyProgramme($programme->programme_id, $ownerAmount, $programme->owner_buy_number, $custNo, $name, 1, $programme->cust_type);
                    if ($ret2 == false) {
                        throw new \Exception('系统自动购买方案失败', 201);
                    }
                    if ($ret1["code"] != 0) {
                        throw new \Exception($ret1['msg'], 200);
                    }
                }
                if ($guaranteeAmount > 0) {
                    if (!$fundsSer->iceRecord($programme->expert_no, $programme->cust_type, $programme->programme_code, $guaranteeAmount, 1, "发布方案保底")) {
                        throw new \Exception('冻结余额明细失败', 202);
                    }
                }
            }
            $footBallCode = Constants::MADE_FOOTBALL_LOTTERY;
            $baskballCode = CompetConst::MADE_BASKETBALL_LOTTERY;
            $bdCode = CompetConst::MADE_BD_LOTTERY;
            $wcCode = CompetConst::MADE_WCUP_LOTTERY;
            switch (true) {
                case in_array($programme->lottery_code, $footBallCode):
                    $madeCode = "3000";
                    break;
                case in_array($programme->lottery_code, $baskballCode):
                    $madeCode = "3100";
                    break;
                case in_array($programme->lottery_code, $bdCode):
                    $madeCode = '5000';
                    break;
                case in_array($programme->lottery_code, $wcCode):
                    $madeCode = '3300';
                    break;
                default:
                    $madeCode = $programme->lottery_code;
                    break;
            }
            KafkaService::addQue('CustomMade', ['expert_no' => $custNo, 'lottery_code' => $madeCode, 'bet_nums' => $programme->programme_last_number, 'programme_id' => $programme->programme_id, 'programme_price' => $programme->programme_univalent]);
            //$lotteryqueue = new \LotteryQueue();
            //$lotteryqueue->pushQueue('custom_made_job', 'default', ['expert_no' => $custNo,'lottery_code' => $madeCode,'bet_nums' => $programme->programme_last_number,'programme_id' => $programme->programme_id,'programme_price' => $programme->programme_univalent]);
            $expertLevel = new ExpertLevelService();
            $expertLevel->createUpdate($programme->user_id, $custNo);
            $programme->status = 2;
            if (!$programme->save()) {
                throw new \Exception('更新表合买表状态失败', 203);
            }
            $tran->commit();
        } catch (\Exception $e) {
            $tran->rollBack();
            \Yii::info($e->getCode() . '===' . $e->getMessage(), 'backuporder_log');
            return ["code" => $e->getCode(), "msg" => $e->getMessage()];
        }
        return ["code" => 600, "msg" => "方案发布成功", "result" => ""];
    }

    /**
     *
     * 跟买合买订单
     *
     * @param unknown $params 提交的参数
     */
    public static function creatProgramUserOrder($userNo, $userId, $post) {
        $user = (new Query())->select("user_name")->from("user")->where(["cust_no" => $userNo])->one();
        $betMoney = $post["bet_money"];
        $betNums = $post['bet_nums'];
        $programmeCode = $post["programme_code"];
        $programme = Programme::findOne(["programme_code" => $programmeCode]);
        ($programme->programme_last_amount < $betMoney) && \yii::jsonError(109, '认购金额超过剩余合买金额');
        $preMoney = floatval($programme->programme_univalent) * $betNums;
        ($preMoney != $betMoney) && \yii::jsonError(109, '认购金额不对');
        $programmeUser = new ProgrammeUser();
        $programmeUser->expert_no = $programme->expert_no;
        $programmeUser->store_id = $programme->store_id;
        $programmeUser->programme_id = $programme->programme_id;
        $programmeUser->bet_money = $betMoney;
        $programmeUser->buy_number = $betNums;
        $programmeUser->programme_code = $programmeCode;
        $programmeUser->lottery_code = $programme->lottery_code;
        $programmeUser->lottery_name = $programme->lottery_name;
        $programmeUser->buy_type = 1;
        $programmeUser->programme_user_code = Commonfun::getCode("FL", "G");
        $programmeUser->cust_no = $userNo;
        $programmeUser->user_id = $userId;
        $programmeUser->user_name = $user["user_name"];
        $programmeUser->status = 1; //未支付状态
        $programmeUser->create_time = date("Y-m-d H:i:s");
        $paySer = new PayService();
        $paySer->productPayRecord($userNo, $programmeUser->programme_user_code, 5, 1, $betMoney, 2);
        if ($programmeUser->validate()) {
            $ret = $programmeUser->save();
            !$ret && \yii::jsonError(109, '数据错误');
        } else {
            \yii::jsonResult(109, '数据错误', $programmeUser->getFirstErrors());
        }
        return ["code" => 600, "msg" => "合买下单成功！", "result" => ["lottery_order_code" => $programmeUser->programme_user_code]];
    }

}

<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\common\services;

use Yii;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\LotteryAdditional;
use app\modules\common\helpers\Commonfun;
use app\modules\common\models\BettingDetail;
use app\modules\common\helpers\Constants;
use yii\db\Query;
use app\modules\common\services\FundsService;
use app\modules\common\services\PayService;
use app\modules\common\models\PayRecord;
use app\modules\competing\services\OptionalService;
use app\modules\competing\services\BasketService;
use app\modules\competing\helpers\CompetConst;
use yii\base\Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use app\modules\common\models\UserFunds;
use app\modules\orders\services\MajorService;
use app\modules\user\models\CouponsDetail;
use app\modules\user\models\UserGlCoinRecord;
use app\modules\competing\services\BdService;
use app\modules\competing\services\FootballService;
use app\modules\competing\services\WorldcupService;
use app\modules\numbers\services\SzcsService;
use app\modules\numbers\services\EszcService;
use app\modules\common\models\LotteryRecord;
use app\modules\numbers\helpers\SzcConstants;
use app\modules\user\helpers\UserTool;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class OrderService {

    const SINGLE_COIN = 1000; //古币单次使用的基数
    const ONE_YUAN_COIN = 100; //1块多少古币
    const MAX_DISCOUNT_COIN = 0.5; //最多古币折扣比例

    /**
     * 数字彩投注单
     * auther 咕啦 zyl
     * create_time 2017/05/24
     * update_time 2017/5/26
     * @return json
     */

    public static function numsOrder($custNo, $userId, $storeId, $storeCode, $source = 1, $sourceId = "", $outType = 1) {
        $request = Yii::$app->request;
        $post = $request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $current = Commonfun::currentPeriods($orderData['lottery_code']);
        if ($current['error'] == true) {
            if (($orderData['periods'] != $current['periods']) || strtotime($current["data"]["limit_time"]) < time()) {
                return ["code" => 109, "msg" => "投注失败，超时,此期已过期，请重新投注"];
            }
        } else {
            return ["code" => 109, "msg" => "投注失败，此彩种已经停止投注，请选择其他彩种进行投注"];
        }

        if (!isset($orderData['contents'])) {
            return ["code" => 109, "msg" => "投注内容不可为空,请重新投注"];
        }

        if (!is_array($orderData['contents'])) {
            return ["code" => 109, "msg" => "投注失败，请重新投注"];
        }

        if (!Commonfun::numsDifferent($orderData['contents'])) {
            return ["code" => 109, "msg" => "投注失败，请重新投注"];
        }
        if (isset($orderData['chase']) && $orderData['chase'] > 1) {
            if (isset($orderData['is_limit']) && $orderData['is_limit'] == 1) {
                if (!isset($orderData['limit_amount'])) {
                    return ["code" => 109, "msg" => "投注失败，请填写追期限制"];
                }
            }
            $boolStr = true;
        } else {
            $boolStr = false;
        }
        switch ($orderData['lottery_code']) {
            case "1001":
            case "1002":
            case "1003":
            case "2001":
            case "2002":
            case "2003":
            case "2004":    
                $ret = SzcsService::playOrder($custNo, $userId, $storeId, $storeCode, $source, $sourceId, $boolStr, $outType);
                break;
            case "2005":
            case '2006':
            case '2007':
            case '2010':
            case '2011':
                $ret = EszcService::playOrder($custNo, $userId, $storeId, $storeCode, $source, $sourceId, $boolStr, $outType);
                break;
            default :
                $ret = ["code" => 109, "msg" => "错误彩种"];
        }
        return $ret;
    }

    /**
     * 
     * 竞彩下订单
     * auther GL ctx
     * @return json
     */
    public static function competingOrder($custNo, $userId, $storeId, $storeCode, $source = 1, $sourceId = "", $outType) {
        $request = Yii::$app->request;
        $post = $request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $lotteryCode = $orderData["lottery_code"];
        $classCopeting = new FootballService();
        $ret = $classCopeting->playOrder($lotteryCode, $custNo, $userId, $storeId, $storeCode, $source, $sourceId, $outType);
        return $ret;
    }

    /**
     * 插入投注单
     * @param array $info
     * @return array
     */
    public static function insertOrder($info, $proAdditional = false) {
        $footballs = Constants::MADE_FOOTBALL_LOTTERY;
        $nums = Constants::MADE_NUMS_LOTTERY;
        $optional = Constants::MADE_OPTIONAL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bdMade = CompetConst::MADE_BD_LOTTERY;
        $wcMdae = CompetConst::MADE_WCUP_LOTTERY;
        // 彩种类型
        if (in_array($info['lottery_id'], $footballs)) {
            $info['type'] = 2; // 竞足
        } elseif (in_array($info['lottery_id'], $nums)) {
            $info['type'] = 1; // 数字彩
        } elseif (in_array($info['lottery_id'], $optional)) {
            $info['type'] = 3; // 任选
        } elseif (in_array($info['lottery_id'], $basketball)) {
            $info['type'] = 4; // 竞篮
        } elseif (in_array($info['lottery_id'], $bdMade)) {
            $info['type'] = 5; // 北单
        } elseif (in_array($info['lottery_id'], $wcMdae)) {
            $info['type'] = 6; // 世界杯冠亚军
        } else {
            return ['error' => false, 'msg' => '此彩种暂未开放'];
        }
        $db = Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
            if ($proAdditional == true) {//如果是追期
                $addInfo = self::createAdditional($info);
                if ($addInfo['code'] != 1) {
                    throw new Exception($addInfo['msg']);
                }
                $insert = ['lottery_type' => $info['lottery_type'], 'lottery_name' => $info['lottery_name'], 'lottery_id' => $info['lottery_id'], 'play_code' => $info['play_code'], 'play_name' => $info['play_name'], 'periods' => $info['periods'],
                    'cust_no' => $info['cust_no'], "store_id" => $info['store_id'], 'source_id' => $addInfo['data']['lottery_additional_id'], 'bet_val' => $info['bet_val'], 'bet_double' => $info['bet_double'], 'agent_id' => $info['agent_id'],
                    'bet_money' => $info['bet_money'], 'source' => 2, 'count' => $info['count'], 'is_bet_add' => $info['is_bet_add'], 'end_time' => $info['end_time'], 'user_id' => $info['user_id'], 'store_no' => $info['store_no'],
                    'periods_total' => $info['periods_total'], 'type' => $info['type'], 'auto_type' => $info['auto_type'], 'remark' => $info['remark']];
                $orderData = self::createOrder($insert);
                if ($orderData['code'] != 1) {
                    throw new Exception($orderData['msg']);
                }
            } else if ($proAdditional == false) {
                $orderData = self::createOrder($info);
                if ($orderData['code'] != 1) {
                    throw new Exception($orderData['msg']);
                }
            }
            $tran->commit();
            return ["error" => true, "orderId" => $orderData['data']['lottery_order_id'], "orderCode" => $orderData['data']['lottery_order_code']];
        } catch (\yii\db\Exception $e) {
            $tran->rollBack();
            return ["error" => false, "msg" => "抛出错误", "data" => $e->getMessage()];
        }
    }

    /**
     * 说明: 生成追期表
     * @author  kevi
     * @date 2017年11月3日 下午6:12:37
     * @param
     * @return 
     */
    public static function createAdditional($addInfo) {
        $additional = new LotteryAdditional();
        $additional->cust_no = $addInfo["cust_no"];
        $additional->cust_type = 1;
        $additional->store_no = $addInfo['store_no'];
        $additional->store_id = $addInfo["store_id"];
        $additional->agent_id = \Yii::$agentId;
        $additional->lottery_id = $addInfo["lottery_id"];
        $additional->lottery_name = $addInfo["lottery_name"];
        $additional->bet_val = $addInfo["bet_val"];
        $additional->play_code = $addInfo["play_code"];
        $additional->play_name = $addInfo["play_name"];
        $additional->status = $addInfo["periods_total"] > 1 ? 1 : 3;
        $additional->periods = $addInfo["periods"];
        $additional->periods_total = $addInfo["periods_total"];
        $additional->is_random = $addInfo["is_random"];
        $additional->bet_double = $addInfo["bet_double"];
        $additional->is_bet_add = $addInfo["is_bet_add"];
        $additional->bet_money = $addInfo["bet_money"];
        $additional->total_money = $addInfo["bet_money"] * $addInfo["periods_total"];
        $additional->win_limit = $addInfo["win_limit"];
        $additional->is_limit = $addInfo["is_limit"];
        $additional->pay_status = 0;
        $additional->lottery_additional_code = Commonfun::getCode($addInfo["lottery_type"], "Z");
        $additional->chased_num = 1;
        $additional->count = $addInfo["count"];
        $additional->modify_time = $additional->create_time = date('Y-m-d H:i:s');
        $additional->store_no = isset($addInfo["store_no"]) ? $addInfo["store_no"] : "";
        $additional->user_id = isset($addInfo['user_id']) ? $addInfo['user_id'] : '';
        $additional->remark = isset($addInfo['remark']) ? $addInfo['remark'] : '';
        if ($additional->validate()) {
            if ($additional->save()) {
                return ['code' => 1, 'msg' => '写入成功', 'data' => $additional->attributes];
            } else {
                return ['code' => 0, 'msg' => '追期表写入失败'];
            }
        } else {
            return ['code' => 0, 'msg' => '追期表验证失败'];
        }
    }

    /**
     * 说明: 生成订单表
     * @author  kevi
     * @date 2017年11月3日 下午5:45:17
     * @param $info
     * @return 
     */
    public static function createOrder($lotteryOrderData) {
        $order = new LotteryOrder();
        $order->lottery_order_code = Commonfun::getCode($lotteryOrderData["lottery_type"], "T");
        $order->play_code = $lotteryOrderData["play_code"];
        $order->play_name = $lotteryOrderData["play_name"];
        $order->lottery_id = $lotteryOrderData["lottery_id"];
        $order->lottery_type = $lotteryOrderData['type'];
        $order->lottery_name = $lotteryOrderData["lottery_name"];
        $order->periods = $lotteryOrderData["periods"];
        $order->cust_no = $lotteryOrderData["cust_no"];
        $order->user_id = $lotteryOrderData['user_id'];
        $order->cust_type = 1;
        $order->store_no = $lotteryOrderData["store_no"];
        $order->store_id = $lotteryOrderData["store_id"];
        $order->source_id = $lotteryOrderData["source_id"];
        $order->agent_id = $lotteryOrderData['agent_id'] == 0 ? \Yii::$agentId : $lotteryOrderData['agent_id'];
        $order->bet_val = $lotteryOrderData["bet_val"];
        $order->chased_num = isset($lotteryOrderData["chased_nums"]) ? $lotteryOrderData["chased_nums"] : 1;
        $order->additional_periods = isset($lotteryOrderData["periods_total"]) ? $lotteryOrderData["periods_total"] : 1;
        $order->bet_double = $lotteryOrderData["bet_double"];
        $order->is_bet_add = (isset($lotteryOrderData["is_bet_add"]) && !empty($lotteryOrderData["is_bet_add"])) ? $lotteryOrderData["is_bet_add"] : 0;
        $order->bet_money = $lotteryOrderData["bet_money"];
        $order->status = 1;
        $order->source = $lotteryOrderData['source'];
        $order->count = $lotteryOrderData["count"];
        $order->odds = isset($lotteryOrderData["odds"]) ? $lotteryOrderData["odds"] : "";
        $order->create_time = $order->modify_time = date('Y-m-d H:i:s');
        $order->end_time = $lotteryOrderData['end_time'];
        $order->build_code = (isset($lotteryOrderData['build_code'])) ? $lotteryOrderData['build_code'] : '';
        $order->build_name = (isset($lotteryOrderData['build_name'])) ? $lotteryOrderData['build_name'] : '';
        $order->major_type = (isset($lotteryOrderData['major_type'])) ? $lotteryOrderData['major_type'] : 0;
        $order->auto_type = $lotteryOrderData['auto_type'];
        $order->remark = isset($lotteryOrderData['remark']) ? $lotteryOrderData['remark'] : '';
        if ($order->validate()) {
            if ($order->saveData()) {
                return ['code' => 1, 'msg' => '写入成功', 'data' => $order->attributes];
            } else {
                return ['code' => 0, 'msg' => '订单表写入失败'];
            }
        } else {
            return ['code' => 0, 'msg' => '订单表验证失败', 'data' => $order->getFirstErrors()];
        }
    }

    /**
     * 详情投注表插入信息
     * @param array $infos
     * @return array
     */
    public static function insertDetail($infos) {
        $db = Yii::$app->db;
        $tran = $db->beginTransaction();
        $lotteryType = Constants::LOTTERY_ABBREVI;
        $chuan = Constants::CHUAN_CODE;
        $footballCode = Constants::MADE_FOOTBALL_LOTTERY;
        $basketballCode = CompetConst::MADE_BASKETBALL_LOTTERY;
        try {
            $vals = [];
            $keys = [
                'agent_id',
                'bet_double',
                'bet_money',
                'bet_val',
                'betting_detail_code',
                'create_time',
                'is_bet_add',
                'lottery_id',
                'lottery_name',
                'lottery_order_code',
                'lottery_order_id',
                'modify_time',
                'one_money',
                'periods',
                'play_code',
                'schedule_nums',
                'status',
                'cust_no',
                'play_name',
                'odds',
                'win_amount',
                'fen_json'
            ];
            foreach ($infos["content"] as $key => $val) {
                $oneMoney_1 = $infos["bet_money"] / $infos["count"];
                $oneMoney_2 = Constants::PRICE * $infos["bet_double"];
                if ($infos["lottery_id"] == "2001" && $infos["is_bet_add"] == 1) {
                    $oneMoney_2 = $oneMoney_2 * 1.5;
                } elseif ($infos['lottery_id'] == '2007' || $infos['lottery_id'] == '2011') {
                    if (in_array($val['play_code'], [200763, 200766, 201163, 201166])) {
                        $oneMoney_1 = $oneMoney_2 = 6 * $infos['bet_double'];
                    } elseif (in_array($val['play_code'], [200764, 200767, 200769, 201164, 201167, 201169])) {
                        $oneMoney_1 = $oneMoney_2 = 10 * $infos['bet_double'];
                    } elseif (in_array($val['play_code'], [200765, 200768, 200770, 201165, 201168, 201170])) {
                        $oneMoney_1 = $oneMoney_2 = 14 * $infos['bet_double'];
                    } else {
                        $oneMoney_1 = $oneMoney_2 = 2 * $infos['bet_double'];
                    }
                }
                if ($oneMoney_1 != $oneMoney_2) {
                    $tran->rollBack();
                    return [
                        "error" => false,
                        "msg" => "第{$key}条金额对不上,对应订单{$infos['lottery_order_id']}_{$oneMoney_1}_{$oneMoney_2}",
                    ];
                }
                if (in_array($infos['lottery_id'], $footballCode)) {
                    $dealNums = (int) $chuan[$val['play_code']];
                } elseif (in_array($infos['lottery_id'], $basketballCode)) {
                    $dealNums = (int) $chuan[$val['play_code']];
                } else {
                    $dealNums = 1;
                }
                $allBetDouble = $infos["bet_double"];
                $count = ceil($allBetDouble / 99);
                for ($num = 1; $num <= $count; $num++) {
                    if ($allBetDouble > 99) {
                        $betDouble = 99;
                    } else {
                        $betDouble = $allBetDouble;
                    }
                    $allBetDouble = $allBetDouble - $betDouble;
                    if ($infos["lottery_id"] == "2001" && $infos["is_bet_add"] == 1) {
                        $betMoney = Constants::PRICE * $betDouble * 1.5;
                    } else {
                        $betMoney = Constants::PRICE * $betDouble;
                    }
//                    if (in_array($infos['lottery_id'], $footballCode)) {
//                        $winAmount = $betMoney;
//                    } else {
//                        $winAmount = 0;
//                    }
                    $winAmount = 0;
                    $vals[] = [
                        $infos["agent_id"],
                        $betDouble, //$infos["bet_double"],
                        $betMoney, //$oneMoney_1,
                        $val["bet_val"],
                        Commonfun::getCode($lotteryType[$infos["lottery_id"]], "X"),
                        date('y/m/d H:i:s'),
                        $infos["is_bet_add"],
                        $infos["lottery_id"],
                        $infos["lottery_name"],
                        $infos["lottery_order_code"],
                        $infos["lottery_order_id"],
                        date('Y-m-d H:i:s'),
                        Constants::PRICE,
                        $infos["periods"],
                        $val["play_code"],
                        $dealNums,
                        $infos["status"],
                        $infos["cust_no"],
                        $val["play_name"],
                        isset($val['odds']) ? $val['odds'] : '',
                        $winAmount, //$oneMoney_1
                        (isset($val["fen_json"]) ? $val["fen_json"] : '')
                    ];
                }
            }
            $insertCount = $db->createCommand()->batchInsert("betting_detail", $keys, $vals)->execute();
            $firstId = \Yii::$app->db->getLastInsertID();
            $tran->commit();
            $lastId = $firstId + $insertCount - 1;
            for ($i = $firstId; $i <= $lastId; $i++) {
                BettingDetail::addQueSync(BettingDetail::$syncInsertType, '*', ['betting_detail_id' => $i]);
            }
            return [
                "error" => true,
                "msg" => "操作成功！"
            ];
        } catch (\yii\db\Exception $e) {
            $tran->rollBack();
            return [
                "error" => false,
                "data" => $e,
                "msg" => "抛出错误！"
            ];
        }
    }

    /**
     * 获取交易情况
     */
    public static function getPayRecord($orderCode) {
        $data = (new \yii\db\Query())->select("*")->from("pay_record")->where(["order_code" => $orderCode])->one();
        return $data;
    }

    /**
     * 充值回调
     * @param unknown $orderCode
     * @param unknown $outer_no
     * @param unknown $total_amount
     * @param unknown $payTime
     */
    public static function rechargeNotify($orderCode, $outer_no, $total_amount, $payTime, $record) {
        $db = \Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
//            $record = (new \yii\db\Query())->select("*")->from("pay_record")->where(["order_code" => $orderCode])->one();
//            if ($record == null) {
//                return ["code" => 109, "msg" => "未找到该记录"];
//            }
            if ($record["status"] != 0) {
                return true;
            }
            $fundsSer = new FundsService();
            $fundsSer->operateUserFunds($record["cust_no"], $record["pay_pre_money"], $record["pay_pre_money"], 0, true);
            $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $record["cust_no"]])->one();
            PayRecord::upData([
                "status" => 1,
                "outer_no" => $outer_no,
                "modify_time" => date("Y-m-d H:i:s"),
                "pay_time" => $payTime,
                "pay_money" => $total_amount,
                "balance" => $funds["all_funds"]
                    ], [
                "order_code" => $orderCode,
            ]);
            $tran->commit();
            $key = 'waitting_recharge:' . $orderCode;
            $code = \Yii::redisGet($key);
            if (!empty($code)) {
                $service = new PayService();
                $service->order_code = $code;
                $service->way_type = "YE";
                $service->pay_way = "3";
                $service->cust_no = $record["cust_no"];
                $service->activeType = 1;
                $service->Pay();
                //\Yii::redisDel($key);//处理掉
            }
        } catch (\yii\base\Exception $e) {
            $tran->rollBack();
            return ["code" => 109, "msg" => json_encode($e, true)];
        }
        return true;
    }

    /**
     * 购彩回调-处理订单/支付记录
     * @param string $orderCode
     * @param string $outer_no
     * @param decimal $total_amount
     * @return boolean
     */
    public static function orderNotify($orderCode, $outer_no, $total_amount, $payTime,$record) {
        $db = \Yii::$app->db;
        $lotOrder = LotteryOrder::find()
                ->where(["lottery_order_code" => $orderCode])
                ->andWhere(["status" => "1"])
                ->asArray()
                ->one();
//        $record = (new \yii\db\Query())->select("*")->from("pay_record")->where(["order_code" => $orderCode])->one();
//        if ($record == null) {
//            return ["code" => 109, "msg" => "未找到该记录"];
//        }
        if ($record["status"] != 0) {
            return true;
        }
        $fundsSer = new FundsService();
        if ($lotOrder['source'] == 2) {//追号
            $lotAddInfo = LotteryAdditional::find()
                    ->where(["lottery_additional_id" => $lotOrder["source_id"]])
                    ->andWhere(["pay_status" => "0"])
                    ->asArray()
                    ->one();
            $iceMoney = $lotAddInfo["total_money"] - $lotAddInfo["bet_money"];
            if ($lotOrder != null) {
                $ret3 = $fundsSer->operateUserFunds($lotAddInfo["cust_no"], 0, (0 - $iceMoney), $iceMoney, true);
                if($ret3['code'] != 0) {
                    return false;
                }
                if ($iceMoney > 0) {
                    $fundsSer->iceRecord($lotAddInfo["cust_no"], $record["cust_type"], $lotAddInfo["lottery_additional_code"], $iceMoney, 1, "追号冻结");
                }
                $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $record["cust_no"]])->one();
                $ret2 = PayRecord::upData(["status" => 1, "outer_no" => $outer_no, "modify_time" => date("Y-m-d H:i:s"), "pay_time" => $payTime, "pay_money" => $total_amount, "balance" => $funds["all_funds"]], ["order_code" => $orderCode]);
                //$ret2 = \Yii::$app->db->createCommand()->update("pay_record", ["status" => 1, "outer_no" => $outer_no, "modify_time" => date("Y-m-d H:i:s"), "pay_time" => $payTime, "pay_money" => $total_amount, "balance" => $funds["all_funds"]], ["order_code" => $orderCode])->execute();
                LotteryAdditional::upData(["pay_status" => "1", "status" => "2"], ["lottery_additional_id" => $lotOrder["source_id"], 'pay_status' => 0]);
                //LotteryAdditional::updateAll(["pay_status" => "1", "status" => "2"], "lottery_additional_id='{$lotOrder["source_id"]}' and pay_status=0");
                $ret1 = LotteryOrder::upData(["status" => "2"], ["lottery_order_id" => $lotOrder["lottery_order_id"]]);
                //$ret1 = $db->createCommand()->update("lottery_order", ["status" => "2"], ["lottery_order_id" => $lotOrder["lottery_order_id"]])->execute();
            }else{
                return false;
            }
        } else {
            //查询用户总金额
            $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $record["cust_no"]])->one();
            //修改支付记录表状态及其他信息
            $ret2 = PayRecord::upData(["status" => 1, "outer_no" => $outer_no, "modify_time" => date("Y-m-d H:i:s"), "pay_time" => $payTime, "pay_money" => $total_amount, "balance" => $funds["all_funds"]], ["order_code" => $orderCode]);
            //修改订单状态为处理中
            $ret1 = LotteryOrder::upData(["status" => "2"], ["lottery_order_id" => $lotOrder["lottery_order_id"]]);
            if ($lotOrder['source'] == 5) {//分享订单
//            	OrderShare::upData(['with_nums'=>new Expression('with_nums+1'),"modify_time" => date("Y-m-d H:i:s")], ['order_share_id'=>$lotOrder['source_id']]);
                $updata = "update order_share set with_nums = with_nums + 1, modify_time = '" . date('Y-m-d H:i:s') . "' where order_share_id={$lotOrder['source_id']}";
                $db->createCommand($updata)->execute();
            }

        }
        if ($ret1 == false || $ret2 == false) {
            KafkaService::addLog('警报','购彩支付订单回调，资金操作失败：订单号-'.$orderCode);
            return false;
        } else {
            //支付成功，逻辑继续执行
            KafkaService::addQue('LotteryJob', ["orderId" => $lotOrder["lottery_order_id"]], true);
            //$lotteryqueue = new \LotteryQueue();
            // $lotteryqueue->pushQueue('lottery_job', 'default', ["orderId" => $lotOrder["lottery_order_id"]]);
            // $lotteryqueue->pushQueue('backupOrder_job', 'backup', ['tablename' => 'lottery_order', "keyname" => 'lottery_order_id', 'keyval' => $lotOrder['lottery_order_id']]);
            // $lotteryqueue->pushQueue('backupOrder_job', 'backup_pay_record', ['tablename' => 'pay_record', "keyname" => 'order_code', 'keyval' => $orderCode]);
            // $lotteryqueue->pushQueue('backupOrder_job', 'backup_userfunds', ['tablename' => 'user_funds', "keyname" => 'cust_no', 'keyval' => $record["cust_no"]]);
        }
        return true;
    }

    /**
     * 出票
     * 
     * @param type $orderId
     * @return boolean
     */
    public static function outOrder($orderCode, $storeCode) {
        $lotOrder = LotteryOrder::find()->select(['lottery_order.*', 'user.user_tel', 'user.user_name', 'store.cust_no as store_cust_no', 'store.store_name', 'store.company_id'])
                ->leftJoin('user', 'user.cust_no = lottery_order.cust_no')
                ->leftJoin('store', 'store.store_code = lottery_order.store_no and store.status = 1')
                ->where(["lottery_order_code" => $orderCode, "lottery_order.store_no" => $storeCode, "lottery_order.status" => 11, "suborder_status" => 1])
                ->asArray()
                ->one();
        if (!$lotOrder) {
            return ['code' => 0, 'msg' => '找不到该订单！'];
        }
        $optId = \Yii::$storeOperatorId;
        $db = \Yii::$app->db;

        $tran = $db->beginTransaction();
        try {
            // $orderUpdateSql = "UPDATE lottery_order SET status=3 ,opt_id = {$optId} WHERE lottery_order_code = '{$orderCode}';
            // UPDATE betting_detail SET status=3 WHERE lottery_order_code = '{$orderCode}';";
            $orderUpdateSql = "UPDATE betting_detail SET status=3 WHERE lottery_order_code = '{$orderCode}';";
            $update = ['status' => 3, 'opt_id' => $optId, 'out_time' => date('Y-m-d H:i:s')];
            $where = ['lottery_order_code' => $orderCode];
            LotteryOrder::upData($update, $where); //修改订单和详情的状态为待开奖3
            $a = $db->createCommand($orderUpdateSql)->execute(); //修改订单和详情的状态为待开奖3
            BettingDetail::addQueSync(BettingDetail::$syncUpdateType, 'status', ['lottery_order_code' => $orderCode]);
            if ($lotOrder['source'] == 4) {//该订单如果是合买
                $programmeroUpdateSql = "UPDATE programme SET status=4 WHERE programme_id = {$lotOrder['source_id']};
                    UPDATE programme_user SET status=4 WHERE programme_id = {$lotOrder['source_id']} and status = 12;";
                $db->createCommand($programmeroUpdateSql)->execute(); //修改合买订单、子单状态
            }
            if ($lotOrder['source'] != 6 || ($lotOrder['auto_type'] != 2 && $lotOrder['source'] == 7)) {//资金变动
                $fundsSer = new FundsService();
                $ret = $fundsSer->operateUserFunds($lotOrder['store_cust_no'], $lotOrder['bet_money'], $lotOrder['bet_money'], 0, false);
                if ($ret["code"] != 0) {
                    $tran->rollBack();
                    return ['code' => 2, 'msg' => $ret['msg']];
                }
                $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $lotOrder['store_cust_no']])->one();
                $payRecord = new PayRecord();
                $payRecord->order_code = $orderCode;
                $payRecord->pay_no = Commonfun::getCode("PAY", "L");
                $payRecord->cust_no = $lotOrder['store_cust_no'];
                $payRecord->cust_type = 2;
                $payRecord->user_name = $lotOrder['store_name'];
                $payRecord->pay_pre_money = $lotOrder['bet_money'];
                $payRecord->pay_money = $lotOrder['bet_money'];
                $payRecord->pay_name = '余额';
                $payRecord->way_name = '余额';
                $payRecord->way_type = 'YE';
                $payRecord->pay_way = 3;
                $payRecord->pay_type_name = '门店出票';
                $payRecord->pay_type = 9;
                $payRecord->body = '门店出票-' . (!empty($lotOrder['user_tel']) ? substr($lotOrder['user_tel'], -4) : "") . "({$lotOrder['user_name']})";
                $payRecord->status = 1;
                $payRecord->balance = $funds["all_funds"];
                $payRecord->pay_time = date('Y-m-d H:i:s');
                $payRecord->modify_time = date("Y-m-d H:i:s");
                $payRecord->create_time = date('Y-m-d H:i:s');

                if (!$payRecord->saveData()) {//交易记录表保存失败
                    $log = new Logger('winning_detail');
                    $log->pushHandler(new StreamHandler(BASE_PATH . '/logs/pay_record/save_error.log'));
                    $log->info("订单编号： - {$orderCode} - 交易订单 保存失败", ['失败原因: ==>'], $payRecord->errors);
                }
                if ($lotOrder['company_id'] != 1) {
                    $serviceCharge = (ceil($lotOrder['bet_money'] * 0.2) / 100);
                    $ret = $fundsSer->operateUserFunds($lotOrder['store_cust_no'], 0 - $serviceCharge, 0 - $serviceCharge, 0, false);
                    if ($ret["code"] != 0) {
                        $tran->rollBack();
                        return ['code' => 2, 'msg' => $ret['msg']];
                    }
                    $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $lotOrder['store_cust_no']])->one();
                    $payRecord2 = new PayRecord(); //服务手续费
                    $payRecord2->order_code = $orderCode;
                    $payRecord2->pay_no = Commonfun::getCode("PAY", "L");
                    $payRecord2->cust_no = $lotOrder['store_cust_no'];
                    $payRecord2->cust_type = 2;
                    $payRecord2->user_name = $lotOrder['store_name'];
                    $payRecord2->pay_pre_money = $serviceCharge;
                    $payRecord2->pay_money = $serviceCharge;
                    $payRecord2->pay_name = '余额';
                    $payRecord2->way_name = '余额';
                    $payRecord2->way_type = 'YE';
                    $payRecord2->pay_way = 3;
                    $payRecord2->pay_type_name = '出票服务费';
                    $payRecord2->pay_type = 16;
                    $payRecord2->body = '出票服务费';
                    $payRecord2->status = 1;
                    $payRecord2->balance = $funds["all_funds"];
                    $payRecord2->pay_time = date('Y-m-d H:i:s');
                    $payRecord2->modify_time = date("Y-m-d H:i:s");
                    $payRecord2->create_time = date('Y-m-d H:i:s');
                    if (!$payRecord2->saveData()) {//交易记录表保存失败
                        $log = new Logger('winning_detail');
                        $log->pushHandler(new StreamHandler(BASE_PATH . '/logs/pay_record/save_error.log'));
                        $log->info("订单编号： - {$orderCode} - 交易订单(手续费) 保存失败", ['失败原因: ==>'], $payRecord2->errors);
                    }
                }
            } elseif($lotOrder['source'] == 6) {//如果是计划购买
                $format = date('Y-m-d H:i:s');
                $userPlan = "update user_plan set betting_funds = betting_funds - {$lotOrder['bet_money']}, modify_time = '" . $format . "' where user_plan_id = {$lotOrder['user_plan_id']} ;";
                $upId = $db->createCommand($userPlan)->execute();
                if ($upId == false) {
                    $tran->rollBack();
                    return ['code' => 2, 'msg' => '计划表更新失败'];
                }
            }
            $tran->commit();
        } catch (\yii\db\Exception $e) {
            $tran->rollBack();
            print $e->getMessage();
            return ['code' => 2, 'msg' => '出票异常,请联系管理员'];
        }
        return ['code' => 1, 'msg' => '订单处理成功'];
    }

    /**
     * 出票失败
     * @param type $orderId
     * @return boolean
     */
    public static function outOrderFalse($orderCode, $falseStatus, $storeId = null, $body = "彩彩宝-彩票出票失败") {
        if ($storeId == null) {
            $lotOrder = LotteryOrder::findOne(["lottery_order_code" => $orderCode, "status" => $falseStatus, "deal_status" => 0]);
        } else {
            $lotOrder = LotteryOrder::findOne(["lottery_order_code" => $orderCode, "status" => $falseStatus, "deal_status" => 0, "store_no" => $storeId]);
        }
        if ($lotOrder == null) {
            return false;
        }
        if ($lotOrder->source == 4) { // 4、合买退款是退给每个认购人
            $proSer = new ProgrammeService();
            $ret = $proSer->outOrderFalse($lotOrder->source_id, $falseStatus);
        } elseif ($lotOrder->source == 6) {
            $ret = PlanService::outFalse($lotOrder->source_id, $lotOrder->bet_money);
        } else {
            $paySer = new PayService();
            $ret = $paySer->refund($lotOrder->lottery_order_code, $body);
        }
        if ($ret == false) {
            $lotOrder->deal_status = 4; //退款失败
            $lotOrder->saveData();
            return false;
        }
        $lotOrder->deal_status = 5; //5、退款成功
        if ($lotOrder->validate()) {
            $lotOrder->saveData();
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 
     * 任选下订单
     * auther GL ctx
     * @return json
     */
    public static function optionalOrder($custNo, $userId, $storeId, $storeCode, $source = 1, $sourceId = "", $outType = 1) {
        $request = Yii::$app->request;
        $post = $request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $lotteryCode = $orderData["lottery_code"];
        $classCopeting = new OptionalService();
        $ret = $classCopeting->optionalOrder($lotteryCode, $custNo, $userId, $storeId, $storeCode, $source, $sourceId, $outType);
        return $ret;
    }

    /**
     * 生成体彩子单
     * auther 咕啦 ctx
     * @param model $model
     * create_time 2017-06-09
     * @return int
     */
    public static function proSuborder($model) {
        set_time_limit(0);
        $ret = "";
        switch ($model->lottery_id) {
            case "1001":
            case "1002":
            case "1003":
            case "2001":
            case "2002":
            case "2003":
            case "2004":
                $ret = SzcsService::productSuborder($model);
                break;
            case "2005":
            case "2006":
            case "2007":
            case '2010':
            case '2011':
                $ret = EszcService::productSuborder($model);
                break;
            case "3006":
            case "3007":
            case "3008":
            case "3009":
            case "3010":
            case "3011":
                $classCopeting = new FootballService();
                $ret = $classCopeting->productSuborder($model);
                break;
            case "4001":
            case "4002":
                $classCopeting = new OptionalService();
                $ret = $classCopeting->optionalDetail($model);
                break;
            case '3001':
            case '3002':
            case '3003':
            case '3004':
            case '3005':
                $lanService = new BasketService();
                $ret = $lanService->productSuborder($model);
                break;
            case '5001':
            case '5002':
            case '5003':
            case '5004':
            case '5005':
            case '5006':
                $bdService = new BdService();
                $ret = $bdService->productSuborder($model);
                break;
            case '301201':
            case '301301':
                $ret = WorldcupService::productSuborder($model);
                break;
            default :$ret = [
                    "code" => 2,
                    "msg" => "错误彩种"
                ];
        }
        return $ret;
    }

    /**
     * 篮球投注
     * @param type $custNo
     * @param type $storeId
     * @param type $source
     * @param type $sourceId
     * @return type
     */
    public static function basketOrder($custNo, $userId, $storeId, $storeCode, $source = 1, $sourceId = "", $outType) {
        $request = Yii::$app->request;
        $post = $request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $lotteryCode = $orderData["lottery_code"];
        $classCopeting = new BasketService();
        $ret = $classCopeting->playOrder($lotteryCode, $custNo, $userId, $storeId, $storeCode, $source, $sourceId, $outType);
        return $ret;
    }

    /**
     * 说明: 扣除服务费
     * @author  kevi
     * @date 2017年11月20日 下午2:53:19
     * @param
     * @return 
     */
    public function serviceMoney() {
        $fundsSer = new FundsService();
        $serviceCharge = (ceil($lotOrder->bet_money * 0.2) / 100);
        $ret = $fundsSer->operateUserFunds($store->cust_no, 0 - $serviceCharge, 0 - $serviceCharge, 0, false);
        if ($ret["code"] != 0) {
            $tran->rollBack();
            return false;
        }
        $funds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $store->cust_no])->one();
        $payRecord = new PayRecord();
        $payRecord->order_code = $orderCode;
        $payRecord->pay_no = Commonfun::getCode("PAY", "L");
        $payRecord->cust_no = $store->cust_no;
        $payRecord->cust_type = 2;
        $payRecord->user_name = $store->store_name;
        $payRecord->pay_pre_money = $serviceCharge;
        $payRecord->pay_money = $serviceCharge;
        $payRecord->pay_name = '余额';
        $payRecord->way_name = '余额';
        $payRecord->way_type = 'YE';
        $payRecord->pay_way = 3;
        $payRecord->pay_type_name = '出票服务费';
        $payRecord->pay_type = 16;
        $payRecord->body = '出票服务费';
        $payRecord->status = 1;
        $payRecord->balance = $funds["all_funds"];
        $payRecord->pay_time = date('Y-m-d H:i:s');
        $payRecord->modify_time = date("Y-m-d H:i:s");
        $payRecord->create_time = date('Y-m-d H:i:s');
        if ($payRecord->validate()) {
            $ret = $payRecord->save();
            if ($ret == false) {
                $tran->rollBack();
                return false;
            }
        } else {
            $tran->rollBack();
            return false;
        }
    }

    /**
     * 检查优惠信息是否可用并返回对应格式数组
     * @return ['code' => 600,'msg' => '优惠信息验证成功','data'=>$data];
     */
    public static function checkDiscount($userNo, $coin, $coupons, $orderMoney, $lotteryCode, $orderType, $payway) {
        $data = [];
        try {
            if ($coupons) {
                $CouponsDetailM = new CouponsDetail();
                $couponsData = $CouponsDetailM->checkCoupon($userNo, $coupons, $lotteryCode, $orderMoney);
                if ($couponsData['code'] != 600) {
                    throw new \Exception($couponsData['msg'], $couponsData['code']);
                }
                $money = array_sum(array_column($couponsData['data'], 'reduce_money'));
                $data['coupons'] = ['type' => 2, 'coupons' => $coupons, 'money' => $money];
            }
            if ($coin > 0) { // 使用古币验证
                if (($coin % self::SINGLE_COIN) !== 0) {
                    throw new \Exception('咕币倍数错误', 110);
                }
                $userFundsData = UserFunds::findOne(['cust_no' => $userNo]);
                if ($userFundsData->user_glcoin < $coin) {
                    throw new \Exception('咕币不足', 111);
                }
                $realpay = $orderMoney;
                $coinMoney = $coin / self::ONE_YUAN_COIN;
                if (isset($data['coupons'])) {
                    $realpay -= $data['coupons']['money'];
                }
                if (($coinMoney / $realpay) > self::MAX_DISCOUNT_COIN) {
                    throw new \Exception('咕币抵扣超过订单金的50%', 113);
                }
                // $userSer = new UserService();
                $javaStatus = Commonfun::javaGetStatus($userNo);
                if ($javaStatus == 0) {
                    throw new \Exception('下单失败，请稍后再试', 401);
                }
                if ($javaStatus['code'] != 1) {
                    throw new \Exception('下单失败，请稍后再试', 404);
                }
                if ($javaStatus['data']['checkStatus'] != 1) {
                    throw new \Exception('下请先通过实名认证', 112);
                }
                $data['coin'] = ['type' => 1, 'coin' => $coin, 'money' => $coinMoney];
            }
        } catch (\Exception $e) {
            return ['code' => $e->getCode(), 'msg' => $e->getMessage()];
        }
        return ['code' => 600, 'msg' => '优惠信息验证成功', 'data' => $data];
    }

    /**
     * 获取订单可用的优惠
     */
    public static function getDiscountList($userNo, $money, $LotteryCode, $orderType = 1, $case) {
        $data = ['couponsNum' => '', 'gubi' => '', 'one_yuan_coin' => self::ONE_YUAN_COIN, 'least_coin' => self::SINGLE_COIN];
        switch ($orderType) {
            case 1: // 普通购彩
                // $total=Cou
                $data['couponsNum'] = 0;
                if (!$case) {
                    $m = new CouponsDetail();
                    $total = $m->couponsNum($userNo, $LotteryCode, $money, 3);
                    $data['couponsNum'] = $total['data']['canUse'];
                }
                // $data['couponsNum']=//$userNo,$money,$orderType
                $fundsData = UserFunds::findOne(['cust_no' => $userNo]);
                $data['gubi'] = $fundsData->user_glcoin;
                break;
            case 2: // 购买文章
                // $total=Cou
                $data['couponsNum'] = 0;
                if (!$case) {
                    $m = new CouponsDetail();
                    $total = $m->couponsNum($userNo, $LotteryCode, $money, 3);
                    $data['couponsNum'] = $total['data']['canUse'];
                }
                // $data['couponsNum']=//$userNo,$money,$orderType
                $fundsData = UserFunds::findOne(['cust_no' => $userNo]);
                $data['gubi'] = $fundsData->user_glcoin;
                break;
        }
        $data['checkStatus'] = 0;
        $javaStatus = Commonfun::javaGetStatus($userNo);
        if (isset($javaStatus['data']['checkStatus']) && $javaStatus['data']['checkStatus'] == 1) {
            $data['checkStatus'] = 1;
        }
        return $data;
    }

    /**
     * 购彩下单(自购)
     * @param unknown $insert 插入的数据
     * @param string $additional //是否追期
     * @param string $majorData 优化明细
     */
    public static function selfDoLotterOrder($insert, $additional, $majorData = '',$isgift=false) {
        $discountData = [];
        if (!$additional) { // 不是追期，计算折扣
            $post = Yii::$app->request->post();
            $payWay = isset($post['pay_way']) ? $post['pay_way'] : 0;
            $postDiscountData = isset($post['discount_data']) ? json_decode($post['discount_data'], true) : '';
            if ($postDiscountData) {
                $coin = isset($postDiscountData['coin']) ? $postDiscountData['coin'] : '';
                $coupons = isset($postDiscountData['coupons']) ? $postDiscountData['coupons'] : [];
                $discount = self::getDiscount($insert['cust_no'], $insert['lottery_id'], $insert['bet_money'], $coin, $coupons, 1, $payWay);
                if ($discount['code'] != 600) {
                    return ["code" => $discount['code'], "msg" => $discount['msg']];
                }
                $discountData = array_filter($discount['data']);
            }
        }
        $realPayMoney = $insert['bet_money'];
        if ($discountData) { // 有折扣
            $realPayMoney = $insert['bet_money'] - $discountData['discount'];
            if ($realPayMoney < 0) {
                return ["code" => 120, "msg" => '折扣金额超过订单金额'];
            }
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $result = OrderService::insertOrder($insert, $additional);
            if ($result["error"] === true) {
                if ($discountData) {
                    // 使用掉优惠
                    $res = self::useDiscount($insert['user_id'], $insert['cust_no'], $result["orderCode"], $discountData['parms']);
                    if ($res['code'] != 600) {
                        throw new \Exception($res['msg'], 9999);
                    }
                }
                if ($insert['source'] != 6) {
                    $paySer = new PayService();
                    $r = $paySer->productPayRecord($insert['cust_no'], $result["orderCode"], 1, 1, $realPayMoney, 1, '', $discountData);
                    if (!$r) {
                        throw new \Exception('支付记录表写入失败', 10001);
                    }
                }
                if (isset($insert['major_type']) && $insert['major_type'] != 0) {
                    $majorOrder = new MajorService();
                    $r1 = $majorOrder->createMajor($result['orderId'], $majorData, $insert['major_type']);
                    if ($r1['code'] != 600) {
                        throw new \Exception($r1['msg'], 10002);
                    }
                }
                $isFree = 0;
                if ($isgift) { // 赠送
                     $paySave = PayService::savePayWay($result["orderCode"], $payWay);
                      if ($paySave['code'] != 600)
                      {
                      throw new \Exception($paySave['msg'],10003);
                      }
                      $payTime = date('Y-m-d H:i:s');
                      $r2 = self::orderNotify($result["orderCode"], '', 0, $payTime,(new \yii\db\Query())->select("*")->from("pay_record")->where(["order_code" => $result["orderCode"]])->one());
                      if (! $r2)
                      {
                      throw new \Exception('0元支付回调失败',10004);
                      } 
                    $isFree = 1;
                }
            } else {
                throw new \Exception($result['data'], 10000);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ["code" => $e->getCode(), "msg" => $e->getMessage()];
        }
        return ["code" => 600, "msg" => "下注成功！", "result" => ["lottery_order_code" => $result["orderCode"], 'isFree' => $isFree]];
    }

    /** 获取折扣金额
     */
    public static function getDiscount($userNo, $lotteryCode, $orderMoney, $coin, $coupons, $orderType, $payway) {
        $res = self::checkDiscount($userNo, $coin, $coupons, $orderMoney, $lotteryCode, $orderType, $payway);
        if ($res['code'] == 600) {
            $data = $res['data'];
            unset($res['data']);
            $res['data']['parms'] = $data; // 优惠参数
            $res['data']['discount'] = 0;
            foreach ($data as $v) {
                $res['data']['discount'] += $v['money'];
            }
        }
        return $res;
    }

    /**
     * 使用掉优惠
     */
    public static function useDiscount($userId, $userNo, $orderCode, $data) {
        if ($data) {
            try {
                foreach ($data as $v) {
                    switch (true) {
//                        case $v['type'] == 1: // 古币
//                            $m = new UserGlCoinRecord();
//                            $res = $m->updateGlCoin($userNo, ['type' => 2, 'coin_value' => $v['coin'], 'remark' => '购彩消费', 'coin_source' => 1, 'order_code' => $orderCode, 'order_source' => 1]);
//                            if ($res['code'] != 600) {
//                                throw new \Exception($res['msg'], $res['code']);
//                            }
//                            break;
                        case $v['type'] == 2: // 优惠券
                            $m = new CouponsDetail();
                            $res = $m->changeCouponsStatus($orderCode, $userNo, $v['coupons']);
                            if ($res['code'] != 600) {
                                throw new \Exception($res['msg'], $res['code']);
                            }
                            break;
                    }
                }
            } catch (\Exception $e) {
                return ['code' => $e->getCode(), 'msg' => $e->getMessage()];
            }
        }
        return ['code' => 600, 'msg' => '使用成功'];
    }

    /**
     * 取消订单+退优惠咕币
     */
    public static function cancleOrder($data) {
        if (!$data) {
            return ['code' => 110, 'msg' => '数据不能为空'];
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            if ($data['discount_detail']) {
                $disDetail = json_decode($data['discount_detail'], true);
                $res = PayService::returnDiscount($data['cust_no'], $data['order_code'], $disDetail);
                if ($res['code'] != 600) {
                    throw new \Exception($res['msg'], 109);
                }
            }
            if (!PayRecord::upData(['status' => 4], ['pay_record_id' => $data['pay_record_id']])) {
                throw new \Exception('订单状态修改失败', 110);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            KafkaService::addLog('queCancleOrder', 'error code:' . $e->getCode() . '.msg:' . $e->getMessage() . ';data:' . var_export($data, true));
            return ['code' => $e->getCode(), 'msg' => $e->getMessage()];
        }
        return ['code' => 600, 'msg' => '退优惠成功'];
    }

    /**
     * 北单下注
     * @param type $custNo 会员编号
     * @param type $userId 会员ID
     * @param type $storeId 门店店主ID
     * @param type $storeCode 门店编号
     * @param type $source 来源
     * @param type $sourceId 来源ID
     * @param type $outType 是否自动出票
     * @return type
     */
    public static function bdOrder($custNo, $userId, $storeId, $storeCode, $source = 1, $sourceId = "", $outType) {
        $request = Yii::$app->request;
        $post = $request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $lotteryCode = $orderData["lottery_code"];
        $bdService = new BdService();
        $ret = $bdService->playOrder($lotteryCode, $custNo, $userId, $storeId, $storeCode, $source, $sourceId, $outType);
        return $ret;
    }
    /**
     * 根据彩种编号 金额，随机生成N注号码
     * @param string $lotteryCode
     * @param string $oldVal
     * @return string
     */
    public static function randomOrder($lotteryCode, $money) {
    	$betNum=floor($money/2);
    	$data=$playCodes=[];
    	for ($i=0;$i<$betNum;$i++){
    		switch ($lotteryCode) {
    			case "1001":       //双色球
    				$rBallArr = AdditionalService::randomBall(1, 33, 6, true);
    				$bBallArr = AdditionalService::randomBall(1, 16, 1, true);
    				$data[] = implode(",", $rBallArr) . "|" . implode(",", $bBallArr);
    				$playCodes[]=100101;
    				break;
    			case "1003":      //七乐彩
    				$rBallArr = AdditionalService::randomBall(1, 30, 6, true);
    				$data[] = implode(",", $rBallArr);
    				$playCodes[]=100301;
    				break;
    			case "2001":      //大乐透
    				$rBallArr = AdditionalService::randomBall(1, 35, 5, true);
    				$bBallArr = AdditionalService::randomBall(1, 12, 2, true);
    				$data[] = implode(",", $rBallArr) . "|" . implode(",", $bBallArr);
    				$playCodes[]=200101;
    				break;
    			case "1002":     //福彩3D
    			case "2002":     //排列三
    				$balls = AdditionalService::randomBall(0, 9, 3);
    				$data[] = implode("|", $balls);
    				if($lotteryCode==1002){
    					$playCodes[]=100201;
    				}else{
    					$playCodes[]=200201;
    				}
    				break;
    			case "2003":     //排列五
    				$balls = AdditionalService::randomBall(0, 9, 5);
    				$data[] = implode("|", $balls);
    				$playCodes[]=200301;
    				break;
    			case "2004":     //七星彩
    				$balls = AdditionalService::randomBall(0, 9, 7);
    				$data[] = implode("|", $balls);
    				$playCodes[]=200401;
    				break;
    			case "2005":     //广东11X5
    			case "2006":     //江西11X5
    			case "2007":     //山东11X5
    			case '2010':     //湖北11X5
    			case '2011':     //福建11X5
    				break;
    		}
    	}
    	return [implode("^", $data) . "^",implode(",", $playCodes)];
    }
    /**
     * 赠送彩票
     */
    public static function giftLottery($lotteryCode,$money,$storeId,$storeCode,$custNo,$userId = ''){
    	$lotName = Constants::LOTTERY[$lotteryCode];
    	$abbName = Constants::LOTTERY_ABBREVI[$lotteryCode];
    	list($betNums,$playCode)=self::randomOrder($lotteryCode, $money);
    	$playCodes=explode(',', $playCode);
    	$betCount=count($playCodes);
    	$playName='';
    	foreach ($playCodes as $playCodec){
    		$playName.=SzcConstants::SZC_PLAYNAME[$lotteryCode][$playCodec].',';
    	}
    	$lotteryR=LotteryRecord::findOne(['lottery_code'=>$lotteryCode,'status'=>1]);
    	if($lotteryR&&$lotteryR->limit_time<$lotteryR->lottery_time){
    		$periods=$lotteryR->periods;
    		$endTime=$lotteryR->limit_time;
    	}
    	else{
    		$lotteryR=LotteryRecord::findOne(['lottery_code'=>$lotteryCode,'status'=>0]);
    		$periods=$lotteryR->periods;
    		$endTime=$lotteryR->limit_time;
    	}
    	$insert = ['lottery_type' => $abbName, 'lottery_name' => $lotName, 'lottery_id' => $lotteryCode, 'play_code' =>$playCode, 'play_name' => rtrim($playName,','), 'periods' => $periods,
    		'cust_no' => $custNo, "store_id" => $storeId, 'source_id' => '', 'bet_val' => $betNums, 'agent_id' => '0', 'periods_total' => '', 'bet_double' => 1, 'bet_money' => $betCount*2, "source" => 3, 'count' => $betCount,
    		'is_bet_add' => 0,  'create_time' => date('Y-m-d H:i:s'), 'end_time' => $endTime, 'user_id' => $userId, 'store_no' => $storeCode, 'auto_type' => 1, 'remark' => '赠送彩票','periods_total'=>1];
    	return OrderService::selfDoLotterOrder($insert, false,'',true);
    }

    /**
     * 2018-06-14 至 2018-07-15 消费满100 1000 赠送代金券
     */
    public static function sendCouponsVerify($money,$cust_no){
        $now = date("Y-m-d H:i:s");
        $type=0;
        if("2018-06-14 00:00:00"<=$now&&$now<="2018-07-15 23:59:59"){
            if($money>=100&&$money<1000){
                $type = 3;
            }elseif($money>=1000){
                $type = 4;
            }else{
                return ["code"=>109,"msg"=>"订单金额未达到活动标准"];
            }
            //验证当天赠送代金券张数
            $coupons = CouponsDetail::getActivityBatch("GL",$type);
            if(!empty($coupons)){
                foreach ($coupons as $k=>$v){
                    $num = CouponsDetail::getUserCouponsNum($v["batch"],$cust_no);
                    if($num>=3){
                        return ["code"=>109,"msg"=>"用户当日获得优惠券已满3张"];
                    }
                    $userAry = UserTool::getUserAry($cust_no,$v["send_num"]);
                    $res = UserTool::regSendCoupons($v["batch"],$userAry);
                    if($res["code"]!=600){
                        KafkaService::addLog("sendCoupons-error",$cust_no.$v["batch"].$res["msg"]);
                    }
                }
                return ["code"=>600,"msg"=>"赠送成功"];
            }else{
                return ["code"=>109,"msg"=>"活动暂未开启"];
            }
        }else{
            return ["code"=>109,"msg"=>"未到活动开始时间"];
        }
    }

}

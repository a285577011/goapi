<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\sports\services;

use Yii;
use app\modules\common\helpers\Constants;
use app\modules\sports\helpers\Guangdong;
use app\modules\common\services\OrderService;
use app\modules\common\services\PayService;

class GuangdongService {

    const LOTTERY_CODE = "2005";

    /**
     * 广东11选5，用户下订单
     * return json
     */
    public function playOrder($custNo, $userId, $storeId, $storeCode, $source = 1, $sourceId = "", $boolStr = false, $outType) {
        $layName = Constants::GD11X5_PLAYNAME;
        $lotteryType = Constants::LOTTERY_ABBREVI;
        $post = Yii::$app->request->post();
        $orderData = json_decode($post["order_data"], true);
        $periods = $orderData['periods']; // 期数
        $betNums = $orderData['contents']; // 投注内容
        $total = $orderData['total']; //总价
        $multiple = $orderData['multiple']; // 倍数
        $chase = isset($orderData['chase']) ? $orderData['chase'] : 1; // 追期数
        $isLimit = isset($orderData['is_limit']) ? $orderData['is_limit'] : 0; // 是否追期限制
        $limitAmount = $isLimit == 1 ? $orderData['limit_amount'] : 0; // 追期限制
        $countBet = $orderData['count_bet']; // 注数
        $isBetadd = 0;
        $remark = isset($post['remark']) ? $post['remark'] : '';
//        if (isset($orderData['end_time'])) {
            $endTime = date('Y-m-d H:i:s', $orderData['end_time'] / 1000);
//        } else {
//            $endTime = '';
//        }
        $count = 0;
        $playName = [];
        $playCode = [];
        $playNum = [];
        foreach ($betNums as $key => $val) {
            $fun = 'GD_' . $val['play'];
            $noteNums = Guangdong::$fun($val['nums']);
            if ($noteNums == false) {
                return [
                    "code" => 2,
                    "msg" => "投注内容格式错误！"
                ];
            }
            $playNum[$key] = $val["nums"];
            $playCode[$key] = $val['play'];
            $playName[$key] = $layName[$val['play']];
            $count += $noteNums;
        }

        if ($countBet != $count) {
            return [
                "code" => 2,
                "msg" => "投注内容注数不对应！"
            ];
        }
        if ($isBetadd == 1) {
            $betMoney = Constants::PRICE * 3 / 2 * $count;
        } else {
            $betMoney = Constants::PRICE * $count;
        }
        if ($multiple >= 1) {
            $betMoney *=$multiple;
        } else {
            return [
                "code" => 2,
                "msg" => "投注加倍参数错误！"
            ];
        }
        $totalMoney = $betMoney;
        if ($chase > 1) {
            $totalMoney *= $chase;
        }
        if ($total != $totalMoney) {
            return [
                "code" => 2,
                "msg" => "投注总金额错误！"
            ];
        }
        $lotteryName = Constants::LOTTERY;
        $insert=[
                    "lottery_type" => $lotteryType[self::LOTTERY_CODE],
                    "play_code" => implode(',', $playCode),
                    "play_name" => implode(',', $playName),
                    "lottery_id" => self::LOTTERY_CODE,
                    "lottery_name" => $lotteryName[self::LOTTERY_CODE],
                    "periods" => $periods,
                    "cust_no" => $custNo, //$this->$custNo,
                    "source_id" => $sourceId,
                    "store_id" => $storeId,
                    "agent_id" => "0", //$this->agentId,
                    "bet_val" => implode('^', $playNum) . "^",
                    "bet_double" => $multiple,
                    "is_bet_add" => $isBetadd,
                    "bet_money" => $betMoney,
                    "source" => $source,
                    "count" => $count,
                    "periods_total" => $chase,
                    "is_random" => isset($orderData["is_random"]) ? $orderData["is_random"] : 0,
                    "win_limit" => $limitAmount,
                    "is_limit" => $isLimit,
                    "end_time" => $endTime,
                    'user_id' => $userId,
                    'store_no' => $storeCode,
                    'create_time' => date('Y-m-d H:i:s'),
                    'auto_type' => $outType, 
                    'remark' => $remark
                        ];
        return OrderService::selfDoLotterOrder($insert,$boolStr);
        /*$ret = OrderService::insertOrder([
                    "lottery_type" => $lotteryType[self::LOTTERY_CODE],
                    "play_code" => implode(',', $playCode),
                    "play_name" => implode(',', $playName),
                    "lottery_id" => self::LOTTERY_CODE,
                    "lottery_name" => $lotteryName[self::LOTTERY_CODE],
                    "periods" => $periods,
                    "cust_no" => $custNo, //$this->$custNo,
                    "source_id" => $sourceId,
                    "store_id" => $storeId,
                    "agent_id" => "0", //$this->agentId,
                    "bet_val" => implode('^', $playNum) . "^",
                    "bet_double" => $multiple,
                    "is_bet_add" => $isBetadd,
                    "bet_money" => $betMoney,
                    "source" => $source,
                    "count" => $count,
                    "periods_total" => $chase,
                    "is_random" => isset($orderData["is_random"]) ? $orderData["is_random"] : 0,
                    "win_limit" => $limitAmount,
                    "is_limit" => $isLimit,
                    "end_time" => $endTime,
                    'user_id' => $userId,
                    'store_no' => $storeCode,
                    'create_time' => date('Y-m-d H:i:s')
                        ], $boolStr);
        if ($ret["error"] === true) {
            if ($source != 6) {
                $paySer = new PayService();
                $paySer->productPayRecord($custNo, $ret["orderCode"], 1, 1, $betMoney, 1);
            }
            return [
                "code" => 600,
                "msg" => "下注成功！",
                "result" => ["lottery_order_code" => $ret["orderCode"]]
            ];
        } elseif ($ret == false) {
            return [
                "code" => 2,
                "msg" => "下注失败！"
            ];
        } else {
            return [
                "code" => 2,
                "msg" => "下注失败！",
                "result" => $ret
            ];
        }*/
    }

    /**
     * 投注验证
     * @return array
     */
    public function playVerification() {
        $post = Yii::$app->request->post();
        $layName = Constants::GD11X5_PLAYNAME;
        $betNums = $post['contents']; // 投注内容
        $total = $post['total']; //总价
        $multiple = $post['multiple']; // 倍数
        $countBet = $post['count_bet']; // 注数
        $isBetadd = isset($post["is_bet_add"]) ? $post["is_bet_add"] : 0;
        $count = 0;
        $playName = [];
        $playNum = [];
        $playCode = [];
        foreach ($betNums as $key => $val) {
            $fun = 'GD_' . $val['play'];
            $noteNums = Guangdong::$fun($val['nums']);
            if ($noteNums == false) {
                return [
                    "code" => 2,
                    "msg" => "投注内容格式错误！"
                ];
            }
            $count += $noteNums;
            $playNum[$key] = $val["nums"];
            $playCode[$key] = $val['play'];
            $playName[$key] = $layName[$val['play']];
        }

        if ($countBet != $count) {
            return [
                "code" => 2,
                "msg" => "投注内容注数不对应！"
            ];
        }
        if ($isBetadd == 1) {
            $betMoney = Constants::PRICE * 3 / 2 * $count;
        } else {
            $betMoney = Constants::PRICE * $count;
        }
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
                "lottery_name" => '粤11选5',
                "play_name" => implode(',', $playName),
                "play_code" => implode(',', $playCode),
                "bet_val" => (implode('^', $playNum) . "^")
            ]
        ];
    }

    /**
     * 生成详情投注表
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
        $content = trim($model->bet_val, "^");
        $noteNums = explode("^", $content);
        $playCodes = explode(",", $model->play_code);
        $playNames = explode(",", $model->play_name);
        $order = [];
        $n = 0;
        foreach ($noteNums as $key => $nums) {
            $fun = "gdNote_" . $playCodes[$key];
            $ret = Guangdong::$fun($nums);
            if ($ret == false) {
                return [
                    "code" => 2,
                    "msg" => "数据错误"
                ];
            }
            foreach ($ret as $k => $v) {
                $order[$n]["bet_val"] = $v;
                $order[$n]["play_code"] = $playCodes[$key];
                $order[$n]["play_name"] = $playNames[$key];
                $n++;
            }
        }
        $infos["content"] = $order;
        $result = OrderService::insertDetail($infos);
        if ($result["error"] === true) {
            return [
                "code" => 0,
                "msg" => "操作成功"
            ];
        } else {
            return [
                "code" => 2,
                "msg" => "操作失败",
                "err" => $result
            ];
        }
    }

}

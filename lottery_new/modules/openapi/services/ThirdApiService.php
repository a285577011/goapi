<?php

namespace app\modules\openapi\services;

use app\modules\openapi\services\PlayOrderService;
use app\modules\competing\helpers\CompetConst;
use app\modules\common\helpers\Constants;
use app\modules\common\services\OrderService;
use app\modules\common\services\PayService;
use app\modules\store\helpers\Storefun;
use app\modules\common\services\KafkaService;
use app\modules\tools\helpers\Des;
use app\modules\orders\services\MajorService;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class ThirdApiService {

    /**
     * @param array $param
     * @return array
     */
    public function playOrder($params, $messageId) {
        $custNo = \Yii::$custNo;
        $userId = \Yii::$userId;
        $result = [];
        foreach ($params as $param) {
            $result[] = $this->validOrder($param, $userId, $custNo, $messageId);
        }
        return ['code' => 0, 'msg' => '操作成功', 'data' => ['orderList' => $result]];

        // $queue = new \LotteryQueue();
        //$queue->pushQueue('third_order_create_job', 'orderCreate', ['apiOrderId' => $ret['data']['api_order_id'], 'thirdOrderCode' => $openOrderId, 'userId' => $userId, 'custNo' => $custNo]);
//        return $this->jsonResult(600, '接单成功！等待处理！', ['orderId' => $openOrderId, 'tradeId' => $ret['data']['api_order_code']]);
    }

    /**
     * 投注结果查询
     * @param $tradeId 第三方订单编号
     * @param $orderId 接口订单编号
     * @return type
     */
    public function inquireOrder($orderIds) {
        $userId = \Yii::$userId;
        if (empty($orderIds)) {
            return ['code' => 4, 'msg' => '参数缺失'];
        }
        $playOrderService = new PlayOrderService();
        $res = $playOrderService->getOrder($userId, $orderIds);
        return $res;
    }

    /**
     * 中奖结果查询接口
     * @return type
     */
    public function inquireWinOrder($orderIds) {
        $userId = \Yii::$userId;
        if (empty($orderIds)) {
            return ['code' => 4, 'msg' => '参数缺失'];
        }
        $playOrderService = new PlayOrderService();
        $res = $playOrderService->getOutOrder($userId, $orderIds);
        return $res;
    }

    /**
     * 获取期数相关信息
     * @return type
     */
    public function getPeriods($lotteryCode, $periods) {
        if (empty($lotteryCode)) {
            return ['code' => 4, 'msg' => '参数缺失'];
        }
        $playOrderService = new PlayOrderService();
        $res = $playOrderService->getPeriods($lotteryCode, $periods);
        return $res;
    }

    /**
     * 获取开奖结果
     * @return type
     */
    public function getLotteryResult($lotteryCode, $periods) {
        if (empty($lotteryCode) || empty($periods)) {
            return ['code' => 4, 'msg' => '参数缺失'];
        }
        $playOrderService = new PlayOrderService();
        $res = $playOrderService->getLotteryResult($lotteryCode, $periods);
        return $res;
    }

    /**
     * 获取用户余额
     * @return type
     */
    public function getCustFunds() {
        $custNo = \Yii::$custNo;
        $playOrderService = new PlayOrderService();
        $res = $playOrderService->getUserFunds($custNo);
        if (empty($res)) {
            return ['code' => 40004, 'msg' => '用户账户不存在'];
        } else {
            return ['code' => 0, 'msg' => 'success', 'data' => $res];
        }
    }

    /**
     * 验证单个订单
     * @param type $order 订单
     * @param type $userId 第三方UID
     * @param type $custNo 第三方CustNo
     * @return type
     */
    public function validOrder($order, $userId, $custNo, $messageId) {
        $lotteryCode = isset($order['lotteryCode']) ? $order['lotteryCode'] : '';
        $periods = isset($order['periods']) ? $order['periods'] : ''; //期数
        $openOrderId = isset($order['orderId']) ? $order['orderId'] : ''; //第三方订单号
        $playCode = isset($order['playCode']) ? $order['playCode'] : ''; //玩法
        $betNums = isset($order['betNums']) ? $order['betNums'] : ''; //投注内容
        $multiple = isset($order['multiple']) ? $order['multiple'] : ''; //投注倍数
        $total = isset($order['total']) ? $order['total'] : ''; //投注倍数
        $add = isset($order['isBetAdd']) ? $order['isBetAdd'] : 0; //投注倍数
//        $endTimeStr = $request->post('endTime', '');
        $majorType = isset($order['major_type']) ? $order['major_type'] : 0;
        $majorData = isset($order['major_data']) ? $order['major_data'] : [];
        $outType = isset($order['out_type']) ? $order['out_type'] : 2;
        $playOrderService = new PlayOrderService();
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $optional = Constants::MADE_OPTIONAL_LOTTERY;
        $wcup = CompetConst::MADE_WCUP_LOTTERY;
        $bd = CompetConst::MADE_BD_LOTTERY;
        $price = Constants::PRICE;
        $abbArr = Constants::LOTTERY_ABBREVI;
        $codeArr = array_keys($abbArr);
        if (empty($lotteryCode) || empty($openOrderId) || empty($playCode) || empty($betNums) || empty($multiple) || empty($total)) {
            return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => 4, 'msg' => '参数缺失'];
        }
        if (!in_array($lotteryCode, $basketball) && !in_array($lotteryCode, $football)) {
            if (empty($periods)) {
                return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => 4, 'msg' => '期数值错误'];   //期数值错误
            }
        }
        if (!in_array($lotteryCode, $codeArr)) {
            return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => 3, 'msg' => '彩种未开放'];  //彩种未开放
        }
        if ($lotteryCode == 2001) {
            if (!in_array($add, [0, 1])) {
                return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => 4, 'msg' => '大乐透追加参数值错误'];  //大乐透追加参数值错误
            }
        } else {
            $add = 0;
        }
        if (!is_int($multiple) || $multiple <= 0) {
            return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => 20003, 'msg' => '注码倍数错误'];    //投注倍数格式不对
        }
        if (!is_int($total) || $total <= 0) {
            return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => 20001, 'msg' => '注码金额错误'];      // 金额错误
        }
        $existOrder = $playOrderService->getExistOrder($openOrderId, $userId, [1, 2, 3]);
        if ($existOrder != 0) {
            return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => 6, 'msg' => '该订单已存在,请勿重复下单！'];      //该订单已存在,请勿重复下单！
        }
        $userFunds = $playOrderService->getUserFunds($custNo);
        if ((floatval($userFunds['able_funds'])) < floatval($total)) {
            return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => 40005, 'msg' => '余额不足,请先充值！']; //余额不足,请先充值！
        }
        if (in_array($lotteryCode, $basketball) || in_array($lotteryCode, $football)) {
            $cacul = $playOrderService->getNewBet($lotteryCode, $betNums, $playCode);
            if ($cacul['code'] != 600) {
                return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => $cacul['code'], 'msg' => $cacul['msg']];
            }
//            $betNums = $cacul['data']['bet_nums'];
            $periods = $cacul['data']['max_time'];
        } elseif (in_array($lotteryCode, $optional)) {
            $cacul = $playOrderService->getOptionalCount($betNums, $periods, $lotteryCode, $playCode);
            if ($cacul['code'] != 600) {
                return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => $cacul['code'], 'msg' => $cacul['msg']];
            }
        } elseif (in_array($lotteryCode, $bd)) {
            $cacul = $playOrderService->getBdCount($lotteryCode, $betNums, $playCode);
            if ($cacul['code'] != 600) {
                return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => $cacul['code'], 'msg' => $cacul['msg']];
            }
            $periods = $cacul['data']['max_time'];
        } elseif (in_array($lotteryCode, $wcup)) {
            $cacul = $playOrderService->getWcupCount($lotteryCode, $betNums);
            if ($cacul['code'] != 600) {
                return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => $cacul['code'], 'msg' => $cacul['msg']];
            }
        } else {
            $cacul = $playOrderService->getSzcCount($betNums, $lotteryCode, $playCode, $periods);
            if ($cacul['code'] != 600) {
                return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => $cacul['code'], 'msg' => $cacul['msg']];
            }
            $price = $cacul['data']['price'];
        }
        if (!empty($majorType) && !empty($majorData)) {
            $betTotal = 0;
            foreach ($majorData as $vm) {
                if (!is_int($vm['mul']) || $vm['mul'] <= 0) {
                    return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => 109, 'msg' => '投注倍数格式不对'];
                }
                $betTotal += $price * $vm['mul'];
//                $subCount += 1;
            }
//            $majorType = $orderData['major_type'];
            $majorData = json_encode($majorData);
        } else {
//            $multiple = $orderData['multiple']; // 倍数
//            $subCount = $cacul['data']['count'];
            $betTotal = $price * $cacul['data']['count'] * $multiple;
            if ($add == 1) {
                $betTotal *= 1.5;
            }
        }
        if ($total != $betTotal) {
            return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => 20001, 'msg' => '投注总金额错误！']; //投注总金额错误
        }
        $endTime = $cacul['data']['end_time'];
        $insert = ['third_order_code' => $openOrderId, 'user_id' => $userId, 'lottery_code' => $lotteryCode, 'periods' => $periods, 'play_code' => $playCode, 'bet_val' => $betNums, 'bet_money' => $total,
            'multiple' => $multiple, 'is_add' => $add, 'end_time' => $endTime, 'major_type' => $majorType, 'message_id' => $messageId, 'out_type' => $outType];
        $ret = $playOrderService->createApiOrder($insert);
        if ($ret['code'] != 600) {
            return ['orderId' => $openOrderId, 'tradeId' => '', 'code' => 20004, 'msg' => '接单失败！！', 'data' => $ret['data']]; //接单失败
        }
        KafkaService::addQue('ThirdOrderCreate', ['apiOrderId' => $ret['data']['api_order_id'], 'thirdOrderCode' => $openOrderId, 'userId' => $userId, 'custNo' => $custNo]);
        if (!empty($majorType)) {
            $majorService = new MajorService();
            $majorService->createMajor($ret['data']['api_order_id'], $majorData, $majorType, 7);
        }
        return ['orderId' => $openOrderId, 'tradeId' => $ret['data']['api_order_code'], 'code' => 0, 'msg' => '接单成功.等待处理！！']; //接单成功.等待处理
    }
    
    public function getOrderDetail($orderId) {
        $userId = \Yii::$userId;
        if (empty($orderId)) {
            return ['code' => 4, 'msg' => '参数缺失'];
        }
        $playOrderService = new PlayOrderService();
        $res = $playOrderService->getOrderDetail($userId, $orderId);
        return $res;
    }
    
    public function getDetailResult($orderId) {
        $userId = \Yii::$userId;
        if (empty($orderId)) {
            return ['code' => 4, 'msg' => '参数缺失'];
        }
        $playOrderService = new PlayOrderService();
        $res = $playOrderService->getDetailResult($userId, $orderId);
        return $res;
    }

}

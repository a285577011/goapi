<?php

namespace app\modules\openapi\controllers;

use yii\web\Controller;
use app\modules\openapi\services\PlayOrderService;
use app\modules\competing\helpers\CompetConst;
use app\modules\common\helpers\Constants;
use app\modules\common\services\OrderService;
use app\modules\common\services\PayService;
use app\modules\store\helpers\Storefun;
use app\modules\common\services\KafkaService;
use app\modules\orders\helpers\OrderDeal;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class OrderController extends Controller {

    public function actionPlayOrder() {
        $custNo = \Yii::$custNo;
        $userId = \Yii::$userId;
        $request = \Yii::$app->request;
        $lotteryCode = $request->post('lotteryCode', '');
        $periods = $request->post('periods', '');
        $openOrderId = $request->post('orderId', '');
        $playCode = $request->post('playCode', '');
        $betNums = $request->post('betNums', '');
        $multiple = $request->post('multiple', '');
        $total = $request->post('total', '');
//        $endTimeStr = $request->post('endTime', '');
        $add = $request->post('isBetAdd', 0);
        $playOrderService = new PlayOrderService();
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $optional = Constants::MADE_OPTIONAL_LOTTERY;
        $price = Constants::PRICE;
        $abbArr = Constants::LOTTERY_ABBREVI;
        $codeArr = array_keys($abbArr);
        if (empty($lotteryCode) || empty($openOrderId) || empty($playCode) || empty($betNums) || empty($multiple) || empty($total)) {
            return $this->jsonError(101, '订单参数缺失');
        }
        if (!in_array($lotteryCode, $basketball) && !in_array($lotteryCode, $football)) {
            if (empty($periods)) {
                return $this->jsonError(101, '订单期数参数缺失');
            }
        }
        if (!in_array($lotteryCode, $codeArr)) {
            return $this->jsonError(109, '此彩种暂未开放接单!!');
        }
        if ($lotteryCode == 2001) {
            if ($add == '' || !in_array($add, [0, 1])) {
                return $this->jsonError(101, '大乐透彩种，是否追加参数缺失');
            }
        } else {
            $add = 0;
        }
        if (intval($total) <= 0) {
            return $this->jsonError(2, '投注金额格式不对');
        }
        if (intval($multiple) <= 0) {
            return $this->jsonError(2, '投注倍数格式不对');
        }

        $existOrder = $playOrderService->getExistOrder($openOrderId, $userId, [1, 2, 3]);
        if ($existOrder != 0) {
            return $this->jsonError(109, '该订单已存在,请勿重复下单！');
        }

        $userFunds = $playOrderService->getUserFunds($custNo);
        if ((floatval($userFunds['able_funds'])) < floatval($total)) {
            return $this->jsonError(109, '余额不足,请先充值！');
        }

        if (in_array($lotteryCode, $basketball) || in_array($lotteryCode, $football)) {
            $cacul = $playOrderService->getNewBet($lotteryCode, $betNums, $playCode);
            if ($cacul['code'] != 600) {
                return $this->jsonError(109, $cacul['msg']);
            }
            $periods = $cacul['data']['max_time'];
        } elseif (in_array($lotteryCode, $optional)) {
            $cacul = $playOrderService->getOptionalCount($betNums, $periods, $lotteryCode, $playCode);
            if ($cacul['code'] != 600) {
                return $this->jsonError(109, $cacul['msg']);
            }
        } else {
            $cacul = $playOrderService->getSzcCount($betNums, $lotteryCode, $playCode, $periods);
            if ($cacul['code'] != 600) {
                return $this->jsonError(109, $cacul['msg']);
            }
            $price = $cacul['data']['price'];
        }

        $betTotal = $price * $cacul['data']['count'] * $multiple;
        if ($add == 1) {
            $betTotal *= 1.5;
        }
        if ($total != $betTotal) {
            return $this->jsonError(109, '投注总金额错误！');
        }
        $endTime = $cacul['data']['end_time'];
        $insert = ['third_order_code' => $openOrderId, 'user_id' => $userId, 'lottery_code' => $lotteryCode, 'periods' => $periods, 'play_code' => $playCode, 'bet_val' => $betNums, 'bet_money' => $total,
            'multiple' => $multiple, 'is_add' => $add, 'end_time' => $endTime];
        $ret = $playOrderService->createApiOrder($insert);
        if ($ret['code'] !== 600) {
            if ($ret['code'] == 108) {
                return $this->jsonError(109, $ret['msg']);
            }
            return $this->jsonError(109, '接单失败');
        }
        KafkaService::addQue('ThirdOrderCreate', ['apiOrderId' => $ret['data']['api_order_id'], 'thirdOrderCode' => $openOrderId, 'userId' => $userId, 'custNo' => $custNo], true);
        // $queue = new \LotteryQueue();
        //$queue->pushQueue('third_order_create_job', 'orderCreate', ['apiOrderId' => $ret['data']['api_order_id'], 'thirdOrderCode' => $openOrderId, 'userId' => $userId, 'custNo' => $custNo]);
        return $this->jsonResult(600, '接单成功！等待处理！', ['orderId' => $openOrderId, 'tradeId' => $ret['data']['api_order_code']]);
    }

    /**
     * 投注结果查询
     * @return type
     */
    public function actionInquireOrder() {
        $custNo = \Yii::$custNo;
        $userId = \Yii::$userId;
        $request = \Yii::$app->request;
        $thirdOrderId = $request->post('orderId', '');
        $orderCode = $request->post('tradeId', '');
        if (empty($thirdOrderId) || empty($orderCode)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $playOrderService = new PlayOrderService();
        $data = $playOrderService->getOrder($userId, $thirdOrderId, $orderCode);
        if ($data['code'] != 600) {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, '获取成功', $data['data']);
    }

    /**
     * 中奖结果查询接口
     * @return type
     */
    public function actionInquireWinOrder() {
        $custNo = \Yii::$custNo;
        $userId = \Yii::$userId;
        $request = \Yii::$app->request;
        $openOrderId = $request->post('orderId', '');
        $orderCode = $request->post('tradeNo', '');
        if (empty($openOrderId) || empty($orderCode)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $playOrderService = new PlayOrderService();
        $data = $playOrderService->getOutOrder($userId, $openOrderId, $orderCode);
        if ($data['code'] != 600) {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, '获取成功', $data['data']);
    }

    /**
     * 获取期数相关信息
     * @return type
     */
    public function actionGetPeriods() {
        $request = \Yii::$app->request;
        $lotteryCode = $request->post('lotteryCode', '');
        $periods = $request->post('period', '');
        if (empty($lotteryCode)) {
            return $this->jsonError(109, '查询彩种缺失');
        }
        $playOrderService = new PlayOrderService();
        $data = $playOrderService->getPeriods($lotteryCode, $periods);
        if ($data['code'] != 600) {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, '获取成功', $data['data']);
    }

    /**
     * 获取开奖结果
     * @return type
     */
    public function actionGetLotteryResult() {
        $request = \Yii::$app->request;
        $lotteryCode = $request->post('lotteryCode', '');
        $periods = $request->post('period', '');
        if (empty($lotteryCode) || empty($periods)) {
            return $this->jsonError(109, '查询彩种/期数缺失');
        }
        $playOrderService = new PlayOrderService();
        $data = $playOrderService->getLotteryResult($lotteryCode, $periods);
        if ($data['code'] != 600) {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, '获取成功', $data['data']);
    }

    /**
     * 获取用户余额
     * @return type
     */
    public function actionGetCustFunds() {
        $custNo = \Yii::$custNo;
        $userId = \Yii::$userId;
        $playOrderService = new PlayOrderService();
        $data = $playOrderService->getUserFunds($custNo);
        return $this->jsonResult(600, '获取成功', $data);
    }

    public function actionW() {
        $custNo = \Yii::$custNo;
        $userId = \Yii::$userId;
        $request = \Yii::$app->request;
        $orderCode = $request->post('orderCode', '');
        $playOrderService = new PlayOrderService();
        $data = $playOrderService->orderPay($custNo, $userId, $orderCode);
        if ($data['code'] != 600) {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, '获取成功', $data['data']);
    }

}

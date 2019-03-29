<?php

namespace app\modules\tools\kafka;

use app\modules\orders\helpers\OrderDeal;
use app\modules\common\services\KafkaService;
use yii\base\Exception;
use app\modules\common\services\OrderService;
use app\modules\common\helpers\Commonfun;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\BettingDetail;
use app\modules\openapi\services\ApiNoticeService;
use app\modules\common\models\ApiOrder;
use app\modules\competing\helpers\CompetConst;
use \app\modules\common\helpers\Constants;
use app\modules\common\services\SyncService;
use app\modules\orders\helpers\NmOrderDeal;
use app\modules\orders\helpers\JoylottOrderDeal;

/**
 * 自动出票表新增
 */
class AutoOrderCreate implements Kafka {

    public $args;

    public function run($params) {
        $this->args = $params;
        Commonfun::updateQueue($this->args['queueId'], 2);

        $lotOrder = LotteryOrder::findOne(["lottery_order_id" => $params['orderId'], "status" => 2, "deal_status" => 0]);
        if (empty($lotOrder)) {
            return false;
        }
        $lotteryCode = $lotOrder->lottery_id;
        $orderCode = $lotOrder->lottery_order_code;
        $betNums = $lotOrder->bet_val;
        $mul = $lotOrder->bet_double;
        $playCode = $lotOrder->play_code;
        $buildCode = $lotOrder->build_code;
        $periods = $lotOrder->periods;
        $add = $lotOrder->is_bet_add;
        $storeNo = $lotOrder->store_no;
        $endTime = $lotOrder->end_time;
        $majorType = $lotOrder->major_type;
        $thirdOrderCode = '';
        if ($lotOrder->source == 7) {
            $thirdOrder = ApiOrder::find()->select(['third_order_code'])->where(['api_order_id' => $lotOrder->source_id])->asArray()->one();
            $thirdOrderCode = $thirdOrder['third_order_code'];
        }
        $autoCompet = CompetConst::COMPET;
        $optionArr = Constants::MADE_OPTIONAL_LOTTERY;
        $wcArr = CompetConst::MADE_WCUP_LOTTERY;
        $elevenArr = Constants::ELEVEN_TREND;
        if (in_array($lotteryCode, $autoCompet)) {
            $outTicket = OrderDeal::deal($lotteryCode, $betNums, $playCode, $buildCode, $mul, $majorType, $params['majorData']);
        } elseif (in_array($lotteryCode, $optionArr)) {
            $outTicket = OrderDeal::optionAutoOrder($lotteryCode, $betNums, $playCode, $mul, $lotOrder->count);
        } elseif (in_array($lotteryCode, $wcArr)) {
            $outTicket = OrderDeal::worldcupAutoOrder($periods, $lotteryCode, $betNums, $mul, $lotOrder->count, $playCode);
        } elseif (in_array($lotteryCode, $elevenArr)) {
            $outTicket = OrderDeal::elevenAutoOrder($lotteryCode, $betNums, $playCode, $mul);
        } else {
            $outTicket = OrderDeal::autoSzOrder($lotteryCode, $playCode, $betNums, $lotOrder->bet_double, $lotOrder->is_bet_add);
        }
        if ($lotOrder->source == 7) {
            $autoThird = OrderDeal::getOutThird($lotteryCode, 1);
            if ($autoThird['code'] != 600) {
                Commonfun::sysAlert('紧急通知', "自动出票出无票方", '流量单', "待处理", "请即时处理！");
                return $autoThird;
            }
            if ($autoThird['data']['third_name'] == 'JW') {
                $result = JoylottOrderDeal::createAutoOrder($lotteryCode, $orderCode, $outTicket, $periods, $add, $endTime);
            } elseif($autoThird['data']['third_name'] == 'NM') {
                $result = NmOrderDeal::createAutoOrder($lotteryCode, $orderCode, $outTicket, $periods, $add, $endTime);
            }
        } else {
            $result = OrderDeal::createAutoOrder($lotteryCode, $orderCode, $outTicket, $periods, $add, $endTime, $storeNo);
        }
        if ($result['code'] == 108) {
            Commonfun::sysAlert('紧急通知', "自动出票写入异常 ", '订单：' . $lotOrder->lottery_order_code, "待处理", "请即时处理！");
            return $result;
        }
//        KafkaService::addLog('autoOrder', json_encode($result));
        Commonfun::updateQueue($this->args['queueId'], 3);

        if (YII_ENV_DEV && $lotOrder->source != 7) {
            KafkaService::addQue('ConfirmOutTicket', ['orderCode' => $orderCode], true);
            return $result;
        }

        $trans = \Yii::$app->db->beginTransaction();
        $apiNotice = new ApiNoticeService();
        try {
            if ($result['code'] != 600) {
                BettingDetail::updateAll(["status" => 6], ['lottery_order_id' => $lotOrder->lottery_order_id, "status" => 2]);
                $lotOrder->status = 10;
                $lotOrder->out_time = date('Y-m-d H:i:s');
                $lotOrder->refuse_reason = '已超时';
                $lotOrder->saveData();
                $outFalse = OrderService::outOrderFalse($orderCode, 10, $storeNo);
                if ($outFalse === false) {
                    throw new Exception('拒绝出票失败！！');
                }
                SyncService::syncFromQueue('AutoOrderCreate');
                $apiNotice->PushNoticePlayOrder(10008, '订单超时，拒绝出票', $orderCode, $thirdOrderCode, $lotOrder->user_id, 6);
            }
            $trans->commit();
            foreach ($result['data'] as $val) {
                if($result['auto_third'] == 'JW') {
                    KafkaService::addQue('JlOutTicket', ['autoCode' => $val, 'thirdOrderCode' => $thirdOrderCode], true);
                }elseif ($result['auto_third'] == 'NM') {
                    KafkaService::addQue('NmOutTicket', ['autoCode' => $val, 'thirdOrderCode' => $thirdOrderCode], true);
                }elseif ($result['auto_third'] == 'ZMF') {
                    KafkaService::addQue('AutoOutTicket', ['autoCode' => $val, 'thirdOrderCode' => $thirdOrderCode], true);
                }
            }
            return $result;
        } catch (\yii\db\Exception $ex) {
            $trans->rollBack();
            KafkaService::addLog('autoOrder', $ex->getMessage());
            return json_encode($ex->getMessage());
        }
    }

}

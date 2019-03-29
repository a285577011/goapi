<?php

namespace app\modules\tools\kafka;

use app\modules\orders\models\AutoOutOrder;
use app\modules\common\models\LotteryOrder;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\KafkaService;
use app\modules\tools\helpers\Nm;
use app\modules\orders\helpers\OrderDeal;

/**
 * 自动出票队列
 */
class NmOutTicket implements Kafka {

    public $args;

    public function run($params) {
        $this->args = $params;
        Commonfun::updateQueue($this->args['queueId'], 2);
        $autoCode = $params['autoCode'];
        $autoData = AutoOutOrder::findOne(['out_order_code' => $autoCode, 'status' => 1, 'source' => 'NM']);
        if (empty($autoData)) {
            return false;
        }
        $orderData = LotteryOrder::findOne(['lottery_order_code' => $autoData->order_code, 'status' => 2]);
        if (empty($orderData)) {
            return false;
        }
        $info = [
            'orderlist' => [
                [
                    'lotterytype' => $autoData->lottery_code, //彩种编号
                    'phase' => $autoData->periods, //期数
                    'orderid' => $autoData->out_order_code, //投注序列号(不可重复)订单编号
                    'playtype' => $autoData->play_code, //玩法
                    'betcode' => $autoData->bet_val, //投注内容
                    'multiple' => $autoData->multiple, //投注倍数
                    'amount' => $autoData->amount, // 投注金额
                    'add' => $autoData->bet_add, //是否追加
                    'endtime' => ''// 截止时间
                ]
            ]
        ];
        $nm = new Nm();
        $data = $nm->to801($info);
//        KafkaService::addLog('autoOrder', json_encode($data));
        $outFalse = 0;
        if ($data == 1) {
            $autoData->status = 2;
        } else {
            if (in_array($data, [10001, 10002, 10004, 10007, 10008, 20004, 20005, 20006, 20023, 40006, 40007, 40008])) {
                $autoData->status = 5;
                $outFalse = 1;
            } else {
                $autoData->status = 3;
                Commonfun::sysAlert('紧急通知', "自动出票接单异常失败", '订单：' . $autoData->out_order_code, "待处理", "请即时处理！");
            }
            KafkaService::addLog('autoOrder', json_encode($data));
        }
        $autoData->modify_time = date('Y-m-d H:i:s');

        if (!$autoData->save()) {
            KafkaService::addLog('autoOrder', $autoData->getFirstErrors());
            return false;
        }
        Commonfun::updateQueue($this->args['queueId'], 3);
        if ($outFalse == 1) {
            OrderDeal::confirmOutTicket($autoData->order_code);
        }
    }

}

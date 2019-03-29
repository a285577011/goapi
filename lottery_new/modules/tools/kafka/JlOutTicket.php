<?php

namespace app\modules\tools\kafka;

use app\modules\orders\models\AutoOutOrder;
use app\modules\common\models\LotteryOrder;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\KafkaService;
use app\modules\orders\helpers\AutoConsts;
use app\modules\tools\helpers\Jw;
use app\modules\orders\helpers\OrderDeal;

/**
 * 自动出票队列
 */
class JlOutTicket implements Kafka {

    public $args;

    public function run($params) {
        $this->args = $params;
        Commonfun::updateQueue($this->args['queueId'], 2);
        $autoCode = $params['autoCode'];
        $autoData = AutoOutOrder::findOne(['out_order_code' => $autoCode, 'status' => 1, 'source' => 'JW']);
        if (empty($autoData)) {
            return false;
        }
        $orderData = LotteryOrder::findOne(['lottery_order_code' => $autoData->order_code, 'status' => 2]);
        if (empty($orderData)) {
            return false;
        }
        $gameId = AutoConsts::JOYLOTT_GAMEID;
        $str = '';
        if($autoData->lottery_code == '3000' || $autoData->lottery_code == '3100'){
            $str = '1;' . $autoData->play_code . ';' . $autoData->multiple . ';' . $autoData->bet_val . ';'  . $autoData->free_type . ';';
            $count = 1;
        } elseif(strpos($autoData->bet_val, '_') !== false) {
            $betArr = explode('_', $autoData->bet_val);
            $i = 1;
            foreach ($betArr as $val) {
                $str .= $i . ';' . $autoData->play_code . ';' . $autoData->multiple . ';' . $val . ';';
                $i++;
            }
            $count = count($betArr);
        } else {
            $str = '1;' . $autoData->play_code . ';' . $autoData->multiple . ';' . $autoData->bet_val . ';';
            $count = 1;
        }
        $info = [
            'gameId' => $gameId[$autoData->lottery_code], //玩法代码
            'issue' => $autoData->periods, //期号（竞彩玩法忽略此字段）
            'orderList' => [
                [
                    'orderId' => $autoData->out_order_code, //投注序列号(不可重复)订单编号
                    'timeStamp' =>(string) Jw::getMillisecond(), //时间戳
                    'ticketMoney' => (string)$autoData->amount, //金额
                    'betCount' =>$count, //注数
                    'betDetail' => $str, //投注内容
                ]
            ]
        ];
        $jw = new Jw();
        $data = $jw->to200008($info);
        $outFalse = 0;
        if ($data == 1) {
            $autoData->status = 2;
        } else {
            if (in_array($data, [20004, 20005, 20012, 20026, 20032, 20038])) {
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

<?php

namespace app\modules\tools\kafka;

use app\modules\tools\helpers\Zmf;
use app\modules\orders\models\AutoOutOrder;
use app\modules\common\models\LotteryOrder;
use app\modules\common\helpers\Commonfun;
use app\modules\orders\models\TicketDispenser;
use yii\db\Expression;
use app\modules\common\services\KafkaService;
use app\modules\orders\helpers\OrderDeal;

/**
 * 自动出票队列
 */
class AutoOutTicket implements Kafka {

    public $args;

    public function run($params) {
        $this->args = $params;
        Commonfun::updateQueue($this->args['queueId'], 2);
        $autoCode = $params['autoCode'];
        $autoData = AutoOutOrder::findOne(['out_order_code' => $autoCode, 'status' => 1]);
        if (empty($autoData)) {
            return false;
        }
        $orderData = LotteryOrder::findOne(['lottery_order_code' => $autoData->order_code, 'status' => 2]);
        if (empty($orderData)) {
            return false;
        }
        $info = [
            'lotteryId' => $autoData->lottery_code, //玩法代码
            'issue' => $autoData->periods, //期号（竞彩玩法忽略此字段）
            'records' => [
                'record' => [
                    'id' => $autoData->out_order_code, //投注序列号(不可重复)订单编号
                    'lotterySaleId' => (string) $autoData->play_code, //销售代码(竞彩自由过关，过关方式以^分开)
                    'freelotterySaleId' => $autoData->free_type, //1:自由过关 0:非自由过关
//                    'phone'=>'13960774169',//手机号（可不填）
//                    'idCard'=>'350681199002095254',//身份证号（可不填）
                    'code' => $autoData->bet_val, //注码。投注内容
                    'money' => (int) $autoData->amount, //金额
                    'timesCount' => $autoData->multiple, //倍数
                    'issueCount' => 1, //期数
                    'investCount' => $autoData->count, //注数
                    'investType' => $autoData->bet_add, //投注方式
                ]
            ]
        ];
        $zmf = new Zmf();
        $data = $zmf->to1000($info);
//        KafkaService::addLog('autoOrder', json_encode($data));
        $outFalse = 0;
        if ($data['head']['result'] == 0) {
            $autoData->status = 2;
        } else {
            $autoData->status = 3;
            $outFalse = 1;
            KafkaService::addLog('autoOrder', json_encode($data));
            $update = ['mod_nums' => new Expression("mod_nums+1"), 'modify_time' => date('Y-m-d H:i:s')];
            $where = ['store_no' => $orderData->store_no, 'type' => 2];
            TicketDispenser::updateAll($update, $where);
            Commonfun::sysAlert('紧急通知', "自动出票接单异常失败", '订单：' . $autoData->out_order_code, "待处理", "请即时处理！");
        }
        $autoData->modify_time = date('Y-m-d H:i:s');

        if (!$autoData->save()) {
            KafkaService::addLog('autoOrder', $autoData->getFirstErrors());
            return false;
        }
        Commonfun::updateQueue($this->args['queueId'], 3);
        if ($outFalse == 1) {
//            OrderDeal::confirmOutTicket($autoData->order_code);
        }
    }

}

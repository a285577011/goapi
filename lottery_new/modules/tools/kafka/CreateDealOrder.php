<?php

namespace app\modules\tools\kafka;

use app\modules\orders\services\DetailService;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\KafkaService;

/**
 * 自动出票表新增
 */
class CreateDealOrder implements Kafka {

    public $args;

    public function run($params) {
        $this->args = $params;
        Commonfun::updateQueue($this->args['queueId'], 2);
        $data = DetailService::creatrDealOrder($params['orderId']);
        if($data['code'] != 600) {
            KafkaService::addLog('dealOrder', $params['orderId']);
            return false;
        } 
        $ret = DetailService::createDealDetail($params['orderId']);
        if($ret['code'] != 600) {
            KafkaService::addLog('dealDetail', $params['orderId']);
            return false;
        }
        Commonfun::updateQueue($this->args['queueId'], 3);
    }

}
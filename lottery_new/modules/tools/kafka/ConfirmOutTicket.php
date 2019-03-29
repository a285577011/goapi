<?php

namespace app\modules\tools\kafka;

use app\modules\orders\helpers\OrderDeal;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\SyncService;

/**
 * 自动出票表新增
 */
class ConfirmOutTicket implements Kafka {

    public $args;

    public function run($params) {
        $this->args = $params;
        Commonfun::updateQueue($this->args['queueId'], 2);
        OrderDeal::confirmOutTicket($params['orderCode']);
        SyncService::syncFromQueue('ConfirmOutTicket');
        Commonfun::updateQueue($this->args['queueId'], 3);
    }

}

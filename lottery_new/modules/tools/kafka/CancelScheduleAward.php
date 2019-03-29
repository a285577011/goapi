<?php

namespace app\modules\tools\kafka;

use app\modules\common\helpers\Commonfun;
use app\modules\common\services\SyncService;
use app\modules\orders\helpers\DealOrder;

/**
 * 自动出票表新增
 */
class CancelScheduleAward implements Kafka {

    public $args;

    public function run($params) {
        $this->args = $params;
        Commonfun::updateQueue($this->args['queueId'], 2);
        $data = DealOrder::dealDelayScheduleOrder($params['mid'], $params['code']);
        SyncService::syncFromQueue('CancelScheduleAward');
        Commonfun::updateQueue($this->args['queueId'], 3);
    }

}

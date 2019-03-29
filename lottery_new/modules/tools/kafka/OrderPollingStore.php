<?php

namespace app\modules\tools\kafka;

use app\modules\common\helpers\Commonfun;
use app\modules\orders\services\TakingService;

/**
 * 自动出票表新增
 */
class OrderPollingStore implements Kafka {

    public $args;

    public function run($params) {
        $this->args = $params;
        Commonfun::updateQueue($this->args['queueId'], 2);
        TakingService::polling($params['orderCode']);
        Commonfun::updateQueue($this->args['queueId'], 3);
    }

}

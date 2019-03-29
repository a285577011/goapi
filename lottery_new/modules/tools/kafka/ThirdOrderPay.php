<?php

namespace app\modules\tools\kafka;

use app\modules\openapi\services\PlayOrderService;
use app\modules\common\models\ApiOrder;

class ThirdOrderPay implements Kafka {

    public function run($params) {
        try {
            $playOrderService = new PlayOrderService();
            $result = $playOrderService->orderPay($params['custNo'], $params['userId'], $params['orderCode']);
            return $result;
        } catch (\yii\db\Exception $ex) {
            return json_encode($ex);
        }
    }

}

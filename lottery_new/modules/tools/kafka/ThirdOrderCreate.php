<?php

namespace app\modules\tools\kafka;

use app\modules\openapi\services\PlayOrderService;
use app\modules\common\models\ApiOrder;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\SyncService;
use yii\base\Exception;

/**
 * 接口订单下单队列
 */
class ThirdOrderCreate implements Kafka {

    public $args;

    public function run($params) {
        $this->args = $params;
        Commonfun::updateQueue($this->args['queueId'], 2);
        try {
            $playOrderService = new PlayOrderService();
            $result = $playOrderService->playOrder($params['apiOrderId'], $this->args['thirdOrderCode'], $this->args['userId'], $this->args['custNo']);
            if($result['code'] == 100) {
//                return $result;
                throw new Exception($result['msg']);
            }
            Commonfun::updateQueue($this->args['queueId'], 3);
            $apiOrder = ApiOrder::findOne(['api_order_id' => $this->args['apiOrderId'], 'third_order_code' => $this->args['thirdOrderCode'], 'user_id' => $this->args['userId']]);
            $apiOrder->status = $result['data'];
            $apiOrder->modify_time = date('Y-m-d H:i:s');
            $apiOrder->save();
            SyncService::syncFromQueue('ThirdOrderCreate');
            return $result;
        } catch (\yii\db\Exception $ex) {
            Commonfun::updateQueue($this->args['queueId'], 4);
            \Yii::redisSet('errors', $ex->getMessage(),300);
            return json_encode($ex);
        }
    }

}

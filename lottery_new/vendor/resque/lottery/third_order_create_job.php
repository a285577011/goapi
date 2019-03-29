<?php

use app\modules\common\helpers\Commonfun;
use app\modules\openapi\services\PlayOrderService;
use app\modules\common\models\ApiOrder;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @auther GL zyl
 * @param
 * @return 
 */
class third_order_create_job {

    public function perform() {
        Commonfun::updateQueue($this->args['queueId'], 2);
        try {
            $playOrderService = new PlayOrderService();
            $result = $playOrderService->playOrder($this->args['apiOrderId'], $this->args['thirdOrderCode'], $this->args['userId'], $this->args['custNo']);
            $apiOrder = ApiOrder::findOne(['api_oder_id' => $this->args['apiOrderId'], 'third_order_code' => $this->args['thirdOrderCode'], 'user_id' => $this->args['userId']]);
            $apiOrder->status = $result['data'];
            $apiOrder->modify_time = date('Y-m-d H:i:s');
            $apiOrder->save();
            Commonfun::updateQueue($this->args['queueId'], 3);
            return $result;
        } catch (\yii\db\Exception $ex) {
            Commonfun::updateQueue($this->args['queueId'], 4);
            return json_encode($ex);
        }
    }

}
